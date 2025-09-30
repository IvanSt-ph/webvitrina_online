@extends('layouts.error')

@section('title', '404 — Мяу! Страница не найдена')

@section('content')
<div class="error-404 w-full max-w-3xl mx-auto px-6 text-center">
  <!-- СЦЕНА С КОТИКОМ -->
  <div class="cute404-viewport mx-auto">
    <div class="cat-scale">
      <div class="main mx-auto">
        <span class="stand"></span>

        <!-- Бейдж 404 -->
        <div class="badge-404">404</div>

        <div class="cat">
          <div class="body"></div>

          <div class="head">
            <div class="ear"></div>
            <div class="ear"></div>
          </div>

          <!-- мордашка -->
          <div class="face">
            <div class="nose"></div>
            <div class="whisker-container">
              <div class="whisker"></div>
              <div class="whisker"></div>
            </div>
            <div class="whisker-container">
              <div class="whisker"></div>
              <div class="whisker"></div>
            </div>
          </div>

          <!-- лапка, «держит» ручку лупы -->
          <div class="paw"></div>

          <!-- ЛУПА -->
          <div class="magnifier">
            <div class="glass">
              <div class="shine"></div>
            </div>
            <div class="handle"></div>
          </div>

          <!-- хвост -->
          <div class="tail-container">
            <div class="tail">
              <div class="tail">
                <div class="tail">
                  <div class="tail">
                    <div class="tail">
                      <div class="tail">
                        <div class="tail"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div> <!-- /tail-container -->
        </div> <!-- /cat -->
      </div> <!-- /main -->
    </div> <!-- /cat-scale -->
  </div> <!-- /cute404-viewport -->

  <!-- Текст и кнопки -->
  <h1 class="mt-6 text-3xl sm:text-4xl font-extrabold text-gray-800">Мяу! Страница не найдена</h1>
  <p class="mt-2 text-gray-600 max-w-xl mx-auto">
    Кот-сыщик всё обнюхал своей лупой, но страничка не нашлась.
    Проверьте адрес или вернитесь на главную.
  </p>

  <div class="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-3 max-w-2xl mx-auto">
    <a href="{{ url('/') }}" class="w-full action inline-flex items-center justify-center gap-2 bg-indigo-600 text-white px-5 py-3 rounded-xl shadow hover:bg-indigo-700 transition">
      🏠 На главную
    </a>
    @auth
      <a href="{{ route('cabinet') }}" class="w-full action inline-flex items-center justify-center gap-2 bg-white border px-5 py-3 rounded-xl shadow-sm hover:bg-gray-50 transition">
        👤 В мой кабинет
      </a>
    @else
      <a href="{{ route('login') }}" class="w-full action inline-flex items-center justify-center gap-2 bg-white border px-5 py-3 rounded-xl shadow-sm hover:bg-gray-50 transition">
        🔐 Войти
      </a>
    @endauth
    <button id="btn-back" type="button" class="w-full action inline-flex items-center justify-center gap-2 bg-white border px-5 py-3 rounded-xl shadow-sm hover:bg-gray-50 transition">
      ↩️ Назад
    </button>
  </div>
</div>

<style>
/* ---------- Переменные ---------- */
:root{
  --pink:#fd6e72; --purple:#745260; --teal:#abe7db; --grey:#74919f; --cream:#fdf9de; --black:#333333;
  --dur:12s;
  /* адаптивный скейл сцены, чтобы всё помещалось на мобилке */
  --cat-scale: min(1, calc(min(92vw, 420px) / 400));
}

/* ---------- Вьюпорт и скейл сцены ---------- */
.cute404-viewport {
    width: 75%;
    display: flex;
    justify-content: center;
    background-color: #cdcdcd87;
    border-radius: 25px;
}

.cat-scale{
  width:calc(400px*var(--cat-scale));
  height:calc(400px*var(--cat-scale));
  transform-origin: top center;
  transform: scale(var(--cat-scale));
}

/* ---------- Сцена кота ---------- */
.main{ height:400px; width:400px; position:relative; }
.main .stand{
  position:absolute; top:50%; left:50%; transform:translate(-50%);
  height:20px; width:200px; border-radius:20px; background:var(--pink); z-index:7;
}
.main .stand::after{
  content:""; position:absolute; bottom:-10px; left:50%; transform:translate(-50%);
  height:10px; width:50px; border-radius:20px; background:var(--cream);
  box-shadow:0 10px 0 var(--cream), 0 20px 0 var(--cream), 0 30px 0 var(--cream), 0 40px 0 var(--cream),
             0 50px 0 var(--cream), 0 60px 0 var(--cream), 0 70px 0 var(--cream), 0 80px 0 var(--cream),
             0 90px 0 var(--cream), 0 100px 0 var(--cream), 0 110px 0 var(--cream), 0 120px 0 var(--cream),
             0 130px 0 var(--cream), 0 140px 0 var(--cream), 0 150px 0 var(--cream), 0 160px 0 var(--cream),
             0 170px 0 var(--cream);
}

