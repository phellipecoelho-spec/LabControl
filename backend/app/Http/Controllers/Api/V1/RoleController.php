<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\UpdatePermissionsRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;

class RoleController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $roles = Role::with('permissions');

        if ($request->boolean('with_users')) {
            $roles->withCount('users');
        }

        return response()->json($roles->orderBy('name')->get());
    }

    public function show(Role $role): JsonResponse
    {
        $role->load('permissions');
        $role->loadCount('users');

        return response()->json($role);
    }

    public function store(UpdateRoleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);

        $role = Role::create($data);
        $role->load('permissions');

        return response()->json($role, Response::HTTP_CREATED);
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        if ($role->slug === 'admin') {
            return response()->json(['message' => 'Perfil Admin não pode ser editado.'], 403);
        }

        $role->update($request->validated());
        $role->load('permissions');

        return response()->json($role);
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->slug === 'admin') {
            return response()->json(['message' => 'Perfil Admin não pode ser excluído.'], 403);
        }

        if ($role->users()->exists()) {
            return response()->json(['message' => 'Perfil possui usuários vinculados.'], 422);
        }

        $role->delete();

        return response()->json(['message' => 'Perfil excluído com sucesso.']);
    }

    public function syncPermissions(UpdatePermissionsRequest $request, Role $role): JsonResponse
    {
        if ($role->slug === 'admin') {
            return response()->json(['message' => 'Permissões do perfil Admin não podem ser alteradas.'], 403);
        }

        $role->permissions()->sync($request->permissions);
        $role->load('permissions');

        return response()->json($role);
    }
}
