<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\BelongsToTenant;
use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssigneeType extends Model
{
    use BelongsToTenant, HasFactory, UsesCentralConnection;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'icon_path',
        'icon_color',
        'description',
        'is_active',
        'sort_order',
        'fields_schema',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'fields_schema' => 'array',
    ];

    public function assignees(): HasMany
    {
        return $this->hasMany(Assignee::class);
    }
}
