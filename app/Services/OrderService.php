<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Jobs\SendOrderConfirmationEmailJob;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Create an order with concurrency-safe stock deduction and queue dispatch.
     *
     * @param array{
     *     customer_name?: string,
     *     customer_email?: string,
     *     customer?: array{name: string, email: string},
     *     items: array<int, array{product_id: int, quantity: int}>,
     *     amount_given?: float|int|null
     * } $data
     * @return Order
     * @throws InsufficientStockException
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // 1. Resolve customer
            $customerEmail = strtolower(trim($data['customer']['email'] ?? $data['customer_email']));
            $customerName = trim($data['customer']['name'] ?? $data['customer_name'] ?? '');

            $customer = Customer::firstOrCreate(
                ['email' => $customerEmail],
                ['name' => $customerName ?: 'Customer ' . Str::random(4)]
            );

            if ($customerName && $customer->name !== $customerName) {
                $customer->update(['name' => $customerName]);
            }

            // 2. Consolidate requested item quantities by product_id
            $consolidatedItems = [];
            foreach ($data['items'] as $item) {
                $pId = (int) $item['product_id'];
                $qty = (int) $item['quantity'];

                if (!isset($consolidatedItems[$pId])) {
                    $consolidatedItems[$pId] = 0;
                }
                $consolidatedItems[$pId] += $qty;
            }

            // 3. Concurrency Safety:
            // Always sort product IDs in ascending numerical order before acquiring row locks
            // to prevent distributed deadlocks between concurrent cross-product transactions.
            $productIds = array_keys($consolidatedItems);
            sort($productIds);

            // 4. Acquire pessimistic lock (SELECT ... FOR UPDATE) on target products
            $products = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // 5. Validate that all requested products exist and have sufficient stock
            foreach ($consolidatedItems as $productId => $requiredQuantity) {
                /** @var Product|null $product */
                $product = $products->get($productId);

                if (!$product) {
                    throw new \InvalidArgumentException("Product with ID {$productId} does not exist.");
                }

                if ($product->stock < $requiredQuantity) {
                    throw new InsufficientStockException($product, $requiredQuantity, $product->stock);
                }
            }

            // 6. Compute line items, order totals, and deduct stock
            $orderSubtotal = 0.00;
            $orderTaxAmount = 0.00;
            $orderItemsData = [];

            foreach ($data['items'] as $item) {
                $productId = (int) $item['product_id'];
                $quantity = (int) $item['quantity'];
                /** @var Product $product */
                $product = $products->get($productId);

                $unitPrice = (float) $product->price;
                $taxPercentage = (float) $product->tax_percentage;

                $lineSubtotal = round($unitPrice * $quantity, 2);
                $lineTax = round($lineSubtotal * ($taxPercentage / 100), 2);
                $lineTotal = round($lineSubtotal + $lineTax, 2);

                $orderSubtotal += $lineSubtotal;
                $orderTaxAmount += $lineTax;

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_code' => $product->code,
                    'unit_price' => $unitPrice,
                    'tax_percentage' => $taxPercentage,
                    'quantity' => $quantity,
                    'subtotal' => $lineSubtotal,
                    'tax_amount' => $lineTax,
                    'total' => $lineTotal,
                ];
            }

            // Deduct stock safely inside transaction
            foreach ($consolidatedItems as $productId => $requiredQuantity) {
                /** @var Product $product */
                $product = $products->get($productId);
                $product->decrement('stock', $requiredQuantity);
            }

            $grandTotal = round($orderSubtotal + $orderTaxAmount, 2);

            // Compute counter change breakdown if amount_given was provided
            $amountGiven = isset($data['amount_given']) && $data['amount_given'] !== null && $data['amount_given'] !== ''
                ? (float) $data['amount_given']
                : null;
            $changeReturned = ($amountGiven !== null && $amountGiven >= $grandTotal)
                ? round($amountGiven - $grandTotal, 2)
                : null;

            // Generate human-readable unique order number
            $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));

            // 7. Persist order
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'subtotal' => $orderSubtotal,
                'tax_amount' => $orderTaxAmount,
                'grand_total' => $grandTotal,
                'amount_given' => $amountGiven,
                'change_returned' => $changeReturned,
                'status' => 'completed',
            ]);

            // Persist order items with historical snapshots
            foreach ($orderItemsData as $itemData) {
                $order->items()->create($itemData);
            }

            // 8. Queue async notification after database transaction commits
            DB::afterCommit(function () use ($order) {
                SendOrderConfirmationEmailJob::dispatch($order->id);
            });

            return $order->load(['customer', 'items']);
        });
    }
}
