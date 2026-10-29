<?php

namespace App\Exceptions;

use App\Models\Product;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsufficientStockException extends Exception
{
    protected Product $product;
    protected int $requestedQuantity;
    protected int $availableStock;

    public function __construct(Product $product, int $requestedQuantity, int $availableStock)
    {
        $this->product = $product;
        $this->requestedQuantity = $requestedQuantity;
        $this->availableStock = $availableStock;

        parent::__construct(
            "Insufficient stock for product '{$product->name}' (Code: {$product->code}). Available: {$availableStock}, Requested: {$requestedQuantity}."
        );
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getRequestedQuantity(): int
    {
        return $this->requestedQuantity;
    }

    public function getAvailableStock(): int
    {
        return $this->availableStock;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => [
                'stock' => [
                    "Product '{$this->product->name}' does not have enough stock.",
                ],
            ],
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_code' => $this->product->code,
            'available_stock' => $this->availableStock,
            'requested_quantity' => $this->requestedQuantity,
        ], 422);
    }
}
