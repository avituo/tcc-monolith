<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Order extends Model
{
    use HasFactory;

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $fillable = [
        'name',
        'user_id',
        'user_name_snapshot',
        'user_email_snapshot',
        'total_price',
        'status',
        'idempotency_key',
        'idempotency_request_hash',
    ];

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot([
                'product_name',
                'product_sku',
                'quantity',
                'list_price',
                'discount',
                'unit_price',
                'subtotal',
            ])
            ->withTimestamps();
    }
}
