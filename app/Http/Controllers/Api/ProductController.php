<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * List all products in catalog.
     */
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->orderBy('name')
            ->get();

        return ProductResource::collection($products)->response();
    }

    /**
     * Return products below or at configurable low-stock threshold.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $customThreshold = $request->has('threshold') ? $request->integer('threshold') : null;
        $lowStockProducts = $this->inventoryService->getLowStockProducts($customThreshold);

        $defaultThreshold = $customThreshold ?? (int) config('inventory.low_stock_threshold', 5);

        return response()->json([
            'effective_threshold' => $defaultThreshold,
            'count' => $lowStockProducts->count(),
            'products' => ProductResource::collection($lowStockProducts),
        ]);
    }
}
