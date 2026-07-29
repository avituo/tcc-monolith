<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListProductsRequest;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(ListProductsRequest $request): AnonymousResourceCollection
    {
        return ProductResource::collection($this->productService->getListPaginated(
            ['name' => $request->validated('name'), 'is_active' => true],
            $request->integer('per_page', 10),
        ));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::query()->create($request->validated());

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        abort_unless($product->is_active, 404);

        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product->update([...$request->validated(), 'version' => $product->version + 1]);

        return new ProductResource($product->refresh());
    }

    public function destroy(Product $product): Response
    {
        $product->update(['is_active' => false, 'version' => $product->version + 1]);
        $product->delete();

        return response()->noContent();
    }
}
