<?php

namespace TrackAnyDevice\Core\Enums;

enum CableProtocol: string
{
    case Uart = 'uart';
    case Usb = 'usb';
    case Jtag = 'jtag';
    case Swd = 'swd';

    public function label(): string
    {
        return match ($this) {
            self::Uart => 'UART',
            self::Usb => 'USB',
            self::Jtag => 'JTAG',
            self::Swd => 'SWD',
        };
    }
}
