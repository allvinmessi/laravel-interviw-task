<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class InventoryService
{
    /**
     * Retrieve all products below or at the low stock threshold.
     *
     * @param int|null $customThreshold
     * @return Collection<int, Product>
     */
    public function getLowStockProducts(?int $customThreshold = null): Collection
    {
        return Product::query()
            ->lowStock($customThreshold)
            ->orderBy('stock', 'asc')
            ->get();
    }
}
