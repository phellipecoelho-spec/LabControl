<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentPhoto extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['equipment_id', 'path', 'original_name', 'size', 'mime_type', 'sort_order'];

    protected $casts = [
        'size' => 'integer',
        'sort_order' => 'integer',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
