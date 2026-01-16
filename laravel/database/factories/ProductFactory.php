<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            "organization_id" => Organization::factory(),
            "name" => fake()->words(3, true),
            "sku" => strtoupper(fake()->unique()->bothify("SKU-####-???")),
            "description" => fake()->sentence(),
            "is_active" => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            "is_active" => false,
        ]);
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn (array $attributes) => [
            "organization_id" => $organization->id,
        ]);
    }
}