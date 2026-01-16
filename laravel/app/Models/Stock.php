<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    use HasFactory, BelongsToTenant;

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