# RunWorkflowJob

`TrackAnyDevice\Core\Jobs\Workflows\RunWorkflowJob` executes a workflow directly from the queue.

## Dispatching manually

```php
use TrackAnyDevice\Core\Jobs\Workflows\RunWorkflowJob;

RunWorkflowJob::dispatch(
    workflowId: $workflow->id,
    triggeredBy: 'manual',
    context: [
        'incident' => ['id' => $incident->id, ...],
    ],
);
```

## Parameters

| Parameter | Type | Description |
|---|---|---|
| `workflowId` | int | ID of the `Workflow` record |
| `triggeredBy` | string | Free-text label for audit — e.g. `'incident_opened'`, `'cron'`, `'manual'` |
| `context` | array | Initial context passed to all action handlers |

## What the job does

1. Looks up the `Workflow` by ID — silently exits if not found or disabled.
2. Creates a `WorkflowRun` record with `status = running`.
3. Calls `WorkflowExecutor::run()` synchronously.
4. Updates `WorkflowRun` to `completed` or `failed` with duration.
5. Updates `Workflow::last_run_at`.

## Workflow not found / disabled

The job logs a warning and returns `SUCCESS` without re-queuing. This prevents the queue from filling up with stale workflow IDs after a workflow is deleted.
