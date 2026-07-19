<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ActivityLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'action', 'module', 'table_name', 'record_id',
        'old_values', 'new_values', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeModule(Builder $query, ?string $module): Builder
    {
        return $module ? $query->where('module', $module) : $query;
    }

    public function scopeAction(Builder $query, ?string $action): Builder
    {
        return $action ? $query->where('action', $action) : $query;
    }

    public function scopeByUser(Builder $query, ?string $userId): Builder
    {
        return $userId ? $query->where('user_id', $userId) : $query;
    }

    public function scopeDateRange(Builder $query, ?string $start, ?string $end): Builder
    {
        if ($start) {
            $query->where('created_at', '>=', Carbon::parse($start));
        }

        if ($end) {
            $query->where('created_at', '<=', Carbon::parse($end)->endOfDay());
        }

        return $query;
    }
}
