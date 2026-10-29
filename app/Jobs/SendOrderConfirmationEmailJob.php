<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $orderId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $order = Order::with(['customer', 'items'])->find($this->orderId);

        if (!$order) {
            Log::warning("Order confirmation email skipped: Order #{$this->orderId} not found.");
            return;
        }

        $customer = $order->customer;
        $itemsList = $order->items->map(function ($item) {
            return "{$item->quantity}x {$item->product_name} (₹{$item->unit_price} + tax {$item->tax_percentage}%) = ₹{$item->total}";
        })->implode(", ");

        $logMessage = sprintf(
            "[SIMULATED EMAIL] Order Confirmation sent to %s <%s> for Order %s | Subtotal: ₹%s | Tax: ₹%s | Grand Total: ₹%s | Items: [%s]",
            $customer->name,
            $customer->email,
            $order->order_number,
            number_format($order->subtotal, 2),
            number_format($order->tax_amount, 2),
            number_format($order->grand_total, 2),
            $itemsList
        );

        Log::info($logMessage, [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'customer_email' => $customer->email,
            'customer_name' => $customer->name,
            'grand_total' => $order->grand_total,
            'items_count' => $order->items->count(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
