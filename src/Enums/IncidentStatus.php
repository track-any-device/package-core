<?php

namespace TrackAnyDevice\Core\Enums;

enum IncidentStatus: string
{
    case Open = 'open';
    case Acknowledged = 'acknowledged';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';
    case Escalated = 'escalated';

    public function label(): string
    {
        return match ($this) {
            IncidentStatus::Open => 'Open',
            IncidentStatus::Acknowledged => 'Acknowledged',
            IncidentStatus::Resolved => 'Resolved',
            IncidentStatus::Dismissed => 'Dismissed',
            IncidentStatus::Escalated => 'Escalated',
        };
    }

    public function color(): string
    {
        return match ($this) {
            IncidentStatus::Open => 'danger',
            IncidentStatus::Acknowledged => 'warning',
            IncidentStatus::Resolved => 'success',
            IncidentStatus::Dismissed => 'gray',
            IncidentStatus::Escalated => 'danger',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [IncidentStatus::Resolved, IncidentStatus::Dismissed]);
    }

    public function canTransitionTo(IncidentStatus $next): bool
    {
        return match ($this) {
            IncidentStatus::Open => in_array($next, [
                IncidentStatus::Acknowledged,
                IncidentStatus::Resolved,
                IncidentStatus::Dismissed,
                IncidentStatus::Escalated,
            ]),
            IncidentStatus::Acknowledged => in_array($next, [
                IncidentStatus::Resolved,
                IncidentStatus::Dismissed,
                IncidentStatus::Escalated,
            ]),
            IncidentStatus::Escalated => in_array($next, [
                IncidentStatus::Acknowledged,
                IncidentStatus::Resolved,
                IncidentStatus::Dismissed,
            ]),
            IncidentStatus::Resolved, IncidentStatus::Dismissed => false,
        };
    }
}
