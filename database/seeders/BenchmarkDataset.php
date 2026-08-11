<?php

namespace Database\Seeders;

use InvalidArgumentException;

final class BenchmarkDataset
{
    public const USER_COUNT = 100;

    public const PRODUCT_COUNT = 1000;

    public const ORDER_COUNT = 5000;

    public const ITEMS_PER_ORDER = 2;

    public const ORDER_ITEM_COUNT = self::ORDER_COUNT * self::ITEMS_PER_ORDER;

    public const BENCHMARK_EMAIL = 'benchmark-user-001@example.test';

    public const BENCHMARK_PASSWORD = 'benchmark-password';

    public const BENCHMARK_PASSWORD_HASH = '$2y$12$Ihd9PKFnQpiHxq7tRWem/.WN16iivrWWeIIsrJtvezynj9xuBZNuG';

    public const LOGICAL_FINGERPRINT = '44cb257fd1b0fbd23dde712b5250ca3d002eded4c2a9e911c74c80c94eb073a7';

    private const BASE_TIMESTAMP = 1767225600;

    /**
     * @return array{
     *     logical_id: int,
     *     name: string,
     *     email: string,
     *     email_verified_at: string,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    public static function user(int $ordinal): array
    {
        self::guardOrdinal($ordinal, self::USER_COUNT, 'user');

        $timestamp = self::timestamp($ordinal);

        return [
            'logical_id' => $ordinal,
            'name' => sprintf('Benchmark User %03d', $ordinal),
            'email' => sprintf('benchmark-user-%03d@example.test', $ordinal),
            'email_verified_at' => $timestamp,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    /**
     * @return array{
     *     logical_id: int,
     *     name: string,
     *     description: string,
     *     slug: string,
     *     image: null,
     *     sku: string,
     *     price: string,
     *     discount: string,
     *     quantity: int,
     *     is_active: bool,
     *     version: int,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    public static function product(int $ordinal): array
    {
        self::guardOrdinal($ordinal, self::PRODUCT_COUNT, 'product');

        $priceInCents = 1000 + ((($ordinal - 1) % 100) * 125);
        $discountInCents = (($ordinal - 1) % 5) * 50;
        $timestamp = self::timestamp(1000 + $ordinal);

        return [
            'logical_id' => $ordinal,
            'name' => sprintf('Benchmark Product %04d', $ordinal),
            'description' => sprintf('Deterministic benchmark product %04d', $ordinal),
            'slug' => sprintf('benchmark-product-%04d', $ordinal),
            'image' => null,
            'sku' => sprintf('TCC-%04d', $ordinal),
            'price' => self::decimal($priceInCents),
            'discount' => self::decimal($discountInCents),
            'quantity' => 100000 + $ordinal,
            'is_active' => true,
            'version' => 1,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    /**
     * @return array{
     *     logical_id: int,
     *     monolith_id: int,
     *     microservice_id: string,
     *     idempotency_key: string,
     *     idempotency_request_hash: string,
     *     name: string,
     *     user_id: int,
     *     user_name_snapshot: string,
     *     user_email_snapshot: string,
     *     total_price: string,
     *     status: string,
     *     reserved_at: string,
     *     paid_at: ?string,
     *     cancelled_at: ?string,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    public static function order(int $ordinal): array
    {
        self::guardOrdinal($ordinal, self::ORDER_COUNT, 'order');

        $userOrdinal = (($ordinal - 1) % self::USER_COUNT) + 1;
        $user = self::user($userOrdinal);
        $status = self::orderStatus($ordinal);
        $timestamp = self::timestamp(86400 + $ordinal);
        $totalInCents = 0;

        for ($position = 1; $position <= self::ITEMS_PER_ORDER; $position++) {
            $totalInCents += self::orderItemInCents($ordinal, $position)['subtotal'];
        }

        return [
            'logical_id' => $ordinal,
            'monolith_id' => $ordinal,
            'microservice_id' => self::microserviceOrderId($ordinal),
            'idempotency_key' => sprintf('10000000-0000-4000-8000-%012d', $ordinal),
            'idempotency_request_hash' => hash('sha256', sprintf('benchmark-order-%05d', $ordinal)),
            'name' => sprintf('Benchmark Order %05d', $ordinal),
            'user_id' => $userOrdinal,
            'user_name_snapshot' => $user['name'],
            'user_email_snapshot' => $user['email'],
            'total_price' => self::decimal($totalInCents),
            'status' => $status,
            'reserved_at' => $timestamp,
            'paid_at' => $status === 'paid' ? $timestamp : null,
            'cancelled_at' => $status === 'cancelled' ? $timestamp : null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    /**
     * @return array{
     *     logical_order_id: int,
     *     position: int,
     *     product_id: int,
     *     product_sku: string,
     *     product_name: string,
     *     quantity: int,
     *     list_price: string,
     *     discount: string,
     *     unit_price: string,
     *     subtotal: string,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    public static function orderItem(int $orderOrdinal, int $position): array
    {
        self::guardOrdinal($orderOrdinal, self::ORDER_COUNT, 'order');
        self::guardOrdinal($position, self::ITEMS_PER_ORDER, 'order item position');

        $values = self::orderItemInCents($orderOrdinal, $position);
        $product = self::product($values['product_id']);
        $timestamp = self::timestamp(86400 + $orderOrdinal);

        return [
            'logical_order_id' => $orderOrdinal,
            'position' => $position,
            'product_id' => $values['product_id'],
            'product_sku' => $product['sku'],
            'product_name' => $product['name'],
            'quantity' => $values['quantity'],
            'list_price' => $product['price'],
            'discount' => $product['discount'],
            'unit_price' => self::decimal($values['unit_price']),
            'subtotal' => self::decimal($values['subtotal']),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    public static function microserviceOrderId(int $ordinal): string
    {
        self::guardOrdinal($ordinal, self::ORDER_COUNT, 'order');

        return sprintf('20000000-0000-4000-8000-%012d', $ordinal);
    }

    /**
     * @return array{pending: int, paid: int, cancelled: int}
     */
    public static function expectedStatusDistribution(): array
    {
        return [
            'pending' => 2500,
            'paid' => 1500,
            'cancelled' => 1000,
        ];
    }

