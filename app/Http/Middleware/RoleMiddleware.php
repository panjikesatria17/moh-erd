<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return new JsonResponse([
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            abort(401);
        }

        $currentRole = $user->role instanceof UserRole
            ? $user->role->value
            : (string) $user->role;

        if ($currentRole === UserRole::ADMIN->value && $request->routeIs('ui.program-control.*')) {
            if ($request->expectsJson()) {
                return new JsonResponse([
                    'message' => 'You do not have permission to access this resource.',
                ], 403);
            }

            abort(403);
        }

        $effectiveRole = $currentRole === UserRole::ADMIN->value
            ? UserRole::SUPER_ADMIN->value
            : $currentRole;

        if (! in_array($effectiveRole, $allowedRoles, true)) {
            if ($request->expectsJson()) {
                return new JsonResponse([
                    'message' => 'You do not have permission to access this resource.',
                ], 403);
            }

            abort(403);
        }

        return $next($request);
    }
}
