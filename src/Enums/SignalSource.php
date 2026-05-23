<?php

namespace TrackAnyDevice\Core\Enums;

enum SignalSource: string
{
    case StreamJt808 = 'stream_jt808';
    case StreamGt06 = 'stream_gt06';
    case StreamH02 = 'stream_h02';
    case StreamSoketi = 'stream_soketi';
    case GsmSms = 'gsm_sms';
    case Manual = 'manual';
    case Api = 'api';

    public function label(): string
    {
        return match ($this) {
            self::StreamJt808 => 'JT808 Stream',
            self::StreamGt06 => 'GT06 Stream',
            self::StreamH02 => 'H02 Stream',
            self::StreamSoketi => 'TAD101 Soketi Stream',
            self::GsmSms => 'GSM SMS',
            self::Manual => 'Manual',
            self::Api => 'API',
        };
    }
}
