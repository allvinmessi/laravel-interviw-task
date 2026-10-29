<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $counterCustomers = [
            [
                'name' => 'Thomas Alva',
                'email' => 'thomas@example.com',
            ],
            [
                'name' => 'Rahul Sharma',
                'email' => 'rahul.sharma@example.com',
            ],
            [
                'name' => 'Priya Patel',
                'email' => 'priya.patel@example.com',
            ],
            [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
            ],
            [
                'name' => 'Anita Desai',
                'email' => 'anita.desai@example.com',
            ],
        ];

        foreach ($counterCustomers as $data) {
            Customer::updateOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name']]
            );
        }

        // Additional generated customers via factory
        Customer::factory()->count(5)->create();
    }
}
