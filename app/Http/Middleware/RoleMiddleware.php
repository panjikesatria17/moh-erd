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

        if (
            in_array($currentRole, [UserRole::ADMIN->value, UserRole::OWNER->value], true)
            && $request->routeIs('ui.program-control.*', 'ui.users-roles.*')
        ) {
            if ($request->expectsJson()) {
                return new JsonResponse([
                    'message' => 'You do not have permission to access this resource.',
                ], 403);
            }

            abort(403);
        }

        $isOwnerOrAdmin = in_array($currentRole, [UserRole::ADMIN->value, UserRole::OWNER->value], true);
        $isAllowed = in_array($currentRole, $allowedRoles, true)
            || ($isOwnerOrAdmin && in_array(UserRole::SUPER_ADMIN->value, $allowedRoles, true));

        if (! $isAllowed) {
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
