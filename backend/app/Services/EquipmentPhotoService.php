<?php

namespace App\Services;

use App\Models\EquipmentPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class EquipmentPhotoService
{
    private const MAX_SIZE = 5 * 1024 * 1024;
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    private const DISK = 'public';

    public function upload(UploadedFile $file, string $equipmentId): EquipmentPhoto
    {
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $path = "equipment/{$equipmentId}/photos/{$filename}";

        $stored = Storage::disk(self::DISK)->put($path, $file->get());

        if (!$stored) {
            throw new RuntimeException('Falha ao armazenar foto.');
        }

        $lastOrder = EquipmentPhoto::where('equipment_id', $equipmentId)
            ->max('sort_order') ?? 0;

        return EquipmentPhoto::create([
            'equipment_id' => $equipmentId,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'sort_order' => $lastOrder + 1,
        ]);
    }

    public function delete(string $photoId): void
    {
        $photo = EquipmentPhoto::findOrFail($photoId);

        if (Storage::disk(self::DISK)->exists($photo->path)) {
            Storage::disk(self::DISK)->delete($photo->path);
        }

        $photo->delete();
    }

    public function reorder(array $photoIds): void
    {
        foreach ($photoIds as $index => $id) {
            EquipmentPhoto::where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }
}
