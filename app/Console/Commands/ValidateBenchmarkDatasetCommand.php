<?php

namespace App\Console\Commands;

use Database\Seeders\BenchmarkDataset;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('experiment:dataset:validate')]
#[Description('Validate and print the deterministic thesis benchmark dataset')]
class ValidateBenchmarkDatasetCommand extends Command
{
    public function handle(): int
    {
        $statusCounts = DB::table('orders')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (mixed $count): int => (int) $count);
        $statusDistribution = [
            'pending' => $statusCounts->get('pending', 0),
            'paid' => $statusCounts->get('paid', 0),
            'cancelled' => $statusCounts->get('cancelled', 0),
        ];

        $ordersPerUser = DB::table('orders')
            ->selectRaw('user_id, COUNT(*) as aggregate')
            ->groupBy('user_id')
            ->pluck('aggregate')
            ->map(fn (mixed $count): int => (int) $count);

        $actual = [
            'user_count' => DB::table('users')->count(),
            'product_count' => DB::table('products')->count(),
            'order_count' => DB::table('orders')->count(),
            'order_item_count' => DB::table('order_product')->count(),
        ];

        $this->line('database='.$this->databaseName());
        foreach ($actual as $name => $value) {
            $this->line(sprintf('%s=%d', $name, $value));
        }
        $this->line('status_distribution='.json_encode($statusDistribution, JSON_THROW_ON_ERROR));
        $this->line(sprintf('orders_per_user_min=%d', (int) $ordersPerUser->min()));
        $this->line(sprintf('orders_per_user_max=%d', (int) $ordersPerUser->max()));
        $logicalFingerprint = BenchmarkDataset::logicalFingerprint();
        $this->line('logical_fingerprint='.$logicalFingerprint);
        $this->line('order_mapping_1=1');
        $this->line('order_mapping_5000=5000');

        $expected = [
            'user_count' => BenchmarkDataset::USER_COUNT,
            'product_count' => BenchmarkDataset::PRODUCT_COUNT,
            'order_count' => BenchmarkDataset::ORDER_COUNT,
            'order_item_count' => BenchmarkDataset::ORDER_ITEM_COUNT,
        ];

        if ($logicalFingerprint !== BenchmarkDataset::LOGICAL_FINGERPRINT
            || $actual !== $expected
            || $statusDistribution !== BenchmarkDataset::expectedStatusDistribution()
            || $ordersPerUser->count() !== BenchmarkDataset::USER_COUNT
            || $ordersPerUser->min() !== 50
            || $ordersPerUser->max() !== 50
        ) {
            $this->error('benchmark_dataset=INVALID');

            return self::FAILURE;
        }

        $this->info('benchmark_dataset=VALID');

        return self::SUCCESS;
    }

    private function databaseName(): string
    {
        return (string) DB::connection()->getDatabaseName();
    }
}
