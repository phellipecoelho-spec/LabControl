<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipment extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    protected $table = 'equipments';

    protected $fillable = [
        'name', 'patrimony_id', 'serial_number', 'category_id', 'manufacturer_id',
        'supplier_id', 'location', 'acquisition_date', 'warranty_end', 'status',
        'description', 'technical_specs', 'notes', 'user_id',
        'verification_frequency',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'warranty_end' => 'date',
        'verification_frequency' => 'string',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by')->withDefault();
    }

    public function photos()
    {
        return $this->hasMany(EquipmentPhoto::class)->orderBy('sort_order');
    }

    public function verifications()
    {
        return $this->hasMany(Verification::class);
    }

    public function lastVerification()
    {
        return $this->hasOne(Verification::class)->latest('verified_at');
    }

    public function maintenanceOrders()
    {
        return $this->hasMany(MaintenanceOrder::class);
    }

    public function lastMaintenance()
    {
        return $this->hasOne(MaintenanceOrder::class)->latestOfMany('completed_at');
    }

    public function loans()
    {
        return $this->belongsToMany(Loan::class, 'equipment_loan')
            ->withPivot('returned_at', 'notes')
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    public function scopeByCategory(Builder $query, string $categoryId): void
    {
        $query->where('category_id', $categoryId);
    }
}
