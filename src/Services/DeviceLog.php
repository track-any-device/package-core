<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Services;

use TrackAnyDevice\Core\Enums\DeviceLogDirection;
use TrackAnyDevice\Core\Enums\DeviceLogSource;
use TrackAnyDevice\Core\Events\DeviceLogEvent;
use TrackAnyDevice\Core\Models\Device;
use Illuminate\Support\Facades\Log;

/**
 * Static facade for emitting runtime device logs over broadcasting.
 *
 * Usage from anywhere in the codebase:
 *
 *   DeviceLog::in (DeviceLogSource::Tad101, 'Punch-in received', $payload, $device);
 *   DeviceLog::out(DeviceLogSource::Sms,   'set_mode',           $payload, $device);
 *
 * Pass a Device model and the tenant_id / imei / device_id are filled
 * automatically. Pass them explicitly when you don't have a model in
 * hand (e.g. inbound webhook before the device is resolved).
 */
class DeviceLog
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function in(
        DeviceLogSource $source,
        string $summary,
        array $payload = [],
        ?Device $device = null,
        ?int $tenantId = null,
        ?string $imei = null,
        ?int $deviceId = null,
        string $level = 'info',
    ): void {
        self::emit(
            $source,
            DeviceLogDirection::In,
            $summary,
            $payload,
            $device,
            $tenantId,
            $imei,
            $deviceId,
            $level,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function out(
        DeviceLogSource $source,
        string $summary,
        array $payload = [],
        ?Device $device = null,
        ?int $tenantId = null,
        ?string $imei = null,
        ?int $deviceId = null,
        string $level = 'info',
    ): void {
        self::emit(
            $source,
            DeviceLogDirection::Out,
            $summary,
            $payload,
            $device,
            $tenantId,
            $imei,
            $deviceId,
            $level,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function emit(
        DeviceLogSource $source,
        DeviceLogDirection $direction,
        string $summary,
        array $payload,
        ?Device $device,
        ?int $tenantId,
        ?string $imei,
        ?int $deviceId,
        string $level,
    ): void {
        // Pure observability — never let a broadcasting failure block
        // the actual device pipeline. Swallow + log and move on.
        try {
            event(new DeviceLogEvent(
                source: $source,
                direction: $direction,
                summary: $summary,
                payload: $payload,
                deviceId: $deviceId ?? $device?->id,
                imei: $imei ?? $device?->imei,
                tenantId: $tenantId ?? $device?->tenant_id,
                level: $level,
            ));
        } catch (\Throwable $e) {
            Log::warning('DeviceLog emit failed', [
                'source' => $source->value,
                'direction' => $direction->value,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
