<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(): Response
    {
        return Inertia::render('orders/Index', [
            'orders' => Order::query()
                ->with('user:id,name')
                ->withCount('products')
                ->latest('id')
                ->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('orders/Create', [
            'products' => $this->availableProducts(),
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $order = DB::transaction(function () use ($request): Order {
            $validated = $request->validated();
            [$totalPrice, $items] = $this->prepareItems($validated['items']);

            $order = Order::query()->create([
                'name' => $validated['name'],
                'status' => $validated['status'],
                'user_id' => $request->user()->id,
                'total_price' => $totalPrice,
            ]);
            $order->products()->sync($items);

            return $order;
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Order created successfully.']);

        return to_route('orders.show', $order);
    }

    public function show(Order $order): Response
    {
        return Inertia::render('orders/Show', [
            'order' => $order->load(['user:id,name,email', 'products']),
        ]);
    }

    public function edit(Order $order): Response
    {
        return Inertia::render('orders/Edit', [
            'order' => $order->load('products'),
            'products' => $this->availableProducts(),
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        DB::transaction(function () use ($request, $order): void {
            $validated = $request->validated();
            [$totalPrice, $items] = $this->prepareItems($validated['items']);

            $order->update([
                'name' => $validated['name'],
                'status' => $validated['status'],
                'total_price' => $totalPrice,
            ]);
            $order->products()->sync($items);
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Order updated successfully.']);

        return to_route('orders.show', $order);
    }

    public function destroy(Order $order): RedirectResponse
    {
        DB::transaction(function () use ($order): void {
            $order->products()->detach();
            $order->delete();
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Order deleted successfully.']);

        return to_route('orders.index');
    }

    public function getList(Request $request): JsonResponse
    {
        $filters = [
            'name' => $request->input('name'),
            'status' => $request->input('status'),
        ];

        $orders = $this->orderService->getListPaginated($filters, $request->integer('per_page', 10));

        return response()->json($orders);
    }

    /**
     * @return Collection<int, Product>
     */
    private function availableProducts(): Collection
    {
        return Product::query()
            ->select(['id', 'name', 'sku', 'price', 'discount', 'quantity', 'is_active'])
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $submittedItems
     * @return array{0: string, 1: array<int, array{quantity: int, unit_price: string, subtotal: string}>}
     */
    private function prepareItems(array $submittedItems): array
    {
        $products = Product::query()
            ->whereKey(collect($submittedItems)->pluck('product_id'))
            ->get()
            ->keyBy('id');
        $items = [];
        $totalPrice = 0.0;

        foreach ($submittedItems as $submittedItem) {
            $product = $products->get($submittedItem['product_id']);
            $unitPrice = max((float) $product->price - (float) $product->discount, 0);
            $subtotal = $unitPrice * $submittedItem['quantity'];
            $totalPrice += $subtotal;
            $items[$product->id] = [
                'quantity' => $submittedItem['quantity'],
                'unit_price' => number_format($unitPrice, 2, '.', ''),
                'subtotal' => number_format($subtotal, 2, '.', ''),
            ];
        }

        return [number_format($totalPrice, 2, '.', ''), $items];
    }
}
