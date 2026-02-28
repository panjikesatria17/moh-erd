<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PurchaseRequest;
use App\Models\User;

class PurchaseRequestPolicy
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

    public function view(User $user, PurchaseRequest $purchaseRequest): bool
    {
        if ($this->hasRole($user, [UserRole::SUPER_ADMIN, UserRole::OWNER, UserRole::PURCHASING, UserRole::FINANCE, UserRole::ADMIN_GUDANG])) {
            return true;
        }

        if ($this->hasRole($user, [UserRole::SPPG_USER])) {
            return (int) $user->sppg_id === (int) $purchaseRequest->sppg_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $this->hasRole($user, [
            UserRole::SUPER_ADMIN,
            UserRole::SPPG_USER,
            UserRole::PURCHASING,
        ]);
    }

    public function approve(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $this->hasRole($user, [
            UserRole::SUPER_ADMIN,
            UserRole::OWNER,
        ]);
    }

    public function generatePo(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $this->hasRole($user, [
            UserRole::SUPER_ADMIN,
            UserRole::PURCHASING,
        ]);
    }

    private function hasRole(User $user, array $allowedRoles): bool
    {
        $role = $user->role instanceof UserRole ? $user->role : UserRole::from((string) $user->role);

        return in_array($role, $allowedRoles, true);
    }
}
