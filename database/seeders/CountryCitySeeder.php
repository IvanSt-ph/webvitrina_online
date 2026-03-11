<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\City;

class CountryCitySeeder extends Seeder
{
    public function run(): void
    {
        // Получаем страны по slug или создаем новые
        $prid = Country::firstOrCreate(
            ['slug' => 'pmr'],
            ['name' => 'Приднестровье', 'currency' => 'RUB', 'currency_symbol' => '₽']
        );
        
        $mold = Country::firstOrCreate(
            ['slug' => 'md'],
            ['name' => 'Молдова', 'currency' => 'MDL', 'currency_symbol' => 'L']
        );
        
        $ukr = Country::firstOrCreate(
            ['slug' => 'ua'],
            ['name' => 'Украина', 'currency' => 'UAH', 'currency_symbol' => '₴']
        );

        // ========== ПМР (ТОЛЬКО ГОРОДА - 8) ==========
        $pmrCities = [
            ['name' => 'Тирасполь', 'country_id' => $prid->id],
            ['name' => 'Бендеры', 'country_id' => $prid->id],
            ['name' => 'Рыбница', 'country_id' => $prid->id],
            ['name' => 'Дубоссары', 'country_id' => $prid->id],
            ['name' => 'Григориополь', 'country_id' => $prid->id],
            ['name' => 'Каменка', 'country_id' => $prid->id],
            ['name' => 'Слободзея', 'country_id' => $prid->id],
            ['name' => 'Днестровск', 'country_id' => $prid->id],
        ];

        // ========== МОЛДОВА (ОСНОВНЫЕ ГОРОДА) ==========
        $moldovaCities = [
            ['name' => 'Кишинёв', 'country_id' => $mold->id],
            ['name' => 'Бельцы', 'country_id' => $mold->id],
            ['name' => 'Кагул', 'country_id' => $mold->id],
            ['name' => 'Унгены', 'country_id' => $mold->id],
            ['name' => 'Сороки', 'country_id' => $mold->id],
            ['name' => 'Оргеев', 'country_id' => $mold->id],
            ['name' => 'Комрат', 'country_id' => $mold->id],
            ['name' => 'Чадыр-Лунга', 'country_id' => $mold->id],
            ['name' => 'Единцы', 'country_id' => $mold->id],
            ['name' => 'Окница', 'country_id' => $mold->id],
            ['name' => 'Дрокия', 'country_id' => $mold->id],
            ['name' => 'Флорешты', 'country_id' => $mold->id],
            ['name' => 'Калараш', 'country_id' => $mold->id],
            ['name' => 'Страшены', 'country_id' => $mold->id],
            ['name' => 'Хынчешты', 'country_id' => $mold->id],
            ['name' => 'Яловены', 'country_id' => $mold->id],
            ['name' => 'Криуляны', 'country_id' => $mold->id],
            ['name' => 'Штефан-Водэ', 'country_id' => $mold->id],
            ['name' => 'Каушаны', 'country_id' => $mold->id],
            ['name' => 'Чимишлия', 'country_id' => $mold->id],
            ['name' => 'Леова', 'country_id' => $mold->id],
            ['name' => 'Кантемир', 'country_id' => $mold->id],
            ['name' => 'Вулканешты', 'country_id' => $mold->id],
            ['name' => 'Тараклия', 'country_id' => $mold->id],
            ['name' => 'Фалешты', 'country_id' => $mold->id],
            ['name' => 'Сынжерея', 'country_id' => $mold->id],
            ['name' => 'Теленешты', 'country_id' => $mold->id],
            ['name' => 'Ниспорены', 'country_id' => $mold->id],
            ['name' => 'Резина', 'country_id' => $mold->id],
            ['name' => 'Шолданешты', 'country_id' => $mold->id],
            ['name' => 'Анений-Ной', 'country_id' => $mold->id],
            ['name' => 'Бричаны', 'country_id' => $mold->id],
            ['name' => 'Дондушены', 'country_id' => $mold->id],
        ];

        // ========== УКРАИНА (ТОП-40) ==========
        $ukraineCities = [
            ['name' => 'Киев', 'country_id' => $ukr->id],
            ['name' => 'Харьков', 'country_id' => $ukr->id],
            ['name' => 'Одесса', 'country_id' => $ukr->id],
            ['name' => 'Днепр', 'country_id' => $ukr->id],
            ['name' => 'Донецк', 'country_id' => $ukr->id],
            ['name' => 'Запорожье', 'country_id' => $ukr->id],
            ['name' => 'Львов', 'country_id' => $ukr->id],
            ['name' => 'Кривой Рог', 'country_id' => $ukr->id],
            ['name' => 'Николаев', 'country_id' => $ukr->id],
            ['name' => 'Мариуполь', 'country_id' => $ukr->id],
            ['name' => 'Винница', 'country_id' => $ukr->id],
            ['name' => 'Херсон', 'country_id' => $ukr->id],
            ['name' => 'Полтава', 'country_id' => $ukr->id],
            ['name' => 'Чернигов', 'country_id' => $ukr->id],
            ['name' => 'Черкассы', 'country_id' => $ukr->id],
            ['name' => 'Сумы', 'country_id' => $ukr->id],
            ['name' => 'Житомир', 'country_id' => $ukr->id],
            ['name' => 'Хмельницкий', 'country_id' => $ukr->id],
            ['name' => 'Ровно', 'country_id' => $ukr->id],
            ['name' => 'Ивано-Франковск', 'country_id' => $ukr->id],
            ['name' => 'Тернополь', 'country_id' => $ukr->id],
            ['name' => 'Луцк', 'country_id' => $ukr->id],
            ['name' => 'Ужгород', 'country_id' => $ukr->id],
            ['name' => 'Черновцы', 'country_id' => $ukr->id],
            ['name' => 'Кропивницкий', 'country_id' => $ukr->id],
            ['name' => 'Кременчуг', 'country_id' => $ukr->id],
            ['name' => 'Белая Церковь', 'country_id' => $ukr->id],
            ['name' => 'Мелитополь', 'country_id' => $ukr->id],
            ['name' => 'Бердянск', 'country_id' => $ukr->id],
            ['name' => 'Северодонецк', 'country_id' => $ukr->id],
            ['name' => 'Лисичанск', 'country_id' => $ukr->id],
            ['name' => 'Славянск', 'country_id' => $ukr->id],
            ['name' => 'Краматорск', 'country_id' => $ukr->id],
            ['name' => 'Алчевск', 'country_id' => $ukr->id],
            ['name' => 'Луганск', 'country_id' => $ukr->id],
            ['name' => 'Енакиево', 'country_id' => $ukr->id],
            ['name' => 'Макеевка', 'country_id' => $ukr->id],
            ['name' => 'Горловка', 'country_id' => $ukr->id],
        ];

        // Объединяем все города
        $allCities = array_merge($pmrCities, $moldovaCities, $ukraineCities);

        // Вставляем города
        foreach ($allCities as $city) {
            City::firstOrCreate(
                ['name' => $city['name'], 'country_id' => $city['country_id']],
                [
                    'name' => $city['name'],
                    'country_id' => $city['country_id'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        $this->command->info('✅ Города успешно добавлены!');
        $this->command->info('ПМР: ' . count($pmrCities) . ' городов');
        $this->command->info('Молдова: ' . count($moldovaCities) . ' городов');
        $this->command->info('Украина: ' . count($ukraineCities) . ' городов');
        $this->command->info('Всего: ' . count($allCities) . ' городов');
    }
}