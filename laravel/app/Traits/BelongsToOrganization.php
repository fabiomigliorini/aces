<?php

namespace App\Traits;

use App\Models\Organization;
use App\Services\TenantService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait para models que pertencem a uma Organization.
 * Não aplica scope automático - a organização é resolvida via tenant atual.
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
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
}