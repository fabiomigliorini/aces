<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, BelongsToOrganization;

    protected $fillable = [
        "organization_id",
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
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, "tenant_user")
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