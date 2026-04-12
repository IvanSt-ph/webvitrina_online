@echo off

echo    🔨 Собираем фронтенд...

call npm run build

echo.

echo    📦 Добавляем изменения в Git...

git add .
git commit -m "Обновление фронтенда"

echo.

echo    🚀 Отправляем на GitHub...

git push

echo.

echo    ✅ Готово!

echo.
echo 📌 Что делать на сервере:
echo    cd /www/wwwroot/webv3
echo    git pull
echo    npm run build
echo    php artisan optimize:clear
echo.
pause