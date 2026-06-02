<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TrackAnyDevice\Core\Models\Device;

class DeviceOnboardedEvent implements ShouldBroadcast
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
        return 'device.onboarded';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->device->id,
            'imei' => $this->device->imei,
            'onboarding_status' => $this->device->onboarding_status?->value,
        ];
    }
}
