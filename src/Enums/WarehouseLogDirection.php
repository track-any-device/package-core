<?php

namespace TrackAnyDevice\Core\Enums;

enum WarehouseLogDirection: string
{
    case In = 'in';
    case Out = 'out';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Received',
            self::Out => 'Dispatched',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::In => 'success',
            self::Out => 'warning',
        };
    }
}
