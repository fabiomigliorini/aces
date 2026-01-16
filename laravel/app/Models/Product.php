<?php

namespace App\Models;

use App\Traits\BelongsToOrganizationWithScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Product - Organization-level (compartilhado entre todos os tenants)
 */
class Product extends Model
{
    use SoftDeletes, BelongsToOrganizationWithScope;

    protected $fillable = [
        "organization_id",
        "name",
        "sku",
        "description",
        "is_active",
    ];

    protected function casts(): array
    {
        return [
            "is_active" => "boolean",
        ];
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }
}