/* Бейдж 404 */
.badge-404{
  position:absolute; top:16%; left:50%;
  transform: translate(-50%, -55%) rotate(-6deg);
  background:#fff; color:#111;
  border:2px solid var(--purple); border-radius:12px;
  padding:.25rem .6rem; font-weight:800; font-size:2.05rem;
  box-shadow:0 6px 14px rgba(0,0,0,.08);
  animation: floaty var(--dur) ease-in-out infinite;
  z-index:1;
}

/* Кот */
.cat{
  width:110px; height:50px; position:absolute; top:calc(50% - 50px); right:130px;
  border-top-left-radius:100px; border-top-right-radius:100px; z-index:2;
}
.cat .body{
  width:110px; height:50px; background:var(--purple);
  border-top-left-radius:100px; border-top-right-radius:100px; animation: body var(--dur) infinite;
}
.cat .head{
  width:70px; height:35px; background:var(--purple);
  position:absolute; top:calc(50% - 10px); left:-40px; border-top-left-radius:80px; border-top-right-radius:80px;
}
.face{ position:absolute; left:-6px; top:calc(50% + 25px); width:1px; height:1px; }

/* Ушки */
.ear{
  position:absolute; left:4px; top:-4px; width:0; height:0;
  border-left:12px solid transparent; border-right:12px solid transparent; border-bottom:20px solid var(--purple);
  transform:rotate(-30deg); animation:left-ear var(--dur) both infinite;
}
.ear + .ear{ top:-12px; left:30px; animation:right-ear var(--dur) both infinite; }

/* Носик + усы */
.nose{ position:absolute; bottom:10px; left:-10px; background:var(--pink); height:5px; width:5px; border-radius:50%; }
.whisker-container{
  position:absolute; bottom:5px; left:-36px; width:20px; height:10px; transform-origin:right; animation:left-whisker var(--dur) both infinite;
}
.whisker-container + .whisker-container{
  left:-20px; bottom:12px; transform-origin:right; transform:rotate(180deg); animation:right-whisker var(--dur) both infinite;
}
.whisker{ position:absolute; top:0; width:100%; border:1px solid var(--cream); transform-origin:100% 0; transform:rotate(10deg); }
.whisker:last-child{ transform:rotate(-20deg); }

/* Лапка (держит ручку лупы) */
.paw {
    position: absolute;
    width: 16px;
    height: 16px;
    background: #fff;
    border: 2px solid var(--purple);
    border-radius: 50%;
    right: 100px;
    top: -70px;
    z-index: 4;
    box-shadow: 0 2px 0 rgba(0, 0, 0, .06);
}

/* ЛУПА */
.magnifier {
    position: absolute;
    right: 50px;
    top: -105px;
    width: 90px;
    height: 60px;
    z-index: 10;
    animation: scan 6s 
ease-in-out infinite;
}
.magnifier .glass{
  position:absolute; width:60px; height:60px; border-radius:50%;
  border:4px solid var(--purple); background: radial-gradient(ellipse at 30% 30%, rgba(255,255,255,.65), rgba(255,255,255,.35) 60%, rgba(255,255,255,0) 61%);
  box-shadow: 0 6px 18px rgba(0,0,0,.12), inset 0 0 0 2px rgba(255,255,255,.35);
}
.magnifier .glass::after{
  /* имитация увеличения: лёгкие полоски */
  content:""; position:absolute; inset:10px; border-radius:50%;
  background: repeating-linear-gradient(135deg, rgba(255,255,255,.25) 0 6px, rgba(255,255,255,0) 6px 12px);
  mix-blend-mode: screen; opacity:.35;
}
.magnifier .shine{
  position:absolute; width:22px; height:10px; border-radius:10px; background:rgba(255,255,255,.8);
  top:10px; left:10px; transform: rotate(-20deg);
}
.magnifier .handle {
    position: absolute;
    width: 60px;
    height: 11px;
    background: #fff;
    border: 3px solid var(--purple);
    border-radius: 10px;
    bottom: 8px;
    left: 50px;
    transform-origin: left center;
    transform: rotate(35deg);
    box-shadow: 0 3px 0 rgba(0, 0, 0, .06);
}

