# WebVitrina

Многостраничный маркетплейс на Laravel с ролями:

- 👤 Покупатель  
- 🧑‍💼 Продавец  
- 🛠 Администратор  

Проект создаётся как реальный коммерческий продукт, а не учебный пример: с товарами, заказами, атрибутами, кабинетами и аналитикой.

---

## 🚀 Основные возможности

- Каталог товаров с категориями и атрибутами  
- Личные кабинеты:
  - Покупатель: избранное, корзина, заказы, отзывы  
  - Продавец: добавление товаров, управление заказами, аналитика  
  - Администратор: управление товарами, категориями, баннерами  
- Корзина и оформление заказов  
- Отзывы и рейтинг товаров  
- Фильтры и сортировка (цена, рейтинг, популярность и т.д.)  
- Современный стек фронтенда:
  - Blade  
  - TailwindCSS  
  - Alpine.js  
  - Vite  

---

## 🧱 Технологический стек

- PHP 8.2+ (рекомендуется 8.3–8.4)  
- Laravel 10/11/12 (см. версию в `composer.json`)  
- MySQL 8.x  
- Node.js + npm  
- TailwindCSS, Alpine.js, Vite  

---


php artisan optimize:clear
php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear


## ⚙️ 1. Требования

- PHP >= 8.2  
- Composer  
- MySQL 8.x  
- Node.js + npm  
- PHP-расширения:  
  `pdo_mysql`, `mbstring`, `openssl`, `json`, `fileinfo`

---

## 🛠 2. Установка (локальная разработка)

```bash
git clone https://github.com/IvanSt-ph/webvitrina.git
cd webvitrina

# Устанавливаем PHP-зависимости
composer install

# Устанавливаем frontend-зависимости
npm install

# Создаём .env
cp .env.example .env

# Генерируем ключ приложения
php artisan key:generate
