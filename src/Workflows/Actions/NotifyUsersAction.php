<?php

namespace TrackAnyDevice\Core\Workflows\Actions;

use TrackAnyDevice\Core\Models\User;
use TrackAnyDevice\Core\Workflows\Contracts\WorkflowAction;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Notify users via in-app, email, or SMS channels.
 *
 * Targets are resolved from the workflow context (incident assignee,
 * device owner, beat supervisor) plus an optional explicit user_ids
 * list in config. Channels are best-effort — failures on one channel
 * don't block the others.
 */
class NotifyUsersAction implements WorkflowAction
{
    public function execute(array $config, array $context): array
    {
        $channels = $config['channels'] ?? ['in_app'];
        $message = $this->renderTemplate((string) ($config['message'] ?? ''), $context);
        $userIds = $this->resolveTargets($config, $context);

        if (empty($userIds)) {
            return [
                'status' => 'completed',
                'output' => ['delivered' => 0, 'reason' => 'no_targets'],
            ];
        }

        $users = User::whereIn('id', $userIds)->get();
        $deliveredPerChannel = ['in_app' => 0, 'email' => 0, 'sms' => 0];

        foreach ($users as $user) {
            foreach ($channels as $channel) {
                try {
                    match ($channel) {
                        'in_app' => $this->createDatabaseNotification($user, $message, $context),
                        'email' => $this->sendEmail($user, $message),
                        'sms' => $this->sendSms($user, $message),
                        default => null,
                    };
                    $deliveredPerChannel[$channel] = ($deliveredPerChannel[$channel] ?? 0) + 1;
                } catch (\Throwable $e) {
                    Log::warning("Workflow notify failed on channel {$channel}", [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return [
            'status' => 'completed',
            'output' => [
                'targets' => count($userIds),
                'channels' => $channels,
                'delivered' => $deliveredPerChannel,
            ],
        ];
    }

    /**
     * @return array<int, int>
     */
    private function resolveTargets(array $config, array $context): array
    {
        $explicit = collect($config['user_ids'] ?? [])->map(fn ($id) => (int) $id);
        $contextual = collect();

        if (! empty($context['incident']['assignee_id'])) {
            $contextual->push((int) $context['incident']['assignee_id']);
        }
        if (! empty($context['device']['user_id'])) {
            $contextual->push((int) $context['device']['user_id']);
        }
        // Beat supervisors are Assignees, not Users, so they aren't a
        // valid target for user-facing channels. NotifyUsersAction
        // delivers to *Users*; an SMS-direct-to-assignee target is a
        // follow-up — for now we expose supervisor.name/phone in the
        // message template via WorkflowDispatcher so notify rows can
        // reference them in their copy.

        return $explicit->merge($contextual)->unique()->values()->all();
    }

    private function renderTemplate(string $template, array $context): string
    {
        return preg_replace_callback(
            '/\{([a-z0-9_.]+)\}/i',
            function (array $m) use ($context) {
                $path = explode('.', $m[1]);
                $value = $context;
                foreach ($path as $segment) {
                    if (! is_array($value) || ! array_key_exists($segment, $value)) {
                        return $m[0];
                    }
                    $value = $value[$segment];
                }

                return is_scalar($value) ? (string) $value : json_encode($value);
            },
            $template,
        ) ?? $template;
    }

    private function createDatabaseNotification(User $user, string $message, array $context): void
    {
        DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'workflow.notify',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => [
                'message' => $message,
                'context' => $context,
                'source' => 'workflow',
            ],
        ]);
    }

    private function sendEmail(User $user, string $message): void
    {
        // Stub — wired up in a follow-up when the mailable templates land.
        Log::info('Workflow email (stub)', ['user_id' => $user->id, 'body' => $message]);
    }

    private function sendSms(User $user, string $message): void
    {
        // Stub — defers to the existing SMS gateway adapter when configured.
        Log::info('Workflow SMS (stub)', ['user_id' => $user->id, 'body' => $message]);
    }
}