/* Хвост */
.tail-container{ position:absolute; right:0; bottom:-13px; z-index:1; }
.tail{
  position:absolute; height:30px; width:14px; bottom:-10px; right:0; border-bottom-right-radius:5px; background:var(--purple);
}
.tail > .tail{ animation: tail var(--dur) infinite; height:100%; width:14px; transform-origin:left;
  border-bottom-left-radius:20px 20px; border-bottom-right-radius:20px 20px; border-top-right-radius:40px; }

/* ---------- Анимации ---------- */
@keyframes tail{
  6.667%{transform:rotate(0)} 10%{transform:rotate(10deg)} 16.667%{transform:rotate(-5deg)}
  20%{transform:rotate(30deg)} 26.667%{transform:rotate(-2deg)} 46.667%{transform:rotate(10deg)}
  53.333%{transform:rotate(-5deg)} 56.667%{transform:rotate(10deg)} 100%{transform:rotate(0)}
}
@keyframes body{
  6.667%{transform:scaleY(1)} 10%{transform:scaleY(1.15)} 16.667%{transform:scaleY(1)}
  20%{transform:scaleY(1.25)} 26.667%{transform:scaleY(1)} 46.667%{transform:scaleY(1.15)}
  53.333%{transform:scaleY(1)} 56.667%{transform:scaleY(1.15)} 100%{transform:scaleY(1)}
}
@keyframes left-whisker{
  6.667%{transform:rotate(0)} 10%{transform:rotate(0)} 16.667%{transform:rotate(-5deg)}
  20%{transform:rotate(0)} 26.667%{transform:rotate(0)} 46.667%{transform:rotate(10deg)}
  53.333%{transform:rotate(-5deg)} 56.667%{transform:rotate(10deg)} 100%{transform:rotate(0)}
}
@keyframes right-whisker{
  6.667%{transform:rotate(180deg)} 10%{transform:rotate(190deg)} 16.667%{transform:rotate(180deg)}
  20%{transform:rotate(175deg)} 26.667%{transform:rotate(190deg)} 46.667%{transform:rotate(180deg)}
  53.333%{transform:rotate(185deg)} 56.667%{transform:rotate(175deg)} 100%{transform:rotate(180deg)}
}
@keyframes left-ear{
  0%{transform:rotate(-20deg)} 6.667%{transform:rotate(-6deg)} 13.333%{transform:rotate(-15deg)}
  26.667%{transform:rotate(-15deg)} 33.333%{transform:rotate(-30deg)} 40%{transform:rotate(-30deg)}
  46.667%{transform:rotate(0)} 53.333%{transform:rotate(0)} 60%{transform:rotate(-15deg)}
  80%{transform:rotate(-15deg)} 93.333%{transform:rotate(-6deg)} 100%{transform:rotate(-6deg)}
}
@keyframes right-ear{
  0%{transform:rotate(-16deg)} 6.667%{transform:rotate(-16deg)} 13.333%{transform:rotate(-19deg)}
  26.667%{transform:rotate(-19deg)} 33.333%{transform:rotate(-30deg)} 36.667%{transform:rotate(-19deg)}
  37.333%{transform:rotate(-30deg)} 38%{transform:rotate(-19deg)} 40%{transform:rotate(-19deg)}
  40.667%{transform:rotate(-30deg)} 41.333%{transform:rotate(-19deg)} 46.667%{transform:rotate(-9deg)}
  53.333%{transform:rotate(-9deg)} 60%{transform:rotate(-19deg)} 60.667%{transform:rotate(-30deg)}
  61.333%{transform:rotate(-19deg)} 62.667%{transform:rotate(-19deg)} 63.333%{transform:rotate(-30deg)}
  64%{transform:rotate(-19deg)} 80%{transform:rotate(-19deg)} 93.333%{transform:rotate(-16deg)} 100%{transform:rotate(-16deg)}
}
@keyframes floaty{
  0%,100%{ transform: translate(-50%, -55%) rotate(-6deg); }
  50%    { transform: translate(-50%, -61%) rotate(-6deg); }
}
/* движение лупы: лёгкое «сканирование» */
@keyframes scan{
  0%   { transform: translate(0,0) rotate(0deg); }
  25%  { transform: translate(6px,-4px) rotate(-3deg); }
  50%  { transform: translate(2px,4px) rotate(2deg); }
  75%  { transform: translate(8px,0) rotate(-2deg); }
  100% { transform: translate(0,0) rotate(0deg); }
}

/* Уважение к prefers-reduced-motion */
@media (prefers-reduced-motion: reduce) {
  .tail > .tail, .cat .body, .ear, .whisker-container, .badge-404, .magnifier { animation: none !important; }
}
</style>

<script>
  // Кнопка «Назад»
  document.getElementById('btn-back')?.addEventListener('click', () => history.back());
</script>
@endsection
