<?php

namespace App\Providers;

use App\Models\Project;
use App\Policies\ProjectPolicy;
use App\Services\TenantService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // TenantService como singleton
        $this->app->singleton(TenantService::class, function () {
            return new TenantService();
        });
    }

    public function boot(): void
    {
        // Policies
        Gate::policy(Project::class, ProjectPolicy::class);
    }
}