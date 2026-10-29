<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_product_stock_price_and_gst_tax(): void
    {
        $product = Product::factory()->create([
            'name' => 'Colgate Toothpaste',
            'code' => 'TOOTH-001',
            'price' => 50.00,
            'tax_percentage' => 8.00,
            'stock' => 15,
            'low_stock_threshold' => 5,
        ]);

        $payload = [
            'stock' => 35,          // Updated stock
            'price' => 55.00,       // Updated price
            'tax_percentage' => 12.00, // Updated GST/tax
            'low_stock_threshold' => 8,
        ];

        $response = $this->putJson(route('api.products.update', $product), $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.stock', 35)
            ->assertJsonPath('data.price', 55)
            ->assertJsonPath('data.tax_percentage', 12);

        $fresh = $product->fresh();
        $this->assertEquals(35, $fresh->stock);
        $this->assertEquals(55.00, $fresh->price);
        $this->assertEquals(12.00, $fresh->tax_percentage);
        $this->assertEquals('TOOTH-001', $fresh->code); // Unchanged code remains intact
    }

    public function test_updating_product_with_its_own_code_does_not_trigger_duplicate_error(): void
    {
        $product = Product::factory()->create([
            'name' => 'Parle-G Biscuit',
            'code' => 'BISC-001',
            'price' => 10.00,
            'stock' => 20,
        ]);

        $response = $this->putJson(route('api.products.update', $product), [
            'name' => 'Parle-G Gold Biscuit',
            'code' => 'BISC-001', // Same code as current product
            'price' => 12.00,
            'stock' => 40,
            'tax_percentage' => 8.00,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Parle-G Gold Biscuit');
    }

    public function test_duplicate_check_prevents_updating_code_to_another_products_existing_code(): void
    {
        $product1 = Product::factory()->create([
            'name' => 'Bread White',
            'code' => 'BAK-001',
        ]);

        $product2 = Product::factory()->create([
            'name' => 'Bread Brown',
            'code' => 'BAK-002',
        ]);

        // Attempt to rename product2's code to product1's code ('BAK-001')
        $response = $this->putJson(route('api.products.update', $product2), [
            'code' => 'BAK-001',
            'price' => 45.00,
            'stock' => 10,
            'tax_percentage' => 5.00,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        // Assert code in database was NOT changed
        $this->assertEquals('BAK-002', $product2->fresh()->code);
    }

    public function test_update_validates_numeric_and_positive_constraints(): void
    {
        $product = Product::factory()->create();

        $response = $this->putJson(route('api.products.update', $product), [
            'stock' => -10,          // Negative stock not allowed
            'price' => 0,            // Price must be > 0
            'tax_percentage' => 110, // Tax cannot exceed 100%
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stock', 'price', 'tax_percentage']);
    }
}
