<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock - Tenant-level (isolado por filial)
 * Suporta queries multi-tenant via forTenants()
 */
class Stock extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        "organization_id",
        "tenant_id",
        "product_id",
        "quantity",
        "min_quantity",
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}