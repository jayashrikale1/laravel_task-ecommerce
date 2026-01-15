<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3'],
            'sku' => ['required', 'string', 'unique:products,sku'],
            'price' => ['required', 'numeric', 'gt:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
