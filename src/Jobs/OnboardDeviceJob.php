<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Jobs;

use TrackAnyDevice\Drivers\Contracts\DeviceDriverInterface;
use TrackAnyDevice\Core\Enums\OnboardingStatus;
use TrackAnyDevice\Core\Events\DeviceOnboardedEvent;
use TrackAnyDevice\Core\Models\Device;
use TrackAnyDevice\Core\Providers\DeviceServiceProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Runs the driver's onboardingAction() for a device. Idempotent —
 * skips devices already in `verified` state. Drivers are responsible
 * for queueing the actual SMS commands.
 */
class OnboardDeviceJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $deviceId) {}

    public function handle(): void
    {
        $device = Device::with(['deviceType', 'driver', 'gsmNetwork'])->find($this->deviceId);

        if ($device === null) {
            return;
        }

        if ($device->onboarding_status === OnboardingStatus::Verified) {
            return;
        }

        $driver = $this->resolveDriver($device);

        if ($driver === null) {
            Log::warning('OnboardDeviceJob: no driver resolvable', ['device_id' => $device->id]);

            return;
        }

        try {
            $driver->onboardingAction($device);

            $device->forceFill([
                'onboarding_status' => OnboardingStatus::Configured,
            ])->save();

            event(new DeviceOnboardedEvent($device->fresh()));
        } catch (\Throwable $e) {
            Log::error('OnboardDeviceJob: onboardingAction failed', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveDriver(Device $device): ?DeviceDriverInterface
    {
        $slug = $device->deviceType?->slug;

        if (! $slug) {
            return null;
        }

        try {
            return DeviceServiceProvider::driverFor($slug);
        } catch (\Throwable) {
            return null;
        }
    }
}
