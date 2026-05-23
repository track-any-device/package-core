<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Events;

use TrackAnyDevice\Core\Models\Device;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a device completes the driver's onboarding sequence
 * (all setup SMS commands queued). Broadcast on admin + tenant + user
 * channels so the UI can flip the onboarding badge.
 */
class DeviceOnboardedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Device $device) {}

    public function broadcastOn(): array
    {
        $channels = [new Channel('admin.devices')];

        if ($this->device->tenant_id) {
            $channels[] = new Channel("tenant.{$this->device->tenant_id}.devices");
        }

        if ($this->device->user_id) {
            $channels[] = new Channel("user.{$this->device->user_id}.devices");
        }

        $channels[] = new Channel("device.{$this->device->id}");

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'device.onboarded';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->device->id,
            'imei' => $this->device->imei,
            'onboarding_status' => $this->device->onboarding_status?->value,
        ];
    }
}
