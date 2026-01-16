<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\MultiTenantTestHelpers;

class TenantAccessControlTest extends TestCase
{
    use RefreshDatabase, MultiTenantTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMultiTenantScenario();
    }

    /** @test */
    public function user_cannot_access_tenant_they_dont_belong_to(): void
    {
        // User só tem acesso ao $this->tenant, não ao $this->otherTenant
        $this->actingAsUser($this->user);

        $response = $this->getJson("/api/stocks", [
            "X-Tenant-Id" => $this->otherTenant->id,
        ]);

        $response->assertStatus(403)
            ->assertJson(["message" => "Tenant not found or access denied."]);
    }

    /** @test */
    public function user_can_access_tenant_they_belong_to(): void
    {
        $this->actingAsUser($this->user);

        $response = $this->getJson("/api/stocks", [
            "X-Tenant-Id" => $this->tenant->id,
        ]);

        $response->assertOk();
    }

    /** @test */
    public function admin_can_access_multiple_tenants(): void
    {
        $this->actingAsUser($this->adminUser);

        // Tenant A
        $response = $this->getJson("/api/stocks", [
            "X-Tenant-Id" => $this->tenant->id,
        ]);
        $response->assertOk();

        // Tenant B
        $response = $this->getJson("/api/stocks", [
            "X-Tenant-Id" => $this->otherTenant->id,
        ]);
        $response->assertOk();
    }

    /** @test */
    public function request_without_tenant_header_requires_tenant_for_protected_routes(): void
    {
        // Cria um user SEM default tenant
        $userWithoutDefault = User::factory()->forOrganization($this->organization)->create();
        $userWithoutDefault->tenants()->attach($this->tenant->id, [
            "role_id" => $this->userRole->id,
            "is_default" => false, // Importante: sem default
        ]);

        $this->actingAsUser($userWithoutDefault);

        // Rota que exige tenant
        $response = $this->getJson("/api/stocks");

        $response->assertStatus(400)
            ->assertJson(["message" => "No tenant selected. Send X-Tenant-Id header."]);
    }

    /** @test */
    public function inactive_tenant_cannot_be_accessed(): void
    {
        // Desativa o tenant
        $this->tenant->update(["is_active" => false]);

        $this->actingAsUser($this->user);

        $response = $this->getJson("/api/stocks", [
            "X-Tenant-Id" => $this->tenant->id,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function cannot_spoof_tenant_id_from_different_organization(): void
    {
        // User da Org A tenta acessar tenant da Org B
        $this->actingAsUser($this->user);

        $response = $this->getJson("/api/stocks", [
            "X-Tenant-Id" => $this->otherOrgTenant->id,
        ]);

        $response->assertStatus(403)
            ->assertJson(["message" => "Tenant not found or access denied."]);
    }

    /** @test */
    public function unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson("/api/stocks", [
            "X-Tenant-Id" => $this->tenant->id,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function tenants_endpoint_only_returns_active_tenants(): void
    {
        $this->otherTenant->update(["is_active" => false]);

        $this->actingAsUser($this->adminUser);

        $response = $this->getJson("/api/tenants");

        $response->assertOk();
        
        $tenantIds = collect($response->json("tenants"))->pluck("id")->toArray();

        $this->assertContains($this->tenant->id, $tenantIds);
        $this->assertNotContains($this->otherTenant->id, $tenantIds);
    }
}