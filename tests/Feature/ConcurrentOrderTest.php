<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrentOrderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that when stock is 1 and two orders arrive for that product,
     * exactly one succeeds and the other fails cleanly with InsufficientStockException.
     */
    public function test_concurrent_order_requests_prevent_overselling_when_single_unit_remains(): void
    {
        $product = Product::factory()->create([
            'name' => 'Last Limited Item',
            'code' => 'LTD-001',
            'price' => 100.00,
            'tax_percentage' => 18.00,
            'stock' => 1,
        ]);

        $orderService = app(OrderService::class);

        $order1Payload = [
            'customer' => ['name' => 'Buyer One', 'email' => 'buyer1@example.com'],
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];

        $order2Payload = [
            'customer' => ['name' => 'Buyer Two', 'email' => 'buyer2@example.com'],
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];

        // Buyer 1 places order
        $order1 = $orderService->createOrder($order1Payload);
        $this->assertNotNull($order1);
        $this->assertEquals(0, $product->fresh()->stock);

        // Buyer 2 places order immediately after
        $failedCleanly = false;
        try {
            $orderService->createOrder($order2Payload);
        } catch (InsufficientStockException $e) {
            $failedCleanly = true;
            $this->assertEquals($product->id, $e->getProduct()->id);
            $this->assertEquals(0, $e->getAvailableStock());
            $this->assertEquals(1, $e->getRequestedQuantity());
        }

        $this->assertTrue($failedCleanly, 'Second order should have failed cleanly with InsufficientStockException');

        // Verify database state: exactly 1 order created, stock is 0 (never negative)
        $this->assertEquals(1, Order::count());
        $this->assertEquals(0, $product->fresh()->stock);
    }

    /**
     * Test that two concurrent transactions serialized via lockForUpdate
     * properly evaluate updated stock and prevent overselling.
     */
    public function test_pessimistic_locking_evaluates_committed_stock_state(): void
    {
        $product = Product::factory()->create([
            'name' => 'High Demand Gadget',
            'code' => 'GADGET-001',
            'stock' => 1,
        ]);

        $results = [];

        // Simulate concurrent attempts
        for ($i = 1; $i <= 2; $i++) {
            $email = "user{$i}@example.com";
            $response = $this->postJson(route('api.orders.store'), [
                'customer' => ['name' => "User {$i}", 'email' => $email],
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
            ]);
            $results[] = $response->status();
        }

        // Exactly one should succeed (201) and one should fail (422)
        $this->assertContains(201, $results);
        $this->assertContains(422, $results);
        $this->assertEquals([201, 422], $results);

        // Final stock must be exactly 0, not -1
        $this->assertEquals(0, $product->fresh()->stock);
        $this->assertEquals(1, Order::count());
    }

    /**
     * Test deadlock prevention by ordering product locks.
     */
    public function test_multiple_products_lock_ordering_prevents_deadlocks(): void
    {
        $productA = Product::factory()->create(['stock' => 10]);
        $productB = Product::factory()->create(['stock' => 10]);

        $orderService = app(OrderService::class);

        // Order with items in order [B, A] - should be internally sorted to [A, B] before locking
        $order = $orderService->createOrder([
            'customer' => ['name' => 'Sorted Buyer', 'email' => 'sorted@example.com'],
            'items' => [
                ['product_id' => $productB->id, 'quantity' => 2],
                ['product_id' => $productA->id, 'quantity' => 3],
            ],
        ]);

        $this->assertNotNull($order);
        $this->assertEquals(7, $productA->fresh()->stock);
        $this->assertEquals(8, $productB->fresh()->stock);
    }
}
