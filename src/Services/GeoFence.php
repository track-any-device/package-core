<?php

namespace TrackAnyDevice\Core\Services;

use TrackAnyDevice\Core\Models\Beat;

class GeoFence
{
    /**
     * Check whether a point (lat, lng) is inside the beat's geo-fence.
     *
     * All beats are now stored as polygon vertex arrays on the database level.
     * Legacy circle format `{lat, lng, radius}` is auto-detected for backward
     * compatibility with existing seeded beats.
     */
    public function isInsideBeat(Beat $beat, float $lat, float $lng): bool
    {
        $coords = $beat->coordinates;

        // Legacy format: {lat, lng, radius} — still used by old seeded beats
        if (isset($coords['radius'])) {
            return $this->isInsideCircle($coords, $lat, $lng);
        }

        // Standard format: [{lat, lng}, ...] — all new beats
        return $this->isInsidePolygon($coords, $lat, $lng);
    }

    /**
     * Approximate a circle as a polygon vertex array.
     *
     * @param  int  $points  Number of vertices (64 gives a smooth circle)
     * @return array<int, array{lat: float, lng: float}>
     */
    public function circleToPolygon(float $centerLat, float $centerLng, float $radiusMetres, int $points = 64): array
    {
        $vertices = [];
        $latPerMetre = 1 / 111_000;
        $lngPerMetre = 1 / (111_000 * cos(deg2rad($centerLat)));

        for ($i = 0; $i < $points; $i++) {
            $angleRad = ($i / $points) * 2 * M_PI;
            $vertices[] = [
                'lat' => round($centerLat + $radiusMetres * cos($angleRad) * $latPerMetre, 7),
                'lng' => round($centerLng + $radiusMetres * sin($angleRad) * $lngPerMetre, 7),
            ];
        }

        return $vertices;
    }

    /**
     * Normalise any coordinate format to a flat polygon vertex array and
     * return the canonical type string ('polygon').
     *
     * Accepts:
     *   • Legacy circle format  : {lat, lng, radius}  → converts to 64-gon
     *   • Vertex array          : [{lat,lng}, ...]    → returned as-is
     *
     * @param  array<mixed>  $coordinates
     * @return array{type: string, coordinates: array<int, array{lat: float, lng: float}>}
     */
    public function normaliseToPolygon(array $coordinates): array
    {
        // Circle format: {lat, lng, radius}
        if (isset($coordinates['lat'], $coordinates['lng'], $coordinates['radius'])) {
            return [
                'type' => 'polygon',
                'coordinates' => $this->circleToPolygon(
                    (float) $coordinates['lat'],
                    (float) $coordinates['lng'],
                    (float) $coordinates['radius'],
                ),
            ];
        }

        // Already a vertex array — just normalise type
        return ['type' => 'polygon', 'coordinates' => $coordinates];
    }

    /**
     * Compute the 6 vertices of a regular hexagon (pointy-top orientation).
     *
     * @return array<int, array{lat: float, lng: float}>
     */
    public function hexagonVertices(float $centerLat, float $centerLng, float $radiusMetres): array
    {
        $vertices = [];
        $latPerMetre = 1 / 111_000;
        $lngPerMetre = 1 / (111_000 * cos(deg2rad($centerLat)));

        for ($i = 0; $i < 6; $i++) {
            // Pointy-top: first vertex at 90° (top), then every 60°
            $angleDeg = 90 + $i * 60;
            $angleRad = deg2rad($angleDeg);
            $vertices[] = [
                'lat' => round($centerLat + $radiusMetres * sin($angleRad) * $latPerMetre, 6),
                'lng' => round($centerLng + $radiusMetres * cos($angleRad) * $lngPerMetre, 6),
            ];
        }

        return $vertices;
    }

    /**
     * Ray-casting point-in-polygon algorithm.
     * Coordinates must be an array of ['lat' => float, 'lng' => float] vertices.
     */
    public function isInsidePolygon(array $polygon, float $lat, float $lng): bool
    {
        $n = count($polygon);

        if ($n < 3) {
            return false;
        }

        $inside = false;
        $j = $n - 1;

        for ($i = 0; $i < $n; $i++) {
            $xi = (float) $polygon[$i]['lng'];
            $yi = (float) $polygon[$i]['lat'];
            $xj = (float) $polygon[$j]['lng'];
            $yj = (float) $polygon[$j]['lat'];

            if ((($yi > $lat) !== ($yj > $lat)) &&
                ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi)) {
                $inside = ! $inside;
            }

            $j = $i;
        }

