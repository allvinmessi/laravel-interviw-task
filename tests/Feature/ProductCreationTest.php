<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_a_new_product_in_catalog(): void
    {
        $payload = [
            'name' => 'Britannia Good Day',
            'code' => 'BISC-002',
            'price' => 35.00,
            'tax_percentage' => 8.00,
            'stock' => 50,
            'low_stock_threshold' => 10,
        ];

        $response = $this->postJson(route('api.products.store'), $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Britannia Good Day')
            ->assertJsonPath('data.code', 'BISC-002')
            ->assertJsonPath('data.price', 35)
            ->assertJsonPath('data.stock', 50);

        $this->assertDatabaseHas('products', [
            'name' => 'Britannia Good Day',
            'code' => 'BISC-002',
            'stock' => 50,
        ]);
    }

    public function test_product_creation_validates_unique_code_and_required_fields(): void
    {
        Product::factory()->create([
            'code' => 'EXISTING-01',
        ]);

        $response = $this->postJson(route('api.products.store'), [
            'name' => 'Duplicate Code Product',
            'code' => 'EXISTING-01', // duplicate code
            'price' => -10,          // negative price
            'tax_percentage' => 150, // tax > 100
            'stock' => -5,           // negative stock
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'price', 'tax_percentage', 'stock']);
    }
}
