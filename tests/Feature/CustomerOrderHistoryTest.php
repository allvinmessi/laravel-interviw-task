<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_order_history_by_email_via_route_parameter(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Thomas Alva',
            'email' => 'thomas@example.com',
        ]);

        $product = Product::factory()->create([
            'name' => 'Colgate Toothpaste',
            'code' => 'TOOTH-001',
            'price' => 50.00,
        ]);

        // Create 2 orders for this customer
        $order1 = Order::create([
            'order_number' => 'ORD-TEST-001',
            'customer_id' => $customer->id,
            'subtotal' => 100.00,
            'tax_amount' => 8.00,
            'grand_total' => 108.00,
            'status' => 'completed',
        ]);
        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_code' => $product->code,
            'unit_price' => 50.00,
            'tax_percentage' => 8.00,
            'quantity' => 2,
            'subtotal' => 100.00,
            'tax_amount' => 8.00,
            'total' => 108.00,
        ]);

        $order2 = Order::create([
            'order_number' => 'ORD-TEST-002',
            'customer_id' => $customer->id,
            'subtotal' => 50.00,
            'tax_amount' => 4.00,
            'grand_total' => 54.00,
            'status' => 'completed',
        ]);
        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_code' => $product->code,
            'unit_price' => 50.00,
            'tax_percentage' => 8.00,
            'quantity' => 1,
            'subtotal' => 50.00,
            'tax_amount' => 4.00,
            'total' => 54.00,
        ]);

        $response = $this->getJson(route('api.customers.orders', ['email' => 'thomas@example.com']));

        $response->assertStatus(200)
            ->assertJsonPath('customer.email', 'thomas@example.com')
            ->assertJsonCount(2, 'orders')
            ->assertJsonPath('orders.0.order_number', 'ORD-TEST-002')
            ->assertJsonPath('orders.1.order_number', 'ORD-TEST-001');
    }

    public function test_can_fetch_order_history_by_email_via_query_parameter(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);

        $response = $this->getJson('/api/customers/orders?email=john.doe@example.com');

        $response->assertStatus(200)
            ->assertJsonPath('customer.email', 'john.doe@example.com')
            ->assertJsonCount(0, 'orders');
    }

    public function test_returns_404_when_customer_email_not_found(): void
    {
        $response = $this->getJson(route('api.customers.orders', ['email' => 'unknown@example.com']));

        $response->assertStatus(404)
            ->assertJson([
                'customer' => null,
                'orders' => [],
            ]);
    }

    public function test_returns_422_when_invalid_email_provided(): void
    {
        $response = $this->getJson('/api/customers/not-an-email/orders');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
