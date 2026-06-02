<?php

namespace TrackAnyDevice\Core\Enums;

enum SignalEventType: string
{
    case Update = 'update';
    case PunchIn = 'punch_in';
    case PunchOut = 'punch_out';
    case Sos = 'sos';
    case Intercom = 'intercom';
    case Heartbeat = 'heartbeat';
    case Registration = 'registration';
    case Alarm = 'alarm';
    case CommandAck = 'command_ack';
    case ConfigReport = 'config_report';
    case LowBattery = 'low_battery';
    case GeofenceExit = 'geofence_exit';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Update => 'Update',
            self::PunchIn => 'Punch In',
            self::PunchOut => 'Punch Out',
            self::Sos => 'SOS',
            self::Intercom => 'Intercom',
            self::Heartbeat => 'Heartbeat',
            self::Registration => 'Registration',
            self::Alarm => 'Alarm',
            self::CommandAck => 'Command Ack',
            self::ConfigReport => 'Config Report',
            self::LowBattery => 'Low Battery',
            self::GeofenceExit => 'Geofence Exit',
            self::Custom => 'Custom',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Sos, self::Alarm, self::LowBattery, self::GeofenceExit => 'danger',
            self::PunchIn, self::PunchOut => 'success',
            self::Heartbeat, self::Update => 'gray',
            self::Intercom, self::Registration => 'info',
            self::CommandAck, self::ConfigReport => 'gray',
            self::Custom => 'info',
        };
    }
}
