@extends('buyer.profile')

@section('profile_content')
<div class="space-y-8">
    <section class="grid items-center gap-8 lg:grid-cols-[minmax(260px,0.72fr)_minmax(0,1.28fr)]">
        <div class="max-w-xl">
            <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-full bg-[#e8f0fe] text-[#1967d2]"><i class="ri-shield-keyhole-line text-xl"></i></div>
            <h2 class="text-2xl font-normal tracking-tight text-[#202124] sm:text-3xl">Безопасность</h2>
            <p class="mt-3 max-w-lg text-base leading-7 text-[#5f6368]">Измените пароль и сохраните надёжный доступ к вашему аккаунту.</p>
            <p class="mt-4 text-sm text-[#5f6368]">Последнее изменение: {{ Auth::user()->updated_at?->diffForHumans() ?? '—' }}</p>
        </div>
        <div class="flex min-h-40 items-center justify-center overflow-hidden rounded-2xl bg-[#f8fafd] p-5">
            <div class="flex h-24 w-24 items-center justify-center rounded-full bg-[#e8f0fe] text-[#1967d2] sm:h-28 sm:w-28"><i class="ri-lock-2-line text-5xl"></i></div>
        </div>
    </section>

    @if(session('status') === 'password-updated')
        <div class="flex items-center gap-3 rounded-2xl border border-[#ceead6] bg-[#e6f4ea] px-4 py-3 text-sm text-[#137333]" role="status"><i class="ri-checkbox-circle-fill text-lg"></i><span>Пароль успешно изменён.</span></div>
    @endif
    @if($errors->updatePassword->any())
        <div class="rounded-2xl border border-[#f4c7c3] bg-[#fce8e6] px-5 py-4 text-sm text-[#c5221f]" role="alert"><ul class="list-inside list-disc space-y-1">@foreach($errors->updatePassword->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" x-data="{ showCurrent:false, showNew:false, showConfirm:false }" class="rounded-2xl border border-[#dadce0] bg-white">
        @csrf @method('PUT')
        <div class="border-b border-[#dadce0] px-5 py-5 sm:px-7">
            <h3 class="text-xl font-normal text-[#202124]">Пароль</h3>
            <p class="mt-1 text-sm text-[#5f6368]">Используйте уникальный пароль, который не применяется на других сайтах</p>
        </div>
        <div class="grid gap-8 p-5 sm:p-7 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div class="space-y-6">
                @if(Auth::user()->hasLocalPassword())
                    <div>
                        <label for="security-current" class="mb-2 block text-sm font-medium text-[#3c4043]">Текущий пароль</label>
                        <div class="relative">
                            <input id="security-current" :type="showCurrent ? 'text' : 'password'" name="current_password" required autocomplete="current-password" class="h-14 w-full rounded-lg border-[#dadce0] bg-white px-4 pr-12 text-base shadow-none hover:border-[#bdc1c6] focus:border-[#1a73e8] focus:ring-1 focus:ring-[#1a73e8]">
                            <button type="button" @click="showCurrent = !showCurrent" class="absolute right-2 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full text-[#5f6368] hover:bg-[#f1f3f4]" aria-label="Показать или скрыть текущий пароль"><i :class="showCurrent ? 'ri-eye-off-line' : 'ri-eye-line'"></i></button>
                        </div>
                    </div>
                @else
                    <div class="rounded-xl bg-[#e8f0fe] px-4 py-3 text-sm leading-6 text-[#174ea6]">Вы входите через Google. Установите пароль, чтобы получить резервный способ входа и подтверждать важные изменения.</div>
                @endif
                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="security-new" class="mb-2 block text-sm font-medium text-[#3c4043]">Новый пароль</label>
                        <div class="relative"><input id="security-new" :type="showNew ? 'text' : 'password'" name="password" required autocomplete="new-password" class="h-14 w-full rounded-lg border-[#dadce0] bg-white px-4 pr-12 text-base shadow-none hover:border-[#bdc1c6] focus:border-[#1a73e8] focus:ring-1 focus:ring-[#1a73e8]"><button type="button" @click="showNew = !showNew" class="absolute right-2 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full text-[#5f6368] hover:bg-[#f1f3f4]" aria-label="Показать или скрыть новый пароль"><i :class="showNew ? 'ri-eye-off-line' : 'ri-eye-line'"></i></button></div>
                    </div>
                    <div>
                        <label for="security-confirm" class="mb-2 block text-sm font-medium text-[#3c4043]">Повторите пароль</label>
                        <div class="relative"><input id="security-confirm" :type="showConfirm ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password" class="h-14 w-full rounded-lg border-[#dadce0] bg-white px-4 pr-12 text-base shadow-none hover:border-[#bdc1c6] focus:border-[#1a73e8] focus:ring-1 focus:ring-[#1a73e8]"><button type="button" @click="showConfirm = !showConfirm" class="absolute right-2 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full text-[#5f6368] hover:bg-[#f1f3f4]" aria-label="Показать или скрыть подтверждение пароля"><i :class="showConfirm ? 'ri-eye-off-line' : 'ri-eye-line'"></i></button></div>
                    </div>
                </div>
            </div>
            <aside class="rounded-2xl bg-[#f8fafd] p-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-[#1967d2] shadow-sm"><i class="ri-lightbulb-line text-xl"></i></div>
                <h4 class="mt-4 text-sm font-medium text-[#202124]">Надёжный пароль</h4>
                <ul class="mt-3 space-y-2 text-sm leading-5 text-[#5f6368]">
                    <li class="flex gap-2"><i class="ri-check-line mt-0.5 text-[#137333]"></i><span>Не менее 8 символов</span></li>
                    <li class="flex gap-2"><i class="ri-check-line mt-0.5 text-[#137333]"></i><span>Не используйте имя или email</span></li>
                    <li class="flex gap-2"><i class="ri-check-line mt-0.5 text-[#137333]"></i><span>Не повторяйте пароль с других сайтов</span></li>
                </ul>
            </aside>
        </div>
        <div class="flex justify-end border-t border-[#dadce0] px-5 py-4 sm:px-7">
            <x-action-button type="submit">
                <i class="ri-lock-password-line" aria-hidden="true"></i>
                {{ Auth::user()->hasLocalPassword() ? 'Сменить пароль' : 'Установить пароль' }}
            </x-action-button>
        </div>
    </form>
</div>
@endsection
