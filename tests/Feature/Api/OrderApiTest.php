<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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

        $this->actingAs($user)->getJson('/api/orders?per_page=1')
            ->assertOk()
            ->assertJsonPath('per_page', 1)
            ->assertJsonPath('data.0.id', $order->id)
            ->assertJsonPath('data.0.user_id', $user->id)
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

        $this->actingAs($user)->getJson('/api/orders/'.$order->id)
            ->assertOk()
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonPath('products.0.id', $product->id)
            ->assertJsonPath('products.0.pivot.quantity', 3)
            ->assertJsonPath('products.0.pivot.unit_price', '12.50')
            ->assertJsonPath('products.0.pivot.subtotal', '37.50');
    }

    public function test_an_order_can_be_created_with_calculated_prices(): void
    {
        $user = User::factory()->create();
        $firstProduct = Product::factory()->create(['price' => 19.95, 'discount' => 1.95, 'quantity' => 10, 'is_active' => true]);
        $secondProduct = Product::factory()->create(['price' => 5.50, 'discount' => 0, 'quantity' => 10, 'is_active' => true]);

        $response = $this->actingAs($user)->postJson('/api/orders', [
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
            ->assertJsonPath('total_price', '52.50')
            ->assertJsonCount(2, 'products');

        $this->assertSame($user->id, $order->user_id);
        $this->assertDatabaseHas('order_product', [
            'order_id' => $order->id,
            'product_id' => $firstProduct->id,
            'quantity' => 2,
            'unit_price' => 18,
            'subtotal' => 36,
        ]);
        $this->assertSame(8, $firstProduct->refresh()->quantity);
    }

    public function test_order_creation_validates_user_products_and_quantities(): void
    {
        $product = Product::factory()->create();

        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/orders', [
            'products' => [
                ['product_id' => $product->id, 'quantity' => 0],
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'products.0.quantity',
                'products.1.product_id',
            ]);
    }

    public function test_order_status_can_be_updated_to_an_allowed_value(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['status' => 'pending']);

        $this->actingAs($user)->patchJson('/api/orders/'.$order->id.'/status', ['status' => 'paid'])
            ->assertOk()
            ->assertJsonPath('status', 'paid');

        $this->assertSame('paid', $order->refresh()->status);
    }

    public function test_order_status_rejects_unknown_values(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['status' => 'pending']);

        $this->actingAs($user)->patchJson('/api/orders/'.$order->id.'/status', ['status' => 'shipped'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_a_user_cannot_view_another_users_order(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->create())
            ->getJson('/api/orders/'.$order->id)
            ->assertForbidden();
    }

    public function test_order_creation_is_idempotent_and_uses_identity_from_session(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 20,
            'discount' => 5,
            'quantity' => 4,
            'is_active' => true,
        ]);
        $idempotencyKey = (string) Str::uuid();
        $payload = ['products' => [['product_id' => $product->id, 'quantity' => 2]]];

        $first = $this->actingAs($user)->withHeader('Idempotency-Key', $idempotencyKey)->postJson('/api/orders', $payload);
        $second = $this->actingAs($user)->withHeader('Idempotency-Key', $idempotencyKey)->postJson('/api/orders', $payload);

        $first->assertCreated();
        $second->assertCreated()->assertJsonPath('id', $first->json('id'));
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(2, $product->refresh()->quantity);
    }

    public function test_cancelling_an_order_releases_reserved_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 3, 'is_active' => true]);
        $order = app(OrderService::class)->createOrder(
            $user,
            [['product_id' => $product->id, 'quantity' => 2]],
        );

        $this->actingAs($user)
            ->patchJson('/api/orders/'.$order->id.'/status', ['status' => 'cancelled'])
            ->assertOk()
            ->assertJsonPath('status', 'cancelled');

        $this->assertSame(3, $product->refresh()->quantity);
    }
}
