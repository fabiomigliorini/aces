<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Super admin bypassa o filtro de organização
            if ($user->isSuperAdmin()) {
                return;
            }

            // Get all organization IDs the user has access to via tenants
            $organizationIds = $user->tenants()
                ->select('organization_id')
                ->distinct()
                ->pluck('organization_id');

            if ($organizationIds->isNotEmpty()) {
                $builder->whereIn($model->getTable() . ".organization_id", $organizationIds);
            } else {
                $builder->whereRaw('1 = 0'); // No access
            }
        }
    }
}