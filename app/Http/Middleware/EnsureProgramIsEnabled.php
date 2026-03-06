<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\AppSetting;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProgramIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $state = $this->getProgramState();
        if ($state['enabled']) {
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

        if ($state['lock_mode'] === 'read_only' && $this->isReadMethod($request)) {
            return $next($request);
        }

        $message = $state['reason'] === 'license_expired'
            ? 'Program tidak aktif karena masa lisensi sudah berakhir.'
            : 'Program sedang dinonaktifkan oleh super admin.';

        if ($state['lock_mode'] === 'read_only') {
            $message .= ' Sistem saat ini hanya dapat diakses dalam mode baca.';
        }

        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => $message,
            ], 423);
        }

        return response()->view('errors.423', [
            'message' => $message.' Hubungi super admin untuk aktivasi penuh.',
            'readOnlyMode' => $state['lock_mode'] === 'read_only',
        ], 423);
    }

    private function getProgramState(): array
    {
        $settings = AppSetting::query()
            ->whereIn('key', [
                'program_enabled',
                'program_lock_mode',
                'program_license_expires_at',
                'program_license_grace_days',
            ])
            ->pluck('value', 'key');

        $enabledRaw = (string) ($settings['program_enabled'] ?? '1');
        $enabled = filter_var($enabledRaw, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true;

        $lockMode = (string) ($settings['program_lock_mode'] ?? 'hard_lock');
        if (! in_array($lockMode, ['hard_lock', 'read_only'], true)) {
            $lockMode = 'hard_lock';
        }

        $licenseExpiresAtRaw = $settings['program_license_expires_at'] ?? null;
        $graceDaysRaw = $settings['program_license_grace_days'] ?? null;
        $graceDays = is_numeric($graceDaysRaw) ? max((int) $graceDaysRaw, 0) : 0;

        $licenseExpired = false;
        if ($licenseExpiresAtRaw !== null && $licenseExpiresAtRaw !== '') {
            try {
                $expiresAt = Carbon::parse((string) $licenseExpiresAtRaw);
                $effectiveDeadline = $expiresAt->copy()->addDays($graceDays)->endOfDay();
                $licenseExpired = now()->greaterThan($effectiveDeadline);
            } catch (\Throwable) {
                $licenseExpired = false;
            }
        }

        if ($licenseExpired) {
            return [
                'enabled' => false,
                'lock_mode' => $lockMode,
                'reason' => 'license_expired',
            ];
        }

        return [
            'enabled' => $enabled,
            'lock_mode' => $lockMode,
            'reason' => $enabled ? 'enabled' : 'manual_disabled',
        ];
    }

    private function isReadMethod(Request $request): bool
    {
        return in_array(strtoupper($request->method()), ['GET', 'HEAD', 'OPTIONS'], true);
    }
}
