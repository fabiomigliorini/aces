<?php

namespace App\Policies;

use App\Models\User;
use App\Services\TenantService;
use Illuminate\Auth\Access\HandlesAuthorization;

abstract class BasePolicy
{
    use HandlesAuthorization;

    protected TenantService $tenantService;

    public function __construct()
    {
        $this->tenantService = app(TenantService::class);
    }

    /**
     * Verifica se usuário é admin no tenant atual.
     */
    protected function isAdmin(User $user): bool
    {
        $tenant = $this->tenantService->current();

        if (!$tenant) {
            return false;
        }

        return $user->isAdminInTenant($tenant);
    }

    /**
     * Verifica se usuário tem permissão específica no tenant atual.
     */
    protected function hasPermission(User $user, string $permission): bool
    {
        $tenant = $this->tenantService->current();

        if (!$tenant) {
            return false;
        }

        return $user->hasPermissionInTenant($tenant, $permission);
    }

    /**
     * Verifica se model pertence ao tenant atual.
     */
    protected function belongsToCurrentTenant($model): bool
    {
        $tenantId = $this->tenantService->currentId();

        if (!$tenantId) {
            return false;
        }

        return $model->tenant_id === $tenantId;
    }

    /**
     * Verifica se model pertence à organization do usuário.
     */
    protected function belongsToUserOrganization(User $user, $model): bool
    {
        return $model->organization_id === $user->organization_id;
    }
}