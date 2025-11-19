<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Тут авторизацию может дополнительно контролировать Policy
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'description' => 'nullable|string|max:1000',

            'category_id' => 'required|exists:categories,id',
            'country_id'  => 'required|exists:countries,id',
            'city_id'     => 'required|exists:cities,id',

            'address'   => 'nullable|string|max:255',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',

            'image'     => 'nullable|image|max:4096',
            'gallery.*' => 'nullable|image|max:4096',
        ];
    }
}
