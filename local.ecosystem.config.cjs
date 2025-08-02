module.exports = {
  apps: [
    {
      name: "npm-dev",
      script: "cmd",
      args: "/c npm run dev",
      cwd: "C:/Users/Matteo/Documents/Progetti/MetallicaMarcon-LaravelVueInertia",
    },
    {
      name: "tailwind",
      script: "cmd",
      args: "/c npx tailwindcss -i ./resources/css/app.css -o ./resources/css/tailwind.css --watch",
      cwd: "C:/Users/Matteo/Documents/Progetti/MetallicaMarcon-LaravelVueInertia",
    },
    {
      name: "artisan-serve",
      script: "cmd",
      args: "/c php artisan serve",
      cwd: "C:/Users/Matteo/Documents/Progetti/MetallicaMarcon-LaravelVueInertia",
    },
  ],
};