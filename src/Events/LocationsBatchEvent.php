<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LocationsBatchEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int, array{device_id:int,imei:?string,lat:float,lng:float,battery:?int,recorded_at:string}>  $locations
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly array $locations,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('tenant.'.$this->tenantId.'.locations')];
    }

    public function broadcastAs(): string
    {
        return 'locations.batch';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'count' => count($this->locations),
            'locations' => $this->locations,
        ];
    }
}
