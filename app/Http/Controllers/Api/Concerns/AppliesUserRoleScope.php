<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait AppliesUserRoleScope
{
    protected function currentRoleValue(Request $request): ?string
    {
        return $request->user()?->role?->value ?? $request->user()?->role;
    }

    protected function applySppgScopeForSppgUser(Request $request, Builder $query, string $column = 'sppg_id'): Builder
    {
        if ($this->currentRoleValue($request) !== UserRole::SPPG_USER->value) {
            return $query;
        }

        $sppgId = (int) ($request->user()?->sppg_id ?? 0);

        return $query->where($column, $sppgId > 0 ? $sppgId : -1);
    }

    protected function applyVendorScopeForVendorAdmin(Request $request, Builder $query, string $column = 'vendor_id'): Builder
    {
        if ($this->currentRoleValue($request) !== UserRole::VENDOR_ADMIN->value) {
            return $query;
        }

        $vendorId = (int) ($request->user()?->vendor_id ?? 0);

        return $query->where($column, $vendorId > 0 ? $vendorId : -1);
    }
}
