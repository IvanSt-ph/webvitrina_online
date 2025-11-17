@if ($paginator->hasPages())
    <nav class="flex justify-center mt-10 select-none" role="navigation">
        <ul class="flex items-center gap-2 pagination-wv">

            {{-- ◀ Назад --}}
            @if ($paginator->onFirstPage())
                <span class="pg-btn disabled">‹</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="pg-btn">‹</a>
            @endif

            {{-- Номера --}}
            @foreach ($elements as $element)

                @if (is_string($element))
                    <span class="pg-ellipsis">…</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pg-btn active">{{ $page }}</span>
                        @else
                            <a class="pg-btn" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif

            @endforeach

            {{-- ▶ Вперёд --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="pg-btn">›</a>
            @else
                <span class="pg-btn disabled">›</span>
            @endif

        </ul>
    </nav>

    <style>
        /* Анимация входа */
        .pagination-wv {
            animation: pagFadeIn .35s ease-out;
        }
        @keyframes pagFadeIn {
            0% { opacity: 0; transform: translateY(5px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* Кнопки */
        .pg-btn {
            padding: 8px 16px;
            min-width: 40px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(230, 230, 240, 0.9);
            border-radius: 14px;

            color: #374151;
            font-size: 14px;
            font-weight: 500;

            display: flex;
            align-items: center;
            justify-content: center;

            box-shadow: 0 1px 2px rgba(0,0,0,0.06);
            transition: all .22s cubic-bezier(.22,.61,.36,1);
        }

        /* Hover - живой UI эффектик */
        .pg-btn:hover {
            background: white;
            border-color: #818cf8;
            color: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99,102,241,0.25);
        }

        /* Active (текущая страница) */
        .pg-btn.active {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            border-color: transparent;
            color: #fff;
            font-weight: 600;

            box-shadow: 0 6px 16px rgba(99,102,241,0.45);
            transform: translateY(-1px) scale(1.02);
        }

        /* Нажатие */
        .pg-btn:active:not(.disabled) {
            transform: scale(0.95);
        }

        /* Disabled */
        .pg-btn.disabled {
            background: #f3f4f6;
            color: #b4b9c4;
            border-color: #e5e7eb;
            opacity: .75;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        /* … */
        .pg-ellipsis {
            padding: 8px 6px;
            font-size: 16px;
            opacity: .5;
            user-select: none;
        }
    </style>
@endif
