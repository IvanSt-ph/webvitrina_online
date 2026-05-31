<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $sortOrder = 0;

        /*
        |--------------------------------------------------------------------------
        | Helper: создание/обновление категории по slug
        |--------------------------------------------------------------------------
        */
        $add = function (string $name, $parent = null, string $slug = null) use (&$sortOrder) {
            $finalSlug = $slug ?: Str::rusSlug($name);
            $data = [
                'name'       => $name,
                'parent_id'  => $parent,
                'sort_order' => $sortOrder++,
                'is_active'  => 1,
                'updated_at' => now(),
            ];

            $existingId = DB::table('categories')->where('slug', $finalSlug)->value('id');

            if ($existingId) {
                DB::table('categories')->where('id', $existingId)->update($data);

                return (int) $existingId;
            }

            return DB::table('categories')->insertGetId($data + [
                'slug'       => $finalSlug,
                'created_at' => now(),
            ]);
        };

        $branch = function (int $parent, array $items) use (&$branch, $add): void {
            foreach ($items as $item) {
                $children = $item[2] ?? [];
                $id = $add($item[0], $parent, $item[1] ?? null);

                if ($children) {
                    $branch($id, $children);
                }
            }
        };

        /*
        |--------------------------------------------------------------------------
        | ROOT CATEGORIES
        |--------------------------------------------------------------------------
        */

        $electronics = $add('Электроника');
        $clothes     = $add('Одежда');
        $shoes       = $add('Обувь');
        $beauty      = $add('Красота и уход');
        $kids        = $add('Детские товары');
        $home        = $add('Дом и быт');
        $auto        = $add('Автотовары');
        $food        = $add('Продукты');
        $sport       = $add('Спорт и отдых', null, 'sport-i-otdyh');
        $books       = $add('Книги и канцелярия', null, 'knigi-i-kancelyariya');
        $repair      = $add('Строительство и ремонт', null, 'stroitelstvo-i-remont');
        $garden      = $add('Сад и огород', null, 'sad-i-ogorod');
        $pets        = $add('Зоотовары', null, 'zootovary');
        $health      = $add('Здоровье', null, 'zdorove');
        $hobby       = $add('Хобби и творчество', null, 'hobbi-i-tvorchestvo');
        $office      = $add('Офис и бизнес', null, 'ofis-i-biznes');

        /*
        |--------------------------------------------------------------------------
        | ОБУВЬ
        |--------------------------------------------------------------------------
        */

        $menShoes = $add('Мужская обувь', $shoes, 'men-shoes');
        $add('Кроссовки', $menShoes, 'krossovki-men');
        $add('Ботинки', $menShoes, 'botinki-men');

        $womenShoes = $add('Женская обувь', $shoes, 'women-shoes');
        $add('Кроссовки', $womenShoes, 'krossovki-women');
        $add('Босоножки', $womenShoes, 'bosonozhki-women');
        $add('Туфли', $womenShoes, 'tufli-women');

        $kidsShoes = $add('Детская обувь', $shoes, 'kids-shoes');
        $add('Кеды', $kidsShoes, 'kedy-kids');
        $add('Сандалии', $kidsShoes, 'sandalii-kids');

        /*
        |--------------------------------------------------------------------------
        | ЭЛЕКТРОНИКА
        |--------------------------------------------------------------------------
        */

        $phones = $add('Смартфоны и гаджеты', $electronics, 'smartfony-i-gadzhety');
        $add('Смартфоны', $phones, 'smartfony');
        $add('Аксессуары', $phones, 'aksessuary-smartfony');

        $pc = $add('Ноутбуки и компьютеры', $electronics, 'noutbuki-kompyutery');
        $add('Игровые ноутбуки', $pc, 'igrovye-noutbuki');
        $add('Офисные ноутбуки', $pc, 'ofisnye-noutbuki');
        $add('Периферия', $pc, 'periferiya');
        $add('Комплектующие', $pc, 'komplektuyushchie');

        $tv = $add('ТВ и мультимедиа', $electronics, 'tv-i-multimedia');
        $add('Телевизоры', $tv, 'televizory');
        $add('Саундбары', $tv, 'soundbary');

        $smart = $add('Умный дом', $electronics, 'umnyi-dom');
        $add('Умные лампочки', $smart, 'umnye-lampy');
        $add('Камеры', $smart, 'umnye-kamery');

        /*
        |--------------------------------------------------------------------------
        | ОДЕЖДА
        |--------------------------------------------------------------------------
        */

        $men = $add('Мужская одежда', $clothes, 'men-wear');
        $add('Футболки', $men, 'futbolki-men');
        $add('Джинсы', $men, 'dzhinsy-men');
        $add('Брюки', $men, 'bryuki-men');
        $add('Верхняя одежда', $men, 'verkhnyaya-odezhda-men');

        $women = $add('Женская одежда', $clothes, 'women-wear');
        $add('Платья', $women, 'platya-women');
        $add('Блузки', $women, 'bluzki-women');
        $add('Топы', $women, 'topy-women');
        $add('Кофты', $women, 'kofty-women');

        $kidsWear = $add('Детская одежда', $clothes, 'kids-wear');
        $add('До 1 года', $kidsWear, 'do-1-goda');
        $add('1–7 лет', $kidsWear, '1-7-let');
        $add('7–14 лет', $kidsWear, '7-14-let');

        /*
        |--------------------------------------------------------------------------
        | КРАСОТА
        |--------------------------------------------------------------------------
        */

        $add('Парфюм', $beauty, 'parfyum');
        $add('Уход за лицом', $beauty, 'uhod-za-litsom');
        $add('Уход за телом', $beauty, 'uhod-za-telom');
        $add('Макияж', $beauty, 'makiyazh');

        /*
        |--------------------------------------------------------------------------
        | ДЕТСКИЕ
        |--------------------------------------------------------------------------
        */

        $add('Игрушки', $kids, 'igrushki');
        $add('Одежда', $kids, 'odezhda-kids');
        $add('Школьные товары', $kids, 'shkolnye-tovary');

        /*
        |--------------------------------------------------------------------------
        | ДОМ И БЫТ
        |--------------------------------------------------------------------------
        */

        $light = $add('Освещение', $home, 'osveshchenie');
        $add('Лампочки', $light, 'lampochki');
        $add('Торшеры', $light, 'torshery');

        $textile = $add('Текстиль', $home, 'tekstil');
        $add('Пледы', $textile, 'pledy');
        $add('Постельное белье', $textile, 'postelnoe-bele');

        $storage = $add('Хранение', $home, 'khranenie');
        $add('Корзины', $storage, 'korziny');
        $add('Контейнеры', $storage, 'konteinery');

        /*
        |--------------------------------------------------------------------------
        | АВТО
        |--------------------------------------------------------------------------
        */

        $add('Шины и диски', $auto, 'shiny-diski');
        $add('Аксессуары для авто', $auto, 'aksessuary-avto');
        $add('Автохимия', $auto, 'avtohimiya');

        /*
        |--------------------------------------------------------------------------
        | ПРОДУКТЫ
        |--------------------------------------------------------------------------
        */

        $add('Фрукты', $food, 'frukty');
        $add('Овощи', $food, 'ovoshchi');
        $add('Мясо', $food, 'myaso');
        $add('Напитки', $food, 'napitki');

        /*
        |--------------------------------------------------------------------------
        | РАСШИРЕННОЕ ДЕРЕВО: востребованные категории, подкатегории и 3-й уровень
        |--------------------------------------------------------------------------
        */

        $branch($electronics, [
            ['Планшеты и электронные книги', 'planshety-i-elektronnye-knigi', [
                ['Планшеты', 'planshety', [
                    ['Планшеты Android', 'planshety-android'],
                    ['Планшеты Apple iPad', 'planshety-apple-ipad'],
                    ['Аксессуары для планшетов', 'aksessuary-dlya-planshetov'],
                ]],
                ['Электронные книги', 'elektronnye-knigi', [
                    ['Ридеры с подсветкой', 'ridery-s-podsvetkoy'],
                    ['Чехлы для электронных книг', 'chehly-dlya-elektronnyh-knig'],
                ]],
            ]],
            ['Аудиотехника', 'audiotehnika', [
                ['Наушники', 'naushniki', [
                    ['Беспроводные наушники', 'besprovodnye-naushniki'],
                    ['Проводные наушники', 'provodnye-naushniki'],
                    ['Игровые гарнитуры', 'igrovye-garnitury'],
                ]],
                ['Колонки', 'kolonki', [
                    ['Портативные колонки', 'portativnye-kolonki'],
                    ['Домашние колонки', 'domashnie-kolonki'],
                ]],
                ['Микрофоны', 'mikrofony', [
                    ['Студийные микрофоны', 'studiynye-mikrofony'],
                    ['Петличные микрофоны', 'petlichnye-mikrofony'],
                ]],
            ]],
            ['Фото и видео', 'foto-i-video', [
                ['Фотоаппараты', 'fotoapparaty', [
                    ['Зеркальные камеры', 'zerkalnye-kamery'],
                    ['Беззеркальные камеры', 'bezzerkalnye-kamery'],
                    ['Экшн-камеры', 'ekshn-kamery'],
                ]],
                ['Объективы', 'obektivy', [
                    ['Объективы Canon', 'obektivy-canon'],
                    ['Объективы Nikon', 'obektivy-nikon'],
                    ['Объективы Sony', 'obektivy-sony'],
                ]],
                ['Штативы и свет', 'shtativy-i-svet', [
                    ['Штативы', 'shtativy'],
                    ['Кольцевые лампы', 'kolcevye-lampy'],
                    ['Софтбоксы', 'softboksy'],
                ]],
            ]],
            ['Бытовая техника', 'bytovaya-tehnika', [
                ['Крупная техника', 'krupnaya-bytovaya-tehnika', [
                    ['Холодильники', 'holodilniki'],
                    ['Стиральные машины', 'stiralnye-mashiny'],
                    ['Посудомоечные машины', 'posudomoechnye-mashiny'],
                ]],
                ['Техника для кухни', 'tehnika-dlya-kuhni', [
                    ['Мультиварки', 'multivarki'],
                    ['Блендеры', 'blendery'],
                    ['Кофемашины', 'kofemashiny'],
                ]],
                ['Климатическая техника', 'klimaticheskaya-tehnika', [
                    ['Кондиционеры', 'kondicionery'],
                    ['Обогреватели', 'obogrevateli'],
                    ['Очистители воздуха', 'ochistiteli-vozduha'],
                ]],
            ]],
        ]);

        $branch($phones, [
            ['Чехлы и защита', 'chehly-i-zashchita-smartfony', [
                ['Чехлы для iPhone', 'chehly-dlya-iphone'],
                ['Чехлы для Samsung', 'chehly-dlya-samsung'],
                ['Защитные стекла', 'zashchitnye-stekla'],
            ]],
            ['Зарядки и кабели', 'zaryadki-i-kabeli-smartfony', [
                ['Блоки питания', 'bloki-pitaniya-smartfony'],
                ['USB-C кабели', 'usb-c-kabeli'],
                ['Power Bank', 'power-bank'],
            ]],
            ['Смарт-часы и браслеты', 'smart-chasy-i-braslety', [
                ['Смарт-часы', 'smart-chasy'],
                ['Фитнес-браслеты', 'fitnes-braslety'],
                ['Ремешки', 'remeshki-dlya-smart-chasov'],
            ]],
        ]);

        $branch($clothes, [
            ['Аксессуары', 'aksessuary-odezhda', [
                ['Сумки и рюкзаки', 'sumki-i-ryukzaki', [
                    ['Женские сумки', 'zhenskie-sumki'],
                    ['Мужские сумки', 'muzhskie-sumki'],
                    ['Рюкзаки', 'ryukzaki'],
                ]],
                ['Головные уборы', 'golovnye-ubory', [
                    ['Шапки', 'shapki'],
                    ['Кепки', 'kepki'],
                    ['Панамы', 'panamy'],
                ]],
                ['Ремни и перчатки', 'remni-i-perchatki', [
                    ['Ремни', 'remni'],
                    ['Перчатки', 'perchatki'],
                ]],
            ]],
            ['Белье и домашняя одежда', 'bele-i-domashnyaya-odezhda', [
                ['Мужское белье', 'muzhskoe-bele'],
                ['Женское белье', 'zhenskoe-bele'],
                ['Пижамы и халаты', 'pizhamy-i-halaty'],
            ]],
            ['Спецодежда', 'specodezhda', [
                ['Рабочая одежда', 'rabochaya-odezhda'],
                ['Медицинская одежда', 'medicinskaya-odezhda'],
                ['Защитная обувь', 'zashchitnaya-obuv'],
            ]],
        ]);

        $branch($shoes, [
            ['Спортивная обувь', 'sportivnaya-obuv', [
                ['Для бега', 'obuv-dlya-bega'],
                ['Для зала', 'obuv-dlya-zala'],
                ['Для футбола', 'futbolnaya-obuv'],
            ]],
            ['Уход за обувью', 'uhod-za-obuvyu', [
                ['Кремы и пропитки', 'kremy-i-propitki-dlya-obuvi'],
                ['Стельки', 'stelki'],
                ['Щетки и губки', 'shchetki-i-gubki-dlya-obuvi'],
            ]],
        ]);

        $branch($beauty, [
            ['Волосы', 'volosy', [
                ['Шампуни', 'shampuni'],
                ['Бальзамы и маски', 'balzamy-i-maski-dlya-volos'],
                ['Краска для волос', 'kraska-dlya-volos'],
            ]],
            ['Маникюр и педикюр', 'manikyur-i-pedikyur', [
                ['Гель-лаки', 'gel-laki'],
                ['Инструменты', 'instrumenty-dlya-manikyura'],
                ['Лампы для маникюра', 'lampy-dlya-manikyura'],
            ]],
            ['Мужской уход', 'muzhskoy-uhod', [
                ['Бритье', 'brite'],
                ['Уход за бородой', 'uhod-za-borodoy'],
                ['Дезодоранты', 'dezodoranty'],
            ]],
        ]);

        $branch($kids, [
            ['Коляски и автокресла', 'kolyaski-i-avtokresla', [
                ['Коляски', 'kolyaski'],
                ['Автокресла', 'avtokresla'],
                ['Аксессуары для колясок', 'aksessuary-dlya-kolyasok'],
            ]],
            ['Кормление и уход', 'kormlenie-i-uhod-deti', [
                ['Бутылочки и соски', 'butylochki-i-soski'],
                ['Подгузники', 'podguzniki'],
                ['Детская косметика', 'detskaya-kosmetika'],
            ]],
            ['Развитие и творчество', 'razvitie-i-tvorchestvo-deti', [
                ['Конструкторы', 'konstruktory-deti'],
                ['Настольные игры', 'nastolnye-igry-deti'],
                ['Наборы для творчества', 'nabory-dlya-tvorchestva-deti'],
            ]],
        ]);

        $branch($home, [
            ['Кухня', 'kuhnya', [
                ['Посуда', 'posuda', [
                    ['Кастрюли и сковороды', 'kastryuli-i-skovorody'],
                    ['Тарелки и салатники', 'tarelki-i-salatniki'],
                    ['Кружки и стаканы', 'kruzhki-i-stakany'],
                ]],
                ['Кухонные аксессуары', 'kuhonnye-aksessuary', [
                    ['Ножи', 'nozhi-kuhonnye'],
                    ['Доски разделочные', 'doski-razdelochnye'],
                    ['Контейнеры для еды', 'konteynery-dlya-edy'],
                ]],
            ]],
            ['Уборка', 'uborka', [
                ['Бытовая химия', 'bytovaya-himiya'],
                ['Инвентарь для уборки', 'inventar-dlya-uborki'],
                ['Мешки и пакеты', 'meshki-i-pakety'],
            ]],
            ['Декор', 'dekor', [
                ['Картины и постеры', 'kartiny-i-postery'],
                ['Вазы', 'vazy'],
                ['Свечи и ароматизаторы', 'svechi-i-aromatizatory'],
            ]],
            ['Мебель', 'mebel', [
                ['Гостиная', 'mebel-gostinaya'],
                ['Спальня', 'mebel-spalnya'],
                ['Офисная мебель', 'ofisnaya-mebel'],
            ]],
        ]);

        $branch($auto, [
            ['Запчасти', 'zapchasti', [
                ['Двигатель', 'zapchasti-dvigatel'],
                ['Тормозная система', 'tormoznaya-sistema'],
                ['Подвеска', 'podveska'],
                ['Фильтры', 'avto-filtry'],
            ]],
            ['Электроника для авто', 'elektronika-dlya-avto', [
                ['Видеорегистраторы', 'videoregistratory'],
                ['Автомагнитолы', 'avtomagnitoly'],
                ['Парктроники и камеры', 'parktroniki-i-kamery'],
            ]],
            ['Автоуход', 'avtouhod', [
                ['Мойка и полировка', 'moyka-i-polirovka'],
                ['Средства для салона', 'sredstva-dlya-salona'],
                ['Щетки и скребки', 'shchetki-i-skrebki'],
            ]],
        ]);

        $branch($food, [
            ['Бакалея', 'bakaleya', [
                ['Крупы', 'krupy'],
                ['Макароны', 'makarony'],
                ['Мука и сахар', 'muka-i-sahar'],
            ]],
            ['Молочные продукты', 'molochnye-produkty', [
                ['Молоко', 'moloko'],
                ['Сыры', 'syry'],
                ['Йогурты', 'yogurty'],
            ]],
            ['Заморозка', 'zamorozka', [
                ['Овощи и смеси', 'zamorozhennye-ovoshchi-i-smesi'],
                ['Полуфабрикаты', 'polufabrikaty'],
                ['Мороженое', 'morozhenoe'],
            ]],
            ['Сладости и снеки', 'sladosti-i-sneki', [
                ['Шоколад', 'shokolad'],
                ['Печенье и вафли', 'pechene-i-vafli'],
                ['Чипсы и сухарики', 'chipsy-i-suhariki'],
            ]],
        ]);

        $branch($sport, [
            ['Фитнес и тренажеры', 'fitnes-i-trenazhery', [
                ['Тренажеры', 'trenazhery'],
                ['Гантели и гири', 'ganteli-i-giri'],
                ['Коврики и резинки', 'kovriki-i-rezinki'],
            ]],
            ['Туризм и кемпинг', 'turizm-i-kemping', [
                ['Палатки', 'palatki'],
                ['Спальные мешки', 'spalnye-meshki'],
                ['Рюкзаки туристические', 'ryukzaki-turisticheskie'],
            ]],
            ['Велоспорт', 'velosport', [
                ['Велосипеды', 'velosipedy'],
                ['Самокаты', 'samokaty'],
                ['Велоаксессуары', 'veloaksessuary'],
            ]],
            ['Командные виды спорта', 'komandnye-vidy-sporta', [
                ['Футбол', 'futbol'],
                ['Баскетбол', 'basketbol'],
                ['Волейбол', 'voleybol'],
            ]],
        ]);

        $branch($books, [
            ['Книги', 'knigi', [
                ['Художественная литература', 'hudozhestvennaya-literatura'],
                ['Детские книги', 'detskie-knigi'],
                ['Бизнес и саморазвитие', 'biznes-i-samorazvitie'],
                ['Учебная литература', 'uchebnaya-literatura'],
            ]],
            ['Канцелярия', 'kancelyariya', [
                ['Письменные принадлежности', 'pismennye-prinadlezhnosti'],
                ['Бумага и тетради', 'bumaga-i-tetradi'],
                ['Папки и органайзеры', 'papki-i-organayzery'],
            ]],
            ['Товары для учебы', 'tovary-dlya-ucheby', [
                ['Рюкзаки школьные', 'ryukzaki-shkolnye'],
                ['Пеналы', 'penaly'],
                ['Наборы школьника', 'nabory-shkolnika'],
            ]],
        ]);

        $branch($repair, [
            ['Инструменты', 'instrumenty', [
                ['Электроинструменты', 'elektroinstrumenty', [
                    ['Дрели и шуруповерты', 'dreli-i-shurupoverty'],
                    ['Болгарки', 'bolgarki'],
                    ['Перфораторы', 'perforatory'],
                ]],
                ['Ручной инструмент', 'ruchnoy-instrument', [
                    ['Отвертки', 'otvertki'],
                    ['Ключи', 'klyuchi'],
                    ['Молотки', 'molotki'],
                ]],
            ]],
            ['Материалы', 'stroitelnye-materialy', [
                ['Сухие смеси', 'suhie-smesi'],
                ['Краски и лаки', 'kraski-i-laki'],
                ['Плитка', 'plitka'],
                ['Обои', 'oboi'],
            ]],
            ['Сантехника', 'santehnika', [
                ['Смесители', 'smesiteli'],
                ['Раковины', 'rakoviny'],
                ['Унитазы', 'unitazy'],
                ['Душевые системы', 'dushevye-sistemy'],
            ]],
            ['Электрика', 'elektrika', [
                ['Розетки и выключатели', 'rozetki-i-vyklyuchateli'],
                ['Кабели и провода', 'kabeli-i-provoda'],
                ['Автоматы и щитки', 'avtomaty-i-shchitki'],
            ]],
        ]);

        $branch($garden, [
            ['Растения и семена', 'rasteniya-i-semena', [
                ['Семена овощей', 'semena-ovoshchey'],
                ['Семена цветов', 'semena-cvetov'],
                ['Саженцы', 'sazhency'],
            ]],
            ['Садовая техника', 'sadovaya-tehnika', [
                ['Газонокосилки', 'gazonokosilki'],
                ['Триммеры', 'trimmery'],
                ['Мойки высокого давления', 'moyki-vysokogo-davleniya'],
            ]],
            ['Инвентарь', 'sadovyy-inventar', [
                ['Лопаты и грабли', 'lopaty-i-grabli'],
                ['Секаторы', 'sekatory'],
                ['Шланги и полив', 'shlangi-i-poliv'],
            ]],
            ['Дача и отдых', 'dacha-i-otdyh', [
                ['Мангалы', 'mangaly'],
                ['Садовая мебель', 'sadovaya-mebel'],
                ['Бассейны', 'basseyny'],
            ]],
        ]);

        $branch($pets, [
            ['Кошки', 'koshki', [
                ['Корм для кошек', 'korm-dlya-koshek'],
                ['Наполнители', 'napolniteli-dlya-koshek'],
                ['Когтеточки и домики', 'kogtetochki-i-domiki'],
            ]],
            ['Собаки', 'sobaki', [
                ['Корм для собак', 'korm-dlya-sobak'],
                ['Амуниция', 'amuniciya-dlya-sobak'],
                ['Игрушки для собак', 'igrushki-dlya-sobak'],
            ]],
            ['Аквариумистика', 'akvariumistika', [
                ['Аквариумы', 'akvariumy'],
                ['Корм для рыб', 'korm-dlya-ryb'],
                ['Фильтры и компрессоры', 'filtry-i-kompressory'],
            ]],
            ['Птицы и грызуны', 'pticy-i-gryzuny', [
                ['Клетки', 'kletki'],
                ['Корм', 'korm-pticy-gryzuny'],
                ['Аксессуары', 'aksessuary-pticy-gryzuny'],
            ]],
        ]);

        $branch($health, [
            ['Медицинская техника', 'medicinskaya-tehnika', [
                ['Тонометры', 'tonometry'],
                ['Ингаляторы', 'ingalyatory'],
                ['Термометры', 'termometry'],
            ]],
            ['Ортопедия', 'ortopediya', [
                ['Стельки ортопедические', 'ortopedicheskie-stelki'],
                ['Бандажи', 'bandazhi'],
                ['Компрессионный трикотаж', 'kompressionnyy-trikotazh'],
            ]],
            ['Витамины и добавки', 'vitaminy-i-dobavki', [
                ['Витамины', 'vitaminy'],
                ['Спортивное питание', 'sportivnoe-pitanie'],
                ['Минералы', 'mineraly'],
            ]],
            ['Гигиена', 'gigiena', [
                ['Зубная гигиена', 'zubnaya-gigiena'],
                ['Средства личной гигиены', 'sredstva-lichnoy-gigieny'],
                ['Маски и перчатки', 'maski-i-perchatki'],
            ]],
        ]);

        $branch($hobby, [
            ['Рукоделие', 'rukodelie', [
                ['Вязание', 'vyazanie'],
                ['Шитье', 'shite'],
                ['Бисер и украшения', 'biser-i-ukrasheniya'],
            ]],
            ['Музыкальные инструменты', 'muzykalnye-instrumenty', [
                ['Гитары', 'gitary'],
                ['Клавишные', 'klavishnye'],
                ['Звук и аксессуары', 'zvuk-i-aksessuary'],
            ]],
            ['Коллекционирование', 'kollekcionirovanie', [
                ['Монеты', 'monety'],
                ['Марки', 'marki'],
                ['Фигурки', 'figurki'],
            ]],
            ['Настольные игры и пазлы', 'nastolnye-igry-i-pazly', [
                ['Настольные игры', 'nastolnye-igry'],
                ['Пазлы', 'pazly'],
                ['Карточные игры', 'kartochnye-igry'],
            ]],
        ]);

        $branch($office, [
            ['Торговое оборудование', 'torgovoe-oborudovanie', [
                ['Кассы и терминалы', 'kassy-i-terminaly'],
                ['Сканеры штрихкодов', 'skanery-shtrihkodov'],
                ['Весы торговые', 'vesy-torgovye'],
            ]],
            ['Офисная техника', 'ofisnaya-tehnika', [
                ['Принтеры', 'printery'],
                ['Сканеры', 'skanery'],
                ['Расходники', 'rashodniki-dlya-orgtehniki'],
            ]],
            ['Упаковка', 'upakovka', [
                ['Коробки', 'korobki'],
                ['Пакеты', 'pakety'],
                ['Пленка и скотч', 'plenka-i-skotch'],
            ]],
        ]);
    }
}
