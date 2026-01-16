<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\TenantUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $currentUser = $request->user();

        $query = User::query();

        // Super admin vê todos os usuários
        if (!$currentUser->isSuperAdmin()) {
            // Filtrar usuários que pertencem às mesmas organizações do usuário logado (via tenants)
            $organizationIds = $currentUser->tenants()
                ->select('organization_id')
                ->distinct()
                ->pluck('organization_id');

            if ($organizationIds->isNotEmpty()) {
                $query->whereHas('tenants', function ($q) use ($organizationIds) {
                    $q->whereIn('organization_id', $organizationIds);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Filtros
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($request->has('organization_id')) {
            $query->whereHas('tenants', function ($q) use ($request) {
                $q->where('organization_id', $request->organization_id);
            });
        }

        if ($request->has('tenant_id')) {
            $query->whereHas('tenants', function ($q) use ($request) {
                $q->where('tenants.id', $request->tenant_id);
            });
        }

        // Relacionamentos
        $query->with(['tenants']);

        // Ordenação
        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // Paginação
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        $currentUser = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::defaults()],
            'tenant_id' => 'required|exists:tenants,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        // Verificar se usuário pode criar users neste tenant (super admin pode em qualquer um)
        if (!$currentUser->isSuperAdmin()) {
            $userTenantIds = $currentUser->tenants()->pluck('tenants.id');
            if (!$userTenantIds->contains($validated['tenant_id'])) {
                return response()->json([
                    'message' => 'Você não tem permissão para criar usuários nesta unidade.',
                ], 403);
            }
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Vincular ao tenant com o role especificado
        $user->tenants()->attach($validated['tenant_id'], [
            'role_id' => $validated['role_id'],
            'is_default' => true,
        ]);

        return response()->json([
            'message' => 'Usuário criado com sucesso.',
            'data' => $user->load('tenants'),
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['tenants']);

        return response()->json([
            'data' => $user,
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['sometimes', 'nullable', Password::defaults()],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Usuário atualizado com sucesso.',
            'data' => $user,
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        // Não permitir excluir o próprio usuário
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'Você não pode excluir sua própria conta.',
            ], 422);
        }

        // Remove vínculos com tenants
        $user->tenants()->detach();

        $user->delete();

        return response()->json([
            'message' => 'Usuário excluído com sucesso.',
        ]);
    }

    /**
     * Lista os tenants de um usuário.
     */
    public function tenants(User $user): JsonResponse
    {
        $tenants = $user->tenants()->with('organization')->get();

        // Load the role for each tenant's pivot
        $tenants->each(function ($tenant) {
            if ($tenant->pivot->role_id) {
                $tenant->pivot->role = Role::find($tenant->pivot->role_id);
            }
        });

        return response()->json([
            'data' => $tenants,
        ]);
    }

    /**
     * Vincula um usuário a um tenant com um role.
     */
    public function attachTenant(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'role_id' => 'required|exists:roles,id',
            'is_default' => 'boolean',
        ]);

        // Verificar se já existe vínculo
        if ($user->tenants()->where('tenant_id', $validated['tenant_id'])->exists()) {
            return response()->json([
                'message' => 'Usuário já está vinculado a esta unidade.',
            ], 422);
        }

        // Se is_default, remover default dos outros
        if ($validated['is_default'] ?? false) {
            TenantUser::where('user_id', $user->id)->update(['is_default' => false]);
        }

        $user->tenants()->attach($validated['tenant_id'], [
            'role_id' => $validated['role_id'],
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return response()->json([
            'message' => 'Usuário vinculado à unidade com sucesso.',
        ]);
    }

    /**
     * Atualiza o vínculo de um usuário com um tenant.
     */
    public function updateTenant(Request $request, User $user, int $tenantId): JsonResponse
    {
        $validated = $request->validate([
            'role_id' => 'sometimes|required|exists:roles,id',
            'is_default' => 'boolean',
        ]);

        // Se is_default, remover default dos outros
        if ($validated['is_default'] ?? false) {
            TenantUser::where('user_id', $user->id)
                ->where('tenant_id', '!=', $tenantId)
                ->update(['is_default' => false]);
        }

        $user->tenants()->updateExistingPivot($tenantId, $validated);

        return response()->json([
            'message' => 'Vínculo atualizado com sucesso.',
        ]);
    }

    /**
     * Remove o vínculo de um usuário com um tenant.
     */
    public function detachTenant(User $user, int $tenantId): JsonResponse
    {
        $user->tenants()->detach($tenantId);

        return response()->json([
            'message' => 'Vínculo removido com sucesso.',
        ]);
    }
}
