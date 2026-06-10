<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderService
{
    public function __construct() {}

    public function getListPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Order::query();

        if (! empty($filters['name'])) {
            $query->where('name', 'like', '%'.$filters['name'].'%');
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }
}
