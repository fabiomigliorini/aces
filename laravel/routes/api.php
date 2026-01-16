<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

// Public
Route::get("/health", HealthController::class);
Route::post("/register", [AuthController::class, "register"]);
Route::post("/login", [AuthController::class, "login"]);

// Authenticated (sem tenant obrigatório)
Route::middleware("auth:sanctum")->group(function () {
    Route::post("/logout", [AuthController::class, "logout"]);
    Route::get("/user", [AuthController::class, "user"]);
    Route::get("/tenants", [TenantController::class, "index"]);
    Route::get("/organizations", [OrganizationController::class, "index"]);
    Route::get("/organizations/{organization}", [OrganizationController::class, "show"]);
});

// Authenticated + Tenant resolvido (pode ser null para multi-tenant queries)
Route::middleware(["auth:sanctum", "tenant"])->group(function () {
    Route::get("/tenant/current", [TenantController::class, "current"]);
    
    // Organization-level (não precisa de tenant)
    // Route::apiResource("products", ProductController::class);
    
    // Multi-tenant queries
    Route::get("/stocks/consolidated", [StockController::class, "consolidated"]);
});

// Authenticated + Tenant obrigatório
Route::middleware(["auth:sanctum", "tenant", "tenant.required"])->group(function () {
    // Tenant-level resources
    Route::apiResource("projects", ProjectController::class);
    Route::apiResource("stocks", StockController::class)->except(["consolidated"]);
});