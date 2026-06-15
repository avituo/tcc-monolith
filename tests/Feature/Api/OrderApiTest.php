<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_are_paginated_with_user_and_product_count(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $order = Order::factory()->for($user)->create();
        $order->products()->attach($product, [
            'quantity' => 2,
            'unit_price' => 20,
            'subtotal' => 40,
        ]);

        $this->getJson('/api/orders?per_page=1')
            ->assertOk()
            ->assertJsonPath('per_page', 1)
            ->assertJsonPath('data.0.id', $order->id)
            ->assertJsonPath('data.0.user.id', $user->id)
            ->assertJsonPath('data.0.products_count', 1);
    }

    public function test_an_order_includes_user_products_and_pivot_values(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $order = Order::factory()->for($user)->create();
        $order->products()->attach($product, [
            'quantity' => 3,
            'unit_price' => 12.50,
            'subtotal' => 37.50,
        ]);

        $this->getJson('/api/orders/'.$order->id)
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('products.0.id', $product->id)
            ->assertJsonPath('products.0.pivot.quantity', 3)
            ->assertJsonPath('products.0.pivot.unit_price', '12.50')
            ->assertJsonPath('products.0.pivot.subtotal', '37.50');
    }

    public function test_an_order_can_be_created_with_calculated_prices(): void
    {
        $user = User::factory()->create();
        $firstProduct = Product::factory()->create(['price' => 19.95]);
        $secondProduct = Product::factory()->create(['price' => 5.50]);

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'products' => [
                ['product_id' => $firstProduct->id, 'quantity' => 2],
                ['product_id' => $secondProduct->id, 'quantity' => 3],
            ],
        ]);

        $order = Order::query()->sole();
        $response
            ->assertCreated()
            ->assertJsonPath('id', $order->id)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('total_price', '56.40')
            ->assertJsonCount(2, 'products');

        $this->assertSame($user->id, $order->user_id);
        $this->assertDatabaseHas('order_product', [
            'order_id' => $order->id,
            'product_id' => $firstProduct->id,
            'quantity' => 2,
            'unit_price' => 19.95,
            'subtotal' => 39.90,
        ]);
    }

    public function test_order_creation_validates_user_products_and_quantities(): void
    {
        $product = Product::factory()->create();

        $this->postJson('/api/orders', [
            'user_id' => 999999,
            'products' => [
                ['product_id' => $product->id, 'quantity' => 0],
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'user_id',
                'products.0.quantity',
                'products.1.product_id',
            ]);
    }

    public function test_order_status_can_be_updated_to_an_allowed_value(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $this->patchJson('/api/orders/'.$order->id.'/status', ['status' => 'paid'])
            ->assertOk()
            ->assertJsonPath('status', 'paid');

        $this->assertSame('paid', $order->refresh()->status);
    }

    public function test_order_status_rejects_unknown_values(): void
    {
        $order = Order::factory()->create();

        $this->patchJson('/api/orders/'.$order->id.'/status', ['status' => 'shipped'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }
}
