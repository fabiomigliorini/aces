<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->city() . " Branch";
        
        return [
            "organization_id" => Organization::factory(),
            "name" => $name,
            "slug" => Str::slug($name) . "-" . fake()->unique()->randomNumber(4),
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