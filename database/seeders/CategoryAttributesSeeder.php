<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Services\CategoryFilterCacheService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoryAttributesSeeder extends Seeder
{
    public function run(): void
    {
        $attributeIds = [];

        $attribute = function (
            string $name,
            string $type = 'text',
            ?array $options = null,
            ?string $unit = null,
            bool $filterable = true
        ) use (&$attributeIds): int {
            $optionsJson = $options ? json_encode($options, JSON_UNESCAPED_UNICODE) : null;
            $key = mb_strtolower($name) . '|' . $type . '|' . ($unit ?? '') . '|' . ($optionsJson ?? '');

            if (isset($attributeIds[$key])) {
                return $attributeIds[$key];
            }

            $existingQuery = DB::table('attributes')
                ->where('name', $name)
                ->where('type', $type)
                ->where('unit', $unit);

            $existingId = $optionsJson
                ? $existingQuery->where('options', $optionsJson)->value('id')
                : $existingQuery->whereNull('options')->value('id');

            $data = [
                'name' => $name,
                'type' => $type,
                'unit' => $unit,
                'is_filterable' => $filterable,
                'options' => $optionsJson,
                'updated_at' => now(),
            ];

            if ($existingId) {
                DB::table('attributes')->where('id', $existingId)->update($data);

                return $attributeIds[$key] = (int) $existingId;
            }

            return $attributeIds[$key] = DB::table('attributes')->insertGetId($data + [
                'created_at' => now(),
            ]);
        };

        $brands = [
            'Apple', 'Samsung', 'Xiaomi', 'Huawei', 'Honor', 'Lenovo', 'Asus', 'Acer', 'HP', 'Dell',
            'LG', 'Sony', 'Bosch', 'Philips', 'Tefal', 'Nike', 'Adidas', 'Puma', 'Reebok', 'Zara',
            'H&M', 'LC Waikiki', 'Geox', 'Ecco', 'Maybelline', 'L’Oreal', 'Nivea', 'Bioderma',
            'Chicco', 'Philips Avent', 'IKEA', 'Jysk', 'Michelin', 'Continental', 'Goodyear',
            'Makita', 'DeWalt', 'Bosch Professional', 'Gardena', 'Royal Canin', 'Purina', 'Trixie',
            'Canon', 'ErichKrause', 'Berlingo', 'Другое',
        ];

        $electronicsBrands = ['Apple', 'Samsung', 'Xiaomi', 'Huawei', 'Honor', 'POCO', 'Realme', 'OnePlus', 'Motorola', 'Nokia', 'Sony', 'LG', 'Lenovo', 'Asus', 'Acer', 'HP', 'Dell', 'MSI', 'Другое'];
        $clothesBrands = ['Zara', 'H&M', 'LC Waikiki', 'Nike', 'Adidas', 'Puma', 'Reebok', 'Reserved', 'Bershka', 'Pull&Bear', 'Другое'];
        $shoeBrands = ['Nike', 'Adidas', 'Puma', 'Reebok', 'Geox', 'Ecco', 'New Balance', 'Skechers', 'Timberland', 'Другое'];
        $beautyBrands = ['Maybelline', 'L’Oreal', 'Nivea', 'Bioderma', 'Garnier', 'Dove', 'Vichy', 'La Roche-Posay', 'Avon', 'Oriflame', 'Другое'];
        $kidsBrands = ['Chicco', 'Philips Avent', 'Canpol Babies', 'Huggies', 'Pampers', 'LEGO', 'Barbie', 'Hot Wheels', 'Другое'];
        $homeBrands = ['IKEA', 'Jysk', 'Tefal', 'Philips', 'Bosch', 'Samsung', 'LG', 'Xiaomi', 'Vileda', 'Другое'];
        $autoPartBrands = ['Bosch', 'Michelin', 'Continental', 'Goodyear', 'Pirelli', 'Bridgestone', 'Nokian', 'Hankook', 'Kumho', 'Mann-Filter', 'Castrol', 'Shell', 'Другое'];
        $sportBrands = ['Nike', 'Adidas', 'Puma', 'Reebok', 'New Balance', 'Under Armour', 'Decathlon', 'Wilson', 'Spalding', 'Другое'];
        $toolBrands = ['Bosch', 'Makita', 'DeWalt', 'Stanley', 'Metabo', 'Einhell', 'Black+Decker', 'Knipex', 'Другое'];
        $gardenBrands = ['Gardena', 'Bosch', 'Makita', 'Stihl', 'Husqvarna', 'Fiskars', 'Karcher', 'Другое'];
        $petBrands = ['Tetra', 'Sera', 'JBL', 'Aquael', 'Eheim', 'Royal Canin', 'Purina', 'Brit', 'Whiskas', 'Felix', 'Pedigree', 'Trixie', 'Vitakraft', 'Другое'];
        $fishFoodBrands = ['Tetra', 'Sera', 'JBL', 'Aquael', 'Hikari', 'Tropical', 'Dajana', 'Prodac', 'Другое'];
        $catBrands = ['Royal Canin', 'Purina', 'Brit', 'Whiskas', 'Felix', 'Sheba', 'Perfect Fit', 'Gourmet', 'Trixie', 'Другое'];
        $dogBrands = ['Royal Canin', 'Purina', 'Brit', 'Pedigree', 'Chappi', 'Club 4 Paws', 'Trixie', 'Monge', 'Другое'];
        $officeBrands = ['Canon', 'HP', 'Epson', 'Brother', 'Xerox', 'ErichKrause', 'Berlingo', 'Brauberg', 'Другое'];

        $materials = [
            'Хлопок', 'Полиэстер', 'Лен', 'Шерсть', 'Кожа', 'Экокожа', 'Замша', 'Металл',
            'Пластик', 'Стекло', 'Дерево', 'Керамика', 'Силикон', 'Текстиль', 'Комбинированный',
        ];

        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', 'Универсальный'];
        $colors = ['Черный', 'Белый', 'Серый', 'Синий', 'Красный', 'Зеленый', 'Бежевый', 'Коричневый', 'Золотой', 'Серебристый'];
        $conditions = ['Новое', 'Б/у', 'Восстановленное'];
        $countries = ['Молдова', 'Приднестровье', 'Украина', 'Польша', 'Германия', 'Италия', 'Турция', 'Китай', 'США', 'Другое'];
        $warranty = ['Нет', 'До 3 месяцев', '6 месяцев', '12 месяцев', '24 месяца'];

        $sets = [
            'common' => [
                ['Бренд', 'select', $brands],
                ['Состояние', 'select', $conditions],
                ['Цвет', 'select', $colors],
            ],
            'electronics' => [
                ['Бренд', 'select', $electronicsBrands],
                ['Серия/модель', 'select', ['iPhone', 'Galaxy', 'Redmi', 'POCO', 'Mi', 'Honor', 'MateBook', 'IdeaPad', 'ROG', 'Aspire', 'Pavilion', 'ThinkPad', 'Другое']],
                ['Состояние', 'select', $conditions],
                ['Цвет', 'select', $colors],
                ['Гарантия', 'select', $warranty],
            ],
            'clothes' => [
                ['Размер', 'select', $sizes],
                ['Цвет', 'select', $colors],
                ['Материал', 'select', $materials],
                ['Пол', 'select', ['Мужской', 'Женский', 'Унисекс']],
                ['Сезон', 'select', ['Лето', 'Демисезон', 'Зима', 'Всесезон']],
            ],
            'shoes' => [
                ['Размер обуви', 'select', ['28', '29', '30', '31', '32', '33', '34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46']],
                ['Цвет', 'select', $colors],
                ['Материал верха', 'select', ['Кожа', 'Экокожа', 'Замша', 'Текстиль', 'Сетка', 'Резина', 'Комбинированный']],
                ['Пол', 'select', ['Мужской', 'Женский', 'Детский', 'Унисекс']],
                ['Сезон', 'select', ['Лето', 'Демисезон', 'Зима', 'Всесезон']],
            ],
            'beauty' => [
                ['Бренд', 'select', $beautyBrands],
                ['Объем', 'number', null, 'мл'],
                ['Тип кожи', 'select', ['Любая', 'Сухая', 'Жирная', 'Комбинированная', 'Чувствительная']],
                ['Назначение', 'select', ['Увлажнение', 'Очищение', 'Питание', 'Восстановление', 'Защита', 'Антивозрастной уход', 'Макияж']],
                ['Срок годности', 'select', ['До 3 месяцев', '3-6 месяцев', '6-12 месяцев', 'Больше 12 месяцев']],
            ],
            'kids' => [
                ['Возраст', 'select', ['0-6 месяцев', '6-12 месяцев', '1-3 года', '3-7 лет', '7-14 лет', '14+']],
                ['Бренд', 'select', $kidsBrands],
                ['Материал', 'select', $materials],
                ['Пол ребенка', 'select', ['Мальчик', 'Девочка', 'Унисекс']],
                ['Состояние', 'select', ['Новое', 'Б/у']],
            ],
            'home' => [
                ['Материал', 'select', $materials],
                ['Цвет', 'select', $colors],
                ['Размер', 'select', ['Маленький', 'Средний', 'Большой', 'Комплект', 'Универсальный']],
                ['Бренд', 'select', $homeBrands],
                ['Комплектация', 'select', ['1 предмет', '2 предмета', '3 предмета', 'Набор', 'Комплект']],
            ],
            'auto' => [
                ['Марка авто', 'select', ['Audi', 'BMW', 'Chevrolet', 'Dacia', 'Ford', 'Honda', 'Hyundai', 'Kia', 'Mercedes-Benz', 'Nissan', 'Opel', 'Renault', 'Skoda', 'Toyota', 'Volkswagen', 'ВАЗ', 'Другое']],
                ['Модель авто', 'select', ['Astra', 'Golf', 'Passat', 'Logan', 'Duster', 'Octavia', 'Corolla', 'Camry', 'Focus', 'Ceed', 'Tucson', 'Другое']],
                ['Производитель', 'select', $autoPartBrands],
                ['Состояние', 'select', ['Новое', 'Б/у']],
                ['Год выпуска', 'number'],
            ],
            'food' => [
                ['Вес/объем', 'select', ['До 100 г/мл', '100-250 г/мл', '250-500 г/мл', '500 г/мл - 1 кг/л', 'Больше 1 кг/л']],
                ['Производитель', 'select', ['Местный производитель', 'Молдова', 'Украина', 'Польша', 'Германия', 'Италия', 'Турция', 'Другое']],
                ['Срок годности', 'select', ['До 7 дней', 'До 30 дней', '1-6 месяцев', 'Больше 6 месяцев']],
                ['Тип упаковки', 'select', ['Пакет', 'Коробка', 'Бутылка', 'Банка', 'Без упаковки']],
                ['Страна производства', 'select', $countries],
            ],
            'sport' => [
                ['Бренд', 'select', $sportBrands],
                ['Размер', 'select', $sizes],
                ['Материал', 'select', $materials],
                ['Вид спорта', 'select', ['Фитнес', 'Бег', 'Футбол', 'Баскетбол', 'Велоспорт', 'Туризм', 'Плавание', 'Единоборства', 'Другое']],
                ['Состояние', 'select', ['Новое', 'Б/у']],
            ],
            'books' => [
                ['Автор', 'select', ['Классика', 'Современный автор', 'Иностранный автор', 'Местный автор', 'Разные авторы', 'Другое']],
                ['Издательство', 'select', ['Эксмо', 'АСТ', 'Азбука', 'МИФ', 'Просвещение', 'Penguin', 'Oxford', 'Другое']],
                ['Язык', 'select', ['Русский', 'Английский', 'Украинский', 'Румынский', 'Другой']],
                ['Год издания', 'number'],
                ['Формат', 'select', ['Твердая обложка', 'Мягкая обложка', 'Электронная книга', 'Комплект']],
            ],
            'repair' => [
                ['Бренд', 'select', $toolBrands],
                ['Материал', 'select', $materials],
                ['Размер', 'select', ['Маленький', 'Средний', 'Большой', 'Комплект', 'Универсальный']],
                ['Назначение', 'select', ['Для дома', 'Для ремонта', 'Для строительства', 'Для сантехники', 'Для электрики', 'Для сада']],
                ['Состояние', 'select', ['Новое', 'Б/у']],
            ],
            'garden' => [
                ['Бренд', 'select', $gardenBrands],
                ['Назначение', 'select', ['Посадка', 'Полив', 'Обрезка', 'Уход за газоном', 'Отдых на даче', 'Хранение']],
                ['Сезон', 'select', ['Весна', 'Лето', 'Осень', 'Зима', 'Всесезон']],
                ['Вес/объем', 'select', ['До 1 кг/л', '1-5 кг/л', '5-10 кг/л', 'Больше 10 кг/л']],
                ['Материал', 'select', $materials],
            ],
            'pets' => [
                ['Вид животного', 'select', ['Кошки', 'Собаки', 'Рыбы', 'Птицы', 'Грызуны', 'Другие']],
                ['Бренд', 'select', $petBrands],
                ['Вес/объем', 'select', ['До 500 г/мл', '500 г/мл - 1 кг/л', '1-3 кг/л', '3-10 кг/л', 'Больше 10 кг/л']],
                ['Возраст животного', 'select', ['Для малышей', 'Для взрослых', 'Для пожилых', 'Для всех возрастов']],
                ['Назначение', 'select', ['Корм', 'Уход', 'Игрушки', 'Лежанка/домик', 'Амуниция', 'Гигиена']],
            ],
            'health' => [
                ['Бренд', 'select', $brands],
                ['Назначение', 'select', ['Контроль здоровья', 'Уход', 'Ортопедия', 'Гигиена', 'Витамины', 'Реабилитация']],
                ['Размер', 'select', $sizes],
                ['Срок годности', 'select', ['До 3 месяцев', '3-6 месяцев', '6-12 месяцев', 'Больше 12 месяцев']],
                ['Тип', 'select', ['Прибор', 'Средство ухода', 'Ортопедия', 'Добавка', 'Гигиена', 'Другое']],
            ],
            'hobby' => [
                ['Материал', 'select', $materials],
                ['Бренд', 'select', ['Hasbro', 'Mattel', 'LEGO', 'Ravensburger', 'DMC', 'Yamaha', 'Casio', 'Fender', 'Другое']],
                ['Возраст', 'select', ['3+', '6+', '12+', '16+', '18+']],
                ['Комплектация', 'select', ['1 предмет', 'Набор', 'Комплект', 'Расширенный комплект']],
                ['Состояние', 'select', ['Новое', 'Б/у']],
            ],
            'office' => [
                ['Бренд', 'select', $officeBrands],
                ['Формат', 'select', ['A3', 'A4', 'A5', 'A6', 'Универсальный', 'Комплект']],
                ['Материал', 'select', $materials],
                ['Цвет', 'select', $colors],
                ['Комплектация', 'select', ['1 предмет', 'Набор', 'Комплект']],
            ],
        ];

        $specific = [
            'smart-chasy' => [
                ['Бренд', 'select', ['Apple', 'Samsung', 'Xiaomi', 'Huawei', 'Honor', 'Amazfit', 'Garmin', 'Haylou', 'Другое']],
                ['Серия/модель', 'select', ['Apple Watch', 'Galaxy Watch', 'Mi Watch', 'Amazfit Bip', 'Amazfit GTS', 'Huawei Watch', 'Honor Watch', 'Garmin', 'Другое']],
                ['Цвет корпуса', 'select', ['Черный', 'Серебристый', 'Золотой', 'Белый', 'Синий']],
                ['Материал ремешка', 'select', ['Силикон', 'Кожа', 'Металл', 'Текстиль']],
                ['Размер корпуса', 'number', null, 'мм'],
                ['Совместимость', 'select', ['Android', 'iOS', 'Android и iOS']],
            ],
            'smartfony' => [
                ['Бренд', 'select', ['Apple', 'Samsung', 'Xiaomi', 'Huawei', 'Honor', 'POCO', 'Realme', 'OnePlus', 'Motorola', 'Nokia', 'Другое']],
                ['Серия/модель', 'select', ['iPhone', 'Galaxy S', 'Galaxy A', 'Redmi Note', 'Redmi', 'POCO', 'Honor', 'Huawei P', 'Realme', 'Другое']],
                ['Память', 'select', ['32 ГБ', '64 ГБ', '128 ГБ', '256 ГБ', '512 ГБ', '1 ТБ']],
                ['Оперативная память', 'select', ['2 ГБ', '3 ГБ', '4 ГБ', '6 ГБ', '8 ГБ', '12 ГБ', '16 ГБ']],
                ['Цвет', 'select', ['Черный', 'Белый', 'Серый', 'Синий', 'Красный', 'Зеленый']],
            ],
            'igrovye-noutbuki' => [
                ['Бренд', 'select', ['Asus', 'Acer', 'Lenovo', 'HP', 'Dell', 'MSI', 'Gigabyte', 'Razer', 'Другое']],
                ['Процессор', 'select', ['Intel Core i5', 'Intel Core i7', 'Intel Core i9', 'AMD Ryzen 5', 'AMD Ryzen 7', 'AMD Ryzen 9']],
                ['Видеокарта', 'select', ['NVIDIA GTX 1650', 'NVIDIA RTX 3050', 'NVIDIA RTX 3060', 'NVIDIA RTX 4060', 'NVIDIA RTX 4070', 'AMD Radeon', 'Другое']],
                ['Оперативная память', 'select', ['8 ГБ', '16 ГБ', '32 ГБ', '64 ГБ']],
                ['SSD', 'select', ['256 ГБ', '512 ГБ', '1 ТБ', '2 ТБ']],
            ],
            'futbolki-men' => [
                ['Размер', 'select', ['S', 'M', 'L', 'XL', 'XXL', '3XL']],
                ['Цвет', 'select', ['Черный', 'Белый', 'Серый', 'Синий', 'Красный']],
                ['Материал', 'select', ['Хлопок', 'Полиэстер', 'Лен', 'Смешанная ткань']],
                ['Посадка', 'select', ['Обычная', 'Свободная', 'Slim']],
                ['Сезон', 'select', ['Лето', 'Всесезон']],
            ],
            'shiny-diski' => [
                ['Диаметр', 'select', ['R13', 'R14', 'R15', 'R16', 'R17', 'R18', 'R19', 'R20']],
                ['Сезон шин', 'select', ['Летние', 'Зимние', 'Всесезонные']],
                ['Ширина профиля', 'number', null, 'мм'],
                ['Высота профиля', 'number', null, '%'],
                ['Производитель', 'select', ['Michelin', 'Continental', 'Goodyear', 'Pirelli', 'Bridgestone', 'Nokian', 'Hankook', 'Kumho', 'Другое']],
            ],
            'korm-dlya-ryb' => [
                ['Бренд', 'select', $fishFoodBrands],
                ['Тип корма', 'select', ['Хлопья', 'Гранулы', 'Таблетки', 'Палочки', 'Замороженный', 'Живой корм']],
                ['Вид рыб', 'select', ['Все виды', 'Золотые рыбки', 'Цихлиды', 'Гуппи и мелкие рыбки', 'Сомы', 'Морские рыбы']],
                ['Вес/объем', 'select', ['До 100 мл/г', '100-250 мл/г', '250-500 мл/г', '500 мл/г - 1 кг/л', 'Больше 1 кг/л']],
                ['Назначение', 'select', ['Ежедневный корм', 'Для окраса', 'Для роста', 'Для мальков', 'Лечебный/специальный']],
            ],
            'akvariumy' => [
                ['Бренд', 'select', ['Aquael', 'Juwel', 'Tetra', 'Eheim', 'SunSun', 'Resun', 'Другое']],
                ['Объем аквариума', 'select', ['До 20 л', '20-60 л', '60-120 л', '120-250 л', 'Больше 250 л']],
                ['Форма', 'select', ['Прямоугольный', 'Круглый', 'Панорамный', 'Куб', 'Угловой']],
                ['Комплектация', 'select', ['Только аквариум', 'С крышкой', 'С фильтром', 'Полный комплект']],
                ['Материал', 'select', ['Стекло', 'Акрил']],
            ],
            'filtry-i-kompressory' => [
                ['Бренд', 'select', ['Aquael', 'Eheim', 'Tetra', 'JBL', 'SunSun', 'Resun', 'Другое']],
                ['Тип оборудования', 'select', ['Фильтр внутренний', 'Фильтр внешний', 'Компрессор', 'Помпа', 'Аэратор']],
                ['Для объема аквариума', 'select', ['До 40 л', '40-100 л', '100-200 л', '200-500 л', 'Больше 500 л']],
                ['Производительность', 'select', ['До 300 л/ч', '300-700 л/ч', '700-1200 л/ч', 'Больше 1200 л/ч']],
                ['Состояние', 'select', ['Новое', 'Б/у']],
            ],
            'korm-dlya-koshek' => [
                ['Бренд', 'select', $catBrands],
                ['Тип корма', 'select', ['Сухой', 'Влажный', 'Лакомство', 'Ветеринарная диета']],
                ['Возраст животного', 'select', ['Котенок', 'Взрослая кошка', 'Пожилая кошка', 'Для всех возрастов']],
                ['Назначение', 'select', ['Повседневный', 'Для стерилизованных', 'Для чувствительного пищеварения', 'Для шерсти', 'Диетический']],
                ['Вес/объем', 'select', ['До 400 г', '400 г - 1 кг', '1-3 кг', '3-10 кг', 'Больше 10 кг']],
            ],
            'korm-dlya-sobak' => [
                ['Бренд', 'select', $dogBrands],
                ['Тип корма', 'select', ['Сухой', 'Влажный', 'Лакомство', 'Ветеринарная диета']],
                ['Размер породы', 'select', ['Мелкие породы', 'Средние породы', 'Крупные породы', 'Все породы']],
                ['Возраст животного', 'select', ['Щенок', 'Взрослая собака', 'Пожилая собака', 'Для всех возрастов']],
                ['Вес/объем', 'select', ['До 1 кг', '1-3 кг', '3-10 кг', '10-15 кг', 'Больше 15 кг']],
            ],
            'napolniteli-dlya-koshek' => [
                ['Бренд', 'select', ['Catsan', 'Fresh Step', 'Барсик', 'Pi-Pi-Bent', 'Super Benek', 'Другое']],
                ['Тип наполнителя', 'select', ['Комкующийся', 'Впитывающий', 'Силикагелевый', 'Древесный', 'Минеральный']],
                ['Вес/объем', 'select', ['До 5 л/кг', '5-10 л/кг', '10-20 л/кг', 'Больше 20 л/кг']],
                ['Аромат', 'select', ['Без запаха', 'Лаванда', 'Древесный', 'Свежесть', 'Другой']],
                ['Назначение', 'select', ['Для кошек', 'Для котят', 'Для нескольких кошек']],
            ],
            'kogtetochki-i-domiki' => [
                ['Бренд', 'select', ['Trixie', 'Ferplast', 'Beeztees', 'Croci', 'Другое']],
                ['Тип', 'select', ['Когтеточка', 'Домик', 'Игровой комплекс', 'Лежанка', 'Тоннель']],
                ['Материал', 'select', ['Сизаль', 'Плюш', 'Дерево', 'Картон', 'Комбинированный']],
                ['Размер', 'select', ['Маленький', 'Средний', 'Большой']],
                ['Цвет', 'select', $colors],
            ],
            'amuniciya-dlya-sobak' => [
                ['Бренд', 'select', ['Trixie', 'Flexi', 'Ferplast', 'Hunter', 'Coastal', 'Другое']],
                ['Тип', 'select', ['Ошейник', 'Поводок', 'Шлейка', 'Намордник', 'Рулетка']],
                ['Размер', 'select', ['XS', 'S', 'M', 'L', 'XL']],
                ['Материал', 'select', ['Нейлон', 'Кожа', 'Металл', 'Текстиль', 'Комбинированный']],
                ['Цвет', 'select', $colors],
            ],
            'igrushki-dlya-sobak' => [
                ['Бренд', 'select', ['Trixie', 'Kong', 'Ferplast', 'GiGwi', 'Другое']],
                ['Тип игрушки', 'select', ['Мяч', 'Канат', 'Пищалка', 'Жевательная', 'Интерактивная']],
                ['Размер породы', 'select', ['Мелкие породы', 'Средние породы', 'Крупные породы', 'Все породы']],
                ['Материал', 'select', ['Резина', 'Текстиль', 'Пластик', 'Силикон', 'Канат']],
                ['Состояние', 'select', ['Новое', 'Б/у']],
            ],
            'kletki' => [
                ['Вид животного', 'select', ['Птицы', 'Грызуны']],
                ['Материал', 'select', ['Металл', 'Пластик', 'Дерево', 'Комбинированный']],
                ['Размер', 'select', ['Маленькая', 'Средняя', 'Большая']],
                ['Комплектация', 'select', ['Только клетка', 'С поилкой', 'С кормушкой', 'Полный комплект']],
                ['Состояние', 'select', ['Новое', 'Б/у']],
            ],
            'korm-pticy-gryzuny' => [
                ['Вид животного', 'select', ['Попугаи', 'Канарейки', 'Хомяки', 'Морские свинки', 'Кролики', 'Шиншиллы']],
                ['Бренд', 'select', ['Vitakraft', 'Versele-Laga', 'Padovan', 'Fiory', 'Природа', 'Другое']],
                ['Тип корма', 'select', ['Зерновая смесь', 'Гранулы', 'Лакомство', 'Сено', 'Минеральная добавка']],
                ['Вес/объем', 'select', ['До 500 г', '500 г - 1 кг', '1-3 кг', 'Больше 3 кг']],
                ['Возраст животного', 'select', ['Для малышей', 'Для взрослых', 'Для всех возрастов']],
            ],
            'aksessuary-pticy-gryzuny' => [
                ['Вид животного', 'select', ['Птицы', 'Хомяки', 'Морские свинки', 'Кролики', 'Шиншиллы']],
                ['Тип', 'select', ['Поилка', 'Кормушка', 'Домик', 'Игрушка', 'Колесо', 'Купалка']],
                ['Материал', 'select', ['Пластик', 'Металл', 'Дерево', 'Керамика', 'Комбинированный']],
                ['Размер', 'select', ['Маленький', 'Средний', 'Большой']],
                ['Состояние', 'select', ['Новое', 'Б/у']],
            ],
        ];

        $managedNames = [
            'Автор', 'Бренд', 'Вес/объем', 'Вид животного', 'Вид спорта', 'Видеокарта',
            'Вид рыб', 'Высота профиля', 'Гарантия', 'Год выпуска', 'Год издания', 'Диаметр',
            'Издательство', 'Комплектация', 'Марка авто', 'Материал', 'Материал верха',
            'Материал ремешка', 'Модель', 'Модель авто', 'Назначение', 'Объем',
            'Оперативная память', 'Память', 'Пол', 'Пол ребенка', 'Посадка',
            'Производитель', 'Производительность', 'Процессор', 'Размер', 'Размер корпуса', 'Размер обуви',
            'Сезон', 'Сезон шин', 'Серия/модель', 'Совместимость', 'Состояние',
            'Срок годности', 'SSD', 'Страна производства', 'Тип', 'Тип кожи',
            'Тип корма', 'Тип игрушки', 'Тип наполнителя', 'Тип оборудования', 'Тип упаковки',
            'Форма', 'Формат', 'Цвет', 'Цвет корпуса', 'Ширина профиля', 'Язык',
            'Аромат', 'Возраст', 'Возраст животного', 'Для объема аквариума', 'Объем аквариума', 'Размер породы',
        ];

        $rootToSet = [
            'elektronika' => 'electronics',
            'odezhda' => 'clothes',
            'obuv' => 'shoes',
            'krasota-i-uhod' => 'beauty',
            'detskie-tovary' => 'kids',
            'dom-i-byt' => 'home',
            'avtotovary' => 'auto',
            'produkty' => 'food',
            'sport-i-otdyh' => 'sport',
            'knigi-i-kancelyariya' => 'books',
            'stroitelstvo-i-remont' => 'repair',
            'sad-i-ogorod' => 'garden',
            'zootovary' => 'pets',
            'zdorove' => 'health',
            'hobbi-i-tvorchestvo' => 'hobby',
            'ofis-i-biznes' => 'office',
        ];

        $allCategories = Category::select('id', 'name', 'slug', 'parent_id')->get()->keyBy('id');
        $parentIds = $allCategories->pluck('parent_id')->filter()->unique()->flip();
        $leafCategories = $allCategories->reject(fn (Category $category) => $parentIds->has($category->id));

        foreach ($leafCategories as $category) {
            $root = $this->rootFor($category, $allCategories);
            $setKey = $rootToSet[$root?->slug] ?? 'common';
            $definitions = $specific[$category->slug] ?? $sets[$setKey] ?? $sets['common'];
            $desiredAttributeIds = [];

            foreach (array_slice($definitions, 0, 6) as $sort => $definition) {
                $attributeId = $attribute(
                    $definition[0],
                    $definition[1] ?? 'text',
                    $definition[2] ?? null,
                    $definition[3] ?? null,
                    $definition[4] ?? true
                );
                $desiredAttributeIds[] = $attributeId;

                DB::table('attribute_category')->updateOrInsert(
                    [
                        'category_id' => $category->id,
                        'attribute_id' => $attributeId,
                    ],
                    [
                        'sort_order' => $sort,
                    ]
                );
            }

            $managedAttributeIds = DB::table('attributes')
                ->whereIn('name', $managedNames)
                ->pluck('id');

            DB::table('attribute_category')
                ->where('category_id', $category->id)
                ->whereIn('attribute_id', $managedAttributeIds)
                ->whereNotIn('attribute_id', $desiredAttributeIds)
                ->delete();

            CategoryFilterCacheService::clearForCategoryAndAncestors($category);
        }
    }

    private function rootFor(Category $category, $categories): ?Category
    {
        $current = $category;
        $guard = 0;

        while ($current?->parent_id && $guard < 20) {
            $current = $categories->get($current->parent_id);
            $guard++;
        }

        return $current;
    }
}
