<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Services;

use TrackAnyDevice\Core\Enums\DeviceStatus;
use TrackAnyDevice\Core\Enums\OnboardingStatus;
use TrackAnyDevice\Core\Models\Device;
use TrackAnyDevice\Core\Providers\DeviceServiceProvider;
use Illuminate\Support\Facades\Log;

/**
 * Detects in-service devices that have gone silent and dispatches one
 * recovery SMS per cron tick — either an onboarding sequence (when the
 * device has never been onboarded) or a single location-request poke.
 *
 * Routing rules:
 *  - TAD101 (Soketi WebSocket) is skipped: it has its own presence /
 *    reconnect logic and we don't want to spend SMS budget on it.
 *  - JT808 trackers (P901, AOT120) are SMS-poked only after a longer
 *    silence threshold (default 30 min) because their TCP socket can
 *    blip briefly without the device actually being offline.
 *  - Pure SMS devices (GF-07 — stream channel `none`) get the short
 *    threshold (default 5 min).
 *
 * Throttling combines three protections:
 *  - Per-device cooldown via `next_connection_attempt_at` (DB row).
 *  - Exponential backoff via `connection_attempt_count`: 5, 10, 20,
 *    40, 80, 160, 320, 360 (cap) minutes.
 *  - Hard cap at MAX_ATTEMPTS attempts — once exceeded the device is
 *    treated as unreachable and the cron stops pinging it until either
 *    a real signal arrives (SignalService resets the counter) or an
 *    admin manually clears the counter.
 */
class OfflineDeviceRecoveryService
{
    public const MAX_ATTEMPTS = 8;

    public const BACKOFF_BASE_MINUTES = 5;

    public const BACKOFF_CAP_MINUTES = 360;

    public const SMS_OFFLINE_THRESHOLD_MINUTES = 5;

    public const JT808_OFFLINE_THRESHOLD_MINUTES = 30;

    public const STREAM_CHANNEL_TAD101 = 'soketi';

    public const STREAM_CHANNEL_JT808 = 'jt808';

    public const CHUNK_SIZE = 100;

    /**
     * Run one pass of offline-device detection.
     *
     * @return array{onboarded:int, requested:int, skipped:int, unreachable:int, scanned:int}
     */
    public function detectAndDispatch(): array
    {
        $stats = ['onboarded' => 0, 'requested' => 0, 'skipped' => 0, 'unreachable' => 0, 'scanned' => 0];

        $this->eligibleQuery()->chunkById(self::CHUNK_SIZE, function ($devices) use (&$stats) {
            foreach ($devices as $device) {
                $stats['scanned']++;
                $outcome = $this->processDevice($device);
                $stats[$outcome]++;
            }
        });

        return $stats;
    }

    /**
     * Decide what (if anything) to do for a single device and dispatch
     * the corresponding driver action.
     */
    private function processDevice(Device $device): string
    {
        // 1. Driver / protocol routing.
        try {
            $driver = DeviceServiceProvider::driverFor($device->deviceType->slug);
        } catch (\Throwable $e) {
            Log::warning('OfflineDeviceRecovery: no driver registered for slug', [
                'device_id' => $device->id,
                'slug' => $device->deviceType?->slug,
            ]);

            return 'skipped';
        }

        $channel = $driver->getStreamChannel();

        // 2. TAD101 has its own reconnect mechanism — skip entirely.
        if ($channel === self::STREAM_CHANNEL_TAD101) {
            return 'skipped';
        }

        // 3. Unreachable cap.
        if ($device->connection_attempt_count >= self::MAX_ATTEMPTS) {
            return 'unreachable';
        }

        // 4. Case A — never onboarded. Dispatch the onboarding sequence.
        if ($device->onboarding_status === OnboardingStatus::Pending) {
            $driver->onboardingAction($device);
            $this->recordAttempt($device);
            Log::info('OfflineDeviceRecovery: queued onboarding', [
                'device_id' => $device->id,
                'attempt' => $device->connection_attempt_count,
            ]);

            return 'onboarded';
        }

        // 5. Case B — onboarded but silent. Apply the per-protocol threshold.
        $thresholdMinutes = $channel === self::STREAM_CHANNEL_JT808
            ? self::JT808_OFFLINE_THRESHOLD_MINUTES
            : self::SMS_OFFLINE_THRESHOLD_MINUTES;

        $silentSince = now()->subMinutes($thresholdMinutes);

        if ($device->last_seen_at !== null && $device->last_seen_at->greaterThanOrEqualTo($silentSince)) {
            return 'skipped';
        }

        $driver->requestSignal('location', $device);
        $this->recordAttempt($device);
        Log::info('OfflineDeviceRecovery: queued location request', [
            'device_id' => $device->id,
            'channel' => $channel,
            'attempt' => $device->connection_attempt_count,
            'silent_for_minutes' => $device->last_seen_at?->diffInMinutes(now()),
        ]);

        return 'requested';
    }

    /**
     * Increment the attempt counter and push the next-eligible-at floor
     * out using the exponential backoff schedule.
     */
    private function recordAttempt(Device $device): void
    {
        $count = $device->connection_attempt_count + 1;
        $delay = min(
            self::BACKOFF_BASE_MINUTES * (2 ** ($count - 1)),
            self::BACKOFF_CAP_MINUTES,
        );

        $device->forceFill([
            'connection_attempt_count' => $count,
            'last_update_requested_at' => now(),
            'next_connection_attempt_at' => now()->addMinutes($delay),
        ])->save();
    }

    /**
     * Devices that are candidates for the offline-detection cron.
     *
     * Filters applied:
     *  - Must have a GSM number (the only outgoing channel).
     *  - Status must be operational (in_service or assigned).
     *  - Backoff window must have elapsed (or this is the first attempt).
     */
    private function eligibleQuery()
    {
        return Device::query()
            ->with('deviceType')
            ->whereNotNull('gsm_number')
            ->whereIn('status', [DeviceStatus::InService->value, DeviceStatus::Assigned->value])
            ->where(fn ($q) => $q->whereNull('next_connection_attempt_at')
                ->orWhere('next_connection_attempt_at', '<=', now()))
            ->orderBy('id');
    }
}
