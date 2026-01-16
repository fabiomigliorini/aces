<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "name",
        "slug",
        "is_active",
    ];

    protected function casts(): array
    {
        return [
            "is_active" => "boolean",
        ];
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    /**
     * Get all users that belong to this organization through tenants.
     */
    public function users(): Collection
    {
        return User::whereHas('tenants', function ($query) {
            $query->where('organization_id', $this->id);
        })->get();
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }
}