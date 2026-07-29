<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['nullable', 'uuid'],
            'products' => ['required', 'array', 'list', 'min:1'],
            'products.*' => ['required', 'array:product_id,quantity'],
            'products.*.product_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists(Product::class, 'id'),
            ],
            'products.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
    }
}
