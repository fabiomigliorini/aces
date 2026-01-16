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
            if (empty($model->organization_id) && auth()->check()) {
                $model->organization_id = auth()->user()->organization_id;
            }
            
            if (empty($model->tenant_id)) {
                $model->tenant_id = app(TenantService::class)->currentId();
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

        // Sempre filtra pela organization do usuário (segurança extra)
        return $query
            ->where($this->getTable() . ".organization_id", $user->organization_id)
            ->whereIn($this->getTable() . ".tenant_id", $tenantIds);
    }
}