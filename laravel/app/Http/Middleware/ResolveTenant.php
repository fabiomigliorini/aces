<?php

namespace App\Http\Middleware;

use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(
        private TenantService $tenantService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Tenant vem do header X-Tenant-Id (nunca confiar em body/query)
        $tenantId = $request->header("X-Tenant-Id");
        $tenantId = $tenantId ? (int) $tenantId : null;

        $tenant = $this->tenantService->resolveFromRequest($tenantId, $user);

        if ($tenantId && !$tenant) {
            return response()->json([
                "message" => "Tenant not found or access denied.",
            ], 403);
        }

        $this->tenantService->setCurrent($tenant);

        return $next($request);
    }
}