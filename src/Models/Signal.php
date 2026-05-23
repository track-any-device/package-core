<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Drivers\ValueObjects\SignalObject;
use TrackAnyDevice\Core\Enums\SignalEventType;
use TrackAnyDevice\Core\Enums\SignalSource;
use TrackAnyDevice\Core\Services\SignalService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/**
 * Read-only projection of a signal row from InfluxDB.
 *
 * This is intentionally NOT an Eloquent model — signals are time-series
 * data stored as InfluxDB points. Use SignalService to read or write.
 *
 * Provided so that Filament resources, API endpoints, and views can
 * type-hint a single representation regardless of storage backend.
 */
final class Signal implements Arrayable
{
    public function __construct(
        public readonly int $deviceId,
        public readonly ?string $imei,
        public readonly SignalEventType $eventType,
        public readonly SignalSource $source,
        public readonly CarbonImmutable $serverTime,
        public readonly ?CarbonImmutable $deviceTime,
        public readonly ?float $latitude,
        public readonly ?float $longitude,
        public readonly ?int $altitude,
        public readonly ?float $speed,
        public readonly ?int $direction,
        public readonly bool $gpsFixed,
        public readonly ?int $satellites,
        public readonly ?string $positioningType,
        public readonly ?float $hdop,
        public readonly ?int $batteryPercent,
        public readonly ?int $batteryVoltage,
        public readonly ?int $batteryCapacityMah,
        public readonly ?int $gsmSignal,
        public readonly ?int $networkSignal,
        public readonly ?int $mcc,
        public readonly ?int $mnc,
        public readonly ?int $lac,
        public readonly ?int $cellId,
        public readonly ?string $workingMode,
        public readonly ?int $alarmFlags,
        public readonly ?int $statusFlags,
        public readonly ?float $level,
        public readonly ?float $temperature,
        public readonly ?string $rawPayload,
        public readonly array $extra,
    ) {}

    public static function fromInfluxRow(array $row): self
    {
        $eventType = $row['event_type'] ?? SignalEventType::Update->value;
        $source = $row['source'] ?? SignalSource::Api->value;

        return new self(
            deviceId: (int) ($row['device_id'] ?? 0),
            imei: $row['imei'] ?? null,
            eventType: SignalEventType::tryFrom((string) $eventType) ?? SignalEventType::Update,
            source: SignalSource::tryFrom((string) $source) ?? SignalSource::Api,
            serverTime: CarbonImmutable::parse($row['_time'] ?? $row['server_time'] ?? 'now')->utc(),
            deviceTime: isset($row['device_time']) ? CarbonImmutable::parse((string) $row['device_time'])->utc() : null,
            latitude: isset($row['latitude']) ? (float) $row['latitude'] : null,
            longitude: isset($row['longitude']) ? (float) $row['longitude'] : null,
            altitude: isset($row['altitude']) ? (int) $row['altitude'] : null,
            speed: isset($row['speed']) ? (float) $row['speed'] : null,
            direction: isset($row['direction']) ? (int) $row['direction'] : null,
            gpsFixed: (bool) ($row['gps_fixed'] ?? false),
            satellites: isset($row['satellites']) ? (int) $row['satellites'] : null,
            positioningType: $row['positioning_type'] ?? null,
            hdop: isset($row['hdop']) ? (float) $row['hdop'] : null,
            batteryPercent: isset($row['battery_percent']) ? (int) $row['battery_percent'] : null,
            batteryVoltage: isset($row['battery_voltage']) ? (int) $row['battery_voltage'] : null,
            batteryCapacityMah: isset($row['battery_capacity_mah']) ? (int) $row['battery_capacity_mah'] : null,
            gsmSignal: isset($row['gsm_signal']) ? (int) $row['gsm_signal'] : null,
            networkSignal: isset($row['network_signal']) ? (int) $row['network_signal'] : null,
            mcc: isset($row['mcc']) ? (int) $row['mcc'] : null,
            mnc: isset($row['mnc']) ? (int) $row['mnc'] : null,
            lac: isset($row['lac']) ? (int) $row['lac'] : null,
            cellId: isset($row['cell_id']) ? (int) $row['cell_id'] : null,
            workingMode: $row['working_mode'] ?? null,
            alarmFlags: isset($row['alarm_flags']) ? (int) $row['alarm_flags'] : null,
            statusFlags: isset($row['status_flags']) ? (int) $row['status_flags'] : null,
            level: isset($row['level']) ? (float) $row['level'] : null,
            temperature: isset($row['temperature']) ? (float) $row['temperature'] : null,
            rawPayload: $row['raw_payload'] ?? null,
            extra: isset($row['extra']) && is_string($row['extra'])
                ? (json_decode($row['extra'], true) ?? [])
                : (array) ($row['extra'] ?? []),
        );
    }

