<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;

class TenantService
{
    private ?Tenant $current = null;

    public function setCurrent(?Tenant $tenant): void
    {
        $this->current = $tenant;
    }

    public function current(): ?Tenant
    {
        return $this->current;
    }

    public function currentId(): ?int
    {
        return $this->current?->id;
    }

    public function check(): bool
    {
        return $this->current !== null;
    }

    public function resolveFromRequest(?int $tenantId, User $user): ?Tenant
    {
        if (!$tenantId) {
            $default = $user->defaultTenant;
            return $default ? Tenant::find($default->tenant_id) : null;
        }

        return $user->tenants()
            ->where("tenants.id", $tenantId)
            ->where("tenants.is_active", true)
            ->first();
    }

    public function allowedTenantIds(User $user): array
    {
        return $user->tenants()
            ->where("is_active", true)
            ->pluck("tenants.id")
            ->toArray();
    }

    public function validateTenantIds(User $user, array $tenantIds): array
    {
        $allowed = $this->allowedTenantIds($user);
        return array_values(array_intersect($tenantIds, $allowed));
    }

    public function userCanAccessTenant(User $user, int $tenantId): bool
    {
        return $user->tenants()
            ->where("tenants.id", $tenantId)
            ->where("tenants.is_active", true)
            ->exists();
    }

    /**
     * Retorna todos os tenants que o usuário tem acesso.
     */
    public function availableForUser(User $user): Collection
    {
        return $user->tenants()
            ->where("is_active", true)
            ->get();
    }
}