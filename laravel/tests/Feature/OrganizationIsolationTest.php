<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\MultiTenantTestHelpers;

class OrganizationIsolationTest extends TestCase
{
    use RefreshDatabase, MultiTenantTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMultiTenantScenario();
    }

    /** @test */
    public function user_cannot_see_products_from_other_organization(): void
    {
        // Produto da organização do usuário
        $myProduct = Product::factory()->forOrganization($this->organization)->create();
        
        // Produto de outra organização
        $otherProduct = Product::factory()->forOrganization($this->otherOrganization)->create();

        // Autentica como usuário da Org A
        $this->actingAsUser($this->user);

        // Query deve retornar apenas produtos da organização do usuário
        $products = Product::all();

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($myProduct));
        $this->assertFalse($products->contains($otherProduct));
    }

    /** @test */
    public function user_cannot_see_stocks_from_other_organization(): void
    {
        // Produto e estoque da minha org
        $myProduct = Product::factory()->forOrganization($this->organization)->create();
        Stock::factory()->forTenant($this->tenant)->forProduct($myProduct)->create(["quantity" => 100]);

        // Produto e estoque de outra org
        $otherProduct = Product::factory()->forOrganization($this->otherOrganization)->create();
        Stock::factory()->forTenant($this->otherOrgTenant)->forProduct($otherProduct)->create(["quantity" => 200]);

        // Autentica e define tenant
        $this->actingAsUser($this->user)->withTenant($this->tenant);

        $stocks = Stock::all();

        $this->assertCount(1, $stocks);
        $this->assertEquals(100, $stocks->first()->quantity);
    }

    /** @test */
    public function user_from_other_org_cannot_access_my_tenant_via_api(): void
    {
        $this->actingAsUser($this->otherOrgUser);

        // Tenta acessar tenant da Org A
        $response = $this->getJson("/api/projects", [
            "X-Tenant-Id" => $this->tenant->id,
        ]);

        $response->assertStatus(403)
            ->assertJson(["message" => "Tenant not found or access denied."]);
    }

    /** @test */
    public function tenants_list_only_shows_user_organization_tenants(): void
    {
        $this->actingAsUser($this->user);

        $response = $this->getJson("/api/tenants");

        $response->assertOk();
        
        $tenantIds = collect($response->json("tenants"))->pluck("id")->toArray();
        
        // Deve conter apenas tenants da Org A que o user tem acesso
        $this->assertContains($this->tenant->id, $tenantIds);
        $this->assertNotContains($this->otherOrgTenant->id, $tenantIds);
    }

    /** @test */
    public function creating_product_assigns_user_organization_automatically(): void
    {
        $this->actingAsUser($this->user);

        $product = Product::create([
            "name" => "Test Product",
            "sku" => "TEST-001",
        ]);

        $this->assertEquals($this->organization->id, $product->organization_id);
    }

    /** @test */
    public function creating_stock_assigns_organization_and_tenant_automatically(): void
    {
        $product = Product::factory()->forOrganization($this->organization)->create();

        $this->actingAsUser($this->user)->withTenant($this->tenant);

        $stock = Stock::create([
            "product_id" => $product->id,
            "quantity" => 50,
        ]);

        $this->assertEquals($this->organization->id, $stock->organization_id);
        $this->assertEquals($this->tenant->id, $stock->tenant_id);
    }
}