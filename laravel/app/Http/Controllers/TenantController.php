<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    public function __construct(
        private TenantService $tenantService
    ) {}

    /**
     * Lista os tenants disponíveis para o usuário autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Tenant::query();

        // Super admin vê todos os tenants
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

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        // Relacionamentos
        $query->with('organization');

        // Ordenação
        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // Paginação
        $perPage = $request->get('per_page', 15);
        $tenants = $query->paginate($perPage);

        return response()->json($tenants);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'organization_id' => 'required|exists:organizations,id',
            'is_active' => 'boolean',
        ]);

        // Validar slug único por organização
        if (!empty($validated['slug'])) {
            $exists = Tenant::where('organization_id', $validated['organization_id'])
                ->where('slug', $validated['slug'])
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Este slug já está em uso nesta organização.',
                    'errors' => ['slug' => ['Este slug já está em uso nesta organização.']],
                ], 422);
            }
        }

        // Verificar se usuário pode criar tenant nesta organization (super admin pode em qualquer uma)
        if (!$user->isSuperAdmin()) {
            $userOrganizationIds = $user->tenants()
                ->select('organization_id')
                ->distinct()
                ->pluck('organization_id');

            if (!$userOrganizationIds->contains($validated['organization_id'])) {
                return response()->json([
                    'message' => 'Você não tem permissão para criar unidades nesta organização.',
                ], 403);
            }
        }

        if (empty($validated['slug'])) {
            $baseSlug = Str::slug($validated['name']);
            $slug = $baseSlug;
            $counter = 1;

            // Gerar slug único dentro da organização
            while (Tenant::where('organization_id', $validated['organization_id'])->where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $validated['slug'] = $slug;
        }

        $tenant = Tenant::create($validated);

        return response()->json([
            'message' => 'Unidade criada com sucesso.',
            'data' => $tenant,
        ], 201);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load(['organization', 'users']);

        return response()->json([
            'data' => $tenant,
        ]);
    }

    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        // Validar slug único por organização (excluindo o próprio tenant)
        if (!empty($validated['slug'])) {
            $exists = Tenant::where('organization_id', $tenant->organization_id)
                ->where('slug', $validated['slug'])
                ->where('id', '!=', $tenant->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Este slug já está em uso nesta organização.',
                    'errors' => ['slug' => ['Este slug já está em uso nesta organização.']],
                ], 422);
            }
        }

        if (isset($validated['name']) && empty($validated['slug'])) {
            $baseSlug = Str::slug($validated['name']);
            $slug = $baseSlug;
            $counter = 1;

            // Gerar slug único dentro da organização
            while (Tenant::where('organization_id', $tenant->organization_id)
                ->where('slug', $slug)
                ->where('id', '!=', $tenant->id)
                ->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $validated['slug'] = $slug;
        }

        $tenant->update($validated);

        return response()->json([
            'message' => 'Unidade atualizada com sucesso.',
            'data' => $tenant,
        ]);
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        // Verificar se tem usuários vinculados
        if ($tenant->users()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir uma unidade com usuários vinculados.',
            ], 422);
        }

        $tenant->delete();

        return response()->json([
            'message' => 'Unidade excluída com sucesso.',
        ]);
    }

    /**
     * Retorna o tenant atual.
     */
    public function current(): JsonResponse
    {
        $tenant = $this->tenantService->current();

        if (!$tenant) {
            return response()->json([
                "message" => "No tenant selected.",
            ], 400);
        }

        $user = auth()->user();
        $role = $user->roleInTenant($tenant);

        return response()->json([
            "tenant" => $tenant,
            "role" => $role,
            "is_admin" => $role?->is_admin ?? false,
        ]);
    }
}