    /** Convenience: query InfluxDB for the most recent signals of a device. */
    public static function forDevice(int $deviceId, int $limit = 100): Collection
    {
        return app(SignalService::class)->latestForDevice($deviceId, $limit);
    }

    public function toArray(): array
    {
        return [
            'device_id' => $this->deviceId,
            'imei' => $this->imei,
            'event_type' => $this->eventType->value,
            'source' => $this->source->value,
            'server_time' => $this->serverTime->toIso8601ZuluString(),
            'device_time' => $this->deviceTime?->toIso8601ZuluString(),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'altitude' => $this->altitude,
            'speed' => $this->speed,
            'direction' => $this->direction,
            'gps_fixed' => $this->gpsFixed,
            'satellites' => $this->satellites,
            'positioning_type' => $this->positioningType,
            'hdop' => $this->hdop,
            'battery_percent' => $this->batteryPercent,
            'battery_voltage' => $this->batteryVoltage,
            'battery_capacity_mah' => $this->batteryCapacityMah,
            'gsm_signal' => $this->gsmSignal,
            'network_signal' => $this->networkSignal,
            'mcc' => $this->mcc,
            'mnc' => $this->mnc,
            'lac' => $this->lac,
            'cell_id' => $this->cellId,
            'working_mode' => $this->workingMode,
            'alarm_flags' => $this->alarmFlags,
            'status_flags' => $this->statusFlags,
            'level' => $this->level,
            'temperature' => $this->temperature,
            'raw_payload' => $this->rawPayload,
            'extra' => $this->extra,
        ];
    }

    public static function fromSignalObject(SignalObject $obj, int $deviceId, ?string $imei): self
    {
        return new self(
            deviceId: $deviceId,
            imei: $imei,
            eventType: $obj->eventType,
            source: $obj->source,
            serverTime: $obj->serverTime ?? CarbonImmutable::now('UTC'),
            deviceTime: $obj->deviceTime,
            latitude: $obj->latitude,
            longitude: $obj->longitude,
            altitude: $obj->altitude,
            speed: $obj->speed,
            direction: $obj->direction,
            gpsFixed: $obj->gpsFixed,
            satellites: $obj->satellites,
            positioningType: $obj->positioningType,
            hdop: $obj->hdop,
            batteryPercent: $obj->batteryPercent,
            batteryVoltage: $obj->batteryVoltage,
            batteryCapacityMah: $obj->batteryCapacityMah,
            gsmSignal: $obj->gsmSignal,
            networkSignal: $obj->networkSignal,
            mcc: $obj->mcc,
            mnc: $obj->mnc,
            lac: $obj->lac,
            cellId: $obj->cellId,
            workingMode: $obj->workingMode,
            alarmFlags: $obj->alarmFlags,
            statusFlags: $obj->statusFlags,
            level: $obj->level,
            temperature: $obj->temperature,
            rawPayload: $obj->rawPayload,
            extra: $obj->extra,
        );
    }
}
