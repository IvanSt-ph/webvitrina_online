<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product && $this->user()?->can('update', $product);
    }

    public function rules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'description' => 'nullable|string|max:3000',
            'status'      => ['nullable', Rule::in(\App\Models\Product::sellerEditableStatuses())],
            'currency_base' => 'nullable|in:PRB,MDL,UAH',
            'price_prb'   => 'nullable|numeric|min:0',
            'price_mdl'   => 'nullable|numeric|min:0',
            'price_uah'   => 'nullable|numeric|min:0',

            'category_id' => 'required|exists:categories,id',
            'country_id'  => 'required|exists:countries,id',
            'city_id'     => ['required', Rule::exists('cities', 'id')->where('country_id', $this->input('country_id'))],

            'address'   => 'nullable|string|max:255',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',

            'image'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'gallery.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ];
    }
}




