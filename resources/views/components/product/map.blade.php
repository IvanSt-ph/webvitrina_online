@props(['product'])

@if ($product->city || $product->country || $product->address || ($product->latitude && $product->longitude))
    <div class="mt-8 bg-white border rounded-2xl p-5 shadow-sm">
        <div class="text-sm text-gray-500 mb-1">Местоположение</div>

        {{-- Текстовое описание --}}
        <div class="font-medium text-gray-800">
            @if ($product->country)
                {{ $product->country->name }}
            @elseif($product->city && $product->city->country)
                {{ $product->city->country->name }}
            @endif

            @if ($product->city)
                , {{ $product->city->name }}
            @endif
        </div>

        @if ($product->address)
            <div class="mt-1 text-gray-700">
                {{ $product->address }}
            </div>
        @endif

        {{-- Карта (ленивая инициализация) --}}
        @if ($product->latitude && $product->longitude)
            <div class="mt-3">
                <div
                    class="w-full h-56 rounded-lg border"
                    data-product-map
                    data-lat="{{ $product->latitude }}"
                    data-lng="{{ $product->longitude }}"
                    data-title="{{ e($product->title) }}"
                ></div>

                <a href="https://www.google.com/maps/search/?api=1&query={{ $product->latitude }},{{ $product->longitude }}"
                   target="_blank"
                   class="mt-2 inline-block text-indigo-600 hover:underline text-sm">
                    📍 Открыть в Google Maps
                </a>
            </div>
        @endif
    </div>
@endif

@once
    @push('styles')
        {{-- Leaflet CSS --}}
        <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

        <style>
            .leaflet-control-attribution {
                font-size: 11px !important;
                color: #666 !important;
                background: rgba(255, 255, 255, .8) !important;
                border-radius: 6px !important;
                padding: 2px 6px !important;
            }

            /* Чуть защищаем z-index, чтобы карта не перекрывала модалки и т.п. */
            [data-product-map],
            [data-product-map] * {
                z-index: 0 !important;
            }
            .leaflet-control-container {
                z-index: 1 !important;
            }
        </style>
    @endpush

    @push('scripts')
        {{-- Leaflet JS --}}
        <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

        <script>
            window.initProductMap = window.initProductMap || function (el) {
                if (!el || el.dataset.inited) return;

                el.dataset.inited = '1';

                const lat   = parseFloat(el.dataset.lat);
                const lng   = parseFloat(el.dataset.lng);
                const title = el.dataset.title || '';

                const map = L.map(el).setView([lat, lng], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                map.attributionControl.setPrefix(false);
                map.attributionControl.setPosition('bottomleft');

                L.marker([lat, lng]).addTo(map).bindPopup(title);
            };

            (function () {
                const maps = document.querySelectorAll('[data-product-map]');
                if (!maps.length) return;

                // Если IntersectionObserver не поддерживается — инициализируем сразу
                if (!('IntersectionObserver' in window)) {
                    maps.forEach(el => window.initProductMap(el));
                    return;
                }

                const observer = new IntersectionObserver(entries => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            window.initProductMap(entry.target);
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.15 });

                maps.forEach(el => observer.observe(el));
            })();
        </script>
    @endpush
@endonce
