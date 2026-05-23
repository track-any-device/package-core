<?php

namespace TrackAnyDevice\Core\Observers;

use TrackAnyDevice\Core\Enums\DeviceOrderStatus;
use TrackAnyDevice\Core\Models\DeviceOrder;

class DeviceOrderObserver
{
    public function updated(DeviceOrder $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        if ($order->status === DeviceOrderStatus::Confirmed && $order->device_id) {
            $order->device()->update(['tenant_id' => $order->tenant_id]);
        }
    }
}
