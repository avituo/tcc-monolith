<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    /**
     * @param  array{name?: mixed, is_active?: mixed}  $filters
     */
    public function getListPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Product::query();

        if (! empty($filters['name'])) {
            $query->where('name', 'like', '%'.$filters['name'].'%');
        }
        if (($filters['is_active'] ?? null) !== null) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }
}
