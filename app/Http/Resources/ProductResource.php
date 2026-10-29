<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $defaultThreshold = (int) config('inventory.low_stock_threshold', 5);
        $effectiveThreshold = $this->low_stock_threshold ?? $defaultThreshold;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'price' => (float) $this->price,
            'tax_percentage' => (float) $this->tax_percentage,
            'stock' => (int) $this->stock,
            'low_stock_threshold' => $effectiveThreshold,
            'is_low_stock' => $this->stock <= $effectiveThreshold,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
