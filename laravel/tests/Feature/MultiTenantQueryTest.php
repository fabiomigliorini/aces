<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\MultiTenantTestHelpers;

class MultiTenantQueryTest extends TestCase
{
    use RefreshDatabase, MultiTenantTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMultiTenantScenario();
    }

    /** @test */
    public function consolidated_endpoint_returns_all_authorized_tenants(): void
    {
        $product = Product::factory()->forOrganization($this->organization)->create();

        Stock::factory()->forTenant($this->tenant)->forProduct($product)->create(["quantity" => 100]);
        Stock::factory()->forTenant($this->otherTenant)->forProduct($product)->create(["quantity" => 50]);

        // Admin tem acesso a ambos tenants
        $this->actingAsUser($this->adminUser);

        $response = $this->getJson("/api/stocks/consolidated");

        $response->assertOk();

        $data = $response->json("data");
        $this->assertCount(1, $data); // 1 produto
        $this->assertEquals(150, $data[0]["total_quantity"]); // 100 + 50
        $this->assertCount(2, $data[0]["by_tenant"]); // 2 tenants
    }

    /** @test */
    public function consolidated_endpoint_filters_by_specific_tenants(): void
    {
        $product = Product::factory()->forOrganization($this->organization)->create();

        Stock::factory()->forTenant($this->tenant)->forProduct($product)->create(["quantity" => 100]);
        Stock::factory()->forTenant($this->otherTenant)->forProduct($product)->create(["quantity" => 50]);

        $this->actingAsUser($this->adminUser);

        // Solicita apenas tenant A
        $response = $this->getJson("/api/stocks/consolidated?tenant_ids=" . $this->tenant->id);

        $response->assertOk();

        $data = $response->json("data");
        $this->assertEquals(100, $data[0]["total_quantity"]); // Apenas tenant A
        $this->assertCount(1, $data[0]["by_tenant"]);
    }

    /** @test */
    public function consolidated_endpoint_ignores_unauthorized_tenant_ids(): void
    {
        $product = Product::factory()->forOrganization($this->organization)->create();

        Stock::factory()->forTenant($this->tenant)->forProduct($product)->create(["quantity" => 100]);
        Stock::factory()->forTenant($this->otherTenant)->forProduct($product)->create(["quantity" => 50]);
        Stock::factory()->forTenant($this->otherOrgTenant)->forProduct(
            Product::factory()->forOrganization($this->otherOrganization)->create()
        )->create(["quantity" => 999]);

        // User normal só tem acesso ao tenant A
        $this->actingAsUser($this->user);

        // Tenta solicitar todos (incluindo B e de outra org)
        $response = $this->getJson(sprintf(
            "/api/stocks/consolidated?tenant_ids=%d,%d,%d",
            $this->tenant->id,
            $this->otherTenant->id,
            $this->otherOrgTenant->id
        ));

        $response->assertOk();

        // Deve retornar apenas dados do tenant A (único autorizado)
        $data = $response->json("data");
        $this->assertEquals(100, $data[0]["total_quantity"]);
        
        $tenantsIncluded = $response->json("tenants_included");
        $this->assertEquals([$this->tenant->id], $tenantsIncluded);
    }

    /** @test */
    public function for_tenants_scope_validates_user_access(): void
    {
        $product = Product::factory()->forOrganization($this->organization)->create();

        Stock::factory()->forTenant($this->tenant)->forProduct($product)->create(["quantity" => 100]);
        Stock::factory()->forTenant($this->otherTenant)->forProduct($product)->create(["quantity" => 200]);

        // User só tem acesso ao tenant A
        $this->actingAsUser($this->user);

        // Tenta query para ambos tenants
        $stocks = Stock::forTenants([$this->tenant->id, $this->otherTenant->id])->get();

        // Deve retornar apenas do tenant A
        $this->assertCount(1, $stocks);
        $this->assertEquals(100, $stocks->first()->quantity);
    }

    /** @test */
    public function for_tenants_scope_without_args_returns_all_user_tenants(): void
    {
        $product = Product::factory()->forOrganization($this->organization)->create();

        Stock::factory()->forTenant($this->tenant)->forProduct($product)->create(["quantity" => 100]);
        Stock::factory()->forTenant($this->otherTenant)->forProduct($product)->create(["quantity" => 200]);

        // Admin tem acesso a ambos
        $this->actingAsUser($this->adminUser);

        $stocks = Stock::forTenants()->get();

        $this->assertCount(2, $stocks);
        $this->assertEquals(300, $stocks->sum("quantity"));
    }

    /** @test */
    public function for_tenants_scope_never_leaks_other_organization_data(): void
    {
        $myProduct = Product::factory()->forOrganization($this->organization)->create();
        $otherProduct = Product::factory()->forOrganization($this->otherOrganization)->create();

        Stock::factory()->forTenant($this->tenant)->forProduct($myProduct)->create(["quantity" => 100]);
        Stock::factory()->forTenant($this->otherOrgTenant)->forProduct($otherProduct)->create(["quantity" => 999]);

        $this->actingAsUser($this->adminUser);

        // Query sem filtro de tenants específicos
        $stocks = Stock::forTenants()->get();

        // Nunca deve incluir dados de outra organização
        $this->assertCount(1, $stocks);
        $this->assertEquals(100, $stocks->first()->quantity);
        $this->assertFalse($stocks->contains("quantity", 999));
    }
}