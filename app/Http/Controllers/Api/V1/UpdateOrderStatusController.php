<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class UpdateOrderStatusController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function __invoke(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        return response()->json(
            $this->orderService->updateStatus($order, $request->validated('status')),
        );
    }
}
