<?php

namespace App\Services;

use App\Models\CalibrationCertificate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CalibrationCertificateService
{
    private const MAX_SIZE = 10 * 1024 * 1024;   // 10MB (D-10: larger than photos for PDFs)
    private const ALLOWED_MIMES = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
    private const DISK = 'public';

    /**
     * Upload a certificate file and create the database record (D-09, D-10).
     *
     * @param  UploadedFile  $file
     * @param  string        $calibrationId
     * @return CalibrationCertificate
     *
     * @throws RuntimeException
     */
    public function upload(UploadedFile $file, string $calibrationId): CalibrationCertificate
    {
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $path = "calibrations/certificates/{$filename}";

        $stored = Storage::disk(self::DISK)->put($path, $file->get());

        if (!$stored) {
            throw new RuntimeException('Falha ao armazenar certificado.');
        }

        return CalibrationCertificate::create([
            'calibration_id' => $calibrationId,
            'filename' => $file->getClientOriginalName(),
            'filepath' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ]);
    }

    /**
     * Delete a certificate file and its database record.
     *
     * @param  string  $certificateId
     * @return void
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(string $certificateId): void
    {
        $certificate = CalibrationCertificate::findOrFail($certificateId);

        if (Storage::disk(self::DISK)->exists($certificate->filepath)) {
            Storage::disk(self::DISK)->delete($certificate->filepath);
        }

        $certificate->delete();
    }
}
