<?php

namespace TrackAnyDevice\Core\Enums;

enum PaymentMethod: string
{
    case CashOnDelivery = 'cod';

    public function label(): string
    {
        return match ($this) {
            self::CashOnDelivery => 'Cash on Delivery',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CashOnDelivery => 'warning',
        };
    }
}
