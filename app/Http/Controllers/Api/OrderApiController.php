<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Http\Requests\Api\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderApiController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(): JsonResponse
    {
        return response()->json(
            $this->orderService->getListPaginated(
                filters: [
                    'user_id' => request()->user()->id,
                    'name' => request()->input('name'),
                    'status' => request()->input('status'),
                ],
                perPage: min(max(request()->integer('per_page', 10), 1), 100),
            ),
        );
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json($this->orderService->findWithRelations($order));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        return response()->json(
            $this->orderService->createOrder(
                user: $request->user(),
                items: $request->validated('products'),
                idempotencyKey: $request->validated('idempotency_key'),
            ),
            201,
        );
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        return response()->json(
            $this->orderService->updateStatus($order, $request->validated('status')),
        );
    }
}
