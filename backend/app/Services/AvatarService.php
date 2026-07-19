<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AvatarService
{
    private const SIZE = 256;
    private const DISK = 'public';
    private const DIRECTORY = 'avatars';

    public function store(User $user, UploadedFile $file): string
    {
        $this->deleteExisting($user);

        $path = Image::fromUpload($file)
            ->cover(self::SIZE, self::SIZE)
            ->toWebp()
            ->quality(80)
            ->storePublicly(path: self::DIRECTORY, disk: self::DISK);

        if (!$path) {
            throw new RuntimeException('Falha ao armazenar avatar.');
        }

        $user->forceFill(['avatar_path' => $path])->save();

        return $path;
    }

    public function deleteExisting(User $user): void
    {
        if ($user->avatar_path !== null && Storage::disk(self::DISK)->exists($user->avatar_path)) {
            Storage::disk(self::DISK)->delete($user->avatar_path);
        }
    }

    public function url(User $user): ?string
    {
        return $user->avatar_path
            ? Storage::disk(self::DISK)->url($user->avatar_path)
            : null;
    }

    public function deleteByPath(string $path): void
    {
        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
