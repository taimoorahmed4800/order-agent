<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        Order::truncate();

        $orders = [
            [
                'order_number' => 'SE-1001', 'customer_name' => 'Ali Raza', 'customer_email' => 'ali@example.com',
                'status' => 'shipped', 'items' => 'Wireless Mouse x1, USB Hub x1', 'total' => 3200,
                'tracking_number' => 'TRK555123', 'estimated_delivery' => now()->addDays(2)->toDateString(),
            ],
            [
                'order_number' => 'SE-1002', 'customer_name' => 'Sara Khan', 'customer_email' => 'sara@example.com',
                'status' => 'processing', 'items' => 'Running Shoes x1', 'total' => 6500,
                'estimated_delivery' => now()->addDays(5)->toDateString(),
            ],
            [
                'order_number' => 'SE-1003', 'customer_name' => 'Hamza Malik', 'customer_email' => 'hamza@example.com',
                'status' => 'delivered', 'items' => 'Bluetooth Speaker x1', 'total' => 4800,
                'tracking_number' => 'TRK555456', 'delivered_at' => now()->subDays(5),
            ],
            [
                'order_number' => 'SE-1004', 'customer_name' => 'Ayesha Noor', 'customer_email' => 'ayesha@example.com',
                'status' => 'delivered', 'items' => 'Backpack x1', 'total' => 2900,
                'tracking_number' => 'TRK555789', 'delivered_at' => now()->subDays(30),
            ],
            [
                'order_number' => 'SE-1005', 'customer_name' => 'Bilal Ahmed', 'customer_email' => 'bilal@example.com',
                'status' => 'cancelled', 'items' => 'Desk Lamp x2', 'total' => 3600,
            ],
        ];

        foreach ($orders as $order) {
            Order::create($order);
        }
    }
}
