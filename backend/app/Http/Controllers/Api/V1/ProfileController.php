<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\StoreAvatarRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\AvatarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles.permissions');
        $avatarUrl = app(AvatarService::class)->url($user);

        return response()->json([
            'user' => $user,
            'avatar_url' => $avatarUrl,
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $request->user()->update($validated);

        return response()->json([
            'user' => $request->user()->fresh()->load('roles.permissions'),
            'message' => 'Perfil atualizado com sucesso.',
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Senha alterada com sucesso.',
        ]);
    }

    public function updateAvatar(StoreAvatarRequest $request, AvatarService $avatarService): JsonResponse
    {
        $user = $request->user();
        $avatarService->store($user, $request->file('avatar'));

        return response()->json([
            'user' => $user->fresh()->load('roles.permissions'),
            'avatar_url' => $avatarService->url($user),
            'message' => 'Avatar atualizado com sucesso.',
        ]);
    }

    public function deleteAvatar(Request $request, AvatarService $avatarService): JsonResponse
    {
        $user = $request->user();
        $avatarService->deleteExisting($user);
        $user->forceFill(['avatar_path' => null])->save();

        return response()->json([
            'message' => 'Avatar removido com sucesso.',
        ]);
    }
}
