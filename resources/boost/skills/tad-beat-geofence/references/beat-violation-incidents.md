# Beat Violation Incidents

`CheckBeatViolation` creates incidents of type `AlertRuleEventType::BeatViolation`. Incidents are re-used and updated rather than creating a new row on every GPS fix.

## Incident lifecycle

| Condition | Action |
|---|---|
| Level > 0, no open incident | Create new incident (or reopen a resolved one from the last 7 days) |
| Level > 0, open incident exists | Update `level`, `beat_id`, `latitude`, `longitude`; escalate status if level increased |
| Level 0, open incident exists | Resolve: `status = resolved`, `resolved_at = now()` |

## Priority mapping

| Level | Status | Priority |
|---|---|---|
| 1 | `open` | `high` |
| ≥ 2 | `escalated` | `critical` |

## Reopen logic

If a resolved incident exists for the same device and beat within the last 7 days, it is **reopened** rather than a new row being created. The reopen count is incremented and the history array (`reopen_history`) gets a new ISO timestamp entry.

```php
// Check reopen history
$incident->reopen_count;      // int
$incident->reopen_history;    // array of ISO-8601 strings
```

## Incident fields specific to beat violations

| Field | Description |
|---|---|
| `beat_id` | The outermost beat the device is still inside (worst-case context) |
| `level` | Depth of violation (1 = just outside assigned beat) |
| `latitude` / `longitude` | Device position at time of detection |

## Workflow integration

`IncidentObserver` fires `WorkflowDispatcher::dispatchForIncident()` on creation and escalation, so workflows with `incident_opened` or `incident_escalated` triggers automatically receive beat violation incidents.

The context payload includes the `beat` object with supervisor details:

```json
{
  "beat": {
    "id": 3,
    "name": "North Zone",
    "supervisor": {
      "id": 7,
      "name": "Ali Khan",
      "phone": "923001234567"
    }
  }
}
```
