<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * @param  array{name?: mixed, status?: mixed, user_id?: int}  $filters
     */
    public function getListPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return Order::query()
            ->withCount('products')
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['name'] ?? null, fn ($query, $name) => $query->where('name', 'like', '%'.$name.'%'))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(min(max($perPage, 1), 100));
    }

    public function findWithRelations(Order $order): Order
    {
        return $order->load('products');
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    public function createOrder(User $user, array $items, ?string $name = null, ?string $idempotencyKey = null): Order
    {
        $requestHash = hash('sha256', json_encode(['user_id' => $user->id, 'items' => $items], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($user, $items, $name, $idempotencyKey, $requestHash): Order {
            if ($idempotencyKey !== null) {
                $existingOrder = Order::query()->where('idempotency_key', $idempotencyKey)->first();

                if ($existingOrder !== null) {
                    if ($existingOrder->user_id !== $user->id || ! hash_equals((string) $existingOrder->idempotency_request_hash, $requestHash)) {
                        throw ValidationException::withMessages(['idempotency_key' => 'The idempotency key is already in use.']);
                    }

                    return $this->findWithRelations($existingOrder);
                }
            }

            [$totalPrice, $pivotItems] = $this->reserveAndPrepareItems($items);
            $order = Order::query()->create([
                'name' => $name ?? 'Order',
                'user_id' => $user->id,
                'user_name_snapshot' => $user->name,
                'user_email_snapshot' => $user->email,
                'total_price' => $totalPrice,
                'status' => 'pending',
                'idempotency_key' => $idempotencyKey,
                'idempotency_request_hash' => $idempotencyKey === null ? null : $requestHash,
            ]);

            if ($name === null) {
                $order->update(['name' => 'Order #'.$order->id]);
            }

            $order->products()->attach($pivotItems);

            return $this->findWithRelations($order);
        }, attempts: 3);
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    public function updateOrder(Order $order, string $name, array $items): Order
    {
        return DB::transaction(function () use ($order, $name, $items): Order {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $this->ensurePending($lockedOrder);
            $this->releaseStock($lockedOrder);
            [$totalPrice, $pivotItems] = $this->reserveAndPrepareItems($items);
            $lockedOrder->update(['name' => $name, 'total_price' => $totalPrice]);
            $lockedOrder->products()->sync($pivotItems);

            return $this->findWithRelations($lockedOrder);
        }, attempts: 3);
    }

    public function updateStatus(Order $order, string $status): Order
    {
        return DB::transaction(function () use ($order, $status): Order {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->status === $status) {
                return $this->findWithRelations($lockedOrder);
            }

            $this->ensurePending($lockedOrder);

            if (! in_array($status, ['paid', 'cancelled'], true)) {
                throw ValidationException::withMessages(['status' => 'This status transition is not allowed.']);
            }

            if ($status === 'cancelled') {
                $this->releaseStock($lockedOrder);
            }

            $lockedOrder->update(['status' => $status]);

            return $this->findWithRelations($lockedOrder);
        }, attempts: 3);
    }

    public function deleteOrder(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $this->ensurePending($lockedOrder);
            $this->releaseStock($lockedOrder);
            $lockedOrder->products()->detach();
            $lockedOrder->delete();
        }, attempts: 3);
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $submittedItems
     * @return array{0: string, 1: array<int, array<string, int|string>>}
     */
    private function reserveAndPrepareItems(array $submittedItems): array
    {
        $products = Product::query()
            ->whereKey(collect($submittedItems)->pluck('product_id')->sort()->values())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        $pivotItems = [];
        $totalCents = 0;

        foreach ($submittedItems as $index => $submittedItem) {
            $product = $products->get($submittedItem['product_id']);

            if ($product === null || ! $product->is_active) {
                throw ValidationException::withMessages(["items.$index.product_id" => 'The selected product is unavailable.']);
            }

            if ($product->quantity < $submittedItem['quantity']) {
                throw ValidationException::withMessages(["items.$index.quantity" => 'The requested quantity is not available.']);
            }

            $listPriceCents = $this->toCents($product->price);
            $discountCents = min($this->toCents($product->discount), $listPriceCents);
            $unitPriceCents = $listPriceCents - $discountCents;
            $subtotalCents = $unitPriceCents * $submittedItem['quantity'];
            $totalCents += $subtotalCents;
            $product->decrement('quantity', $submittedItem['quantity']);
            $pivotItems[$product->id] = [
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => $submittedItem['quantity'],
                'list_price' => $this->fromCents($listPriceCents),
                'discount' => $this->fromCents($discountCents),
                'unit_price' => $this->fromCents($unitPriceCents),
                'subtotal' => $this->fromCents($subtotalCents),
            ];
        }

        return [$this->fromCents($totalCents), $pivotItems];
    }

    private function releaseStock(Order $order): void
    {
        $order->loadMissing('products');

        foreach ($order->products->sortBy('id') as $product) {
            Product::query()->whereKey($product->id)->lockForUpdate()->first()?->increment('quantity', $product->pivot->quantity);
        }
    }

    private function ensurePending(Order $order): void
    {
        if ($order->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Only pending orders may be changed.']);
        }
    }

    private function toCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function fromCents(int $amount): string
    {
        return sprintf('%d.%02d', intdiv($amount, 100), $amount % 100);
    }
}
