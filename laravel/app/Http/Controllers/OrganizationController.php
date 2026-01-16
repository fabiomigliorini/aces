<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Organization::query();

        // Super admin vê todas as organizações
        if (!$user->isSuperAdmin()) {
            // Obter IDs das organizações que o usuário tem acesso (via tenants)
            $organizationIds = $user->tenants()
                ->select('organization_id')
                ->distinct()
                ->pluck('organization_id');

            // Se o usuário tem tenants, filtrar pelas organizações desses tenants
            // Caso contrário, retorna vazio (usuário sem acesso)
            if ($organizationIds->isNotEmpty()) {
                $query->whereIn('id', $organizationIds);
            } else {
                // Usuário sem tenants não vê nenhuma organização
                $query->whereRaw('1 = 0');
            }
        }

        // Filtros
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('slug', 'ilike', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Ordenação
        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // Paginação
        $perPage = $request->get('per_page', 15);
        $organizations = $query->paginate($perPage);

        return response()->json($organizations);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:organizations,slug',
            'is_active' => 'boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Usar transaction para criar organization + recursos relacionados
        $organization = DB::transaction(function () use ($validated, $user) {
            // Criar a organização
            $organization = Organization::create($validated);

            // Criar role de administrador padrão
            $adminRole = Role::create([
                'organization_id' => $organization->id,
                'name' => 'Administrador',
                'slug' => 'admin',
                'is_admin' => true,
                'permissions' => [],
            ]);

            // Criar tenant/unidade padrão (Matriz)
            $tenant = Tenant::create([
                'organization_id' => $organization->id,
                'name' => 'Matriz',
                'slug' => 'matriz',
                'is_active' => true,
            ]);

            // Verificar se o usuário já tem um tenant padrão
            $hasDefaultTenant = $user->tenants()->wherePivot('is_default', true)->exists();

            // Vincular usuário ao tenant como admin
            $user->tenants()->attach($tenant->id, [
                'role_id' => $adminRole->id,
                'is_default' => !$hasDefaultTenant, // Definir como default se não tiver outro
            ]);

            return $organization;
        });

        return response()->json([
            'message' => 'Organização criada com sucesso.',
            'data' => $organization->load(['tenants', 'roles']),
        ], 201);
    }

    public function show(Organization $organization)
    {
        $organization->load(['tenants', 'users', 'roles']);

        return response()->json([
            'data' => $organization,
        ]);
    }

    public function update(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('organizations', 'slug')->ignore($organization->id),
            ],
            'is_active' => 'boolean',
        ]);

        if (isset($validated['name']) && empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $organization->update($validated);

        return response()->json([
            'message' => 'Organização atualizada com sucesso.',
            'data' => $organization,
        ]);
    }

    public function destroy(Organization $organization)
    {
        // Verificar se tem tenants vinculados
        if ($organization->tenants()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir uma organização com unidades vinculadas.',
            ], 422);
        }

        // Verificar se tem usuários vinculados (através de tenants)
        if ($organization->users()->isNotEmpty()) {
            return response()->json([
                'message' => 'Não é possível excluir uma organização com usuários vinculados.',
            ], 422);
        }

        $organization->delete();

        return response()->json([
            'message' => 'Organização excluída com sucesso.',
        ]);
    }
}
