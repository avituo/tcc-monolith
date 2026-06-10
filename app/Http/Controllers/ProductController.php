<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function getList(Request $request): JsonResponse
    {
        $filters = [
            'name' => $request->input('name'),
            'is_active' => $request->input('is_active'),
        ];

        $products = $this->productService->getListPaginated($filters, $request->integer('per_page', 10));

        return response()->json($products);
    }
}
