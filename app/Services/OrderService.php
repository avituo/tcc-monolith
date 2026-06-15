<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderService
{
    /**
     * @param  array{name?: mixed, status?: mixed}  $filters
     */
    public function getListPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Order::query()
            ->with('user:id,name,email')
            ->withCount('products');

        if (! empty($filters['name'])) {
            $query->where('name', 'like', '%'.$filters['name'].'%');
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function findWithRelations(Order $order): Order
    {
        return $order->load([
            'user:id,name,email',
            'products',
        ]);
    }

    /**
     * @param array{
     *     user_id: int,
     *     products: array<int, array{product_id: int, quantity: int}>
     * } $attributes
     * @throws Throwable
     */
    public function createOrder(array $attributes): Order
    {
        return DB::transaction(function () use ($attributes): Order {
            $products = Product::query()
                ->whereKey(collect($attributes['products'])->pluck('product_id'))
                ->get()
                ->keyBy('id');
            $items = [];
            $totalPrice = 0.0;

            foreach ($attributes['products'] as $submittedProduct) {
                $product = $products->get($submittedProduct['product_id']);
                $unitPrice = (float) $product->price;
                $subtotal = $unitPrice * $submittedProduct['quantity'];
                $totalPrice += $subtotal;
                $items[$product->id] = [
                    'quantity' => $submittedProduct['quantity'],
                    'unit_price' => number_format($unitPrice, 2, '.', ''),
                    'subtotal' => number_format($subtotal, 2, '.', ''),
                ];
            }

            $order = Order::query()->create([
                'name' => 'Order',
                'user_id' => $attributes['user_id'],
                'total_price' => number_format($totalPrice, 2, '.', ''),
                'status' => 'pending',
            ]);
            $order->update(['name' => 'Order #'.$order->id]);
            $order->products()->attach($items);

            return $this->findWithRelations($order);
        });
    }

    public function updateStatus(Order $order, string $status): Order
    {
        $order->update(['status' => $status]);

        return $this->findWithRelations($order);
    }
}
