<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug'],
            'image' => ['nullable', 'url', 'max:2048'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'discount' => ['required', 'numeric', 'min:0', 'lte:price'],
            'quantity' => ['required', 'integer', 'min:0'],
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
