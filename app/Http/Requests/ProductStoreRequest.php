<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        'old_price'   => ['nullable', 'numeric', 'min:0', 'gt:price'],
        'stock'       => ['required', 'integer', 'min:0'],
        'user_id'     => ['required', Rule::exists('users', 'id')->where('role', 'seller')],
        'category_id' => ['required', 'exists:categories,id'],
        'country_id'  => ['required', 'exists:countries,id'],
        'city_id'     => ['required', Rule::exists('cities', 'id')->where('country_id', $this->input('country_id'))],
        'address'     => ['nullable', 'string', 'max:255'],
        'latitude'    => ['nullable', 'numeric'],
        'longitude'   => ['nullable', 'numeric'],
        'description' => ['nullable', 'string'],
        'status'      => ['required', Rule::in(\App\Models\Product::sellerEditableStatuses())],
        'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        'gallery.*'   => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
    ];
}

}



