<?php

namespace TrackAnyDevice\Core\Enums;

/**
 * Protocol source for a device log entry.
 *
 * Add new sources here when a new driver/connector lands so the
 * runtime log viewer's filter automatically picks them up.
 */
enum DeviceLogSource: string
{
    case Sms = 'SMS';
    case Tad101 = 'TAD101';
    case Jt808 = 'JT808';

    public function label(): string
    {
        return $this->value;
    }
}
