<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TrackAnyDevice\Core\Models\Device;

class DeviceUpdatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Device $device) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        if ($this->device->tenant_id) {
            $channels[] = new PrivateChannel("tenant.{$this->device->tenant_id}.devices");
        }

        if ($this->device->user_id) {
            $channels[] = new PrivateChannel("user.{$this->device->user_id}.devices");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'device.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->device->id,
            'imei' => $this->device->imei,
            'name' => $this->device->name,
            'status' => $this->device->status?->value,
            'onboarding_status' => $this->device->onboarding_status?->value,
            'last_lat' => $this->device->last_lat !== null ? (float) $this->device->last_lat : null,
            'last_lon' => $this->device->last_lon !== null ? (float) $this->device->last_lon : null,
            'battery_level' => $this->device->battery_level,
            'last_signal_at' => $this->device->last_signal_at?->toIso8601ZuluString(),
            'last_seen_at' => $this->device->last_seen_at?->toIso8601ZuluString(),
            'tenant_id' => $this->device->tenant_id,
            'user_id' => $this->device->user_id,
        ];
    }
}
