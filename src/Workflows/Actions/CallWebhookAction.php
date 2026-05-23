<?php

namespace TrackAnyDevice\Core\Workflows\Actions;

use TrackAnyDevice\Core\Workflows\Contracts\WorkflowAction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * POST/PUT/PATCH/DELETE the workflow run context to a tenant-supplied URL.
 *
 * Retries up to 3 times with exponential backoff (2s, 8s) on transient
 * failures (network errors or non-2xx HTTP status). Per-attempt HTTP
 * timeout is 10 seconds, so total inline time is capped at ~40 seconds
 * before the action returns failure.
 *
 * The executor halts the workflow on a returned `failed` status.
 */
class CallWebhookAction implements WorkflowAction
{
    private const MAX_ATTEMPTS = 3;

    /**
     * Backoff between retries, in seconds (index = attempt-1).
     * Tests override this via config('workflows.webhook_backoff') to
     * avoid 10s sleeps in unit tests.
     */
    private const DEFAULT_BACKOFF_SECONDS = [2, 8];

    public function execute(array $config, array $context): array
    {
        $url = trim((string) ($config['url'] ?? ''));
        $method = strtoupper((string) ($config['method'] ?? 'POST'));

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return ['status' => 'failed', 'error' => 'Invalid URL'];
        }

        if (! in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return ['status' => 'failed', 'error' => "Method {$method} not allowed"];
        }

        $attempts = [];
        $lastError = null;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders(['X-Workflow-Source' => 'track-any-device'])
                    ->send($method, $url, ['json' => $context]);

                $attempts[] = [
                    'attempt' => $attempt,
                    'http_status' => $response->status(),
                ];

                if ($response->successful()) {
                    return [
                        'status' => 'completed',
                        'output' => [
                            'attempts' => $attempts,
                            'final_http_status' => $response->status(),
                            'body_preview' => substr($response->body(), 0, 500),
                        ],
                    ];
                }

                $lastError = "HTTP {$response->status()}";
            } catch (\Throwable $e) {
                $attempts[] = [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ];
                $lastError = $e->getMessage();

                Log::warning('Workflow webhook attempt failed', [
                    'url' => $url,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
            }

            // Sleep before next attempt (skip after final attempt).
            if ($attempt < self::MAX_ATTEMPTS) {
                $backoff = config('workflows.webhook_backoff', self::DEFAULT_BACKOFF_SECONDS);
                $delay = $backoff[$attempt - 1] ?? end($backoff);
                if ($delay > 0) {
                    sleep((int) $delay);
                }
            }
        }

        return [
            'status' => 'failed',
            'output' => ['attempts' => $attempts],
            'error' => $lastError ?? 'All attempts failed',
        ];
    }
}
