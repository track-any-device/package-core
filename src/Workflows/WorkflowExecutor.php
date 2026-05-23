<?php

namespace TrackAnyDevice\Core\Workflows;

use TrackAnyDevice\Core\Enums\WorkflowActionType;
use TrackAnyDevice\Core\Enums\WorkflowRunStatus;
use TrackAnyDevice\Core\Models\Incident;
use TrackAnyDevice\Core\Models\Workflow;
use TrackAnyDevice\Core\Models\WorkflowRun;
use TrackAnyDevice\Core\Models\WorkflowStepLog;
use TrackAnyDevice\Core\Scopes\TenantScope;
use TrackAnyDevice\Core\Workflows\Actions\CallWebhookAction;
use TrackAnyDevice\Core\Workflows\Actions\EscalateIncidentAction;
use TrackAnyDevice\Core\Workflows\Actions\NotifyUsersAction;
use TrackAnyDevice\Core\Workflows\Actions\SendDeviceCommandAction;
use TrackAnyDevice\Core\Workflows\Actions\WaitDelayAction;
use TrackAnyDevice\Core\Workflows\Contracts\WorkflowAction;
use Illuminate\Support\Facades\Log;

/**
 * Executes a workflow graph synchronously.
 *
 * Walks from the trigger node along edges in order, invoking each
 * action handler. A "wait" action delays the next step's start time
 * via PHP sleep (capped at 180s). For high-volume tenants this can be
 * upgraded later to dispatch each step as a queued job so wait nodes
 * don't tie up a worker — the action contract already returns enough
 * info to swap in a queue-based driver.
 */
class WorkflowExecutor
{
    /** @var array<string, class-string<WorkflowAction>> */
    private array $handlers = [
        'wait' => WaitDelayAction::class,
        'notify' => NotifyUsersAction::class,
        'send_command' => SendDeviceCommandAction::class,
        'escalate_incident' => EscalateIncidentAction::class,
        'webhook' => CallWebhookAction::class,
    ];

    /**
     * @param  array<string, mixed>  $context  Trigger context.
     */
    public function run(Workflow $workflow, string $triggeredBy, array $context): WorkflowRun
    {
        // Only persist incident_id when the referenced row exists — the
        // FK constraint requires it. Synthetic test contexts can pass an
        // incident.id without a real row, in which case we skip the link.
        $incidentId = $context['incident']['id'] ?? null;
        if ($incidentId) {
            $exists = Incident::withoutGlobalScope(TenantScope::class)
                ->whereKey($incidentId)
                ->exists();
            if (! $exists) {
                $incidentId = null;
            }
        }

        $run = WorkflowRun::create([
            'workflow_id' => $workflow->id,
            'tenant_id' => $workflow->tenant_id,
            'incident_id' => $incidentId,
            'triggered_by' => $triggeredBy,
            'input_context' => $context,
            'status' => WorkflowRunStatus::Running,
            'started_at' => now(),
        ]);

        $startedAt = microtime(true);

        try {
            $context = $this->walk($workflow, $run, $context);

            $run->update([
                'status' => WorkflowRunStatus::Completed,
                'completed_at' => now(),
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);

            $workflow->update(['last_run_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Workflow run failed', [
                'workflow_id' => $workflow->id,
                'run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);

            $run->update([
                'status' => WorkflowRunStatus::Failed,
                'completed_at' => now(),
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'error' => $e->getMessage(),
            ]);
        }

        return $run->refresh();
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function walk(Workflow $workflow, WorkflowRun $run, array $context): array
    {
        $graph = $workflow->graph;
        $nodes = collect($graph['nodes'] ?? [])->keyBy('id');
        $edges = collect($graph['edges'] ?? []);

        $trigger = $nodes->first(fn ($n) => ($n['data']['action_type'] ?? null) === 'trigger');
        if (! $trigger) {
            throw new \RuntimeException('Workflow has no trigger node');
        }

        $currentId = $trigger['id'];
        $visited = [$currentId];

        while (true) {
            $nextEdge = $edges->firstWhere('source', $currentId);
            if (! $nextEdge) {
                break;
            }

            $nextId = $nextEdge['target'];
            if (in_array($nextId, $visited, true)) {
                throw new \RuntimeException("Cycle detected at node {$nextId}");
            }

            $visited[] = $nextId;
            $node = $nodes->get($nextId);
            if (! $node) {
                break;
            }

            $result = $this->executeNode($node, $context, $run);

            if ($result['status'] === 'failed') {
                throw new \RuntimeException("Step {$nextId} failed: ".($result['error'] ?? 'unknown'));
            }

            // Wait directive — sleep before advancing.
            if (($result['output']['directive'] ?? null) === 'delay_next_step') {
                $seconds = (int) ($result['output']['delay_seconds'] ?? 0);
                if ($seconds > 0) {
                    sleep($seconds);
                }
            }

            $context = $this->mergeOutput($context, $node['id'], $result['output'] ?? []);
            $currentId = $nextId;
        }

        return $context;
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $context
     * @return array{status: string, output?: array<string, mixed>, error?: ?string}
     */
    private function executeNode(array $node, array $context, WorkflowRun $run): array
    {
        $actionType = $node['data']['action_type'] ?? '';
        $stepStart = microtime(true);

        $stepLog = WorkflowStepLog::create([
            'workflow_run_id' => $run->id,
            'node_id' => $node['id'],
            'action_type' => WorkflowActionType::tryFrom($actionType) ?? WorkflowActionType::Trigger,
            'input' => $node['data']['config'] ?? [],
            'status' => WorkflowRunStatus::Running,
            'executed_at' => now(),
        ]);

        $handlerClass = $this->handlers[$actionType] ?? null;
        if (! $handlerClass) {
            $result = ['status' => 'failed', 'error' => "No handler for action {$actionType}"];
        } else {
            /** @var WorkflowAction $handler */
            $handler = app($handlerClass);
            $result = $handler->execute($node['data']['config'] ?? [], $context);
        }

        $stepLog->update([
            'status' => WorkflowRunStatus::tryFrom($result['status']) ?? WorkflowRunStatus::Completed,
            'output' => $result['output'] ?? null,
            'duration_ms' => (int) ((microtime(true) - $stepStart) * 1000),
            'error' => $result['error'] ?? null,
        ]);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $output
     * @return array<string, mixed>
     */
    private function mergeOutput(array $context, string $nodeId, array $output): array
    {
        $context['steps'] ??= [];
        $context['steps'][$nodeId] = $output;

        return $context;
    }
}
