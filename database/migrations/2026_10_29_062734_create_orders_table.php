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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('subtotal', 12, 2)->unsigned();
            $table->decimal('tax_amount', 12, 2)->unsigned();
            $table->decimal('grand_total', 12, 2)->unsigned();
            $table->decimal('amount_given', 12, 2)->unsigned()->nullable();
            $table->decimal('change_returned', 12, 2)->unsigned()->nullable();
            $table->string('status', 32)->default('completed');
            $table->timestamps();

            $table->index('order_number');
            $table->index('customer_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
