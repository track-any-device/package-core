<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Jobs;

use TrackAnyDevice\Core\Enums\AlertRuleEventType;
use TrackAnyDevice\Core\Enums\IncidentStatus;
use TrackAnyDevice\Core\Models\Device;
use TrackAnyDevice\Core\Models\Incident;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Creates, maintains, and auto-resolves incidents from named alarm events.
 *
 * This job is intentionally protocol-agnostic: it receives a list of
 * currently-active alarm names (e.g. ["sos","overspeed"]) and a snapshot
 * of sensor readings.  All protocol-specific bit-flag parsing lives in the
 * originating device service (e.g. Go JT808 server).
 *
 * Alarm lifecycle:
 *   - Alarm name present in $activeAlarms → open (or keep open) incident
 *   - Alarm name absent → auto-resolve any open incident for that type
 *
 * Overspeed threshold: OVERSPEED_THRESHOLD_KMH env (default 80).
 * Low-battery threshold: LOW_BATTERY_THRESHOLD env (default 20).
 */
class ProcessAlarmEvents implements ShouldQueue
{
    use Queueable;

    private const HANDLED_ALARMS = ['sos', 'overspeed', 'low_battery', 'power_failure', 'vibration'];

    public function __construct(
        public readonly int $deviceId,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly float $speedKmh,
        public readonly ?int $batteryLevel,
        public readonly bool $accOn,
        /** @var string[] */
        public readonly array $activeAlarms,
    ) {}

    public function handle(): void
    {
        $device = Device::find($this->deviceId);
        if ($device === null) {
            return;
        }

        $activeAssigneeId = $device->deviceAssignments()
            ->where('status', 'active')
            ->value('assignee_id');

        foreach (self::HANDLED_ALARMS as $alarmName) {
            $isActive = in_array($alarmName, $this->activeAlarms, true);
            $eventType = $this->alarmToEventType($alarmName);

            if ($eventType === null) {
                continue;
            }

            // Vibration is captured by the signal write itself — no separate row.
            if ($alarmName === 'vibration') {
                continue;
            }

            $openIncident = $this->findOpenIncident($this->deviceId, $eventType);

            if ($isActive) {
                if ($openIncident === null) {
                    $this->openIncident($device, $eventType, $activeAssigneeId);
                }
            } elseif ($openIncident !== null && $eventType->isAutoResolvable()) {
                $openIncident->update([
                    'status' => IncidentStatus::Resolved,
                    'resolved_at' => now(),
                    'resolution_notes' => $this->autoResolveNote($alarmName),
                ]);
            }
        }

        // Telemetry time-series (battery / motion) lives in the signal point
        // itself — written by SignalService — so we no longer mirror it here.
    }

    private function alarmToEventType(string $alarmName): ?AlertRuleEventType
    {
        return match ($alarmName) {
            'sos' => AlertRuleEventType::Sos,
            'overspeed' => AlertRuleEventType::Overspeed,
            'low_battery' => AlertRuleEventType::LowBattery,
            'power_failure' => AlertRuleEventType::PowerFailure,
            'vibration' => AlertRuleEventType::Vibration,
            default => null,
        };
    }

    private function findOpenIncident(int $deviceId, AlertRuleEventType $eventType): ?Incident
    {
        return Incident::where('device_id', $deviceId)
            ->where('event_type', $eventType->value)
            ->whereIn('status', [
                IncidentStatus::Open->value,
                IncidentStatus::Acknowledged->value,
                IncidentStatus::Escalated->value,
            ])
            ->latest()
            ->first();
    }

    private function openIncident(Device $device, AlertRuleEventType $eventType, ?int $assigneeId): void
    {
        Incident::create([
            'tenant_id' => $device->tenant_id,
            'device_id' => $device->id,
            'assignee_id' => $assigneeId,
            'event_type' => $eventType,
            'priority' => $eventType->defaultPriority(),
            'status' => IncidentStatus::Open,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'triggered_at' => now(),
            'reopen_count' => 0,
            'payload' => $this->incidentPayload($eventType),
        ]);
    }

    private function incidentPayload(AlertRuleEventType $eventType): array
    {
        return match ($eventType) {
            AlertRuleEventType::Overspeed => [
                'speed_kmh' => $this->speedKmh,
                'threshold_kmh' => (int) config('fleet.overspeed_threshold', 80),
            ],
            AlertRuleEventType::LowBattery => [
                'battery' => $this->batteryLevel,
                'threshold' => (int) config('fleet.low_battery_threshold', 20),
            ],
            default => [],
        };
    }

    private function autoResolveNote(string $alarmName): string
    {
        return match ($alarmName) {
            'sos' => 'Auto-resolved: SOS alarm cleared by device.',
            'overspeed' => sprintf('Auto-resolved: speed returned below threshold (%.1f km/h).', $this->speedKmh),
            'low_battery' => sprintf('Auto-resolved: battery recovered (%d%%).', $this->batteryLevel ?? 0),
            'power_failure' => 'Auto-resolved: external power restored.',
            default => 'Auto-resolved: alarm condition cleared.',
        };
    }
}
