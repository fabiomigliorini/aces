<?php

namespace App\Traits;

use App\Models\Organization;
use App\Scopes\OrganizationScope;
use App\Services\TenantService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait para models organization-level (compartilhados entre tenants).
 * Aplica OrganizationScope automaticamente.
 *
 * Usar em: Product, Category, Brand, etc.
 */
trait BelongsToOrganizationWithScope
{
    public static function bootBelongsToOrganizationWithScope(): void
    {
        static::addGlobalScope(new OrganizationScope());

        static::creating(function ($model) {
            if (empty($model->organization_id)) {
                // Try to get organization_id from current tenant
                $tenantService = app(TenantService::class);
                $tenant = $tenantService->current();
                if ($tenant) {
                    $model->organization_id = $tenant->organization_id;
                }
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeWithoutOrganizationScope($query)
    {
        return $query->withoutGlobalScope(OrganizationScope::class);
    }
}