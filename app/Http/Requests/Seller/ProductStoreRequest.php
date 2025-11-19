<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

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
            'stock'       => 'required|integer|min:0',
            'description' => 'nullable|string|max:1000',

            'category_id' => 'required|exists:categories,id',
            'country_id'  => 'required|exists:countries,id',
            'city_id'     => 'required|exists:cities,id',

            'address'   => 'nullable|string|max:255',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',

            'image'     => 'nullable|image|max:4096',
            'gallery.*' => 'nullable|image|max:4096',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'      => 'Введите название товара.',
            'price.required'      => 'Укажите цену.',
            'price.numeric'       => 'Цена должна быть числом.',
            'stock.required'      => 'Укажите количество на складе.',
            'stock.integer'       => 'Количество должно быть целым числом.',
            'category_id.required'=> 'Выберите категорию.',
            'city_id.required'    => 'Выберите город.',
            'image.image'         => 'Главное изображение должно быть файлом-картинкой.',
            'gallery.*.image'     => 'Каждое изображение в галерее должно быть файлом-картинкой.',
        ];
    }
}
