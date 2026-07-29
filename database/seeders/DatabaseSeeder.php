<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::disableQueryLog();

        User::factory(100)->create();
        $products = Product::factory(1000)->create();

        Order::factory()
            ->count(5000)
            ->create()
            ->each(function (Order $order) use ($products): void {
                $selectedProducts = $products->random(rand(1, 5));

                $attachData = [];
                $totalPrice = 0;

                foreach ($selectedProducts as $product) {
                    $qty = rand(1, 3);
                    $subtotal = $qty * $product->price;
                    $totalPrice += $subtotal;

                    $attachData[$product->id] = [
                        'quantity' => $qty,
                        'unit_price' => $product->price,
                        'subtotal' => $subtotal,
                    ];
                }

                $order->products()->attach($attachData);

                $order->updateQuietly([
                    'total_price' => $totalPrice,
                ]);
            });
    }
}
