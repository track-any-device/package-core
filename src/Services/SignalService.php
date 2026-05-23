<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Services;

use TrackAnyDevice\Drivers\ValueObjects\SignalObject;
use TrackAnyDevice\Core\Enums\DeviceStatus;
use TrackAnyDevice\Core\Enums\OnboardingStatus;
use TrackAnyDevice\Core\Enums\SignalEventType;
use TrackAnyDevice\Core\Events\SignalCreatedEvent;
use TrackAnyDevice\Core\Models\Device;
use TrackAnyDevice\Core\Models\Signal;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InfluxDB2\Client;
use InfluxDB2\Model\WritePrecision;
use InfluxDB2\Point;
use InfluxDB2\QueryApi;
use InfluxDB2\WriteApi;

/**
 * Canonical store for device telemetry. Writes signals as InfluxDB points
 * (measurement = "signal") and updates the device's snapshot columns in
 * the central MySQL row so the live map / Filament list have a fast path.
 *
 * Signals are stored in UTC at second precision. All inputs are normalised
 * to UTC before write; all outputs from queryHistory()/latestForDevice()
 * are CarbonImmutable in UTC.
 */
class SignalService
{
    private ?Client $client = null;

    public const MEASUREMENT = 'signal';

    /**
     * Persist a SignalObject for the given device.
     *
     * - Writes the InfluxDB point.
     * - Updates the device's snapshot columns (last_signal_at, last_seen_at,
     *   last_lat/lon, battery_level, firmware_version on registration).
     * - Dispatches SignalCreatedEvent so observers/broadcasts can react.
     *
     * Returns the persisted Signal projection (useful for tests + API).
     */
    public function record(SignalObject $signal, Device $device): Signal
    {
        $now = CarbonImmutable::now('UTC');
        $signal = $signal->withServerTime($signal->serverTime ?? $now);

        $this->writePoint($signal, $device);

        $this->updateDeviceSnapshot($signal, $device);

        event(new SignalCreatedEvent($device->id, $signal));

        return Signal::fromSignalObject($signal, $device->id, $device->imei);
    }

