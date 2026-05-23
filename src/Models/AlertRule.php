<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\AlertRuleEventType;
use TrackAnyDevice\Core\Enums\IncidentPriority;
use TrackAnyDevice\Core\Database\Factories\AlertRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lives in the central database. Rules with `tenant_id = null` are global
 * defaults visible to all tenants; rules with a `tenant_id` are
 * tenant-specific overrides. No global TenantScope — application code
 * decides whether to merge defaults with tenant overrides.
 */
#[Fillable([
    'tenant_id',
    'name',
    'description',
    'event_type',
    'device_type_slug',
    'scope',
    'priority',
    'is_enabled',
    'condition',
    'notification_channels',
    'escalation_rules',
])]
class AlertRule extends Model
{
    /** @use HasFactory<AlertRuleFactory> */
    use HasFactory, UsesCentralConnection;

    protected function casts(): array
    {
        return [
            'event_type' => AlertRuleEventType::class,
            'priority' => IncidentPriority::class,
            'is_enabled' => 'boolean',
            'condition' => 'array',
            'notification_channels' => 'array',
            'escalation_rules' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }
}
