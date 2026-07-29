<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'user_id' => (string) $this->user_id,
            'user_name' => $this->user_name_snapshot,
            'user_email' => $this->user_email_snapshot,
            'status' => $this->status,
            'total_price' => $this->total_price,
            'stock_reservation_id' => null,
            'items_count' => $this->whenCounted('products'),
            'items' => OrderItemResource::collection($this->whenLoaded('products')),
            'reserved_at' => $this->reserved_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
