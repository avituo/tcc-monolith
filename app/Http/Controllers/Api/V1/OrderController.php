<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListOrdersRequest;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Http\Requests\Api\V1\UpdateOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(ListOrdersRequest $request): AnonymousResourceCollection
    {
        return OrderResource::collection($this->orderService->getListPaginated(
            filters: [
                ...$request->validated(),
                'user_id' => $request->user()->id,
            ],
            perPage: $request->integer('per_page', 10),
        ));
    }

    public function show(Request $request, Order $order): OrderResource
    {
        abort_unless($request->user()->can('view', $order), 404);

        return new OrderResource($this->orderService->findWithRelations($order));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder(
            user: $request->user(),
            items: $request->validated('items'),
            name: $request->validated('name'),
            idempotencyKey: $request->validated('idempotency_key'),
        );

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    public function update(UpdateOrderRequest $request, Order $order): OrderResource
    {
        return new OrderResource($this->orderService->updateOrder(
            order: $order,
            name: $request->validated('name'),
            items: $request->validated('items'),
        ));
    }

    public function destroy(Request $request, Order $order): Response
    {
        abort_unless($request->user()->can('delete', $order), 404);
        $this->orderService->deleteOrder($order);

        return response()->noContent();
    }
}
