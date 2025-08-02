@echo off
echo Starting your app...

REM Start npm run dev in a new console
start cmd /k "npm run dev"

REM Start tailwindcss in another new console
start cmd /k "npx tailwindcss -i ./resources/css/app.css -o ./resources/css/tailwind.css --watch"

REM Start php artisan serve in yet another console
start cmd /k "php artisan serve"

echo All processes started in separate windows.
exit
