<?php

namespace TrackAnyDevice\Core\Enums;

enum SensorDataType: string
{
    case Float = 'float';
    case Integer = 'integer';
    case Boolean = 'boolean';
    case Coordinate = 'coordinate';
    case String = 'string';

    public function label(): string
    {
        return match ($this) {
            self::Float => 'Float',
            self::Integer => 'Integer',
            self::Boolean => 'Boolean',
            self::Coordinate => 'Coordinate',
            self::String => 'String',
        };
    }
}
