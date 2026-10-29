<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            
            // Historical snapshots to preserve invoice accuracy even if product details change later
            $table->string('product_name');
            $table->string('product_code');
            $table->decimal('unit_price', 10, 2)->unsigned();
            $table->decimal('tax_percentage', 5, 2)->unsigned();
            $table->unsignedInteger('quantity');
            $table->decimal('subtotal', 12, 2)->unsigned();
            $table->decimal('tax_amount', 12, 2)->unsigned();
            $table->decimal('total', 12, 2)->unsigned();
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
