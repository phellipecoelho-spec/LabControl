<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Services\EquipmentPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EquipmentPhotoController extends Controller
{
    public static function middleware(): array
    {
        return [
            ['middleware' => 'auth:sanctum'],
            ['middleware' => 'permission:equipamentos.edit'],
        ];
    }

    public function index(Equipment $equipment): JsonResponse
    {
        $photos = $equipment->photos()->orderBy('sort_order')->get();

        return response()->json($photos);
    }

    public function store(Request $request, Equipment $equipment, EquipmentPhotoService $photoService): JsonResponse
    {
        $validated = $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $photo = $photoService->upload($validated['photo'], $equipment->id);

        return response()->json($photo, 201);
    }

    public function destroy(Equipment $equipment, string $photo, EquipmentPhotoService $photoService): JsonResponse
    {
        $photoService->delete($photo);

        return response()->json(null, 204);
    }

    public function reorder(Request $request, Equipment $equipment, EquipmentPhotoService $photoService): JsonResponse
    {
        $validated = $request->validate([
            'photo_ids' => 'required|array',
            'photo_ids.*' => 'string|exists:equipment_photos,id',
        ]);

        $photoService->reorder($validated['photo_ids']);

        $photos = $equipment->photos()->orderBy('sort_order')->get();

        return response()->json($photos);
    }
}
