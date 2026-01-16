<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\MultiTenantTestHelpers;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase, MultiTenantTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMultiTenantScenario();
    }

    /** @test */
    public function user_only_sees_stocks_from_current_tenant(): void
    {
        $product = Product::factory()->forOrganization($this->organization)->create();

        // Estoque no Tenant A
        $stockA = Stock::factory()->forTenant($this->tenant)->forProduct($product)->create(["quantity" => 100]);
        
        // Estoque no Tenant B
        $stockB = Stock::factory()->forTenant($this->otherTenant)->forProduct($product)->create(["quantity" => 200]);

        // Como user no Tenant A
        $this->actingAsUser($this->adminUser)->withTenant($this->tenant);

        $stocks = Stock::all();

        $this->assertCount(1, $stocks);
        $this->assertEquals(100, $stocks->first()->quantity);
        $this->assertEquals($this->tenant->id, $stocks->first()->tenant_id);
    }

    /** @test */
    public function switching_tenant_changes_visible_data(): void
    {
        $product = Product::factory()->forOrganization($this->organization)->create();

        Stock::factory()->forTenant($this->tenant)->forProduct($product)->create(["quantity" => 100]);
        Stock::factory()->forTenant($this->otherTenant)->forProduct($product)->create(["quantity" => 200]);

        $this->actingAsUser($this->adminUser);

        // No Tenant A
        $this->withTenant($this->tenant);
        $this->assertCount(1, Stock::all());
        $this->assertEquals(100, Stock::first()->quantity);

        // Troca para Tenant B
        $this->withTenant($this->otherTenant);
        $this->assertCount(1, Stock::all());
        $this->assertEquals(200, Stock::first()->quantity);
    }

    /** @test */
    public function api_request_with_tenant_header_filters_data(): void
    {
        $product = Product::factory()->forOrganization($this->organization)->create();

        Stock::factory()->forTenant($this->tenant)->forProduct($product)->create(["quantity" => 111]);
        Stock::factory()->forTenant($this->otherTenant)->forProduct($product)->create(["quantity" => 222]);

        $this->actingAsUser($this->adminUser);

        // Request para Tenant A
        $response = $this->getJson("/api/stocks", ["X-Tenant-Id" => $this->tenant->id]);
        
        $response->assertOk();
        $data = $response->json("data");
        $this->assertCount(1, $data);
        $this->assertEquals(111, $data[0]["quantity"]);

        // Request para Tenant B
        $response = $this->getJson("/api/stocks", ["X-Tenant-Id" => $this->otherTenant->id]);
        
        $response->assertOk();
        $data = $response->json("data");
        $this->assertCount(1, $data);
        $this->assertEquals(222, $data[0]["quantity"]);
    }

    /** @test */
    public function stock_created_via_api_belongs_to_correct_tenant(): void
    {
        $product = Product::factory()->forOrganization($this->organization)->create();

        $this->actingAsUser($this->adminUser);

        $response = $this->postJson("/api/stocks", [
            "product_id" => $product->id,
            "quantity" => 50,
        ], ["X-Tenant-Id" => $this->otherTenant->id]);

        $response->assertStatus(201);

        $stock = Stock::withoutTenantScope()->first();
        $this->assertEquals($this->otherTenant->id, $stock->tenant_id);
        $this->assertEquals($this->organization->id, $stock->organization_id);
    }

    /** @test */
    public function products_are_visible_across_all_tenants_same_organization(): void
    {
        // Produtos são organization-level, não tenant-level
        $product = Product::factory()->forOrganization($this->organization)->create();

        // User no Tenant A pode ver
        $this->actingAsUser($this->user)->withTenant($this->tenant);
        $this->assertCount(1, Product::all());

        // Admin no Tenant B também pode ver (mesma organização)
        $this->actingAsUser($this->adminUser)->withTenant($this->otherTenant);
        $this->assertCount(1, Product::all());
    }
}