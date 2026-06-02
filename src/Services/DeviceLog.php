<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Services;

use Illuminate\Support\Facades\Log;
use TrackAnyDevice\Core\Enums\DeviceLogDirection;
use TrackAnyDevice\Core\Enums\DeviceLogSource;
use TrackAnyDevice\Core\Events\DeviceLogEvent;
use TrackAnyDevice\Core\Models\Device;

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
            $source, DeviceLogDirection::In, $summary, $payload,
            $device, $tenantId, $imei, $deviceId, $level,
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
            $source, DeviceLogDirection::Out, $summary, $payload,
            $device, $tenantId, $imei, $deviceId, $level,
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
                includeAdminChannel: self::shouldIncludeAdminChannel($level),
            ));
        } catch (\Throwable $e) {
            Log::warning('DeviceLog emit failed', [
                'source' => $source->value,
                'direction' => $direction->value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private static function shouldIncludeAdminChannel(string $level): bool
    {
        if (! (bool) config('device_logs.admin_enabled', true)) {
            return false;
        }

        if ($level !== 'info') {
            return true;
        }

        $rate = (float) config('device_logs.admin_sample_rate', 0.01);
        if ($rate >= 1.0) {
            return true;
        }
        if ($rate <= 0.0) {
            return false;
        }

        return mt_rand() / mt_getrandmax() < $rate;
    }
}
