<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Observers;

use TrackAnyDevice\Core\Enums\DeviceStatus;
use TrackAnyDevice\Core\Enums\OnboardingStatus;
use TrackAnyDevice\Core\Events\DeviceUpdatedEvent;
use TrackAnyDevice\Core\Jobs\OnboardDeviceJob;
use TrackAnyDevice\Core\Models\Device;

/**
 * Drives the device lifecycle:
 *
 *  - On create: reconcile status from ownership/SIM state; if a GSM number
 *    is present, queue the driver onboarding sequence.
 *  - On GSM-number change after the fact: re-trigger onboarding.
 *  - On every update: broadcast on admin / tenant / user / device channels
 *    so the operational map stays in sync without polling.
 */
class DeviceObserver
{
    public function created(Device $device): void
    {
        // Only reconcile the default 'inventory' status — explicit statuses
        // (e.g. when admin moves a device into Maintenance / Lost / Retired
        // on creation) must be preserved.
        if ($device->status === DeviceStatus::Inventory) {
            $this->reconcileStatusIfChanged($device);
        }

        if (! empty($device->gsm_number)) {
            $this->markSimAdded($device);
            OnboardDeviceJob::dispatch($device->id);
        }
    }

    public function updated(Device $device): void
    {
        // Reconcile status only when ownership / SIM number toggles after
        // creation, so explicit statuses set by callers are respected.
        if ($device->wasChanged(['gsm_number', 'tenant_id', 'user_id'])) {
            $this->reconcileStatusIfChanged($device);
        }

        // GSM was just added or swapped to a different number → re-onboard.
        // A previous successful onboarding is invalidated because the new SIM
        // means different APN, different IMSI, possibly different network.
        if ($device->wasChanged('gsm_number') && ! empty($device->gsm_number)) {
            $this->resetOnboardingStatus($device);
            OnboardDeviceJob::dispatch($device->id);
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

    private function markSimAdded(Device $device): void
    {
        // Pending may be either the enum value or null when the model row
        // pre-dates the column. Either way, advance to sim_added.
        if ($device->onboarding_status === null
            || $device->onboarding_status === OnboardingStatus::Pending) {
            $device->forceFill(['onboarding_status' => OnboardingStatus::SimAdded])->saveQuietly();
        }
    }

    /**
     * Roll onboarding back to `sim_added` regardless of where it was. Called
     * when a GSM number is added OR swapped — both cases need the driver's
     * setup sequence to run against the new SIM. Pending devices that never
     * had a SIM also advance via markSimAdded() before this is reached.
     */
    private function resetOnboardingStatus(Device $device): void
    {
        $this->markSimAdded($device);

        if (! in_array($device->onboarding_status, [
            OnboardingStatus::Pending,
            OnboardingStatus::SimAdded,
        ], true)) {
            $device->forceFill(['onboarding_status' => OnboardingStatus::SimAdded])->saveQuietly();
        }
    }
}
