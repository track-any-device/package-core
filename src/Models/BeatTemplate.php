<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\GeoFenceType;
use TrackAnyDevice\Core\Database\Factories\BeatTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Curated polygon shape an admin captured from a real beat.
 *
 * Templates are global (no tenant_id) so the same school-zone or
 * district-boundary shape can serve every tenant and every central
 * user. When the admin updates a template, every beat linked via
 * beat_template_id is re-synced to the new coordinates.
 */
#[Fillable([
    'name',
    'description',
    'geo_fence_type',
    'coordinates',
    'created_by',
    'source_beat_id',
    'is_active',
    'version',
])]
class BeatTemplate extends Model
{
    /** @use HasFactory<BeatTemplateFactory> */
    use HasFactory, UsesCentralConnection;

    protected $attributes = [
        'is_active' => true,
        'version' => 1,
    ];

    protected function casts(): array
    {
        return [
            'geo_fence_type' => GeoFenceType::class,
            'coordinates' => 'array',
            'is_active' => 'boolean',
            'version' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sourceBeat(): BelongsTo
    {
        return $this->belongsTo(Beat::class, 'source_beat_id');
    }

    /**
     * Beats currently using this template. Updating the template's
     * coordinates propagates to every row here via syncBeats().
     */
    public function beats(): HasMany
    {
        return $this->hasMany(Beat::class, 'beat_template_id');
    }

    /**
     * Push this template's current coordinates + geo_fence_type to
     * every beat that references it. Returns the number of rows
     * updated.
     *
     * Bypasses Beat's global scopes (TenantScope) so a single admin
     * action covers beats across every tenant and personal-user beat
     * uniformly.
     */
    public function syncBeats(): int
    {
        return Beat::query()
            ->withoutGlobalScopes()
            ->where('beat_template_id', $this->id)
            ->update([
                'coordinates' => json_encode($this->coordinates),
                'geo_fence_type' => $this->geo_fence_type->value,
            ]);
    }
}
