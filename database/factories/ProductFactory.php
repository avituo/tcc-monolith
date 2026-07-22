<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(rand(2, 4), true);
        $price = fake()->randomFloat(2, 10, 5000);

        return [
            'name' => ucfirst($name),
            'description' => fake()->paragraph(),
            'slug' => Str::slug($name),
            'image' => '',
            'price' => $price,
            'discount' => fake()->randomFloat(2, 0, $price),
            'quantity' => fake()->numberBetween(0, 500),
            'sku' => strtoupper(fake()->bothify('SKU-####??')),
            'is_active' => fake()->boolean(90),
        ];
    }
}
