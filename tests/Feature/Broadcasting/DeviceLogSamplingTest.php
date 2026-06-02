<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Tests\Feature\Broadcasting;

use Illuminate\Broadcasting\PrivateChannel;
use Orchestra\Testbench\TestCase;
use TrackAnyDevice\Core\CoreServiceProvider;
use TrackAnyDevice\Core\Enums\DeviceLogDirection;
use TrackAnyDevice\Core\Enums\DeviceLogSource;
use TrackAnyDevice\Core\Events\DeviceLogEvent;

class DeviceLogSamplingTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }

    /** @test */
    public function it_always_includes_admin_channel_for_warnings(): void
    {
        $event = new DeviceLogEvent(
            source: DeviceLogSource::Sms,
            direction: DeviceLogDirection::In,
            summary: 'test warning',
            level: 'warning',
            includeAdminChannel: true,
            tenantId: 42,
        );

        $channels = $event->broadcastOn();
        $names = array_map(fn (PrivateChannel $c) => $c->name, $channels);

        $this->assertContains('private-admin.device-logs', $names);
    }

    /** @test */
    public function it_always_includes_admin_channel_when_admin_sample_rate_is_1(): void
    {
        config(['device_logs.admin_sample_rate' => 1.0, 'device_logs.admin_enabled' => true]);

        $event = new DeviceLogEvent(
            source: DeviceLogSource::Sms,
            direction: DeviceLogDirection::In,
            summary: 'test',
            includeAdminChannel: true,
            tenantId: 42,
        );

        $channels = $event->broadcastOn();
        $names = array_map(fn (PrivateChannel $c) => $c->name, $channels);

        $this->assertContains('private-admin.device-logs', $names);
    }

    /** @test */
    public function it_never_includes_admin_channel_when_admin_sample_rate_is_0(): void
    {
        $event = new DeviceLogEvent(
            source: DeviceLogSource::Sms,
            direction: DeviceLogDirection::In,
            summary: 'test',
            includeAdminChannel: false,
            tenantId: 42,
        );

        $channels = $event->broadcastOn();
        $names = array_map(fn (PrivateChannel $c) => $c->name, $channels);

        $this->assertNotContains('private-admin.device-logs', $names);
    }

    /** @test */
    public function it_omits_admin_channel_when_admin_enabled_is_false(): void
    {
        $event = new DeviceLogEvent(
            source: DeviceLogSource::Sms,
            direction: DeviceLogDirection::In,
            summary: 'test',
            includeAdminChannel: false,
            tenantId: 42,
        );

        $channels = $event->broadcastOn();
        $names = array_map(fn (PrivateChannel $c) => $c->name, $channels);

        $this->assertNotContains('private-admin.device-logs', $names);
        $this->assertContains('private-tenant.42.device-logs', $names);
    }

    /** @test */
    public function it_always_includes_tenant_channel_when_tenant_id_provided(): void
    {
        $event = new DeviceLogEvent(
            source: DeviceLogSource::Sms,
            direction: DeviceLogDirection::In,
            summary: 'test',
            includeAdminChannel: false,
            tenantId: 42,
        );

        $channels = $event->broadcastOn();
        $names = array_map(fn (PrivateChannel $c) => $c->name, $channels);

        $this->assertContains('private-tenant.42.device-logs', $names);
    }
}
