<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->orderService->getListPaginated(
                filters: [
                    'user_id' => $request->user()->id,
                    'name' => $request->input('name'),
                    'status' => $request->input('status'),
                ],
                perPage: min(max($request->integer('per_page', 10), 1), 100),
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
}
