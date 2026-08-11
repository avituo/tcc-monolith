<?php

namespace Tests\Feature;

use Database\Seeders\BenchmarkDataset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BenchmarkDatasetSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_the_exact_deterministic_benchmark_dataset(): void
    {
        $this->seed();

        $this->assertSame(BenchmarkDataset::USER_COUNT, DB::table('users')->count());
        $this->assertSame(BenchmarkDataset::PRODUCT_COUNT, DB::table('products')->count());
        $this->assertSame(BenchmarkDataset::ORDER_COUNT, DB::table('orders')->count());
        $this->assertSame(BenchmarkDataset::ORDER_ITEM_COUNT, DB::table('order_product')->count());
        $this->assertSame(BenchmarkDataset::LOGICAL_FINGERPRINT, BenchmarkDataset::logicalFingerprint());

        $this->assertDatabaseHas('users', [
            'id' => 1,
            'name' => 'Benchmark User 001',
            'email' => BenchmarkDataset::BENCHMARK_EMAIL,
        ]);
        $this->assertTrue(Hash::check(BenchmarkDataset::BENCHMARK_PASSWORD, (string) DB::table('users')->where('id', 1)->value('password')));

        $product = BenchmarkDataset::product(1000);
        $this->assertDatabaseHas('products', [
            'id' => $product['logical_id'],
            'sku' => $product['sku'],
            'price' => $product['price'],
            'discount' => $product['discount'],
            'quantity' => $product['quantity'],
        ]);

        $order = BenchmarkDataset::order(5000);
        $this->assertDatabaseHas('orders', [
            'id' => $order['monolith_id'],
            'user_id' => $order['user_id'],
            'status' => $order['status'],
            'total_price' => $order['total_price'],
        ]);
        $this->assertSame(50, DB::table('orders')->where('user_id', 1)->count());
        $this->assertSame(BenchmarkDataset::expectedStatusDistribution(), $this->statusDistribution());

        $item = BenchmarkDataset::orderItem(5000, 1);
        $this->assertDatabaseHas('order_product', [
            'order_id' => 5000,
            'product_id' => $item['product_id'],
            'product_sku' => $item['product_sku'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'subtotal' => $item['subtotal'],
        ]);

        $this->artisan('experiment:dataset:validate')->assertSuccessful();
    }

    /**
     * @return array{pending: int, paid: int, cancelled: int}
     */
    private function statusDistribution(): array
    {
        $counts = DB::table('orders')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'pending' => (int) $counts->get('pending', 0),
            'paid' => (int) $counts->get('paid', 0),
            'cancelled' => (int) $counts->get('cancelled', 0),
        ];
    }
}
