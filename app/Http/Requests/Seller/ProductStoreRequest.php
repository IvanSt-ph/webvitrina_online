<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Продавец должен быть авторизован
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'old_price'   => 'nullable|numeric|min:0|gt:price',
            'stock'       => 'required|integer|min:0',
            'description' => 'nullable|string|max:3000',
            'status'      => ['nullable', Rule::in(\App\Models\Product::sellerEditableStatuses())],
            'currency_base' => 'nullable|in:PRB,MDL,UAH',
            'price_prb'   => 'nullable|numeric|min:0',
            'old_price_prb' => 'nullable|numeric|min:0',
            'price_mdl'   => 'nullable|numeric|min:0',
            'old_price_mdl' => 'nullable|numeric|min:0',
            'price_uah'   => 'nullable|numeric|min:0',
            'old_price_uah' => 'nullable|numeric|min:0',

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

    public function messages(): array
    {
        return [
            'title.required'      => 'Введите название товара.',
            'price.required'      => 'Укажите цену.',
            'price.numeric'       => 'Цена должна быть числом.',
            'old_price.gt'        => 'Старая цена должна быть больше текущей цены.',
            'stock.required'      => 'Укажите количество на складе.',
            'stock.integer'       => 'Количество должно быть целым числом.',
            'status.in'           => 'Выберите корректный статус товара.',
            'category_id.required'=> 'Выберите категорию.',
            'city_id.required'    => 'Выберите город.',
            'image.image'         => 'Главное изображение должно быть файлом-картинкой.',
            'gallery.*.image'     => 'Каждое изображение в галерее должно быть файлом-картинкой.',
        ];
    }
}

