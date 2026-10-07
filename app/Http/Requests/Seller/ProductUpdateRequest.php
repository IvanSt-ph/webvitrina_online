<?php

namespace App\Http\Requests\Seller;

use App\Rules\ImageUploadConstraints;
use App\Support\MoneyLimits;
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
            'price'       => 'required|numeric|min:0|max:' . MoneyLimits::PRODUCT_PRICE_MAX,
            'old_price'   => 'nullable|numeric|min:0|max:' . MoneyLimits::PRODUCT_PRICE_MAX . '|gt:price',
            'stock'       => 'required|integer|min:0',
            'description' => 'nullable|string|max:3000',
            'status'      => ['nullable', Rule::in(\App\Models\Product::sellerEditableStatuses())],
            'currency_base' => 'nullable|in:PRB,MDL,UAH',
            'price_prb'   => 'nullable|numeric|min:0|max:' . MoneyLimits::DECIMAL_10_2_MAX,
            'old_price_prb' => 'nullable|numeric|min:0|max:' . MoneyLimits::DECIMAL_10_2_MAX,
            'price_mdl'   => 'nullable|numeric|min:0|max:' . MoneyLimits::DECIMAL_10_2_MAX,
            'old_price_mdl' => 'nullable|numeric|min:0|max:' . MoneyLimits::DECIMAL_10_2_MAX,
            'price_uah'   => 'nullable|numeric|min:0|max:' . MoneyLimits::DECIMAL_10_2_MAX,
            'old_price_uah' => 'nullable|numeric|min:0|max:' . MoneyLimits::DECIMAL_10_2_MAX,

            'category_id' => 'required|exists:categories,id',
            'country_id'  => 'bail|required|integer|min:1|exists:countries,id',
            'city_id'     => ['bail', 'required', 'integer', 'min:1', Rule::exists('cities', 'id')->where('country_id', filter_var($this->input('country_id'), FILTER_VALIDATE_INT) ?: null)],

            'address'   => 'nullable|string|max:255',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',

            'image'     => ImageUploadConstraints::rules(4096),
            'gallery'   => ['nullable', 'array', 'max:' . ImageUploadConstraints::MAX_GALLERY_IMAGES],
            'gallery.*' => ImageUploadConstraints::rules(4096),
        ];
    }
}



