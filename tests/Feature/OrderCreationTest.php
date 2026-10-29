<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmationEmailJob;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_can_be_created_successfully(): void
    {
        Queue::fake();

        $product1 = Product::factory()->create([
            'name' => 'Colgate Toothpaste',
            'code' => 'TOOTH-001',
            'price' => 50.00,
            'tax_percentage' => 8.00,
            'stock' => 15,
        ]);

        $product2 = Product::factory()->create([
            'name' => 'Parle-G Biscuit',
            'code' => 'BISC-001',
            'price' => 10.00,
            'tax_percentage' => 8.00,
            'stock' => 30,
        ]);

        $payload = [
            'customer' => [
                'name' => 'Thomas Alva',
                'email' => 'thomas@example.com',
            ],
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 2],
                ['product_id' => $product2->id, 'quantity' => 5],
            ],
            'amount_given' => 250.00,
        ];

        $response = $this->postJson(route('api.orders.store'), $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_number',
                    'customer' => ['id', 'name', 'email'],
                    'subtotal',
                    'tax_amount',
                    'grand_total',
                    'amount_given',
                    'change_returned',
                    'status',
                    'items' => [
                        '*' => [
                            'id',
                            'product_id',
                            'product_name',
                            'product_code',
                            'unit_price',
                            'tax_percentage',
                            'quantity',
                            'subtotal',
                            'tax_amount',
                            'total',
                        ],
                    ],
                    'created_at',
                ],
            ]);

        // Colgate: 2 * 50 = 100 subtotal, 8% tax = 8.00, total = 108.00
        // Parle-G: 5 * 10 = 50 subtotal, 8% tax = 4.00, total = 54.00
        // Order Subtotal = 150.00, Tax = 12.00, Grand Total = 162.00
        // Amount Given = 250.00, Change Returned = 88.00
        $this->assertEquals(150.00, $response->json('data.subtotal'));
        $this->assertEquals(12.00, $response->json('data.tax_amount'));
        $this->assertEquals(162.00, $response->json('data.grand_total'));
        $this->assertEquals(250.00, $response->json('data.amount_given'));
        $this->assertEquals(88.00, $response->json('data.change_returned'));

        // Assert customer created in DB
        $this->assertDatabaseHas('customers', [
            'email' => 'thomas@example.com',
            'name' => 'Thomas Alva',
        ]);

        // Assert stock decremented
        $this->assertEquals(13, $product1->fresh()->stock);
        $this->assertEquals(25, $product2->fresh()->stock);

        // Assert queued job was dispatched
        Queue::assertPushed(SendOrderConfirmationEmailJob::class, 1);
    }

    public function test_order_creation_fails_when_stock_is_insufficient(): void
    {
        Queue::fake();

        $product = Product::factory()->create([
            'name' => 'Bread',
            'code' => 'BAK-001',
            'price' => 40.00,
            'tax_percentage' => 5.00,
            'stock' => 4,
        ]);

        $payload = [
            'customer' => [
                'name' => 'Rahul Sharma',
                'email' => 'rahul@example.com',
            ],
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10], // Requests 10, only 4 available
            ],
        ];

        $response = $this->postJson(route('api.orders.store'), $payload);

        $response->assertStatus(422)
            ->assertJson([
                'product_id' => $product->id,
                'available_stock' => 4,
                'requested_quantity' => 10,
            ]);

        // Assert stock was NOT deducted
        $this->assertEquals(4, $product->fresh()->stock);

        // Assert no order was created
        $this->assertDatabaseCount('orders', 0);

        // Assert email job was not pushed
        Queue::assertNotPushed(SendOrderConfirmationEmailJob::class);
    }

    public function test_customer_is_reused_when_email_already_exists(): void
    {
        Queue::fake();

        $existingCustomer = Customer::factory()->create([
            'name' => 'Priya Patel',
            'email' => 'priya@example.com',
        ]);

        $product = Product::factory()->create(['stock' => 20]);

        $payload = [
            'customer' => [
                'name' => 'Priya Patel',
                'email' => 'priya@example.com',
            ],
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        $response = $this->postJson(route('api.orders.store'), $payload);
        $response->assertStatus(201);

        // Customer table should still have only 1 customer
        $this->assertEquals(1, Customer::count());
        $order = Order::first();
        $this->assertEquals($existingCustomer->id, $order->customer_id);
    }

    public function test_validation_rejects_empty_items_or_invalid_quantity(): void
    {
        $payload = [
            'customer' => [
                'name' => 'Invalid Request',
                'email' => 'invalid@example.com',
            ],
            'items' => [],
        ];

        $response = $this->postJson(route('api.orders.store'), $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);

        $product = Product::factory()->create();

        // Zero or negative quantity
        $responseZero = $this->postJson(route('api.orders.store'), [
            'customer' => ['name' => 'Zero Qty', 'email' => 'zero@example.com'],
            'items' => [['product_id' => $product->id, 'quantity' => 0]],
        ]);
        $responseZero->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_validation_rejects_non_existent_product(): void
    {
        $payload = [
            'customer' => [
                'name' => 'Non Existent',
                'email' => 'none@example.com',
            ],
            'items' => [
                ['product_id' => 999999, 'quantity' => 1],
            ],
        ];

        $response = $this->postJson(route('api.orders.store'), $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.product_id']);
    }
}
