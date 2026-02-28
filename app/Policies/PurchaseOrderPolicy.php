<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, [
            UserRole::SUPER_ADMIN,
            UserRole::OWNER,
            UserRole::FINANCE,
            UserRole::PURCHASING,
            UserRole::ADMIN_GUDANG,
            UserRole::SPPG_USER,
            UserRole::VENDOR_ADMIN,
        ]);
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        if ($this->hasRole($user, [UserRole::SUPER_ADMIN, UserRole::OWNER, UserRole::FINANCE, UserRole::PURCHASING, UserRole::ADMIN_GUDANG])) {
            return true;
        }

        if ($this->hasRole($user, [UserRole::SPPG_USER])) {
            return (int) $user->sppg_id === (int) $purchaseOrder->sppg_id;
        }

        if ($this->hasRole($user, [UserRole::VENDOR_ADMIN])) {
            return (int) $user->vendor_id === (int) $purchaseOrder->vendor_id;
        }

        return false;
    }

    private function hasRole(User $user, array $allowedRoles): bool
    {
        $role = $user->role instanceof UserRole ? $user->role : UserRole::from((string) $user->role);

        return in_array($role, $allowedRoles, true);
    }
}