    public static function logicalFingerprint(): string
    {
        $context = hash_init('sha256');

        for ($ordinal = 1; $ordinal <= self::USER_COUNT; $ordinal++) {
            hash_update($context, self::encode(self::user($ordinal)));
        }

        for ($ordinal = 1; $ordinal <= self::PRODUCT_COUNT; $ordinal++) {
            hash_update($context, self::encode(self::product($ordinal)));
        }

        for ($ordinal = 1; $ordinal <= self::ORDER_COUNT; $ordinal++) {
            hash_update($context, self::encode(self::order($ordinal)));

            for ($position = 1; $position <= self::ITEMS_PER_ORDER; $position++) {
                hash_update($context, self::encode(self::orderItem($ordinal, $position)));
            }
        }

        return hash_final($context);
    }

    private static function orderStatus(int $ordinal): string
    {
        $statusPosition = intdiv($ordinal - 1, self::USER_COUNT) % 10;

        return match (true) {
            $statusPosition < 5 => 'pending',
            $statusPosition < 8 => 'paid',
            default => 'cancelled',
        };
    }

    /**
     * @return array{product_id: int, quantity: int, unit_price: int, subtotal: int}
     */
    private static function orderItemInCents(int $orderOrdinal, int $position): array
    {
        $productOrdinal = (((($orderOrdinal - 1) * 17) + (($position - 1) * 37)) % self::PRODUCT_COUNT) + 1;
        $product = self::product($productOrdinal);
        $quantity = (($orderOrdinal + $position - 2) % 3) + 1;
        $unitPriceInCents = self::cents($product['price']) - self::cents($product['discount']);

        return [
            'product_id' => $productOrdinal,
            'quantity' => $quantity,
            'unit_price' => $unitPriceInCents,
            'subtotal' => $unitPriceInCents * $quantity,
        ];
    }

    private static function timestamp(int $offsetInSeconds): string
    {
        return gmdate('Y-m-d H:i:s', self::BASE_TIMESTAMP + $offsetInSeconds);
    }

    private static function decimal(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    private static function cents(string $decimal): int
    {
        [$whole, $fraction] = explode('.', $decimal);

        return ((int) $whole * 100) + (int) $fraction;
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private static function encode(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)."\n";
    }

    private static function guardOrdinal(int $ordinal, int $maximum, string $entity): void
    {
        if ($ordinal < 1 || $ordinal > $maximum) {
            throw new InvalidArgumentException(sprintf('Invalid %s ordinal: %d.', $entity, $ordinal));
        }
    }
}
