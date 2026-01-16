<?php

namespace App\Http\Controllers;

use App\Services\TenantService;
use Illuminate\Http\JsonResponse;

class TenantController extends Controller
{
    public function __construct(
        private TenantService $tenantService
    ) {}

    /**
     * Lista os tenants disponíveis para o usuário autenticado.
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();
        $tenants = $this->tenantService->availableForUser($user);

        return response()->json([
            "tenants" => $tenants,
            "current" => $this->tenantService->current(),
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