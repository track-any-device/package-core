<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Tests\Feature\Broadcasting;

use Illuminate\Broadcasting\PrivateChannel;
use PHPUnit\Framework\TestCase;
use TrackAnyDevice\Core\Events\LocationsBatchEvent;

class LocationsBatchEventTest extends TestCase
{
    /** @test */
    public function it_broadcasts_on_the_tenant_locations_channel(): void
    {
        $event = new LocationsBatchEvent(42, [
            ['device_id' => 1, 'imei' => 'A', 'lat' => 31.52, 'lng' => 74.36, 'battery' => 80, 'recorded_at' => '2026-01-01T00:00:00Z'],
        ]);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-tenant.42.locations', $channels[0]->name);
        $this->assertSame('locations.batch', $event->broadcastAs());
    }

    /** @test */
    public function it_payload_includes_count_and_locations_array(): void
    {
        $locations = [
            ['device_id' => 1, 'imei' => 'A', 'lat' => 31.52, 'lng' => 74.36, 'battery' => 80, 'recorded_at' => '2026-01-01T00:00:00Z'],
            ['device_id' => 2, 'imei' => 'B', 'lat' => 32.00, 'lng' => 75.00, 'battery' => null, 'recorded_at' => '2026-01-01T00:00:01Z'],
        ];

        $event = new LocationsBatchEvent(42, $locations);
        $data = $event->broadcastWith();

        $this->assertSame(42, $data['tenant_id']);
        $this->assertSame(2, $data['count']);
        $this->assertCount(2, $data['locations']);
    }
}
