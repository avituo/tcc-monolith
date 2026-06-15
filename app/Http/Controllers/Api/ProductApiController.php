<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'name' => $request->input('name'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
        ];

        return response()->json(
            $this->productService->getListPaginated(
                $filters,
                min(max($request->integer('per_page', 10), 1), 100),
            ),
        );
    }
}
