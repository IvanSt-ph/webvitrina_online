@echo off

echo.
echo    BUILD FRONTEND...

call npm install
call npm run build

echo.
echo    GIT ADD...

git add .

git commit -m "Frontend update"

echo.
echo    PUSH TO GITHUB...

git push

echo.
echo    DONE!

echo.
echo    SERVER COMMANDS:
echo ----------------------------------------
echo    cd /www/wwwroot/webv3
echo    git pull
echo    npm install
echo    npm run build
echo    php artisan optimize:clear
echo ----------------------------------------

pause