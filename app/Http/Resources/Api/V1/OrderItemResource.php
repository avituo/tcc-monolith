<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->id,
            'sku' => $this->pivot->product_sku ?? $this->sku,
            'name' => $this->pivot->product_name ?? $this->name,
            'quantity' => $this->pivot->quantity,
            'list_price' => $this->pivot->list_price,
            'discount' => $this->pivot->discount,
            'unit_price' => $this->pivot->unit_price,
            'subtotal' => $this->pivot->subtotal,
        ];
    }
}
