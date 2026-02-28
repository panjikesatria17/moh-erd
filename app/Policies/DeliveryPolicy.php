<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Delivery;
use App\Models\User;

class DeliveryPolicy
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

    public function view(User $user, Delivery $delivery): bool
    {
        if ($this->hasRole($user, [UserRole::SUPER_ADMIN, UserRole::OWNER, UserRole::FINANCE, UserRole::PURCHASING, UserRole::ADMIN_GUDANG])) {
            return true;
        }

        if ($this->hasRole($user, [UserRole::SPPG_USER])) {
            return (int) $user->sppg_id === (int) $delivery->sppg_id;
        }

        if ($this->hasRole($user, [UserRole::VENDOR_ADMIN])) {
            return (int) $user->vendor_id === (int) $delivery->vendor_id;
        }

        return false;
    }

    public function generateInvoice(User $user, Delivery $delivery): bool
    {
        return $this->hasRole($user, [
            UserRole::SUPER_ADMIN,
            UserRole::FINANCE,
            UserRole::PURCHASING,
        ]);
    }

    private function hasRole(User $user, array $allowedRoles): bool
    {
        $role = $user->role instanceof UserRole ? $user->role : UserRole::from((string) $user->role);

        return in_array($role, $allowedRoles, true);
    }
}
