<?php

namespace App\Http\Controllers;

use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $status = "healthy";
        $checks = [];

        // Database
        try {
            DB::connection()->getPdo();
            $checks["database"] = "ok";
        } catch (\Exception $e) {
            $checks["database"] = "error";
            $status = "unhealthy";
        }

        // Cache
        try {
            cache()->put("health_check", true, 10);
            cache()->forget("health_check");
            $checks["cache"] = "ok";
        } catch (\Exception $e) {
            $checks["cache"] = "error";
            $status = "unhealthy";
        }

        $httpStatus = $status === "healthy" ? 200 : 503;

        return response()->json([
            "status" => $status,
            "checks" => $checks,
            "timestamp" => now()->toIso8601String(),
        ], $httpStatus);
    }
}