<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, [
            UserRole::SUPER_ADMIN,
            UserRole::OWNER,
            UserRole::FINANCE,
            UserRole::PURCHASING,
            UserRole::SPPG_USER,
            UserRole::VENDOR_ADMIN,
        ]);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($this->hasRole($user, [UserRole::SUPER_ADMIN, UserRole::OWNER, UserRole::FINANCE, UserRole::PURCHASING])) {
            return true;
        }

        if ($this->hasRole($user, [UserRole::SPPG_USER])) {
            return (int) $user->sppg_id === (int) $invoice->sppg_id;
        }

        if ($this->hasRole($user, [UserRole::VENDOR_ADMIN])) {
            return (int) $user->vendor_id === (int) $invoice->vendor_id;
        }

        return false;
    }

    private function hasRole(User $user, array $allowedRoles): bool
    {
        $role = $user->role instanceof UserRole ? $user->role : UserRole::from((string) $user->role);

        return in_array($role, $allowedRoles, true);
    }
}
