<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Observers;

use TrackAnyDevice\Core\Enums\DeviceLogSource;
use TrackAnyDevice\Core\Jobs\CheckBeatViolation;
use TrackAnyDevice\Core\Models\Device;
use TrackAnyDevice\Core\Models\IncomingSms;
use TrackAnyDevice\Core\Providers\DeviceServiceProvider;
use TrackAnyDevice\Core\Services\DeviceLog;
use TrackAnyDevice\Core\Services\SignalService;
use Illuminate\Support\Facades\Log;

/**
 * Bridges the inbox poller (app's PollSmsInbox command) and the
 * signal pipeline. Every newly-stored IncomingSms row is:
 *
 *   1. Matched to a Device by sender_number (sim_number or gsm_number).
 *   2. Parsed through the device's driver via parseSmsToSignal().
 *   3. Persisted as a Signal point (InfluxDB) via SignalService.
 *   4. Routed through CheckBeatViolation when GPS fix is present.
 */
class IncomingSmsObserver
{
    public function __construct(private readonly SignalService $signalService) {}

    public function created(IncomingSms $sms): void
    {
        $device = $this->resolveDevice($sms->sender_number);

        if ($device === null) {
            DeviceLog::in(
                source: DeviceLogSource::Sms,
                summary: 'Inbound SMS from unknown sender',
                // sender_number is scrubbed by DeviceLogEvent's privacy
                // filter — admins can still inspect the IncomingSms row.
                payload: ['body' => $sms->raw_message, 'sender_number' => $sms->sender_number],
                level: 'warning',
            );

            $sms->update([
                'processed_at' => now(),
                'processing_error' => "No device found for sender number: {$sms->sender_number}",
            ]);

            return;
        }

        try {
            $driver = DeviceServiceProvider::driverFor($device->deviceType->slug);

            $signalObject = $driver->parseSmsToSignal($sms->raw_message, $device);

            $signal = $this->signalService->record($signalObject, $device);

            if ($signal->latitude !== null && $signal->longitude !== null) {
                CheckBeatViolation::dispatch($device->id, $signal->latitude, $signal->longitude);
            }

            DeviceLog::in(
                source: DeviceLogSource::Sms,
                summary: 'Inbound SMS parsed',
                payload: [
                    'body' => $sms->raw_message,
                    'parsed_event' => $signalObject->eventType?->value ?? null,
                    'lat' => $signal->latitude,
                    'lng' => $signal->longitude,
                ],
                device: $device,
            );

            $sms->update(['processed_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('IncomingSmsObserver: failed to process SMS', [
                'sms_id' => $sms->id,
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            DeviceLog::in(
                source: DeviceLogSource::Sms,
                summary: 'Inbound SMS parse failed: '.$e->getMessage(),
                payload: ['body' => $sms->raw_message],
                device: $device,
                level: 'error',
            );

            $sms->update([
                'processed_at' => now(),
                'processing_error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveDevice(?string $senderNumber): ?Device
    {
        if (! $senderNumber) {
            return null;
        }

        return Device::with('deviceType')
            ->where('sim_number', $senderNumber)
            ->orWhere('gsm_number', $senderNumber)
            ->first();
    }
}
