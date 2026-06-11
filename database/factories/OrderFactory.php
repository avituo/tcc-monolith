<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'name' => 'Pedido #'.fake()->unique()->numberBetween(1000, 99999),
            'user_id' => User::factory(),
            'total_price' => fake()->randomFloat(2, 50, 10000),
            'status' => fake()->randomElement([
                'pending',
                'paid',
                'cancelled',
            ]),
        ];
    }
}