        return $inside;
    }

    /**
     * Circle check using the Haversine formula.
     * Coordinates must be ['lat' => float, 'lng' => float, 'radius' => float] (radius in metres).
     */
    public function isInsideCircle(array $circle, float $lat, float $lng): bool
    {
        $centerLat = (float) $circle['lat'];
        $centerLng = (float) $circle['lng'];
        $radiusMetres = (float) $circle['radius'];

        $distanceMetres = $this->haversineMetres($centerLat, $centerLng, $lat, $lng);

        return $distanceMetres <= $radiusMetres;
    }

    // ── Child boundary validation ──────────────────────────────────────────────

    /**
     * Validate that proposed child coordinates fit entirely within a parent beat.
     *
     * Rules:
     *   Circle-in-Circle  → dist(centers) + child_radius ≤ parent_radius
     *   Polygon-in-Circle → every vertex inside parent circle
     *   Circle-in-Polygon → center + 8 edge points all inside parent polygon
     *   Polygon-in-Polygon → every vertex inside parent polygon
     */
    /**
     * Validate that proposed child coordinates fit entirely within the parent beat.
     *
     * Both parent and child may be in legacy circle format {lat,lng,radius} or
     * the new vertex-array format [{lat,lng},...]. Auto-detected per coordinates.
     */
    public function childFitsWithinParent(Beat $parent, array $childCoords): bool
    {
        $parentCoords = $parent->coordinates;
        $isParentCircle = isset($parentCoords['radius']);
        $isChildCircle = isset($childCoords['radius']);

        return match (true) {
            $isParentCircle && $isChildCircle => $this->circleInCircle($parentCoords, $childCoords),
            $isParentCircle && ! $isChildCircle => $this->polygonInCircle($parentCoords, $childCoords),
            ! $isParentCircle && $isChildCircle => $this->circleInPolygon($parentCoords, $childCoords),
            default => $this->polygonInPolygon($parentCoords, $childCoords),
        };
    }

    private function circleInCircle(array $parent, array $child): bool
    {
        $dist = $this->haversineMetres(
            (float) $parent['lat'], (float) $parent['lng'],
            (float) $child['lat'], (float) $child['lng'],
        );

        return ($dist + (float) $child['radius']) <= (float) $parent['radius'];
    }

    private function polygonInCircle(array $circle, array $polygon): bool
    {
        foreach ($polygon as $vertex) {
            if (! $this->isInsideCircle($circle, (float) $vertex['lat'], (float) $vertex['lng'])) {
                return false;
            }
        }

        return true;
    }

    private function circleInPolygon(array $polygon, array $circle): bool
    {
        $lat = (float) $circle['lat'];
        $lng = (float) $circle['lng'];
        $r = (float) $circle['radius'];

        // Center must be inside
        if (! $this->isInsidePolygon($polygon, $lat, $lng)) {
            return false;
        }

        // Sample 8 points on the circumference
        for ($i = 0; $i < 8; $i++) {
            $angle = deg2rad($i * 45);
            // Approximate degree offsets: 1° lat ≈ 111 000 m, 1° lng ≈ 111 000 * cos(lat) m
            $dLat = ($r * cos($angle)) / 111000;
            $dLng = ($r * sin($angle)) / (111000 * cos(deg2rad($lat)));

            if (! $this->isInsidePolygon($polygon, $lat + $dLat, $lng + $dLng)) {
                return false;
            }
        }

        return true;
    }

    private function polygonInPolygon(array $parent, array $child): bool
    {
        foreach ($child as $vertex) {
            if (! $this->isInsidePolygon($parent, (float) $vertex['lat'], (float) $vertex['lng'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Distance between two coordinates in metres (Haversine formula).
     */
    public function haversineMetres(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // metres
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earthRadius * asin(sqrt($a));
    }
}
