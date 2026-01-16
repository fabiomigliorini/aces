<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        "name",
        "email",
        "password",
    ];

    protected $hidden = [
        "password",
        "remember_token",
    ];

    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
            "password" => "hashed",
            "is_super_admin" => "boolean",
        ];
    }

    /**
     * Check if user is a super admin (has access to everything).
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    /**
     * Get all organizations the user has access to through their tenants.
     * Super admins get all organizations.
     */
    public function organizations(): Collection
    {
        return Organization::whereHas('tenants', function ($query) {
            $query->whereIn('id', $this->tenants()->pluck('tenants.id'));
        })->get();
    }

    /**
     * Check if user belongs to an organization (through any tenant).
     */
    public function belongsToOrganization(Organization $organization): bool
    {
        return $this->tenants()
            ->where('organization_id', $organization->id)
            ->exists();
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, "tenant_user")
            ->using(TenantUser::class)
            ->withPivot(["role_id", "is_default"])
            ->withTimestamps();
    }

    public function defaultTenant(): HasOne
    {
        return $this->hasOne(TenantUser::class)->where("is_default", true);
    }

    public function roleInTenant(Tenant $tenant): ?Role
    {
        $pivot = $this->tenants()->where("tenant_id", $tenant->id)->first();
        
        if (!$pivot) {
            return null;
        }

        return Role::find($pivot->pivot->role_id);
    }

    public function hasPermissionInTenant(Tenant $tenant, string $permission): bool
    {
        $role = $this->roleInTenant($tenant);

        return $role?->hasPermission($permission) ?? false;
    }

    public function isAdminInTenant(Tenant $tenant): bool
    {
        $role = $this->roleInTenant($tenant);

        return $role?->is_admin ?? false;
    }

    public function belongsToTenant(Tenant $tenant): bool
    {
        return $this->tenants()->where("tenant_id", $tenant->id)->exists();
    }
}