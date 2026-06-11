<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrderCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_view_order_pages(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('orders/Index')
                ->has('orders.data', 1));

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('orders/Show')
                ->where('order.id', $order->id));
    }

    public function test_authenticated_users_can_create_update_and_delete_orders(): void
    {
        $user = User::factory()->create();
        $firstProduct = Product::factory()->create([
            'price' => 100,
            'discount' => 10,
        ]);
        $secondProduct = Product::factory()->create([
            'price' => 25,
            'discount' => 0,
        ]);

        $response = $this->actingAs($user)->post(route('orders.store'), [
            'name' => 'Order 1001',
            'status' => 'pending',
            'items' => [
                ['product_id' => $firstProduct->id, 'quantity' => 2],
                ['product_id' => $secondProduct->id, 'quantity' => 1],
            ],
        ]);

        $order = Order::query()->sole();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame('205.00', $order->total_price);
        $this->assertDatabaseHas('order_product', [
            'order_id' => $order->id,
            'product_id' => $firstProduct->id,
            'quantity' => 2,
            'unit_price' => 90,
            'subtotal' => 180,
        ]);

        $this->actingAs($user)->put(route('orders.update', $order), [
            'name' => 'Order 1001 updated',
            'status' => 'paid',
            'items' => [
                ['product_id' => $secondProduct->id, 'quantity' => 3],
            ],
        ])->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertSame('75.00', $order->total_price);
        $this->assertSame('paid', $order->status);
        $this->assertCount(1, $order->products);

        $this->actingAs($user)
            ->delete(route('orders.destroy', $order))
            ->assertRedirect(route('orders.index'));
        $this->assertModelMissing($order);
        $this->assertDatabaseCount('order_product', 0);
    }

    public function test_order_validation_requires_distinct_existing_products(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)->post(route('orders.store'), [
            'name' => 'Invalid order',
            'status' => 'unknown',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 0],
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertSessionHasErrors([
            'status',
            'items.0.quantity',
            'items.1.product_id',
        ]);
    }
}
