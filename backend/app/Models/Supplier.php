<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['name', 'cnpj', 'contact_name', 'contact_email', 'contact_phone', 'address'];

    protected $casts = ['cnpj' => 'string'];

    public function equipments()
    {
        return $this->hasMany(Equipment::class);
    }
}
