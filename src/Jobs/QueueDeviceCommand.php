<?php

namespace TrackAnyDevice\Core\Jobs;

use TrackAnyDevice\Core\Enums\DeviceCommandStatus;
use TrackAnyDevice\Core\Models\DeviceCommand;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Phase 1 stub — logs the command as Queued. Phase 2 will implement actual
 * SMS dispatch via the app SMS pipeline and mark the command Sent/Delivered.
 */
class QueueDeviceCommand implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $commandId) {}

    public function handle(): void
    {
        $command = DeviceCommand::find($this->commandId);

        if ($command === null || $command->status->isTerminal()) {
            return;
        }

        // Phase 1: mark as queued for audit trail; actual SMS dispatch in Phase 2
        $command->update(['status' => DeviceCommandStatus::Queued]);
    }
}
