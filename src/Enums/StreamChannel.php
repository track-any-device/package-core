<?php

namespace TrackAnyDevice\Core\Enums;

enum StreamChannel: string
{
    case Jt808 = 'jt808';
    case Gt06 = 'gt06';
    case H02 = 'h02';
    case Gps103 = 'gps103';
    case Soketi = 'soketi';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Jt808 => 'JT/T 808',
            self::Gt06 => 'GT06',
            self::H02 => 'H02',
            self::Gps103 => 'GPS103',
            self::Soketi => 'TAD101 (Soketi)',
            self::None => 'None',
        };
    }
}