    /**
     * Query historical signals for a device within a time range.
     *
     * @return Collection<int, Signal>
     */
    public function queryHistory(
        int $deviceId,
        DateTimeInterface $from,
        DateTimeInterface $to,
        int $limit = 500,
        ?string $eventType = null,
    ): Collection {
        if (! $this->enabled()) {
            return collect();
        }

        $bucket = config('influxdb.bucket');
        $org = config('influxdb.org');
        $fromRfc = CarbonImmutable::instance($from)->utc()->toIso8601ZuluString();
        $toRfc = CarbonImmutable::instance($to)->utc()->toIso8601ZuluString();

        $measurement = self::MEASUREMENT;
        $eventFilter = $eventType
            ? "|> filter(fn: (r) => r.event_type == \"{$eventType}\")"
            : '';

        $flux = <<<FLUX
            from(bucket: "{$bucket}")
              |> range(start: {$fromRfc}, stop: {$toRfc})
              |> filter(fn: (r) => r._measurement == "{$measurement}")
              |> filter(fn: (r) => r.device_id == "{$deviceId}")
              {$eventFilter}
              |> pivot(rowKey: ["_time"], columnKey: ["_field"], valueColumn: "_value")
              |> sort(columns: ["_time"])
              |> limit(n: {$limit})
            FLUX;

        try {
            $result = $this->queryApi($org)->query($flux);
        } catch (\Throwable $e) {
            Log::warning('SignalService: queryHistory failed', [
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }

        return $this->fluxToSignals($result, $deviceId);
    }

    /**
     * Most recent signals for a device (across all event types).
     *
     * @return Collection<int, Signal>
     */
    public function latestForDevice(int $deviceId, int $limit = 100): Collection
    {
        return $this->queryHistory(
            $deviceId,
            (new CarbonImmutable('-30 days'))->utc(),
            CarbonImmutable::now('UTC'),
            $limit,
        );
    }

    /** Diagnostic helper — true when InfluxDB writes are enabled. */
    public function enabled(): bool
    {
        return (bool) config('influxdb.enabled', false);
    }

    // ── Internals ────────────────────────────────────────────────────────────

    private function writePoint(SignalObject $signal, Device $device): void
    {
        if (! $this->enabled()) {
            return;
        }

        try {
            $point = Point::measurement(self::MEASUREMENT)
                ->addTag('device_id', (string) $device->id)
                ->addTag('imei', (string) $device->imei)
                ->addTag('event_type', $signal->eventType->value)
                ->addTag('source', $signal->source->value);

            if ($device->tenant_id) {
                $point->addTag('tenant_id', (string) $device->tenant_id);
            }
            if ($device->user_id) {
                $point->addTag('user_id', (string) $device->user_id);
            }

            $fields = [
                'latitude' => $signal->latitude,
                'longitude' => $signal->longitude,
                'altitude' => $signal->altitude,
                'speed' => $signal->speed,
                'direction' => $signal->direction,
                'gps_fixed' => $signal->gpsFixed,
                'satellites' => $signal->satellites,
                'positioning_type' => $signal->positioningType,
                'hdop' => $signal->hdop,
                'battery_percent' => $signal->batteryPercent,
                'battery_voltage' => $signal->batteryVoltage,
                'battery_capacity_mah' => $signal->batteryCapacityMah,
                'battery_length' => $signal->batteryLength,
                'gsm_signal' => $signal->gsmSignal,
                'network_signal' => $signal->networkSignal,
                'mcc' => $signal->mcc,
                'mnc' => $signal->mnc,
                'lac' => $signal->lac,
                'cell_id' => $signal->cellId,
                'working_mode' => $signal->workingMode,
                'alarm_flags' => $signal->alarmFlags,
                'status_flags' => $signal->statusFlags,
                'level' => $signal->level,
                'temperature' => $signal->temperature,
                'raw_payload' => $signal->rawPayload,
                'extra' => $signal->extra === [] ? null : json_encode($signal->extra),
                'device_time' => $signal->deviceTime?->toIso8601ZuluString(),
            ];

            foreach ($fields as $name => $value) {
                if ($value === null) {
                    continue;
                }
                $point->addField($name, $value);
            }

            $point->time(($signal->serverTime ?? CarbonImmutable::now('UTC'))->getTimestamp(), WritePrecision::S);

            $this->writeApi()->write($point);
        } catch (\Throwable $e) {
            Log::warning('SignalService: write failed', [
                'device_id' => $device->id,
                'event_type' => $signal->eventType->value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function updateDeviceSnapshot(SignalObject $signal, Device $device): void
    {
        $updates = [
            'last_signal_at' => $signal->serverTime,
            'last_seen_at' => $signal->serverTime,
        ];

        // Device has reported in — clear any outstanding offline-recovery backoff
        // so the cron stops re-trying it.
        if ($device->connection_attempt_count > 0 || $device->next_connection_attempt_at !== null) {
            $updates['connection_attempt_count'] = 0;
            $updates['next_connection_attempt_at'] = null;
        }

        if ($signal->batteryPercent !== null) {
            $updates['battery_level'] = $signal->batteryPercent;
        }

        if ($signal->hasLocation()) {
            $updates['last_lat'] = $signal->latitude;
            $updates['last_lon'] = $signal->longitude;
        }

        if ($signal->eventType === SignalEventType::Registration) {
            $updates['onboarding_status'] = $device->onboarding_status === OnboardingStatus::Pending
                ? OnboardingStatus::SimAdded
                : $device->onboarding_status;
        }

        // Reconcile coarse-grained DeviceStatus on first real signal.
        if (in_array($device->status, [DeviceStatus::Warehouse, DeviceStatus::Inventory], true)) {
            $updates['status'] = $device->reconciledStatus();
        }

        $device->forceFill($updates)->save();
    }

    private function fluxToSignals(mixed $tables, int $deviceId): Collection
    {
        $signals = collect();

        foreach ($tables as $table) {
            foreach ($table->records as $record) {
                $values = $record->values;
                $values['device_id'] = $deviceId;
                $signals->push(Signal::fromInfluxRow($values));
            }
        }

        return $signals;
    }

    private function client(): Client
    {
        if ($this->client === null) {
            $this->client = new Client([
                'url' => config('influxdb.url'),
                'token' => config('influxdb.token'),
                'bucket' => config('influxdb.bucket'),
                'org' => config('influxdb.org'),
                'precision' => WritePrecision::S,
            ]);
        }

        return $this->client;
    }

    private function writeApi(): WriteApi
    {
        return $this->client()->createWriteApi();
    }

    private function queryApi(string $org): QueryApi
    {
        return $this->client()->createQueryApi();
    }
}
