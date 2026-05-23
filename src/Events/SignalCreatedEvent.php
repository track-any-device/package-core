<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Events;

use TrackAnyDevice\Drivers\ValueObjects\SignalObject;
use TrackAnyDevice\Core\Models\Device;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired right after a signal is persisted to InfluxDB and the device
 * snapshot is updated. Broadcast on the device's per-id channel so
 * map listeners get a real-time push.
 */
class SignalCreatedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $deviceId,
        public readonly SignalObject $signal,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("device.{$this->deviceId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'signal.created';
    }

    public function broadcastWith(): array
    {
        $device = Device::find($this->deviceId);

        return [
            'device_id' => $this->deviceId,
            'imei' => $device?->imei,
            'tenant_id' => $device?->tenant_id,
            'user_id' => $device?->user_id,
            'signal' => $this->signal->toArray(),
        ];
    }
}
