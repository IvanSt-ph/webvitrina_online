@echo off
echo ========================================
echo    🔨 Собираем фронтенд...
echo ========================================
call npm run build

echo.
echo ========================================
echo    📦 Добавляем изменения в Git...
echo ========================================
git add .
git commit -m "Обновление фронтенда"

echo.
echo ========================================
echo    🚀 Отправляем на GitHub...
echo ========================================
git push

echo.
echo ========================================
echo    ✅ Готово!
echo ========================================
echo.
echo 📌 Что делать на сервере:
echo    cd /www/wwwroot/webv3
echo    git pull
echo    npm run build
echo    php artisan optimize:clear
echo.
pause