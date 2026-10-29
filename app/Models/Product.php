<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'price',
        'tax_percentage',
        'stock',
        'low_stock_threshold',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'stock' => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    /**
     * Scope query to products that have stock at or below the effective low-stock threshold.
     */
    public function scopeLowStock(Builder $query, ?int $customThreshold = null): Builder
    {
        $defaultThreshold = $customThreshold ?? (int) config('inventory.low_stock_threshold', 5);

        return $query->where(function (Builder $subQuery) use ($defaultThreshold) {
            $subQuery->where(function (Builder $q) use ($defaultThreshold) {
                // If per-product threshold is set, use it; otherwise use default
                $q->whereNotNull('low_stock_threshold')
                  ->whereColumn('stock', '<=', 'low_stock_threshold');
            })->orWhere(function (Builder $q) use ($defaultThreshold) {
                $q->whereNull('low_stock_threshold')
                  ->where('stock', '<=', $defaultThreshold);
            });
        });
    }

    /**
     * Get the order items associated with the product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
