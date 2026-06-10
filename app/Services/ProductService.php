<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    public function __construct() {}

    public function getListPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Product::query();

        if (! empty($filters['name'])) {
            $query->where('name', 'like', '%'.$filters['name'].'%');
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }
}
