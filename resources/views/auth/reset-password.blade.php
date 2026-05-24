<x-guest-layout>
    <div class="grid min-h-[640px] lg:grid-cols-[0.95fr_1.05fr]">
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
                        <i class="ri-refresh-line text-base"></i>
                        Новый пароль
                    </p>
                    <h1 class="text-4xl font-extrabold leading-tight tracking-tight">Обновите пароль безопасно.</h1>
                    <p class="mt-5 text-base leading-7 text-white/80">После сохранения вы сможете войти с новым паролем.</p>
                </div>
                <div class="rounded-2xl border border-white/15 bg-white/10 p-4 text-sm text-white/75 backdrop-blur">
                    <i class="ri-lock-password-line mr-2 text-indigo-100"></i>
                    Используйте пароль минимум из 8 символов.
                </div>
            </div>
        </section>

        <section class="flex min-h-[640px] flex-col bg-white/85 backdrop-blur-xl">
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
                        <p class="text-sm font-semibold text-indigo-600">Сброс пароля</p>
                        <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Новый пароль</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Укажите email и придумайте новый пароль.</p>
                    </div>

                    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
                        @csrf
                        <input type="hidden" name="token" value="{{ $request->route('token') }}">

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-800">Email</label>
                            <div class="relative">
                                <i class="ri-mail-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                                <input type="email" name="email" required value="{{ old('email', $request->email) }}"
                                       class="h-[52px] w-full rounded-2xl border border-slate-200 bg-slate-50/80 py-3.5 pl-12 pr-4 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100">
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm" />
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div x-data="{ show: false }">
                                <label class="mb-2 block text-sm font-bold text-slate-800">Новый пароль</label>
                                <div class="relative">
                                    <i class="ri-lock-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                                    <input :type="show ? 'text' : 'password'" name="password" required
                                           class="h-[52px] w-full rounded-2xl border border-slate-200 bg-slate-50/80 py-3.5 pl-12 pr-12 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100"
                                           placeholder="Минимум 8 символов">
                                    <button type="button" @click="show = !show" class="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                                        <i x-show="!show" class="ri-eye-line text-lg"></i>
                                        <i x-show="show" class="ri-eye-off-line text-lg"></i>
                                    </button>
                                </div>
                                <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm" />
                            </div>

                            <div x-data="{ show: false }">
                                <label class="mb-2 block text-sm font-bold text-slate-800">Повторите пароль</label>
                                <div class="relative">
                                    <i class="ri-lock-password-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                                    <input :type="show ? 'text' : 'password'" name="password_confirmation" required
                                           class="h-[52px] w-full rounded-2xl border border-slate-200 bg-slate-50/80 py-3.5 pl-12 pr-12 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100"
                                           placeholder="Повторите пароль">
                                    <button type="button" @click="show = !show" class="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                                        <i x-show="!show" class="ri-eye-line text-lg"></i>
                                        <i x-show="show" class="ri-eye-off-line text-lg"></i>
                                    </button>
                                </div>
                                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-sm" />
                            </div>
                        </div>

                        <button type="submit" class="group relative flex h-[52px] w-full items-center justify-center gap-2 overflow-hidden rounded-2xl border border-indigo-400/30 bg-indigo-500/90 px-5 py-3.5 font-bold text-white shadow-lg shadow-indigo-500/25 transition-all duration-300 hover:-translate-y-0.5 hover:bg-indigo-600">
                            <i class="ri-refresh-line text-lg"></i>
                            Сбросить пароль
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</x-guest-layout>
