<?php

namespace App\Http\Middleware;

use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsSet
{
    public function __construct(
        private TenantService $tenantService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->tenantService->check()) {
            return response()->json([
                "message" => "No tenant selected. Send X-Tenant-Id header.",
            ], 400);
        }

        return $next($request);
    }
}