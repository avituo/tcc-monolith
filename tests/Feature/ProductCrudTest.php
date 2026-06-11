<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_view_product_pages(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->get(route('products.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('products/Index')
                ->has('products.data', 1));

        $this->actingAs($user)
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('products/Show')
                ->where('product.id', $product->id));
    }

    public function test_authenticated_users_can_create_update_and_delete_products(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Mechanical Keyboard',
            'description' => 'A compact mechanical keyboard.',
            'slug' => 'mechanical-keyboard',
            'image' => 'https://example.com/keyboard.jpg',
            'price' => 150,
            'discount' => 25,
            'quantity' => 12,
            'sku' => 'KEY-001',
            'is_active' => true,
        ]);

        $product = Product::query()->sole();
        $response->assertRedirect(route('products.show', $product));
        $this->assertSame('150.00', $product->price);

        $this->actingAs($user)->put(route('products.update', $product), [
            'name' => 'Mechanical Keyboard Pro',
            'description' => 'An updated keyboard.',
            'slug' => 'mechanical-keyboard-pro',
            'image' => null,
            'price' => 175,
            'discount' => 20,
            'quantity' => 8,
            'sku' => 'KEY-001',
            'is_active' => false,
        ])->assertRedirect(route('products.show', $product));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Mechanical Keyboard Pro',
            'image' => '',
            'is_active' => false,
        ]);

        $order = Order::factory()->for($user)->create();
        $order->products()->attach($product, [
            'quantity' => 1,
            'unit_price' => 155,
            'subtotal' => 155,
        ]);

        $this->actingAs($user)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));
        $this->assertModelMissing($product);
        $this->assertDatabaseCount('order_product', 0);
    }

    public function test_product_validation_rejects_invalid_pricing_and_duplicate_skus(): void
    {
        $user = User::factory()->create();
        Product::factory()->create(['sku' => 'DUPLICATE']);

        $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Invalid product',
            'description' => 'Invalid pricing.',
            'slug' => 'invalid-product',
            'image' => null,
            'price' => 10,
            'discount' => 11,
            'quantity' => -1,
            'sku' => 'DUPLICATE',
            'is_active' => true,
        ])->assertSessionHasErrors(['discount', 'quantity', 'sku']);
    }
}
