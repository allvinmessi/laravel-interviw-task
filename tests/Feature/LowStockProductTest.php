<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class LowStockProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_low_stock_products_using_default_threshold(): void
    {
        Config::set('inventory.low_stock_threshold', 5);

        // Product with stock <= 5 (low stock)
        $low1 = Product::factory()->create([
            'name' => 'Bread',
            'code' => 'BAK-001',
            'stock' => 4,
            'low_stock_threshold' => null,
        ]);

        $low2 = Product::factory()->create([
            'name' => 'Eggs (12)',
            'code' => 'EGG-001',
            'stock' => 2,
            'low_stock_threshold' => null,
        ]);

        // Product with stock > 5 (healthy stock)
        $healthy = Product::factory()->create([
            'name' => 'Colgate Toothpaste',
            'code' => 'TOOTH-001',
            'stock' => 15,
            'low_stock_threshold' => null,
        ]);

        $response = $this->getJson(route('api.products.low-stock'));

        $response->assertStatus(200)
            ->assertJsonPath('effective_threshold', 5)
            ->assertJsonPath('count', 2)
            ->assertJsonCount(2, 'products');

        $codes = collect($response->json('products'))->pluck('code');
        $this->assertTrue($codes->contains('BAK-001'));
        $this->assertTrue($codes->contains('EGG-001'));
        $this->assertFalse($codes->contains('TOOTH-001'));
    }

    public function test_can_override_threshold_via_query_parameter(): void
    {
        Config::set('inventory.low_stock_threshold', 5);

        Product::factory()->create([
            'name' => 'Milk 1L',
            'code' => 'DARY-001',
            'stock' => 9,
            'low_stock_threshold' => null,
        ]);

        // At default threshold 5, Milk with 9 stock is NOT returned
        $res1 = $this->getJson(route('api.products.low-stock'));
        $res1->assertJsonPath('count', 0);

        // With threshold=10, Milk with 9 stock IS returned
        $res2 = $this->getJson(route('api.products.low-stock', ['threshold' => 10]));
        $res2->assertStatus(200)
            ->assertJsonPath('effective_threshold', 10)
            ->assertJsonPath('count', 1)
            ->assertJsonPath('products.0.code', 'DARY-001');
    }

    public function test_respects_per_product_low_stock_threshold_override(): void
    {
        Config::set('inventory.low_stock_threshold', 5);

        // Per-product threshold is 10, stock is 8 (should be low stock even though global default is 5)
        Product::factory()->create([
            'name' => 'Special Item',
            'code' => 'SPEC-001',
            'stock' => 8,
            'low_stock_threshold' => 10,
        ]);

        $response = $this->getJson(route('api.products.low-stock'));
        $response->assertStatus(200)
            ->assertJsonPath('count', 1)
            ->assertJsonPath('products.0.code', 'SPEC-001');
    }
}
