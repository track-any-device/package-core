<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Tests\Feature\Broadcasting;

use Illuminate\Broadcasting\PrivateChannel;
use PHPUnit\Framework\TestCase;
use TrackAnyDevice\Core\Events\SignalCreatedEvent;
use TrackAnyDevice\Drivers\ValueObjects\SignalObject;

class SignalCreatedEventTest extends TestCase
{
    private function makeSignal(): SignalObject
    {
        return new SignalObject(
            eventType: \TrackAnyDevice\Core\Enums\SignalEventType::Update,
            source: \TrackAnyDevice\Core\Enums\SignalSource::Tcp,
            latitude: 31.5204,
            longitude: 74.3587,
        );
    }

    /** @test */
    public function it_routes_to_tenant_locations_channel_when_tenant_present(): void
    {
        $event = new SignalCreatedEvent(
            deviceId: 1,
            imei: '123456789012345',
            tenantId: 42,
            userId: 10,
            signal: $this->makeSignal(),
        );

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-tenant.42.locations', $channels[0]->name);
    }

    /** @test */
    public function it_routes_to_user_devices_channel_when_no_tenant(): void
    {
        $event = new SignalCreatedEvent(
            deviceId: 1,
            imei: '123456789012345',
            tenantId: null,
            userId: 10,
            signal: $this->makeSignal(),
        );

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertSame('private-user.10.devices', $channels[0]->name);
    }

    /** @test */
    public function it_returns_empty_channels_when_neither_tenant_nor_user(): void
    {
        $event = new SignalCreatedEvent(
            deviceId: 1,
            imei: '123456789012345',
            tenantId: null,
            userId: null,
            signal: $this->makeSignal(),
        );

        $this->assertSame([], $event->broadcastOn());
    }

    /** @test */
    public function it_does_not_call_device_find_on_broadcast_with(): void
    {
        $signal = $this->makeSignal();
        $event = new SignalCreatedEvent(
            deviceId: 99,
            imei: 'ABCDE',
            tenantId: 5,
            userId: 3,
            signal: $signal,
        );

        $data = $event->broadcastWith();

        $this->assertSame(99, $data['device_id']);
        $this->assertSame('ABCDE', $data['imei']);
        $this->assertSame(5, $data['tenant_id']);
        $this->assertSame(3, $data['user_id']);
        $this->assertArrayHasKey('signal', $data);
    }
}
