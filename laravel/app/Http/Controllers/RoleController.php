<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Role::query();

        // Super admin vê todos os roles
        if (!$user->isSuperAdmin()) {
            // Obter IDs das organizações que o usuário tem acesso (via tenants)
            $organizationIds = $user->tenants()
                ->select('organization_id')
                ->distinct()
                ->pluck('organization_id');

            // Filtrar pelas organizações que o usuário tem acesso
            if ($organizationIds->isNotEmpty()) {
                $query->whereIn('organization_id', $organizationIds);
            } else {
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

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('is_admin')) {
            $query->where('is_admin', $request->boolean('is_admin'));
        }

        // Relacionamentos
        $query->with('organization');

        // Ordenação
        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // Paginação
        $perPage = $request->get('per_page', 15);
        $roles = $query->paginate($perPage);

        return response()->json($roles);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'organization_id' => 'required|exists:organizations,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'is_admin' => 'boolean',
        ]);

        // Verificar se usuário pode criar role nesta organization (super admin pode em qualquer uma)
        if (!$user->isSuperAdmin()) {
            $userOrganizationIds = $user->tenants()
                ->select('organization_id')
                ->distinct()
                ->pluck('organization_id');

            if (!$userOrganizationIds->contains($validated['organization_id'])) {
                return response()->json([
                    'message' => 'Você não tem permissão para criar perfis nesta organização.',
                ], 403);
            }
        }

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $role = Role::create($validated);

        return response()->json([
            'message' => 'Perfil criado com sucesso.',
            'data' => $role,
        ], 201);
    }

    public function show(Role $role): JsonResponse
    {
        $role->load('organization');

        return response()->json([
            'data' => $role,
        ]);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'is_admin' => 'boolean',
        ]);

        if (isset($validated['name']) && empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $role->update($validated);

        return response()->json([
            'message' => 'Perfil atualizado com sucesso.',
            'data' => $role,
        ]);
    }

    public function destroy(Role $role): JsonResponse
    {
        // Verificar se o role está sendo usado em algum tenant_user
        $inUse = \DB::table('tenant_user')->where('role_id', $role->id)->exists();

        if ($inUse) {
            return response()->json([
                'message' => 'Não é possível excluir um perfil que está sendo utilizado.',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'message' => 'Perfil excluído com sucesso.',
        ]);
    }

    /**
     * Lista todas as permissões disponíveis no sistema.
     */
    public function permissions(): JsonResponse
    {
        $permissions = [
            'users.view' => 'Visualizar usuários',
            'users.create' => 'Criar usuários',
            'users.edit' => 'Editar usuários',
            'users.delete' => 'Excluir usuários',
            'tenants.view' => 'Visualizar unidades',
            'tenants.create' => 'Criar unidades',
            'tenants.edit' => 'Editar unidades',
            'tenants.delete' => 'Excluir unidades',
            'roles.view' => 'Visualizar perfis',
            'roles.create' => 'Criar perfis',
            'roles.edit' => 'Editar perfis',
            'roles.delete' => 'Excluir perfis',
            'reports.view' => 'Visualizar relatórios',
            'settings.view' => 'Visualizar configurações',
            'settings.edit' => 'Editar configurações',
        ];

        return response()->json([
            'data' => $permissions,
        ]);
    }
}
