<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class UpdateOrderStatusController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function __invoke(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $status = $request->validated('status');

        $this->orderService->updateStatus($order, $status);
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $status === 'paid'
                ? 'Order marked as paid.'
                : 'Order cancelled and stock released.',
        ]);

        return back();
    }
}
