@php($favCount = auth()->check() ? \App\Models\Favorite::where('user_id',auth()->id())->count() : 0)
@php($cartCount = auth()->check() ? \App\Models\CartItem::where('user_id',auth()->id())->sum('qty') : 0)
<header class="border-b bg-white/80 backdrop-blur sticky top-0 z-50">
  <div class="max-w-7xl mx-auto flex items-center justify-between p-3">
    <a href="/" class="flex items-center gap-2 font-bold text-lg">
      <span class="inline-block w-7 h-7 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-500"></span>
      <span>WebV3</span>
    </a>
    <form action="/" method="get" class="hidden md:block">
      <input name="search" placeholder="Поиск товаров..." value="{{ request('search') }}" class="border rounded px-3 py-1.5 w-80" />
    </form>
    <nav class="flex items-center gap-4">
      <a href="/favorites" class="relative group">
        <span class="lucide lucide-heart"></span>
        <span class="absolute -top-2 -right-2 text-xs bg-pink-600 text-white rounded-full px-1">{{ $favCount }}</span>
      </a>
      <a href="/cart" class="relative group">
        <span class="lucide lucide-shopping-cart"></span>
        <span class="absolute -top-2 -right-2 text-xs bg-indigo-600 text-white rounded-full px-1">{{ $cartCount }}</span>
      </a>
      @auth
        @if(auth()->user()->role==='seller')
          <a href="/seller/products" class="text-sm font-medium">Мои товары</a>
        @endif
        <form method="post" action="/logout">@csrf<button class="text-sm">Выйти</button></form>
      @else
        <a href="/login" class="text-sm">Войти</a>
        <a href="/register" class="text-sm">Регистрация</a>
      @endauth
    </nav>
  </div>
</header>
