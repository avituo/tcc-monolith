<?php

namespace App\Http\Resources\Api\V1;

use App\Support\DecimalMoney;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
            'image' => $this->image,
            'sku' => $this->sku,
            'price' => $this->price,
            'discount' => $this->discount,
            'sale_price' => DecimalMoney::subtract($this->price, $this->discount),
            'quantity' => $this->quantity,
            'is_active' => $this->is_active,
            'version' => $this->version,
        ];
    }
}
