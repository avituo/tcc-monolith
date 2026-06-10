<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function getList(Request $request): JsonResponse
    {
        $filters = [
            'name' => $request->input('name'),
            'status' => $request->input('pending'),
        ];

        $orders = $this->orderService->getListPaginated($filters, $request->integer('per_page', 10));

        return response()->json($orders);
    }
}
