<?php

namespace TrackAnyDevice\Core\Enums;

enum GeoFenceType: string
{
    case Hexagon = 'hexagon';
    case Circle = 'circle';
    case Polygon = 'polygon'; // free-form polygon (used internally by editor)

    public function label(): string
    {
        return match ($this) {
            GeoFenceType::Hexagon => 'Hexagon',
            GeoFenceType::Circle => 'Circle',
            GeoFenceType::Polygon => 'Polygon',
        };
    }

    /** Whether coordinates are stored as an array of {lat,lng} vertices. */
    public function isVertexBased(): bool
    {
        return $this === GeoFenceType::Hexagon || $this === GeoFenceType::Polygon;
    }
}
