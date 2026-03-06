<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\AppSetting;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProgramIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $isProgramEnabled = $this->isProgramEnabled();
        if ($isProgramEnabled) {
            return $next($request);
        }

        $user = $request->user();
        $currentRole = $user?->role instanceof UserRole
            ? $user->role->value
            : (string) ($user?->role ?? '');

        // Super admin can still access system control to re-enable the program.
        if ($currentRole === UserRole::SUPER_ADMIN->value) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => 'Program sedang dinonaktifkan oleh super admin.',
            ], 423);
        }

        return response()->view('errors.423', [
            'message' => 'Program sedang dinonaktifkan oleh super admin. Hubungi super admin untuk mengaktifkan kembali.',
        ], 423);
    }

    private function isProgramEnabled(): bool
    {
        $storedValue = AppSetting::query()
            ->where('key', 'program_enabled')
            ->value('value');

        if ($storedValue === null || $storedValue === '') {
            return true;
        }

        return filter_var($storedValue, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true;
    }
}
