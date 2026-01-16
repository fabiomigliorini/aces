<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        return [
            "organization_id" => Organization::factory(),
            "tenant_id" => Tenant::factory(),
            "product_id" => Product::factory(),
            "quantity" => fake()->numberBetween(0, 1000),
            "min_quantity" => fake()->numberBetween(0, 50),
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            "organization_id" => $tenant->organization_id,
            "tenant_id" => $tenant->id,
        ]);
    }

    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            "organization_id" => $product->organization_id,
            "product_id" => $product->id,
        ]);
    }
}