<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => 'sometimes|required|string|max:255',
            'price'       => 'sometimes|required|numeric|min:0',
            'stock'       => 'sometimes|required|integer|min:0',
            'category_id' => 'sometimes|exists:categories,id',
            'user_id'     => 'sometimes|exists:users,id',
            'country_id'  => 'sometimes|exists:countries,id',
            'city_id'     => 'sometimes|exists:cities,id',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|max:4096',
            'gallery.*'   => 'nullable|image|max:4096',
            'status'      => 'nullable|boolean',
            'address'     => 'nullable|string|max:255',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
        ];
    }
}
