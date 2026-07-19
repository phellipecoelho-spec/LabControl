<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['name', 'slug', 'group', 'description'];
    public $timestamps = true;

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}