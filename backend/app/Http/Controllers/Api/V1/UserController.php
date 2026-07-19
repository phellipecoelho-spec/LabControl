<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;

class UserController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
            new Middleware('permission:usuarios.view', only: ['index', 'show']),
            new Middleware('permission:usuarios.create', only: ['store']),
            new Middleware('permission:usuarios.edit', only: ['update']),
            new Middleware('permission:usuarios.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $query = User::with('roles');

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($role = $request->role) {
            $query->whereHas('roles', fn($q) => $q->where('slug', $role));
        }

        if ($status = $request->status) {
            $query->where('is_active', $status === 'active');
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        $user->load('roles.permissions');

        return response()->json($user);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        if ($request->filled('roles')) {
            $user->roles()->attach($request->roles);
        }

        $user->load('roles');

        return response()->json($user, Response::HTTP_CREATED);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        if ($request->filled('password')) {
            $data['password'] = Hash::make($data['password']);
        }

        $data['updated_by'] = $request->user()->id;

        $user->update($data);

        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        }

        $user->load('roles');

        return response()->json($user);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(['message' => 'Usuário excluído com sucesso.']);
    }
}
