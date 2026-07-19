<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user->roles->contains('slug', 'admin')) {
            return $next($request);
        }

        if (!$user->hasPermission($permission)) {
            throw new AuthorizationException('Ação não autorizada.');
        }

        return $next($request);
    }
}
