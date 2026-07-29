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

        $this->actingAs($user)->getJson('/api/v1/orders?per_page=1')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('data.0.id', $order->id)
            ->assertJsonPath('data.0.user_id', (string) $user->id)
            ->assertJsonPath('data.0.items_count', 1);
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

        $this->actingAs($user)->getJson('/api/v1/orders/'.$order->id)
            ->assertOk()
            ->assertJsonPath('data.user_id', (string) $user->id)
            ->assertJsonPath('data.items.0.product_id', $product->id)
            ->assertJsonPath('data.items.0.quantity', 3)
            ->assertJsonPath('data.items.0.unit_price', '12.50')
            ->assertJsonPath('data.items.0.subtotal', '37.50');
    }

    public function test_an_order_can_be_created_with_calculated_prices(): void
    {
        $user = User::factory()->create();
        $firstProduct = Product::factory()->create(['price' => 19.95, 'discount' => 1.95, 'quantity' => 10, 'is_active' => true]);
        $secondProduct = Product::factory()->create(['price' => 5.50, 'discount' => 0, 'quantity' => 10, 'is_active' => true]);

        $response = $this->actingAs($user)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/orders', [
                'items' => [
                    ['product_id' => $firstProduct->id, 'quantity' => 2],
                    ['product_id' => $secondProduct->id, 'quantity' => 3],
                ],
            ]);

        $order = Order::query()->sole();
        $response
            ->assertCreated()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total_price', '52.50')
            ->assertJsonCount(2, 'data.items');

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
        $this->actingAs($user)->withHeader('Idempotency-Key', (string) Str::uuid())->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 0],
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.0.quantity',
                'items.1.product_id',
            ]);
    }

    public function test_order_status_can_be_updated_to_an_allowed_value(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['status' => 'pending']);

        $this->actingAs($user)->postJson('/api/v1/internal/orders/'.$order->id.'/paid')
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $this->assertSame('paid', $order->refresh()->status);
    }

    public function test_only_pending_orders_can_be_changed(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['status' => 'paid']);

        $this->actingAs($user)->postJson('/api/v1/orders/'.$order->id.'/cancel')
            ->assertNotFound();
    }

    public function test_a_user_cannot_view_another_users_order(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->create())
            ->getJson('/api/v1/orders/'.$order->id)
            ->assertNotFound();
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
        $payload = ['items' => [['product_id' => $product->id, 'quantity' => 2]]];

        $first = $this->actingAs($user)->withHeader('Idempotency-Key', $idempotencyKey)->postJson('/api/v1/orders', $payload);
        $second = $this->actingAs($user)->withHeader('Idempotency-Key', $idempotencyKey)->postJson('/api/v1/orders', $payload);

        $first->assertCreated();
        $second->assertCreated()->assertJsonPath('data.id', $first->json('data.id'));
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(2, $product->refresh()->quantity);
    }

    public function test_pending_order_can_be_updated_and_deleted(): void
    {
        $user = User::factory()->create();
        $firstProduct = Product::factory()->create(['quantity' => 5, 'is_active' => true]);
        $secondProduct = Product::factory()->create(['price' => 15, 'discount' => 5, 'quantity' => 5, 'is_active' => true]);
        $order = app(OrderService::class)->createOrder(
            $user,
            [['product_id' => $firstProduct->id, 'quantity' => 2]],
            'Original order',
        );

        $this->actingAs($user)->putJson('/api/v1/orders/'.$order->id, [
            'name' => 'Updated order',
            'items' => [['product_id' => $secondProduct->id, 'quantity' => 3]],
        ])->assertOk()
            ->assertJsonPath('data.name', 'Updated order')
            ->assertJsonPath('data.total_price', '30.00');

        $this->assertSame(5, $firstProduct->refresh()->quantity);
        $this->assertSame(2, $secondProduct->refresh()->quantity);
        $this->actingAs($user)->deleteJson('/api/v1/orders/'.$order->id)->assertNoContent();
        $this->assertModelMissing($order);
        $this->assertSame(5, $secondProduct->refresh()->quantity);
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
            ->postJson('/api/v1/orders/'.$order->id.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame(3, $product->refresh()->quantity);
    }
}
