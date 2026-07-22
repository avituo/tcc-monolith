<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_are_paginated_and_can_be_filtered(): void
    {
        $this->actingAs(User::factory()->create());
        Product::factory()->create([
            'name' => 'Active Keyboard',
            'is_active' => true,
        ]);
        Product::factory()->create([
            'name' => 'Inactive Keyboard',
            'is_active' => false,
        ]);
        Product::factory()->create([
            'name' => 'Active Mouse',
            'is_active' => true,
        ]);

        $this->getJson('/api/products?name=Keyboard&is_active=true&per_page=1')
            ->assertOk()
            ->assertJsonPath('per_page', 1)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.name', 'Active Keyboard')
            ->assertJsonPath('data.0.is_active', true);
    }

    public function test_product_page_size_is_bounded(): void
    {
        $this->actingAs(User::factory()->create());
        Product::factory()->count(2)->create();

        $this->getJson('/api/products?per_page=0')
            ->assertOk()
            ->assertJsonPath('per_page', 1);
    }

    public function test_products_require_authentication(): void
    {
        $this->getJson('/api/products')->assertUnauthorized();
    }
}
