<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'code' => 'SKU-' . strtoupper(fake()->unique()->bothify('??###')),
            'price' => fake()->randomFloat(2, 10, 500),
            'tax_percentage' => fake()->randomElement([0.00, 5.00, 8.00, 12.00, 18.00]),
            'stock' => fake()->numberBetween(0, 50),
            'low_stock_threshold' => 5,
        ];
    }

    /**
     * State for low stock products.
     */
    public function lowStock(int $stock = 2): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => $stock,
            'low_stock_threshold' => 5,
        ]);
    }

    /**
     * State for out of stock products.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }
}
