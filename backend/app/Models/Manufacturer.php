<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manufacturer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['name', 'country', 'website', 'logo_path'];

    public function equipments()
    {
        return $this->hasMany(Equipment::class);
    }
}
