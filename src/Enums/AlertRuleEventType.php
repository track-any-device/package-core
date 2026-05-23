<?php

namespace TrackAnyDevice\Core\Enums;

enum AlertRuleEventType: string
{
    // ── Alarm-flag driven (JT808 / device hardware) ──────────────────────────
    case Sos = 'sos';               // alarm bit 0  — SOS distress button
    case Overspeed = 'overspeed';   // alarm bit 1  — speed > threshold
    case LowBattery = 'low_battery'; // alarm bit 7 — battery below threshold
    case PowerFailure = 'power_failure'; // alarm bit 12 — external power cut
    case Vibration = 'vibration';   // alarm bit 16 — vibration/shock detected

    // ── Position driven ──────────────────────────────────────────────────────
    case BeatViolation = 'beat_violation';

    // ── Connectivity ─────────────────────────────────────────────────────────
    case DeviceOffline = 'device_offline';
    case DeviceOnline = 'device_online';
    case GpsLost = 'gps_lost';

    // ── Duty / operational ───────────────────────────────────────────────────
    case PunchIn = 'punch_in';
    case PunchOut = 'punch_out';
    case IdleTooLong = 'idle_too_long';
    case PunchInOutsideBeat = 'punch_in_outside_beat';
    case PunchOutOutsideBeat = 'punch_out_outside_beat';
    case MissedPunchIn = 'missed_punch_in';

    public function label(): string
    {
        return match ($this) {
            AlertRuleEventType::Sos => 'SOS Distress',
            AlertRuleEventType::Overspeed => 'Overspeed',
            AlertRuleEventType::LowBattery => 'Low Battery',
            AlertRuleEventType::PowerFailure => 'Power Failure',
            AlertRuleEventType::Vibration => 'Vibration / Shock',
            AlertRuleEventType::BeatViolation => 'Beat Violation',
            AlertRuleEventType::DeviceOffline => 'Device Offline',
            AlertRuleEventType::DeviceOnline => 'Device Online',
            AlertRuleEventType::GpsLost => 'GPS Lost',
            AlertRuleEventType::PunchIn => 'Punch In',
            AlertRuleEventType::PunchOut => 'Punch Out',
            AlertRuleEventType::IdleTooLong => 'Idle Too Long',
            AlertRuleEventType::PunchInOutsideBeat => 'Punch In Outside Beat',
            AlertRuleEventType::PunchOutOutsideBeat => 'Punch Out Outside Beat',
            AlertRuleEventType::MissedPunchIn => 'Missed Punch In',
        };
    }

    /** Default priority for auto-generated incidents of this type. */
    public function defaultPriority(): IncidentPriority
    {
        return match ($this) {
            AlertRuleEventType::Sos => IncidentPriority::Critical,
            AlertRuleEventType::PowerFailure => IncidentPriority::Critical,
            AlertRuleEventType::Overspeed => IncidentPriority::High,
            AlertRuleEventType::BeatViolation => IncidentPriority::High,
            AlertRuleEventType::LowBattery => IncidentPriority::Medium,
            AlertRuleEventType::Vibration => IncidentPriority::Medium,
            AlertRuleEventType::DeviceOffline => IncidentPriority::High,
            AlertRuleEventType::GpsLost => IncidentPriority::Medium,
            default => IncidentPriority::Low,
        };
    }

    /** Whether this incident type can be auto-resolved when the condition clears. */
    public function isAutoResolvable(): bool
    {
        return in_array($this, [
            AlertRuleEventType::Sos,
            AlertRuleEventType::Overspeed,
            AlertRuleEventType::LowBattery,
            AlertRuleEventType::PowerFailure,
            AlertRuleEventType::Vibration,
            AlertRuleEventType::BeatViolation,
            AlertRuleEventType::GpsLost,
            AlertRuleEventType::DeviceOffline,
        ]);
    }
}
