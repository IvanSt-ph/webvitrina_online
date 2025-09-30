@props(['title'=>null])
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{{ $title ? $title.' — ' : '' }}WebV3 на Pomer</title>
  <link rel="stylesheet" href="https://unpkg.com/lucide-static/font/lucide.css">
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900">
  <x-nav />
  <main class="max-w-7xl mx-auto p-4">
    <x-flash />
    {{ $slot }}
  </main>
</body>
</html>
