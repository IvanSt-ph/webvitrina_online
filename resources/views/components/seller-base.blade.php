@props(['title' => null])

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title ? $title . ' — WebVitrina Seller' : 'WebVitrina Seller' }}</title>

  {{-- ⚡ Подключаем уже готовые стили/скрипты (один раз через app.blade.php) --}}
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-50 text-gray-800 font-sans antialiased">
  {{ $slot }}
</body>
</html>

{{-- resources\views\layouts\seller-base.blade.php --}}