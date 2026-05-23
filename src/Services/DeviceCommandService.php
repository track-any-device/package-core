<?php

namespace TrackAnyDevice\Core\Services;

use TrackAnyDevice\Core\Enums\DeviceCommandStatus;
use TrackAnyDevice\Core\Jobs\QueueDeviceCommand;
use TrackAnyDevice\Core\Models\Device;
use TrackAnyDevice\Core\Models\DeviceCommand;
use TrackAnyDevice\Core\Models\User;

class DeviceCommandService
{
    /**
     * Create a command record and dispatch it to the queue.
     *
     * Phase 1: records the command with Pending status and dispatches
     * QueueDeviceCommand which advances it to Queued. Phase 2 will add
     * real SMS dispatch inside the job.
     */
    public function dispatch(
        Device $device,
        string $commandType,
        string $commandPayload,
        User $requestedBy,
        string $channel = 'sms',
    ): DeviceCommand {
        $command = DeviceCommand::create([
            'device_id' => $device->id,
            'command_type' => $commandType,
            'command_payload' => $commandPayload,
            'channel' => $channel,
            'status' => DeviceCommandStatus::Pending,
            'requested_by' => $requestedBy->id,
        ]);

        QueueDeviceCommand::dispatch($command->id);

        return $command;
    }

    /**
     * Cancel a pending or queued command.
     */
    public function cancel(DeviceCommand $command): void
    {
        if ($command->status->isTerminal()) {
            return;
        }

        $command->update(['status' => DeviceCommandStatus::Cancelled]);
    }
}
