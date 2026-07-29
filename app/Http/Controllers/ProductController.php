<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListProductsRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(ListProductsRequest $request): Response
    {
        $filters = $request->validated();

        return Inertia::render('products/Index', [
            'products' => $this->productService->getListPaginated(
                filters: $filters,
                perPage: (int) ($filters['per_page'] ?? 10),
            )->withQueryString(),
            'filters' => [
                'name' => $filters['name'] ?? null,
                'is_active' => array_key_exists('is_active', $filters)
                    ? (bool) $filters['is_active']
                    : null,
                'per_page' => (int) ($filters['per_page'] ?? 10),
            ],
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

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function productAttributes(array $attributes): array
    {
        return $attributes;
    }
}
