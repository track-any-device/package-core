<?php

namespace TrackAnyDevice\Core\Enums;

enum WorkflowTriggerType: string
{
    case Time = 'time';
    case IncidentCreated = 'incident.created';
    case IncidentClosed = 'incident.closed';

    public function label(): string
    {
        return match ($this) {
            self::Time => 'Scheduled (time-based)',
            self::IncidentCreated => 'Incident created',
            self::IncidentClosed => 'Incident closed',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Time => 'Clock',
            self::IncidentCreated => 'AlertTriangle',
            self::IncidentClosed => 'CheckCircle2',
        };
    }
}
