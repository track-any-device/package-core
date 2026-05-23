<?php

namespace TrackAnyDevice\Core\Enums;

enum WorkflowActionType: string
{
    case Trigger = 'trigger';
    case Wait = 'wait';
    case Notify = 'notify';
    case SendCommand = 'send_command';
    case EscalateIncident = 'escalate_incident';
    case Webhook = 'webhook';

    public function label(): string
    {
        return match ($this) {
            self::Trigger => 'Trigger',
            self::Wait => 'Wait',
            self::Notify => 'Notify users',
            self::SendCommand => 'Send device command',
            self::EscalateIncident => 'Escalate incident',
            self::Webhook => 'Call webhook',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Trigger => 'Zap',
            self::Wait => 'Hourglass',
            self::Notify => 'Bell',
            self::SendCommand => 'Send',
            self::EscalateIncident => 'AlertCircle',
            self::Webhook => 'Webhook',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Trigger => 'primary',
            self::Wait => 'warning',
            self::Notify => 'info',
            self::SendCommand => 'primary',
            self::EscalateIncident => 'danger',
            self::Webhook => 'accent',
        };
    }
}
