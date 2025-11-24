<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttributesSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        DB::table('attributes')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        $attributes = [
            // ====== ОБЩИЕ ======
            ['name' => 'Бренд',               'type' => 'text',   'unit' => null,   'filterable' => 1],
            ['name' => 'Модель',              'type' => 'text',   'unit' => null,   'filterable' => 1],
            ['name' => 'Цвет',                'type' => 'color',  'unit' => null,   'filterable' => 1,
                'options' => ['#000000','#FFFFFF','#FF0000','#0000FF','#008000','#FFFF00','#FFA500','#808080']
            ],
            ['name' => 'Материал',            'type' => 'text',   'unit' => null,   'filterable' => 1],
            ['name' => 'Страна производства', 'type' => 'text',   'unit' => null,   'filterable' => 1],

            // ====== ОДЕЖДА ======
            ['name' => 'Размер одежды',       'type' => 'select', 'unit' => null,   'filterable' => 1,
                'options' => ['XXS','XS','S','M','L','XL','XXL','3XL']
            ],
            ['name' => 'Состав ткани',        'type' => 'text',   'unit' => null,   'filterable' => 0],
            ['name' => 'Сезон',               'type' => 'select', 'unit' => null,   'filterable' => 1,
                'options' => ['Зима','Весна','Лето','Осень','Всесезон']
            ],
            ['name' => 'Посадка',             'type' => 'select', 'unit' => null,   'filterable' => 1,
                'options' => ['Оверсайз','Прямая','Приталенная','Свободная']
            ],
            ['name' => 'Длина рукава',        'type' => 'select', 'unit' => null,   'filterable' => 0,
                'options' => ['Без рукавов','Короткий','3/4','Длинный']
            ],
            ['name' => 'Длина изделия',       'type' => 'select', 'unit' => null,   'filterable' => 0,
                'options' => ['Короткое','Средней длины','Длинное']
            ],

            // ====== ОБУВЬ ======
            ['name' => 'Размер обуви',        'type' => 'number', 'unit' => 'EU',   'filterable' => 1],
            ['name' => 'Тип обуви',           'type' => 'select', 'unit' => null,   'filterable' => 1,
                'options' => ['Кроссовки','Кеды','Ботинки','Туфли','Босоножки','Сандалии']
            ],
            ['name' => 'Материал верха',      'type' => 'text',   'unit' => null,   'filterable' => 0],
            ['name' => 'Материал подкладки',  'type' => 'text',   'unit' => null,   'filterable' => 0],
            ['name' => 'Материал подошвы',    'type' => 'text',   'unit' => null,   'filterable' => 0],
            ['name' => 'Высота каблука',      'type' => 'number', 'unit' => 'см',   'filterable' => 0],

            // ====== ДЕТЯМ / ИГРУШКИ ======
            ['name' => 'Возраст от',          'type' => 'number', 'unit' => 'лет',  'filterable' => 1],
            ['name' => 'Возраст до',          'type' => 'number', 'unit' => 'лет',  'filterable' => 1],
            ['name' => 'Рост ребёнка',        'type' => 'number', 'unit' => 'см',   'filterable' => 0],
            ['name' => 'Тип игрушки',         'type' => 'text',   'unit' => null,   'filterable' => 1],
            ['name' => 'Наличие мелких деталей', 'type' => 'select', 'unit' => null, 'filterable' => 0,
                'options' => ['Да','Нет']
            ],

            // ====== ЭЛЕКТРОНИКА / СМАРТФОНЫ / НОУТБУКИ ======
            ['name' => 'Объём памяти',        'type' => 'number', 'unit' => 'ГБ',   'filterable' => 1],
            ['name' => 'Оперативная память',  'type' => 'number', 'unit' => 'ГБ',   'filterable' => 1],
            ['name' => 'Диагональ экрана',    'type' => 'number', 'unit' => 'дюйм','filterable' => 1],
            ['name' => 'Ёмкость аккумулятора','type' => 'number', 'unit' => 'мАч',  'filterable' => 1],
            ['name' => 'Тип матрицы',         'type' => 'select', 'unit' => null,   'filterable' => 1,
                'options' => ['IPS','OLED','TN','VA']
            ],
            ['name' => 'Тип разъёма',         'type' => 'select', 'unit' => null,   'filterable' => 0,
                'options' => ['USB-A','USB-C','Lightning','3.5 мм','HDMI','DisplayPort']
            ],
            ['name' => 'Поддержка 5G',        'type' => 'select', 'unit' => null,   'filterable' => 0,
                'options' => ['Да','Нет']
            ],

            // ====== ТЕЛЕВИЗОРЫ / МУЛЬТИМЕДИА ======
            ['name' => 'Разрешение экрана',   'type' => 'select', 'unit' => null,   'filterable' => 1,
                'options' => ['HD','Full HD','4K','8K']
            ],
            ['name' => 'Тип подсветки',       'type' => 'select', 'unit' => null,   'filterable' => 0,
                'options' => ['LED','Mini-LED','OLED']
            ],
            ['name' => 'Мощность звука',      'type' => 'number', 'unit' => 'Вт',   'filterable' => 0],

            // ====== УМНЫЙ ДОМ ======
            ['name' => 'Тип устройства',      'type' => 'text',   'unit' => null,   'filterable' => 1],
            ['name' => 'Подключение',         'type' => 'select', 'unit' => null,   'filterable' => 1,
                'options' => ['Wi-Fi','Bluetooth','Zigbee','Z-Wave']
            ],
            ['name' => 'Совместимость',       'type' => 'text',   'unit' => null,   'filterable' => 0],

            // ====== КРАСОТА / КОСМЕТИКА ======
            ['name' => 'Тип продукта',        'type' => 'text',   'unit' => null,   'filterable' => 1],
            ['name' => 'Объём',               'type' => 'number', 'unit' => 'мл',   'filterable' => 1],
            ['name' => 'Тип кожи',            'type' => 'select', 'unit' => null,   'filterable' => 0,
                'options' => ['Сухая','Жирная','Комбинированная','Нормальная','Чувствительная']
            ],
            ['name' => 'Эффект',              'type' => 'text',   'unit' => null,   'filterable' => 0],
            ['name' => 'Срок годности',       'type' => 'number', 'unit' => 'мес',  'filterable' => 0],

            // ====== ДОМ И БЫТ ======
            ['name' => 'Тип товара для дома', 'type' => 'text',   'unit' => null,   'filterable' => 1],
            ['name' => 'Размер',              'type' => 'text',   'unit' => 'см',   'filterable' => 0],
            ['name' => 'Мощность',            'type' => 'number', 'unit' => 'Вт',   'filterable' => 0],
            ['name' => 'Количество ламп',     'type' => 'number', 'unit' => 'шт',   'filterable' => 0],
            ['name' => 'Тип ткани',           'type' => 'text',   'unit' => null,   'filterable' => 0],

            // ====== АВТОТОВАРЫ ======
            ['name' => 'Тип автоаксессуара',  'type' => 'text',   'unit' => null,   'filterable' => 1],
            ['name' => 'Размер шины',         'type' => 'text',   'unit' => null,   'filterable' => 1],
            ['name' => 'Сезонность шины',     'type' => 'select', 'unit' => null,   'filterable' => 1,
                'options' => ['Зимние','Летние','Всесезонные']
            ],

            // ====== ПРОДУКТЫ ======
            ['name' => 'Вес товара',          'type' => 'number', 'unit' => 'кг',   'filterable' => 1],
            ['name' => 'Сорт',                'type' => 'text',   'unit' => null,   'filterable' => 0],
            ['name' => 'Условия хранения',    'type' => 'text',   'unit' => null,   'filterable' => 0],
            ['name' => 'Органический продукт','type' => 'select', 'unit' => null,   'filterable' => 0,
                'options' => ['Да','Нет']
            ],

            // ====== НАПИТКИ ======
            ['name' => 'Объём напитка',       'type' => 'number', 'unit' => 'л',    'filterable' => 1],
            ['name' => 'Тип напитка',         'type' => 'text',   'unit' => null,   'filterable' => 1],
            ['name' => 'Без сахара',          'type' => 'select', 'unit' => null,   'filterable' => 0,
                'options' => ['Да','Нет']
            ],
        ];

        foreach ($attributes as $attr) {
            DB::table('attributes')->insert([
                'name'         => $attr['name'],
                'type'         => $attr['type'],
                'unit'         => $attr['unit'] ?? null,
                'is_filterable'=> $attr['filterable'] ?? 1,
                'options'      => isset($attr['options']) ? json_encode($attr['options']) : null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }
}
