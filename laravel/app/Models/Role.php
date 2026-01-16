<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Role extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        "organization_id",
        "name",
        "slug",
        "permissions",
        "is_admin",
    ];

    protected function casts(): array
    {
        return [
            "permissions" => "array",
            "is_admin" => "boolean",
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return in_array($permission, $this->permissions ?? []);
    }
}