<?php

namespace App\Traits;

use App\Models\Tenant;
use App\Scopes\TenantScope;
use App\Services\TenantService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait para models tenant-level com suporte a multi-tenant queries.
 * 
 * Por padrão aplica TenantScope (single tenant).
 * Usar forTenants() para queries multi-tenant.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            // Auto-fill tenant_id from current tenant
            if (empty($model->tenant_id)) {
                $model->tenant_id = app(TenantService::class)->currentId();
            }

            // Auto-fill organization_id from the tenant
            if (empty($model->organization_id) && $model->tenant_id) {
                $tenant = Tenant::find($model->tenant_id);
                if ($tenant) {
                    $model->organization_id = $tenant->organization_id;
                }
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Remove TenantScope para query customizada.
     */
    public function scopeWithoutTenantScope($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /**
     * Query para múltiplos tenants autorizados.
     * Valida que o usuário tem acesso a todos os tenants solicitados.
     *
     * @param array|null $tenantIds - IDs dos tenants. Se null, usa todos do usuário.
     */
    public function scopeForTenants($query, ?array $tenantIds = null)
    {
        $query->withoutGlobalScope(TenantScope::class);

        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw("1 = 0"); // Retorna vazio
        }

        // Tenants que o usuário tem acesso
        $allowedTenantIds = $user->tenants()->pluck("tenants.id")->toArray();

        if ($tenantIds === null) {
            // Todos os tenants do usuário
            $tenantIds = $allowedTenantIds;
        } else {
            // Filtra apenas os que o usuário tem acesso
            $tenantIds = array_intersect($tenantIds, $allowedTenantIds);
        }

        // Filtra pelos tenants autorizados
        return $query->whereIn($this->getTable() . ".tenant_id", $tenantIds);
    }
}