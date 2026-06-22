<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Observers;

use TrackAnyDevice\Core\Enums\DeviceStatus;
use TrackAnyDevice\Core\Events\DeviceUpdatedEvent;
use TrackAnyDevice\Core\Models\Device;

/**
 * Drives the device lifecycle:
 *
 *  - On create: reconcile status from ownership/SIM state.
 *  - On every update: reconcile status when ownership/SIM toggles, and broadcast on
 *    admin / tenant / user / device channels so the operational map stays in sync.
 */
class DeviceObserver
{
    public function created(Device $device): void
    {
        // Only reconcile the default status — explicit statuses set on creation are preserved.
        if ($device->status === DeviceStatus::Inventory) {
            $this->reconcileStatusIfChanged($device);
        }
    }

    public function updated(Device $device): void
    {
        // Reconcile status only when ownership / SIM number toggles after creation.
        if ($device->wasChanged(['gsm_number', 'tenant_id', 'user_id'])) {
            $this->reconcileStatusIfChanged($device);
        }

        broadcast(new DeviceUpdatedEvent($device))->toOthers();
    }

    private function reconcileStatusIfChanged(Device $device): void
    {
        $reconciled = $device->reconciledStatus();

        if ($device->status !== $reconciled) {
            $device->forceFill(['status' => $reconciled])->saveQuietly();
        }
    }
}
