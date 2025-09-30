@extends('layouts.error')

@section('title', '403 — Мяу! Доступа нет')

@section('content')
<div class="error-403 w-full max-w-3xl mx-auto px-6 text-center">
  <!-- КОТИК -->
  <div class="mx-auto cat403">
    <div class="main mx-auto">
      <span class="stand"></span>
      <div class="cat">
        <div class="body"></div>

        <div class="head">
          <div class="ear"></div>
          <div class="ear"></div>
        </div>

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
  </div>

  <!-- ТЕКСТЫ/КНОПКИ -->
<h1 class="mt-6 text-3xl sm:text-4xl font-extrabold text-gray-800">
  Мяу! Секретная комната
</h1>
<p class="mt-2 text-gray-600 max-w-xl mx-auto">
  За этой дверью — панель чудес, но ключ только у <span class="font-semibold">admin</span>.
  @auth
    Вы вошли как <span class="font-semibold">{{ auth()->user()->role ?? 'user' }}</span>. Если это недоразумение — свяжитесь с админом.
  @else
    Похоже, вы гость. Войдите в аккаунт — возможно, ключик появится.
  @endauth
</p>



  <div class="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-3 max-w-2xl mx-auto">

    <a href="{{ url('/') }}" class="action inline-flex items-center justify-center gap-2 bg-indigo-600 text-white px-5 py-3 rounded-xl shadow hover:bg-indigo-700 transition">
      🏠 На главную
    </a>
    @auth
      <a href="{{ route('cabinet') }}" class="action inline-flex items-center justify-center gap-2 bg-white border px-5 py-3 rounded-xl shadow-sm hover:bg-gray-50 transition">
        👤 В мой кабинет
      </a>
    @else
      <a href="{{ route('login') }}" class="action inline-flex items-center justify-center gap-2 bg-white border px-5 py-3 rounded-xl shadow-sm hover:bg-gray-50 transition">
        🔐 Войти
      </a>
    @endauth
    <button id="btn-back" type="button" class="action inline-flex items-center justify-center gap-2 bg-white border px-5 py-3 rounded-xl shadow-sm hover:bg-gray-50 transition">
      ↩️ Назад
    </button>
  </div>
</div>

<style>
/* ---------- Цвета (замена SCSS-переменных) ---------- */
:root{
  --pink:#fd6e72; --purple:#745260; --teal:#abe7db; --grey:#74919f; --cream:#fdf9de; --black:#333333;
  --dur:12s;
}

/* ---------- Каркас из твоего примера ---------- */
.main{ height:400px; width:400px; position:relative; }
.main .stand{
  position:absolute; top:50%; left:50%; transform:translate(-50%);
  height:20px; width:200px; border-radius:20px; background:var(--pink); z-index:2;
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
.cat{
  width:110px; height:50px; position:absolute; top:calc(50% - 50px); right:130px;
  border-top-left-radius:100px; border-top-right-radius:100px;
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

/* УШКИ */
.ear{
  position:absolute; left:4px; top:-4px; width:0; height:0;
  border-left:12px solid transparent; border-right:12px solid transparent; border-bottom:20px solid var(--purple);
  transform:rotate(-30deg); animation:left-ear var(--dur) both infinite;
}
.ear + .ear{ top:-12px; left:30px; animation:right-ear var(--dur) both infinite; }

/* ХВОСТ */
.tail-container{ position:absolute; right:0; bottom:-13px; z-index:3; }
.tail{
  position:absolute; height:30px; width:14px; bottom:-10px; right:0; border-bottom-right-radius:5px; background:var(--purple); z-index:0;
}
.tail > .tail{
  animation: tail var(--dur) infinite; height:100%; width:14px; transform-origin:left;
  border-bottom-left-radius:20px 20px; border-bottom-right-radius:20px 20px; border-top-right-radius:40px;
}

/* НОСИК */
.nose{ position:absolute; bottom:10px; left:-10px; background:var(--pink); height:5px; width:5px; border-radius:50%; }

/* УСЫ */
.whisker-container{
  position:absolute; bottom:5px; left:-36px; width:20px; height:10px; transform-origin:right; animation:left-whisker var(--dur) both infinite;
}
.whisker-container + .whisker-container{
  left:-20px; bottom:12px; transform-origin:right; transform:rotate(180deg); animation:right-whisker var(--dur) both infinite;
}
.whisker{ position:absolute; top:0; width:100%; border:1px solid var(--cream); transform-origin:100% 0; transform:rotate(10deg); }
.whisker:last-child{ transform:rotate(-20deg); }

/* ---------- АНИМАЦИИ ---------- */
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


</style>

<script>
  // Кнопка «Назад»
  document.getElementById('btn-back')?.addEventListener('click', () => history.back());
</script>
@endsection
