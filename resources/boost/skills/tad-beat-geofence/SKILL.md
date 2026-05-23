---
name: tad-beat-geofence
description: Create and manage TAD beat geo-fence zones — covering beat creation, parent/child hierarchy, coordinate formats, beat assignment to devices, violation detection, and the GeoFence service. Use when working with the Beat model, BeatAssignment, CheckBeatViolation job, or GeoFence service.
---

# TAD Beat Geo-fence

## Beat model

A Beat is a named geo-fence zone. Beats can be nested: a beat can have a parent, forming a hierarchy used by the violation detector to determine escalation level.

Key columns:

| Column | Description |
|---|---|
| `name` | Display name |
| `tenant_id` | Owning tenant (auto-set via `BelongsToTenant`) |
| `parent_id` | Parent beat ID — null for root beats |
| `coordinates` | JSON polygon vertices or legacy circle |
| `geo_fence_type` | `polygon` or `circle` |

## Coordinate formats

**Polygon (current standard)** — array of `{lat, lng}` vertices:
```json
[
  {"lat": 31.52, "lng": 74.35},
  {"lat": 31.53, "lng": 74.36},
  {"lat": 31.52, "lng": 74.37},
  {"lat": 31.51, "lng": 74.36}
]
```

**Circle (legacy)** — still supported for backward compatibility:
```json
{"lat": 31.52, "lng": 74.35, "radius": 500}
```

`GeoFence::isInsideBeat()` auto-detects both formats. Migrate legacy circles with:
```bash
php artisan beats:normalize-to-polygon
```

## Creating a beat

```php
use TrackAnyDevice\Core\Models\Beat;

$beat = Beat::create([
    'tenant_id'      => $tenant->id,
    'name'           => 'North Zone',
    'geo_fence_type' => 'polygon',
    'coordinates'    => [
        ['lat' => 31.520, 'lng' => 74.350],
        ['lat' => 31.530, 'lng' => 74.360],
        ['lat' => 31.520, 'lng' => 74.370],
        ['lat' => 31.510, 'lng' => 74.360],
    ],
]);

// Create a child beat inside North Zone
$child = Beat::create([
    'tenant_id'   => $tenant->id,
    'name'        => 'North Zone — Sector A',
    'parent_id'   => $beat->id,
    'geo_fence_type' => 'polygon',
    'coordinates' => [...],
]);
```

Always validate that a child beat fits within its parent before saving:

```php
use TrackAnyDevice\Core\Services\GeoFence;

$geo = app(GeoFence::class);

if (! $geo->childFitsWithinParent($parentBeat, $childCoordinates)) {
    throw new \InvalidArgumentException('Child beat must be contained within its parent.');
}
```

## Generating a circular beat

```php
$geo = app(GeoFence::class);

$vertices = $geo->circleToPolygon(
    centerLat: 31.52,
    centerLng: 74.35,
    radiusMetres: 500,
    points: 64,        // 64 vertices gives a smooth circle
);

$beat = Beat::create([
    'name'           => 'Headquarters',
    'geo_fence_type' => 'polygon',
    'coordinates'    => $vertices,
]);
```

## Assigning a device to a beat

```php
use TrackAnyDevice\Core\Services\BeatAssignmentService;

$service = app(BeatAssignmentService::class);
$assignment = $service->assign($device, $beat, $assignedBy);
```

The active beat assignment drives violation detection. A device can only have one active beat assignment at a time.

## Point-in-polygon checks

```php
$geo = app(GeoFence::class);

// Check using Beat model (auto-detects format)
$inside = $geo->isInsideBeat($beat, $latitude, $longitude);

// Check a raw polygon
$inside = $geo->isInsidePolygon($polygon, $lat, $lng);

// Check a raw circle
$inside = $geo->isInsideCircle(
    ['lat' => 31.52, 'lng' => 74.35, 'radius' => 500],
    $lat,
    $lng,
);

// Distance between two points (metres)
$metres = $geo->haversineMetres($lat1, $lng1, $lat2, $lng2);
```

## Violation detection

`CheckBeatViolation` is dispatched by `SignalCreatedEvent` listeners when a device with an active beat assignment reports a new position.

**Level algorithm:**
- Level 0: inside assigned beat → no violation
- Level 1: outside assigned beat, inside parent
- Level 2: outside parent, inside grandparent
- Level N: outside all N beats in the chain

```php
use TrackAnyDevice\Core\Jobs\CheckBeatViolation;

CheckBeatViolation::dispatch(
    deviceId: $device->id,
    latitude: 31.52,
    longitude: 74.35,
);
```

The job creates a new `Incident` (type `beat_violation`) or updates an existing one, escalating the level and status as the device moves further out. The incident auto-resolves when the device re-enters its assigned beat.

## Beat ancestor chain

```php
// Returns an ordered Collection from parent → root
$ancestors = $beat->ancestors();

// Walk the full chain (assigned beat + ancestors)
$chain = collect([$beat])->merge($beat->ancestors());
```

## See also

- `references/beat-violation-incidents.md` — how violations map to incidents and escalation
