<?php

namespace TrackAnyDevice\Core\Workflows\Actions;

use TrackAnyDevice\Core\Enums\DeviceCommandStatus;
use TrackAnyDevice\Core\Models\Device;
use TrackAnyDevice\Core\Models\DeviceCommand;
use TrackAnyDevice\Core\Workflows\Contracts\WorkflowAction;
use Illuminate\Support\Facades\Log;

/**
 * Queue a driver command on the workflow's context device.
 *
 * The target device is taken from $context['device']['id']; the command
 * type + parameters from $config. The command lands in device_commands
 * with status=queued so the outbound dispatcher (SMSConnector etc.)
 * picks it up on its next tick.
 *
 * No DriverInterface contract is invoked here directly — the existing
 * outbound pipeline handles the actual SMS/socket dispatch. This step
 * just records the intent.
 */
class SendDeviceCommandAction implements WorkflowAction
{
    public function execute(array $config, array $context): array
    {
        $deviceId = $context['device']['id'] ?? null;
        if (! $deviceId) {
            return ['status' => 'failed', 'error' => 'No device in context'];
        }

        $device = Device::find($deviceId);
        if (! $device) {
            return ['status' => 'failed', 'error' => "Device {$deviceId} not found"];
        }

        $commandType = trim((string) ($config['command'] ?? ''));
        if ($commandType === '') {
            return ['status' => 'failed', 'error' => 'Empty command type'];
        }

        try {
            $command = DeviceCommand::create([
                'device_id' => $device->id,
                'command_type' => $commandType,
                'command_payload' => json_encode($config['parameters'] ?? []),
                'channel' => 'sms',
                'status' => DeviceCommandStatus::Queued,
                'requested_by' => $context['user']['id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Workflow send_command failed', [
                'device_id' => $device->id,
                'command' => $commandType,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'failed', 'error' => $e->getMessage()];
        }

        return [
            'status' => 'completed',
            'output' => [
                'device_command_id' => $command->id,
                'command_type' => $commandType,
            ],
        ];
    }
}
