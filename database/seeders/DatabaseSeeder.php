<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(100)->create();
        Product::factory(500)->create();
        Order::factory()
            ->count(1000)
            ->create()
            ->each(function ($order) {
                $products = Product::inRandomOrder()
                    ->limit(rand(1, 5))
                    ->get();

                foreach ($products as $product) {
                    $qty = rand(1, 3);

                    $order->products()->attach(
                        $product->id,
                        [
                            'quantity' => $qty,
                            'unit_price' => $product->price,
                            'subtotal' => $qty * $product->price,
                        ]
                    );
                }

                $order->update([
                    'total_price' => $order->products
                        ->sum(fn ($p) => $p->pivot->subtotal),
                ]);
            });
    }
}
