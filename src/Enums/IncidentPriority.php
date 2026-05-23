<?php

namespace TrackAnyDevice\Core\Enums;

enum IncidentPriority: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
    case Info = 'info';

    public function label(): string
    {
        return match ($this) {
            IncidentPriority::Critical => 'Critical',
            IncidentPriority::High => 'High',
            IncidentPriority::Medium => 'Medium',
            IncidentPriority::Low => 'Low',
            IncidentPriority::Info => 'Info',
        };
    }

    public function color(): string
    {
        return match ($this) {
            IncidentPriority::Critical => 'danger',
            IncidentPriority::High => 'warning',
            IncidentPriority::Medium => 'info',
            IncidentPriority::Low => 'gray',
            IncidentPriority::Info => 'gray',
        };
    }
}
