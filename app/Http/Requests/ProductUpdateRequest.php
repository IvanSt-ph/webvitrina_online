<?php

namespace App\Http\Requests;

use App\Rules\ImageUploadConstraints;
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
        'country_id'  => ['bail', 'sometimes', 'integer', 'min:1', 'exists:countries,id'],
        'city_id'     => ['bail', 'sometimes', 'required', 'integer', 'min:1', Rule::exists('cities', 'id')->where('country_id', filter_var($this->input('country_id', $this->route('product')?->city?->country_id), FILTER_VALIDATE_INT) ?: null)],
        'address'     => ['nullable', 'string', 'max:255'],
        'latitude'    => ['nullable', 'numeric'],
        'longitude'   => ['nullable', 'numeric'],
        'description' => ['nullable', 'string'],
        'status'      => ['sometimes', 'required', Rule::in(\App\Models\Product::statuses())],
        'attributes'  => ['nullable', 'array'],
        'attributes.*'=> ['nullable'],
        'image'       => ImageUploadConstraints::rules(4096),
        'gallery'     => ['nullable', 'array', 'max:' . ImageUploadConstraints::MAX_GALLERY_IMAGES],
        'gallery.*'   => ImageUploadConstraints::rules(4096),
    ];
}


}


