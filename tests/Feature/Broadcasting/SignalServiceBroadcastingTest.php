<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Tests\Feature\Broadcasting;

use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;
use TrackAnyDevice\Core\CoreServiceProvider;
use TrackAnyDevice\Core\Enums\SignalEventType;
use TrackAnyDevice\Core\Enums\SignalSource;
use TrackAnyDevice\Core\Events\SignalCreatedEvent;
use TrackAnyDevice\Core\Models\Device;
use TrackAnyDevice\Core\Services\SignalBroadcastBuffer;
use TrackAnyDevice\Core\Services\SignalService;
use TrackAnyDevice\Drivers\ValueObjects\SignalObject;

class SignalServiceBroadcastingTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }

    private function makeSignal(SignalEventType $type = SignalEventType::Update): SignalObject
    {
        return new SignalObject(
            eventType: $type,
            source: SignalSource::Tcp,
            latitude: 31.5204,
            longitude: 74.3587,
        );
    }

    private function makeDevice(array $attrs = []): Device
    {
        $device = new Device;
        $device->forceFill(array_merge([
            'id' => 1,
            'imei' => '123456789012345',
            'tenant_id' => 42,
            'user_id' => 10,
            'status' => \TrackAnyDevice\Core\Enums\DeviceStatus::Active,
        ], $attrs));

        return $device;
    }

    /** @test */
    public function it_broadcasts_critical_signals_immediately(): void
    {
        Event::fake([SignalCreatedEvent::class]);
        $buffer = $this->mock(SignalBroadcastBuffer::class);
        $buffer->shouldNotReceive('push');

        $service = new SignalService($buffer);
        $device = $this->makeDevice();

        $service->record($this->makeSignal(SignalEventType::Sos), $device);

        Event::assertDispatched(SignalCreatedEvent::class);
    }

    /** @test */
    public function it_buffers_routine_signals(): void
    {
        Event::fake([SignalCreatedEvent::class]);
        $buffer = $this->mock(SignalBroadcastBuffer::class);
        $buffer->shouldReceive('push')->once();

        $service = new SignalService($buffer);
        $device = $this->makeDevice();

        $service->record($this->makeSignal(SignalEventType::Update), $device);

        Event::assertNotDispatched(SignalCreatedEvent::class);
    }

    /** @test */
    public function it_treats_sos_low_battery_geofence_exit_as_critical(): void
    {
        $criticalTypes = [SignalEventType::Sos, SignalEventType::LowBattery, SignalEventType::GeofenceExit];

        foreach ($criticalTypes as $type) {
            Event::fake([SignalCreatedEvent::class]);
            $buffer = $this->mock(SignalBroadcastBuffer::class);
            $buffer->shouldNotReceive('push');

            $service = new SignalService($buffer);
            $device = $this->makeDevice();

            $service->record($this->makeSignal($type), $device);

            Event::assertDispatched(SignalCreatedEvent::class, fn ($e) => true);
        }
    }
}
