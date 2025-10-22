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
        'title'       => ['required', 'string', 'max:255'],
        'slug'        => ['nullable', 'string', 'max:255'],
        'sku'         => ['nullable', 'string', 'max:64', 'unique:products,sku'],
        'price'       => ['required', 'numeric', 'min:0'],
        'stock'       => ['required', 'integer', 'min:0'],
        'user_id'     => ['nullable', 'exists:users,id'],
        'category_id' => ['required', 'exists:categories,id'],
        'country_id'  => ['required', 'exists:countries,id'],
        'city_id'     => ['required', 'exists:cities,id'],
        'address'     => ['nullable', 'string', 'max:255'],
        'latitude'    => ['nullable', 'numeric'],
        'longitude'   => ['nullable', 'numeric'],
        'description' => ['nullable', 'string'],
        'status'      => ['nullable', 'boolean'],
        'image'       => ['nullable', 'image', 'max:4096'],
        'gallery.*'   => ['nullable', 'image', 'max:4096'],
    ];
}

}
