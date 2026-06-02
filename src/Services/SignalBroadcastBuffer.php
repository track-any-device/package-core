<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Services;

use Illuminate\Support\Facades\Redis;
use TrackAnyDevice\Core\Events\LocationsBatchEvent;
use TrackAnyDevice\Core\Models\Device;
use TrackAnyDevice\Drivers\ValueObjects\SignalObject;

class SignalBroadcastBuffer
{
    private const PREFIX = 'signal-buffer:';

    private const INDEX = 'signal-buffer:tenants';

    public function push(Device $device, SignalObject $signal): void
    {
        if ($device->tenant_id === null || ! $signal->hasLocation()) {
            return;
        }

        $payload = json_encode([
            'imei' => $device->imei,
            'lat' => $signal->latitude,
            'lng' => $signal->longitude,
            'battery' => $signal->batteryPercent,
            'recorded_at' => ($signal->serverTime ?? now())->toIso8601ZuluString(),
        ]);

        Redis::pipeline(function ($pipe) use ($device, $payload) {
            $pipe->hset(self::PREFIX.$device->tenant_id, (string) $device->id, $payload);
            $pipe->sadd(self::INDEX, (string) $device->tenant_id);
            $pipe->expire(self::PREFIX.$device->tenant_id, 60);
        });
    }

    public function flush(): int
    {
        $tenantIds = Redis::smembers(self::INDEX);
        if ($tenantIds === []) {
            return 0;
        }

        $total = 0;

        foreach ($tenantIds as $tenantId) {
            $key = self::PREFIX.$tenantId;

            $rows = Redis::pipeline(function ($pipe) use ($key) {
                $pipe->hgetall($key);
                $pipe->del($key);
            })[0] ?? [];

            Redis::srem(self::INDEX, $tenantId);

            if ($rows === []) {
                continue;
            }

            $locations = [];
            foreach ($rows as $deviceId => $json) {
                $row = json_decode((string) $json, true);
                if (! is_array($row)) {
                    continue;
                }
                $locations[] = [
                    'device_id' => (int) $deviceId,
                    'imei' => $row['imei'] ?? null,
                    'lat' => (float) $row['lat'],
                    'lng' => (float) $row['lng'],
                    'battery' => $row['battery'] !== null ? (int) $row['battery'] : null,
                    'recorded_at' => (string) $row['recorded_at'],
                ];
            }

            if ($locations === []) {
                continue;
            }

            event(new LocationsBatchEvent((int) $tenantId, $locations));
            $total += count($locations);
        }

        return $total;
    }
}
