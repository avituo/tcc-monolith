<?php

namespace App\Http\Controllers\Api\V1\Internal;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class MarkOrderPaidController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __construct(private readonly OrderService $orderService) {}

    public function __invoke(Request $request, Order $order): OrderResource
    {
        abort_unless($request->user()->can('update', $order), 404);

        return new OrderResource($this->orderService->updateStatus($order, 'paid'));
    }
}
