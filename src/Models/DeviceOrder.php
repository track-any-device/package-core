<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\DeviceOrderStatus;
use TrackAnyDevice\Core\Enums\PaymentMethod;
use TrackAnyDevice\Core\Database\Factories\DeviceOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'tenant_id', 'user_id', 'device_id', 'device_type_id', 'product_id',
    'quantity', 'status', 'claim_code',
    'shipping_name', 'shipping_phone', 'shipping_address', 'billing_address',
    'payment_method', 'total_amount', 'currency',
    'notes', 'admin_notes', 'confirmed_by', 'confirmed_at', 'delivered_at',
])]
class DeviceOrder extends Model
{
    /** @use HasFactory<DeviceOrderFactory> */
    use HasFactory, UsesCentralConnection;

    protected function casts(): array
    {
        return [
            'status' => DeviceOrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'total_amount' => 'decimal:2',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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

    public function isDelivered(): bool
    {
        return $this->status === DeviceOrderStatus::Delivered;
    }

    public function isCancelled(): bool
    {
        return $this->status === DeviceOrderStatus::Cancelled;
    }

    public static function generateClaimCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::where('claim_code', $code)->exists());

        return $code;
    }

    public function referenceNumber(): string
    {
        return 'ORD-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
