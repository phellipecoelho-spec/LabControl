<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalibrationCertificate extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'calibration_id', 'filename', 'filepath', 'mime_type', 'size_bytes',
        'certificate_number', 'issuer', 'issued_at', 'validity_start',
        'validity_end', 'notes',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'issued_at' => 'date',
        'validity_start' => 'date',
        'validity_end' => 'date',
    ];

    public function calibration()
    {
        return $this->belongsTo(Calibration::class);
    }
}
