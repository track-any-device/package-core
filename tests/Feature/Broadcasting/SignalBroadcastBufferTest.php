<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Tests\Feature\Broadcasting;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Orchestra\Testbench\TestCase;
use TrackAnyDevice\Core\CoreServiceProvider;
use TrackAnyDevice\Core\Enums\SignalEventType;
use TrackAnyDevice\Core\Enums\SignalSource;
use TrackAnyDevice\Core\Events\LocationsBatchEvent;
use TrackAnyDevice\Core\Models\Device;
use TrackAnyDevice\Core\Services\SignalBroadcastBuffer;
use TrackAnyDevice\Drivers\ValueObjects\SignalObject;

class SignalBroadcastBufferTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }

    private function makeDevice(array $attrs = []): Device
    {
        $device = new Device;
        $device->forceFill(array_merge([
            'id' => 1,
            'imei' => '123456789012345',
            'tenant_id' => 42,
            'user_id' => 10,
        ], $attrs));

        return $device;
    }

    private function makeSignal(float $lat = 31.52, float $lng = 74.36): SignalObject
    {
        return new SignalObject(
            eventType: SignalEventType::Update,
            source: SignalSource::Tcp,
            latitude: $lat,
            longitude: $lng,
            batteryPercent: 85,
        );
    }

    /** @test */
    public function it_is_last_write_wins_per_device(): void
    {
        Redis::shouldReceive('pipeline')->twice();

        $buffer = new SignalBroadcastBuffer;
        $device = $this->makeDevice();

        $buffer->push($device, $this->makeSignal(31.0, 74.0));
        $buffer->push($device, $this->makeSignal(32.0, 75.0));
    }

    /** @test */
    public function it_flush_emits_one_locations_batch_event_per_tenant(): void
    {
        Event::fake([LocationsBatchEvent::class]);

        Redis::shouldReceive('smembers')
            ->with('signal-buffer:tenants')
            ->andReturn(['42', '99']);

        Redis::shouldReceive('pipeline')->twice()->andReturn([
            ['1' => json_encode(['imei' => 'A', 'lat' => 31.52, 'lng' => 74.36, 'battery' => 80, 'recorded_at' => '2026-01-01T00:00:00Z'])],
        ]);

        Redis::shouldReceive('srem')->twice();

        $buffer = new SignalBroadcastBuffer;
        $count = $buffer->flush();

        $this->assertSame(2, $count);
        Event::assertDispatched(LocationsBatchEvent::class, 2);
    }

    /** @test */
    public function it_flush_returns_total_location_count(): void
    {
        Event::fake([LocationsBatchEvent::class]);

        Redis::shouldReceive('smembers')
            ->with('signal-buffer:tenants')
            ->andReturn(['42']);

        Redis::shouldReceive('pipeline')->once()->andReturn([
            [
                '1' => json_encode(['imei' => 'A', 'lat' => 31.52, 'lng' => 74.36, 'battery' => 80, 'recorded_at' => '2026-01-01T00:00:00Z']),
                '2' => json_encode(['imei' => 'B', 'lat' => 32.00, 'lng' => 75.00, 'battery' => null, 'recorded_at' => '2026-01-01T00:00:01Z']),
            ],
        ]);

        Redis::shouldReceive('srem')->once();

        $buffer = new SignalBroadcastBuffer;
        $count = $buffer->flush();

        $this->assertSame(2, $count);
    }

    /** @test */
    public function it_drops_devices_without_a_tenant_or_without_location(): void
    {
        $buffer = new SignalBroadcastBuffer;

        $noTenant = $this->makeDevice(['tenant_id' => null]);
        $signal = $this->makeSignal();

        Redis::shouldNotReceive('pipeline');
        $buffer->push($noTenant, $signal);

        $noLocation = new SignalObject(
            eventType: SignalEventType::Heartbeat,
            source: SignalSource::Tcp,
        );

        $buffer->push($this->makeDevice(), $noLocation);
    }
}
