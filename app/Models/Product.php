<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'is_active' => true,
        'version' => 1,
    ];

    protected $fillable = [
        'name',
        'description',
        'image',
        'slug',
        'sku',
        'price',
        'discount',
        'quantity',
        'is_active',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount' => 'decimal:2',
            'is_active' => 'boolean',
            'version' => 'integer',
        ];
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class)
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
