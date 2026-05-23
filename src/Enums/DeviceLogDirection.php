<?php

namespace TrackAnyDevice\Core\Enums;

enum DeviceLogDirection: string
{
    case In = 'in';
    case Out = 'out';

    public function label(): string
    {
        return strtoupper($this->value);
    }
}
