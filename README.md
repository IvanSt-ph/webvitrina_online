# WebVitrina

Многостраничный маркетплейс на Laravel (PHP 8.4, Laravel 12) с ролями:
- 👤 Покупатель
- 🏪 Продавец
- 🛠 Администратор

Фичи:
- товары, категории, атрибуты;
- корзина, заказы, отзывы;
- отдельные кабинеты продавца и покупателя;
- админка для управления товарами, категориями, баннерами и т.д.

---

## 1. Требования

- PHP >= 8.2 (рекомендуется 8.3–8.4)
- Composer
- MySQL 8.x
- Node.js + npm
- Расширения PHP: `pdo_mysql`, `mbstring`, `openssl`, `json`, `fileinfo`

---

## 2. Установка (локальная разработка)

```bash
git clone https://github.com/IvanSt-ph/webvitrina.git
cd webvitrina

# зависимости PHP
composer install

# зависимости фронтенда
npm install
