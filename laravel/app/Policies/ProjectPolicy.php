<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, Permission::PROJECT_VIEW->value);
    }

    public function view(User $user, Project $project): bool
    {
        return $this->belongsToCurrentTenant($project)
            && $this->hasPermission($user, Permission::PROJECT_VIEW->value);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, Permission::PROJECT_CREATE->value);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->belongsToCurrentTenant($project)
            && $this->hasPermission($user, Permission::PROJECT_UPDATE->value);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->belongsToCurrentTenant($project)
            && $this->hasPermission($user, Permission::PROJECT_DELETE->value);
    }
}