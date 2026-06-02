<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TrackAnyDevice\Drivers\ValueObjects\SignalObject;

class SignalCreatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $deviceId,
        public readonly ?string $imei,
        public readonly ?int $tenantId,
        public readonly ?int $userId,
        public readonly SignalObject $signal,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        if ($this->tenantId !== null) {
            return [new PrivateChannel('tenant.'.$this->tenantId.'.locations')];
        }

        if ($this->userId !== null) {
            return [new PrivateChannel('user.'.$this->userId.'.devices')];
        }

        return [];
    }

    public function broadcastAs(): string
    {
        return 'signal.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'device_id' => $this->deviceId,
            'imei' => $this->imei,
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'signal' => $this->signal->toArray(),
        ];
    }
}
