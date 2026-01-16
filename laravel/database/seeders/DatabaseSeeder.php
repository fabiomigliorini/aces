<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Organization (Grupo Econômico)
        $org = Organization::create([
            "name" => "ACES Grupo",
            "slug" => "aces-grupo",
        ]);

        // Roles
        $adminRole = Role::create([
            "organization_id" => $org->id,
            "name" => "Administrador",
            "slug" => "admin",
            "is_admin" => true,
        ]);

        $userRole = Role::create([
            "organization_id" => $org->id,
            "name" => "Usuário",
            "slug" => "user",
            "permissions" => [
                "project.view",
                "project.create",
                "project.update",
            ],
        ]);

        // Tenants (Empresas)
        $tenant1 = Tenant::create([
            "organization_id" => $org->id,
            "name" => "Matriz",
            "slug" => "matriz",
        ]);

        $tenant2 = Tenant::create([
            "organization_id" => $org->id,
            "name" => "Filial SP",
            "slug" => "filial-sp",
        ]);

        // User Admin
        $admin = User::create([
            "organization_id" => $org->id,
            "name" => "Admin",
            "email" => "admin@aces.local",
            "password" => Hash::make("password"),
        ]);

        // Attach admin to both tenants as admin
        $admin->tenants()->attach($tenant1->id, ["role_id" => $adminRole->id, "is_default" => true]);
        $admin->tenants()->attach($tenant2->id, ["role_id" => $adminRole->id]);

        // User normal
        $user = User::create([
            "organization_id" => $org->id,
            "name" => "Usuário Teste",
            "email" => "user@aces.local",
            "password" => Hash::make("password"),
        ]);

        // Attach user only to tenant1 as user role
        $user->tenants()->attach($tenant1->id, ["role_id" => $userRole->id, "is_default" => true]);
    }
}