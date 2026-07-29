<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListOrdersRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(ListOrdersRequest $request): Response
    {
        $filters = $request->validated();

        return Inertia::render('orders/Index', [
            'orders' => $this->orderService->getListPaginated(
                filters: [
                    ...$filters,
                    'user_id' => $request->user()->id,
                ],
                perPage: (int) ($filters['per_page'] ?? 10),
            )->withQueryString(),
            'filters' => [
                'name' => $filters['name'] ?? null,
                'status' => $filters['status'] ?? null,
                'per_page' => (int) ($filters['per_page'] ?? 10),
            ],
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
        $order = $this->orderService->createOrder(
            user: $request->user(),
            items: $request->validated('items'),
            name: $request->validated('name'),
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Order created successfully.']);

        return to_route('orders.show', $order);
    }

    public function show(Order $order): Response
    {
        $this->authorize('view', $order);

        return Inertia::render('orders/Show', [
            'order' => $order->load('products'),
        ]);
    }

    public function edit(Order $order): Response
    {
        $this->authorize('update', $order);

        return Inertia::render('orders/Edit', [
            'order' => $order->load('products'),
            'products' => $this->availableProducts(),
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $this->orderService->updateOrder(
            order: $order,
            name: $request->validated('name'),
            items: $request->validated('items'),
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Order updated successfully.']);

        return to_route('orders.show', $order);
    }

    public function destroy(Order $order): RedirectResponse
    {
        $this->authorize('delete', $order);
        $this->orderService->deleteOrder($order);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Order deleted successfully.']);

        return to_route('orders.index');
    }

    /**
     * @return Collection<int, Product>
     */
    private function availableProducts(): Collection
    {
        return Product::query()
            ->select(['id', 'name', 'sku', 'price', 'discount', 'quantity', 'is_active'])
            ->orderBy('name')
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->get();
    }
}
