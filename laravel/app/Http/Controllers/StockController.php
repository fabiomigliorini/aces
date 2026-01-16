<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(
        private TenantService $tenantService
    ) {}

    /**
     * Lista estoque do tenant atual.
     */
    public function index(): JsonResponse
    {
        // TenantScope filtra automaticamente
        $stocks = Stock::with("product")->paginate(15);

        return response()->json($stocks);
    }

    /**
     * Lista estoque consolidado de múltiplos tenants.
     * Query param: tenant_ids=1,2,3 (opcional, default = todos)
     */
    public function consolidated(Request $request): JsonResponse
    {
        $tenantIds = null;
        
        if ($request->has("tenant_ids")) {
            $tenantIds = array_map("intval", explode(",", $request->tenant_ids));
        }

        // forTenants() valida acesso e filtra
        $stocks = Stock::forTenants($tenantIds)
            ->with(["product", "tenant:id,name"])
            ->get()
            ->groupBy("product_id")
            ->map(function ($items) {
                $first = $items->first();
                return [
                    "product" => $first->product,
                    "total_quantity" => $items->sum("quantity"),
                    "by_tenant" => $items->map(fn($s) => [
                        "tenant" => $s->tenant->name,
                        "quantity" => $s->quantity,
                    ]),
                ];
            })
            ->values();

        return response()->json([
            "data" => $stocks,
            "tenants_included" => $tenantIds ?? $this->tenantService->allowedTenantIds(auth()->user()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "product_id" => "required|exists:products,id",
            "quantity" => "required|integer|min:0",
            "min_quantity" => "nullable|integer|min:0",
        ]);

        $stock = Stock::updateOrCreate(
            [
                "tenant_id" => $this->tenantService->currentId(),
                "product_id" => $validated["product_id"],
            ],
            [
                "quantity" => $validated["quantity"],
                "min_quantity" => $validated["min_quantity"] ?? 0,
            ]
        );

        return response()->json($stock, 201);
    }

    public function update(Request $request, Stock $stock): JsonResponse
    {
        $validated = $request->validate([
            "quantity" => "sometimes|integer|min:0",
            "min_quantity" => "sometimes|integer|min:0",
        ]);

        $stock->update($validated);

        return response()->json($stock);
    }
}