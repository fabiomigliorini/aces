<?php

namespace App\Scopes;

use App\Services\TenantService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app(TenantService::class)->currentId();

        if ($tenantId) {
            $builder->where($model->getTable() . ".tenant_id", $tenantId);
        }
    }
}