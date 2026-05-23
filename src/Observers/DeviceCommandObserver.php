<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Observers;

use TrackAnyDevice\Drivers\Connectors\SMSConnector;
use TrackAnyDevice\Core\Enums\DeviceLogSource;
use TrackAnyDevice\Core\Models\DeviceCommand;
use TrackAnyDevice\Core\Providers\DeviceServiceProvider;
use TrackAnyDevice\Core\Services\DeviceLog;
use Illuminate\Support\Facades\Log;

class DeviceCommandObserver
{
    public function __construct(private readonly SMSConnector $connector) {}

    public function created(DeviceCommand $command): void
    {
        $command->load('device.deviceType');
        $device = $command->device;

        if ($device === null) {
            return;
        }

        // Stream-channel commands (JT808 etc.) are dispatched by the driver
        // directly to Redis pub/sub — the DB row exists only for audit.
        if ($command->channel !== 'sms') {
            return;
        }

        try {
            $driver = DeviceServiceProvider::driverFor($device->deviceType->slug);

            $decoded = json_decode($command->command_payload ?? '{}', true);
            $params = is_array($decoded) ? $decoded : [];

            if (! isset($params['password']) && ! empty($device->password)) {
                $params['password'] = $device->password;
            }

            if (! method_exists($driver, 'buildSmsBody')) {
                Log::warning('DeviceCommandObserver: driver lacks buildSmsBody', [
                    'driver' => $driver::class,
                    'command_id' => $command->id,
                ]);

                return;
            }

            $message = $driver->buildSmsBody($command->command_type, $params);

            if ($message === null) {
                Log::warning('DeviceCommandObserver: driver returned no SMS body', [
                    'driver' => $driver::class,
                    'command_type' => $command->command_type,
                    'command_id' => $command->id,
                ]);

                return;
            }

            $this->connector->send($device, $command, $message);

            DeviceLog::out(
                source: DeviceLogSource::Sms,
                summary: $command->command_type,
                payload: [
                    'command_id' => $command->id,
                    'command_type' => $command->command_type,
                    'sms_body' => $message,
                    'channel' => 'sms',
                ],
                device: $device,
            );
        } catch (\Throwable $e) {
            Log::error('DeviceCommandObserver: failed to dispatch command', [
                'command_id' => $command->id,
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            DeviceLog::out(
                source: DeviceLogSource::Sms,
                summary: 'Failed to dispatch '.$command->command_type.': '.$e->getMessage(),
                payload: ['command_id' => $command->id, 'command_type' => $command->command_type],
                device: $device,
                level: 'error',
            );
        }
    }
}
