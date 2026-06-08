<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
{
    $productId = $this->product->id ?? null;

    return [
        'title'       => ['sometimes', 'required', 'string', 'max:255'],
        'slug'        => ['nullable', 'string', 'max:255'],
        'sku'         => ['nullable', 'string', 'max:64', 'unique:products,sku,' . $productId],
        'price'       => ['sometimes', 'required', 'numeric', 'min:0'],
        'old_price'   => ['nullable', 'numeric', 'min:0', 'gt:price'],
        'stock'       => ['sometimes', 'required', 'integer', 'min:0'],
        'user_id'     => ['sometimes', Rule::exists('users', 'id')->where('role', 'seller')],
        'category_id' => ['sometimes', 'exists:categories,id'],
        'country_id'  => ['sometimes', 'exists:countries,id'],
        'city_id'     => ['sometimes', 'required', Rule::exists('cities', 'id')->where('country_id', $this->input('country_id', $this->route('product')?->city?->country_id))],
        'address'     => ['nullable', 'string', 'max:255'],
        'latitude'    => ['nullable', 'numeric'],
        'longitude'   => ['nullable', 'numeric'],
        'description' => ['nullable', 'string'],
        'status'      => ['sometimes', 'required', Rule::in(\App\Models\Product::statuses())],
        'attributes'  => ['nullable', 'array'],
        'attributes.*'=> ['nullable'],
        'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        'gallery.*'   => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
    ];
}


}



