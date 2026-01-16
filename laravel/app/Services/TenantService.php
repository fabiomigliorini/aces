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

    /**
     * Resolve tenant a partir do request.
     * Fontes (em ordem de prioridade):
     * 1. Header X-Tenant-Id
     * 2. Subdomínio (se configurado)
     * 3. Tenant default do usuário
     */
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

    /**
     * Retorna IDs dos tenants que o usuário pode acessar.
     */
    public function allowedTenantIds(User $user): array
    {
        return $user->tenants()
            ->where("is_active", true)
            ->pluck("tenants.id")
            ->toArray();
    }

    /**
     * Valida se usuário tem acesso a um conjunto de tenants.
     * Retorna apenas os IDs válidos.
     */
    public function validateTenantIds(User $user, array $tenantIds): array
    {
        $allowed = $this->allowedTenantIds($user);
        return array_values(array_intersect($tenantIds, $allowed));
    }

    /**
     * Verifica se usuário pode acessar um tenant específico.
     */
    public function userCanAccessTenant(User $user, int $tenantId): bool
    {
        return $user->tenants()
            ->where("tenants.id", $tenantId)
            ->where("tenants.is_active", true)
            ->exists();
    }
}