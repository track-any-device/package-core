<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\StaffDepartment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffDepartmentUser extends Model
{
    use UsesCentralConnection;

    protected $table = 'staff_department_user';

    protected $fillable = [
        'user_id',
        'department',
        'warehouse_id',
        'is_workshop',
    ];

    protected function casts(): array
    {
        return [
            'department' => StaffDepartment::class,
            'is_workshop' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
