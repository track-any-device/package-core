<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\BelongsToTenant;
use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\AlertRuleEventType;
use TrackAnyDevice\Core\Enums\IncidentPriority;
use TrackAnyDevice\Core\Enums\IncidentStatus;
use TrackAnyDevice\Core\Database\Factories\IncidentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lives in the central database. Each incident is owned by exactly one
 * tenant via `tenant_id`. The BelongsToTenant trait applies a global scope
 * that filters by the active tenant on tenant subdomains and auto-fills
 * `tenant_id` on create when a tenant context is initialized.
 *
 * Jobs that create incidents outside any HTTP request (queued workers) must
 * pass `tenant_id` explicitly — usually derived from the device.
 */
#[Fillable([
    'tenant_id',
    'alert_rule_id',
    'device_id',
    'assignee_id',
    'beat_id',
    'event_type',
    'priority',
    'level',
    'status',
    'latitude',
    'longitude',
    'triggered_at',
    'acknowledged_by',
    'acknowledged_at',
    'resolved_by',
    'resolved_at',
    'resolution_notes',
    'payload',
    'reopen_count',
    'reopen_history',
])]
class Incident extends Model
{
    /** @use HasFactory<IncidentFactory> */
    use BelongsToTenant, HasFactory, UsesCentralConnection;

    protected function casts(): array
    {
        return [
            'event_type' => AlertRuleEventType::class,
            'priority' => IncidentPriority::class,
            'status' => IncidentStatus::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'triggered_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
            'payload' => 'array',
            'reopen_history' => 'array',
            'reopen_count' => 'integer',
            'level' => 'integer',
        ];
    }

    public function alertRule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Assignee::class);
    }

    public function beat(): BelongsTo
    {
        return $this->belongsTo(Beat::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isCritical(): bool
    {
        return $this->priority === IncidentPriority::Critical;
    }

    public function isOpen(): bool
    {
        return $this->status === IncidentStatus::Open;
    }

    public function isSos(): bool
    {
        return $this->event_type === AlertRuleEventType::Sos;
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }
}
