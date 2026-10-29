<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Colgate Toothpaste',
                'code' => 'TOOTH-001',
                'price' => 50.00,
                'tax_percentage' => 8.00,
                'stock' => 15,
                'low_stock_threshold' => 5,
            ],
            [
                'name' => 'Parle-G Biscuit',
                'code' => 'BISC-001',
                'price' => 10.00,
                'tax_percentage' => 8.00,
                'stock' => 30,
                'low_stock_threshold' => 10,
            ],
            [
                'name' => 'Bread',
                'code' => 'BAK-001',
                'price' => 40.00,
                'tax_percentage' => 5.00,
                'stock' => 4, // Low stock alert!
                'low_stock_threshold' => 5,
            ],
            [
                'name' => 'Milk 1L',
                'code' => 'DARY-001',
                'price' => 60.00,
                'tax_percentage' => 5.00,
                'stock' => 9, // Low stock alert if threshold = 10
                'low_stock_threshold' => 10,
            ],
            [
                'name' => 'Eggs (12)',
                'code' => 'EGG-001',
                'price' => 84.00,
                'tax_percentage' => 0.00,
                'stock' => 2, // Low stock alert!
                'low_stock_threshold' => 5,
            ],
            [
                'name' => 'Dettol Soap',
                'code' => 'SOAP-001',
                'price' => 45.00,
                'tax_percentage' => 18.00,
                'stock' => 3, // Low stock alert!
                'low_stock_threshold' => 5,
            ],
            [
                'name' => 'Tata Salt 1kg',
                'code' => 'SALT-001',
                'price' => 28.00,
                'tax_percentage' => 5.00,
                'stock' => 25,
                'low_stock_threshold' => 5,
            ],
            [
                'name' => 'Fortune Sunflower Oil 1L',
                'code' => 'OIL-001',
                'price' => 145.00,
                'tax_percentage' => 5.00,
                'stock' => 18,
                'low_stock_threshold' => 5,
            ],
            [
                'name' => 'Ashirvaad Atta 5kg',
                'code' => 'ATTA-001',
                'price' => 240.00,
                'tax_percentage' => 5.00,
                'stock' => 12,
                'low_stock_threshold' => 5,
            ],
            [
                'name' => 'Maggi Noodles 4-Pack',
                'code' => 'NOOD-001',
                'price' => 56.00,
                'tax_percentage' => 12.00,
                'stock' => 22,
                'low_stock_threshold' => 5,
            ],
            [
                'name' => 'Nescafe Classic Coffee 50g',
                'code' => 'BEV-001',
                'price' => 185.00,
                'tax_percentage' => 18.00,
                'stock' => 14,
                'low_stock_threshold' => 5,
            ],
            [
                'name' => 'Basmati Rice 1kg',
                'code' => 'RICE-001',
                'price' => 120.00,
                'tax_percentage' => 5.00,
                'stock' => 8,
                'low_stock_threshold' => 5,
            ],
        ];

        foreach ($products as $data) {
            Product::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }

        // Additional generated products via factory
        Product::factory()->count(5)->create();
    }
}
