@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

echo.
echo ===============================
echo   DEPLOY START
echo ===============================

echo.
echo [1/7] Installing dependencies...
call npm ci
if errorlevel 1 (
    echo ERROR: npm ci failed
    pause
    exit /b
)

echo.
echo [2/7] Checking Composer security advisories...
call composer audit
if errorlevel 1 (
    echo ERROR: composer audit failed
    pause
    exit /b
)

echo.
echo [3/7] Checking npm production security advisories...
call npm audit --omit=dev
if errorlevel 1 (
    echo ERROR: npm audit failed
    pause
    exit /b
)

echo.
echo [4/7] Running tests...
call php artisan test
if errorlevel 1 (
    echo ERROR: tests failed
    pause
    exit /b
)

echo.
echo [5/7] Building frontend...
call npm run build
if errorlevel 1 (
    echo ERROR: build failed
    pause
    exit /b
)

echo.
echo [6/7] Versioning...

if not exist version.txt (
    echo 1 > version.txt
)

set /p version=<version.txt
set /a version=%version%+1
echo %version% > version.txt

echo.
set /p commitMsg=Enter commit message: 

if "%commitMsg%"=="" set commitMsg=update

set fullMsg=%commitMsg% v%version%

echo.
echo [7/7] Git operations...

git add .
git commit -m "%fullMsg%"
git push

echo.
echo ===============================
echo DEPLOY DONE: %fullMsg%
echo ===============================

pause
