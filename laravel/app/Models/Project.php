<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes, BelongsToOrganization, BelongsToTenant;

    protected $fillable = [
        "organization_id",
        "tenant_id",
        "name",
        "description",
        "status",
    ];
}