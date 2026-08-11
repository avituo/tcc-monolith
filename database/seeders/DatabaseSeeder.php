<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the deterministic thesis benchmark dataset.
     */
    public function run(): void
    {
        DB::disableQueryLog();

        DB::transaction(function (): void {
            $this->seedUsers();
            $this->seedProducts();
            $this->seedOrders();
        });
    }

    private function seedUsers(): void
    {
        $users = [];

        for ($ordinal = 1; $ordinal <= BenchmarkDataset::USER_COUNT; $ordinal++) {
            $user = BenchmarkDataset::user($ordinal);
            $users[] = [
                'id' => $user['logical_id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'email_verified_at' => $user['email_verified_at'],
                'password' => BenchmarkDataset::BENCHMARK_PASSWORD_HASH,
                'remember_token' => null,
                'created_at' => $user['created_at'],
                'updated_at' => $user['updated_at'],
            ];
        }

        DB::table('users')->insert($users);
    }

    private function seedProducts(): void
    {
        $products = [];

        for ($ordinal = 1; $ordinal <= BenchmarkDataset::PRODUCT_COUNT; $ordinal++) {
            $product = BenchmarkDataset::product($ordinal);
            $products[] = [
                'id' => $product['logical_id'],
                'name' => $product['name'],
                'description' => $product['description'],
                'slug' => $product['slug'],
                'image' => $product['image'],
                'sku' => $product['sku'],
                'price' => $product['price'],
                'discount' => $product['discount'],
                'quantity' => $product['quantity'],
                'is_active' => $product['is_active'],
                'version' => $product['version'],
                'deleted_at' => null,
                'created_at' => $product['created_at'],
                'updated_at' => $product['updated_at'],
            ];
        }

        foreach (array_chunk($products, 500) as $chunk) {
            DB::table('products')->insert($chunk);
        }
    }

    private function seedOrders(): void
    {
        $orders = [];
        $items = [];
        $itemId = 1;

        for ($ordinal = 1; $ordinal <= BenchmarkDataset::ORDER_COUNT; $ordinal++) {
            $order = BenchmarkDataset::order($ordinal);
            $orders[] = [
                'id' => $order['monolith_id'],
                'idempotency_key' => $order['idempotency_key'],
                'idempotency_request_hash' => $order['idempotency_request_hash'],
                'name' => $order['name'],
                'user_id' => $order['user_id'],
                'user_name_snapshot' => $order['user_name_snapshot'],
                'user_email_snapshot' => $order['user_email_snapshot'],
                'total_price' => $order['total_price'],
                'status' => $order['status'],
                'reserved_at' => $order['reserved_at'],
                'paid_at' => $order['paid_at'],
                'cancelled_at' => $order['cancelled_at'],
                'created_at' => $order['created_at'],
                'updated_at' => $order['updated_at'],
            ];

            for ($position = 1; $position <= BenchmarkDataset::ITEMS_PER_ORDER; $position++) {
                $item = BenchmarkDataset::orderItem($ordinal, $position);
                $items[] = [
                    'id' => $itemId++,
                    'order_id' => $order['monolith_id'],
                    'product_id' => $item['product_id'],
                    'product_sku' => $item['product_sku'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'list_price' => $item['list_price'],
                    'discount' => $item['discount'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'],
                ];
            }

            if (count($orders) === 500) {
                DB::table('orders')->insert($orders);
                DB::table('order_product')->insert($items);
                $orders = [];
                $items = [];
            }
        }
    }
}
