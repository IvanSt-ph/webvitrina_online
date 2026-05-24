<x-guest-layout>
    <div class="grid min-h-[560px] lg:grid-cols-[0.95fr_1.05fr]">
        <section class="relative hidden overflow-hidden bg-slate-950 lg:block">
            <video autoplay muted loop playsinline class="absolute inset-0 h-full w-full scale-[1.03] object-cover opacity-80 blur-[1.5px] saturate-125">
                <source src="{{ asset('videos/login-bg.mp4') }}" type="video/mp4">
            </video>
            <div class="absolute inset-0 bg-slate-950/65"></div>
            <div class="absolute inset-0 bg-white/[0.03] backdrop-blur-[2px]"></div>
            <div class="relative z-10 flex h-full flex-col justify-between p-8 xl:p-10 text-white">
                <a href="{{ route('home') }}" class="inline-flex w-fit items-center gap-3 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 shadow-lg backdrop-blur">
                    <img src="{{ asset('images/logo.png') }}" class="h-9 w-9 rounded-xl bg-white object-contain p-1" alt="WebVitrina">
                    <span class="text-lg font-extrabold tracking-tight">WebVitrina</span>
                </a>
                <div class="max-w-md">
                    <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-indigo-200/30 bg-indigo-100/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-indigo-100">
                        <i class="ri-mail-check-line text-base"></i>
                        Подтверждение email
                    </p>
                    <h1 class="text-4xl font-extrabold leading-tight tracking-tight">Остался один шаг.</h1>
                    <p class="mt-5 text-base leading-7 text-white/80">Подтвердите email, чтобы завершить настройку аккаунта.</p>
                </div>
                <div class="rounded-2xl border border-white/15 bg-white/10 p-4 text-sm text-white/75 backdrop-blur">
                    <i class="ri-mail-send-line mr-2 text-indigo-100"></i>
                    Если письма нет, проверьте папку спам или отправьте ссылку повторно.
                </div>
            </div>
        </section>

        <section class="flex min-h-[560px] flex-col bg-white/85 backdrop-blur-xl">
            <div class="relative h-36 overflow-hidden lg:hidden">
                <video autoplay muted loop playsinline class="absolute inset-0 h-full w-full scale-[1.03] object-cover blur-[1.5px] saturate-125">
                    <source src="{{ asset('videos/login-bg.mp4') }}" type="video/mp4">
                </video>
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/75 via-slate-950/30 to-transparent"></div>
                <div class="absolute inset-0 bg-white/[0.03] backdrop-blur-[2px]"></div>
                <a href="{{ route('home') }}" class="absolute left-4 top-4 inline-flex items-center gap-2 rounded-xl bg-white/90 px-3 py-2 text-sm font-extrabold text-slate-900 shadow-sm backdrop-blur">
                    <img src="{{ asset('images/logo.png') }}" class="h-7 w-7 rounded-lg object-contain" alt="WebVitrina">
                    WebVitrina
                </a>
            </div>

            <div class="flex flex-1 items-center px-5 py-7 sm:px-8 lg:px-10 xl:px-12">
                <div class="mx-auto w-full max-w-md">
                    <div class="mb-7">
                        <p class="text-sm font-semibold text-indigo-600">Почта аккаунта</p>
                        <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Подтвердите email</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Мы отправили письмо. Перейдите по ссылке в письме, чтобы завершить регистрацию.
                        </p>
                    </div>

                    @if (session('status') == 'verification-link-sent')
                        <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">
                            <i class="ri-check-line mr-1"></i>
                            Новая ссылка отправлена на ваш email.
                        </div>
                    @endif

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <form method="POST" action="{{ route('verification.send') }}" class="flex-1">
                            @csrf
                            <button type="submit" class="group relative flex h-[52px] w-full items-center justify-center gap-2 overflow-hidden rounded-2xl border border-indigo-400/30 bg-indigo-500/90 px-5 py-3.5 font-bold text-white shadow-lg shadow-indigo-500/25 transition-all duration-300 hover:-translate-y-0.5 hover:bg-indigo-600">
                                <i class="ri-send-plane-line text-lg"></i>
                                Отправить снова
                            </button>
                        </form>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex h-[52px] w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 font-bold text-slate-700 transition hover:bg-slate-50 sm:w-auto">
                                <i class="ri-logout-box-r-line"></i>
                                Выйти
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-guest-layout>
