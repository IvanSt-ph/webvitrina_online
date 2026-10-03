{{-- resources/views/seller/partials/phone/verify-code-form.blade.php --}}
<form method="POST"
      action="{{ route('shop.phone.verify') }}"
      class="space-y-3 rounded-xl border border-gray-200 bg-gray-50/70 p-3 sm:p-4"
      x-data="{ timer: 600, formattedTime: '10:00' }"
      x-init="
        let interval = setInterval(() => {
          timer--;
          let minutes = Math.floor(timer / 60);
          let seconds = timer % 60;
          formattedTime = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
          if(timer <= 0) clearInterval(interval);
        }, 1000);
      ">
    @csrf

    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-gray-950">Код из SMS</p>
            <p class="mt-0.5 text-xs text-gray-500">Введите 6 цифр из сообщения</p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-600">
            <i class="ri-time-line"></i>
            <span x-text="formattedTime"></span>
        </span>
    </div>

    <div class="flex flex-col gap-2 sm:flex-row">
        <div class="relative min-w-0 flex-1">
            <input type="text"
                   name="code"
                   placeholder="000000"
                   maxlength="6"
                   autocomplete="off"
                   class="w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-11 pr-4 text-base font-semibold tracking-[0.2em] shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                   required>
            <i class="ri-shield-keyhole-line absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
        </div>

        <x-action-button size="sm" class="shrink-0">
            <i class="ri-check-line"></i>
            Подтвердить
        </x-action-button>
    </div>
</form>
