<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Можно добавить: return auth()->user()->isSeller() || auth()->user()->isAdmin();
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'user_id'     => 'nullable|exists:users,id',
            'country_id'  => 'required|exists:countries,id',
            'city_id'     => 'required|exists:cities,id',
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
