<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $name = fake()->jobTitle();
        
        return [
            "organization_id" => Organization::factory(),
            "name" => $name,
            "slug" => Str::slug($name) . "-" . fake()->unique()->randomNumber(4),
            "permissions" => [],
            "is_admin" => false,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            "name" => "Administrator",
            "slug" => "admin-" . fake()->unique()->randomNumber(4),
            "is_admin" => true,
        ]);
    }

    public function withPermissions(array $permissions): static
    {
        return $this->state(fn (array $attributes) => [
            "permissions" => $permissions,
        ]);
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn (array $attributes) => [
            "organization_id" => $organization->id,
        ]);
    }
}