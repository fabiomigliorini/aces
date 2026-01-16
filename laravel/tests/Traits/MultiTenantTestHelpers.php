<?php

namespace Tests\Traits;

use App\Models\Organization;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantService;
use Laravel\Sanctum\Sanctum;

trait MultiTenantTestHelpers
{
    protected Organization $organization;
    protected Organization $otherOrganization;
    protected Tenant $tenant;
    protected Tenant $otherTenant;
    protected Tenant $otherOrgTenant;
    protected User $user;
    protected User $adminUser;
    protected User $otherOrgUser;
    protected Role $adminRole;
    protected Role $userRole;

    /**
     * Configura o cenário multi-tenant completo para testes.
     */
    protected function setUpMultiTenantScenario(): void
    {
        // Organization 1
        $this->organization = Organization::factory()->create(["name" => "Org A"]);
        
        // Organization 2 (para testes de isolamento)
        $this->otherOrganization = Organization::factory()->create(["name" => "Org B"]);

        // Roles da Org 1
        $this->adminRole = Role::factory()->forOrganization($this->organization)->admin()->create();
        $this->userRole = Role::factory()->forOrganization($this->organization)->withPermissions([
            "project.view",
            "project.create",
            "project.update",
        ])->create(["name" => "User", "slug" => "user"]);

        // Tenants da Org 1
        $this->tenant = Tenant::factory()->forOrganization($this->organization)->create(["name" => "Tenant A"]);
        $this->otherTenant = Tenant::factory()->forOrganization($this->organization)->create(["name" => "Tenant B"]);

        // Tenant da Org 2 (para testes de isolamento)
        $this->otherOrgTenant = Tenant::factory()->forOrganization($this->otherOrganization)->create(["name" => "Tenant Org B"]);

        // User admin (acesso a ambos tenants da Org 1)
        $this->adminUser = User::factory()->forOrganization($this->organization)->create(["name" => "Admin"]);
        $this->adminUser->tenants()->attach($this->tenant->id, ["role_id" => $this->adminRole->id, "is_default" => true]);
        $this->adminUser->tenants()->attach($this->otherTenant->id, ["role_id" => $this->adminRole->id]);

        // User normal (acesso apenas ao tenant A da Org 1)
        $this->user = User::factory()->forOrganization($this->organization)->create(["name" => "User"]);
        $this->user->tenants()->attach($this->tenant->id, ["role_id" => $this->userRole->id, "is_default" => true]);

        // User de outra organization
        $otherOrgRole = Role::factory()->forOrganization($this->otherOrganization)->admin()->create();
        $this->otherOrgUser = User::factory()->forOrganization($this->otherOrganization)->create(["name" => "Other Org User"]);
        $this->otherOrgUser->tenants()->attach($this->otherOrgTenant->id, ["role_id" => $otherOrgRole->id, "is_default" => true]);
    }

    /**
     * Autentica como um usuário específico.
     */
    protected function actingAsUser(User $user): static
    {
        Sanctum::actingAs($user);
        return $this;
    }

    /**
     * Define o tenant atual no TenantService.
     */
    protected function withTenant(Tenant $tenant): static
    {
        app(TenantService::class)->setCurrent($tenant);
        return $this;
    }

    /**
     * Faz request com header X-Tenant-Id.
     */
    protected function withTenantHeader(Tenant $tenant): array
    {
        return ["X-Tenant-Id" => $tenant->id];
    }

    /**
     * Helper para criar request autenticado com tenant.
     */
    protected function authenticatedRequest(User $user, ?Tenant $tenant = null): static
    {
        $this->actingAsUser($user);
        
        if ($tenant) {
            $this->withTenant($tenant);
        }
        
        return $this;
    }
}