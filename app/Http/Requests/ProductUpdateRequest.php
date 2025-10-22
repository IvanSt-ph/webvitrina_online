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
    $productId = $this->product->id ?? null;

    return [
        'title'       => ['sometimes', 'required', 'string', 'max:255'],
        'slug'        => ['nullable', 'string', 'max:255'],
        'sku'         => ['nullable', 'string', 'max:64', 'unique:products,sku,' . $productId],
        'price'       => ['sometimes', 'required', 'numeric', 'min:0'],
        'stock'       => ['sometimes', 'required', 'integer', 'min:0'],
        'user_id'     => ['sometimes', 'exists:users,id'],
        'category_id' => ['sometimes', 'exists:categories,id'],
        'country_id'  => ['sometimes', 'exists:countries,id'],
        'city_id'     => ['sometimes', 'exists:cities,id'],
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
