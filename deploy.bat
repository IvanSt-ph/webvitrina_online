@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

echo.
echo ===============================
echo   DEPLOY START
echo ===============================

echo.
echo [1/4] Installing dependencies...
call npm install
if errorlevel 1 (
    echo ERROR: npm install failed
    pause
    exit /b
)

echo.
echo [2/4] Building frontend...
call npm run build
if errorlevel 1 (
    echo ERROR: build failed
    pause
    exit /b
)

echo.
echo [3/4] Versioning...

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
echo [4/4] Git operations...

git add .
git commit -m "%fullMsg%"
git push

echo.
echo ===============================
echo DEPLOY DONE: %fullMsg%
echo ===============================

pause