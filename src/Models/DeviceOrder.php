<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\DeviceOrderStatus;
use TrackAnyDevice\Core\Database\Factories\DeviceOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'user_id', 'device_id', 'device_type_id', 'quantity', 'status', 'notes', 'admin_notes', 'confirmed_by', 'confirmed_at', 'delivered_at'])]
class DeviceOrder extends Model
{
    /** @use HasFactory<DeviceOrderFactory> */
    use HasFactory, UsesCentralConnection;

    protected function casts(): array
    {
        return [
            'status' => DeviceOrderStatus::class,
            'confirmed_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function isPending(): bool
    {
        return $this->status === DeviceOrderStatus::Pending;
    }

    public function isConfirmed(): bool
    {
        return $this->status === DeviceOrderStatus::Confirmed;
    }
}
