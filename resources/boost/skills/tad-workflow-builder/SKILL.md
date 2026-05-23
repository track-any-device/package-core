---
name: tad-workflow-builder
description: Build, dispatch, and execute TAD automation workflows — covering the workflow graph JSON schema, trigger types, action nodes, WorkflowDispatcher, and time-based scheduling. Use when creating workflows, adding workflow steps, wiring incident triggers, or debugging workflow runs.
---

# TAD Workflow Builder

## Concepts

A `Workflow` is a directed graph stored as JSON in the `graph` column. The executor walks from a trigger node along edges, calling an action handler at each node. Workflows are tenant-scoped.

## Workflow graph schema

```json
{
  "nodes": [
    {
      "id": "trigger-1",
      "data": {
        "action_type": "trigger",
        "config": {}
      }
    },
    {
      "id": "notify-1",
      "data": {
        "action_type": "notify",
        "config": {
          "channel": "sms",
          "message": "SOS alert from device {{ device.imei }}"
        }
      }
    },
    {
      "id": "webhook-1",
      "data": {
        "action_type": "webhook",
        "config": {
          "url": "https://dispatch.example.com/webhook",
          "method": "POST"
        }
      }
    }
  ],
  "edges": [
    { "source": "trigger-1", "target": "notify-1" },
    { "source": "notify-1",  "target": "webhook-1" }
  ]
}
```

Edges define execution order. The executor walks a **linear chain** — one outgoing edge per node. Branching is not yet supported.

## Creating a workflow

```php
use TrackAnyDevice\Core\Models\Workflow;
use TrackAnyDevice\Core\Enums\WorkflowTriggerType;

$workflow = Workflow::create([
    'tenant_id'      => $tenant->id,
    'name'           => 'SOS Alert Response',
    'trigger_type'   => WorkflowTriggerType::IncidentOpened,
    'trigger_config' => [],
    'graph'          => $graphArray,
    'is_enabled'     => true,
]);
```

## Trigger types

| Enum value | When it fires |
|---|---|
| `incident_opened` | New incident created (`IncidentObserver`) |
| `incident_escalated` | Incident status set to `escalated` |
| `time` | Cron schedule — requires `trigger_config.cron` |

## Action types and their `config` shape

### `notify`
```json
{
  "channel": "sms",
  "message": "Alert: {{ incident.event_type }} on device {{ device.imei }}"
}
```

### `send_command`
```json
{
  "command": "location"
}
```

### `escalate_incident`
```json
{
  "notes": "Auto-escalated after 10 minutes without response."
}
```

### `webhook`
```json
{
  "url": "https://your-system.example.com/webhook",
  "method": "POST"
}
```
Retries 3 times with 2s / 8s backoff. Fails the workflow step on all retries exhausted.

### `wait`
```json
{
  "seconds": 60
}
```
Max 180 seconds. **Blocks the queue worker for the full delay** — keep delays short until async step dispatch is implemented (see [#3](https://github.com/track-any-device/package-core/issues/3)).

## Dispatching for an incident

```php
use TrackAnyDevice\Core\Workflows\WorkflowDispatcher;
use TrackAnyDevice\Core\Enums\WorkflowTriggerType;

$dispatcher = app(WorkflowDispatcher::class);

// Returns the number of workflows dispatched
$count = $dispatcher->dispatchForIncident($incident, WorkflowTriggerType::IncidentOpened);
```

The dispatcher automatically loads incident, device, beat, and tenant context into the workflow run payload.

## Time-triggered workflows

Set `trigger_type = time` and provide a cron expression in `trigger_config`:

```php
$workflow->update([
    'trigger_type'   => WorkflowTriggerType::Time,
    'trigger_config' => [
        'cron'     => '*/15 * * * *',      // every 15 minutes
        'timezone' => 'Asia/Karachi',       // optional, defaults to app.timezone
    ],
]);
```

Schedule `workflows:run-scheduled` every minute in `routes/console.php`:

```php
Schedule::command('workflows:run-scheduled')->everyMinute();
```

## Run context shape

Every workflow run receives a context array. Incident-triggered runs include:

```php
[
    'incident' => [
        'id'          => 42,
        'event_type'  => 'sos',
        'priority'    => 'critical',
        'status'      => 'open',
        'triggered_at' => '2026-05-23T10:00:00Z',
        'latitude'    => 31.5204,
        'longitude'   => 74.3587,
        'assignee_id' => 7,
    ],
    'device' => [
        'id'   => 5,
        'name' => 'Tracker 001',
        'imei' => '123456789012345',
    ],
    'beat' => [
        'id'   => 3,
        'name' => 'North Zone',
        'supervisor' => ['id' => 2, 'name' => 'Ali', 'phone' => '923001234567'],
    ],
    'tenant' => ['id' => 1, 'name' => 'Acme', 'slug' => 'acme'],
    'steps'  => [],   // populated as steps complete
]
```

## Inspecting a workflow run

```php
use TrackAnyDevice\Core\Models\WorkflowRun;
use TrackAnyDevice\Core\Enums\WorkflowRunStatus;

$run = WorkflowRun::with('stepLogs')->find($runId);

$run->status;          // WorkflowRunStatus enum
$run->duration_ms;     // total execution time
$run->error;           // message if failed
$run->stepLogs;        // per-node execution records
```

## See also

- `references/workflow-run-job.md` — dispatching `RunWorkflowJob` directly
