<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(): Response
    {
        return Inertia::render('products/Index', [
            'products' => Product::query()
                ->latest('id')
                ->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('products/Create');
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = Product::query()->create($this->productAttributes($request->validated()));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Product created successfully.']);

        return to_route('products.show', $product);
    }

    public function show(Product $product): Response
    {
        return Inertia::render('products/Show', [
            'product' => $product,
        ]);
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('products/Edit', [
            'product' => $product,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($this->productAttributes($request->validated()));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Product updated successfully.']);

        return to_route('products.show', $product);
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orders()->exists()) {
            $product->update(['is_active' => false]);
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Product deactivated to preserve order history.']);
        } else {
            $product->delete();
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Product deleted successfully.']);
        }

        return to_route('products.index');
    }

    public function getList(Request $request): JsonResponse
    {
        $filters = [
            'name' => $request->input('name'),
            'is_active' => $request->input('is_active'),
        ];

        $products = $this->productService->getListPaginated($filters, $request->integer('per_page', 10));

        return response()->json($products);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function productAttributes(array $attributes): array
    {
        return $attributes;
    }
}
