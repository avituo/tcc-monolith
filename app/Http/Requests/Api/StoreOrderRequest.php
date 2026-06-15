<?php

namespace App\Http\Requests\Api;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists(User::class, 'id')],
            'products' => ['required', 'array', 'list', 'min:1'],
            'products.*' => ['required', 'array:product_id,quantity'],
            'products.*.product_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists(Product::class, 'id'),
            ],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
