<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Calibration;
use App\Models\CalibrationCertificate;
use App\Services\CalibrationCertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;

class CalibrationCertificateController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be applied to the controller.
     *
     * @return array<int, \Illuminate\Routing\Controllers\Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
            new Middleware('permission:calibracoes.edit'),
        ];
    }

    /**
     * List all certificates for a calibration.
     */
    public function index(Calibration $calibration): JsonResponse
    {
        $certificates = $calibration->certificates()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($certificates);
    }

    /**
     * Upload a certificate for a calibration.
     */
    public function store(Request $request, Calibration $calibration): JsonResponse
    {
        $validated = $request->validate([
            'certificate' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);

        $certificate = app(CalibrationCertificateService::class)->upload($validated['certificate'], $calibration->id);

        return response()->json($certificate, 201);
    }

    /**
     * Download a certificate file.
     */
    public function download(Calibration $calibration, string $certificate): \Illuminate\Http\StreamedResponse
    {
        $cert = CalibrationCertificate::findOrFail($certificate);

        return Storage::disk('public')->download($cert->filepath, $cert->filename);
    }

    /**
     * Delete a certificate.
     */
    public function destroy(Calibration $calibration, string $certificate): JsonResponse
    {
        app(CalibrationCertificateService::class)->delete($certificate);

        return response()->json(null, 204);
    }
}
