{{-- resources/views/seller/partials/phone/verify-code-form.blade.php --}}
<form method="POST"
      action="{{ route('shop.phone.verify') }}"
      class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-sm space-y-4"
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
            <p class="text-sm font-semibold text-gray-950">Введите код из SMS</p>
            <p class="text-xs text-gray-500 mt-0.5">6 цифр из сообщения Twilio</p>
        </div>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-gray-50 border border-gray-200 text-xs font-semibold text-gray-600">
            <i class="ri-time-line"></i>
            <span x-text="formattedTime"></span>
        </span>
    </div>

    <div class="space-y-3">
        <div class="relative">
            <input type="text"
                   name="code"
                   placeholder="000000"
                   maxlength="6"
                   autocomplete="off"
                   class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-emerald-100 focus:border-emerald-500 text-base font-semibold tracking-[0.2em]"
                   required>
            <i class="ri-shield-keyhole-line absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
        </div>

        <button type="submit"
                class="relative overflow-hidden group h-11 px-5 bg-indigo-500/90 hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center gap-2 w-full backdrop-blur-sm border border-indigo-400/30">
            <span class="relative z-10 flex items-center gap-2">
                <i class="ri-check-line"></i>
                Подтвердить код
            </span>
            <span class="absolute inset-0 bg-indigo-600 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
        </button>
    </div>

    <p class="text-xs text-gray-500 flex items-start gap-2">
        <i class="ri-information-line text-gray-400 mt-0.5"></i>
        Если код не пришёл, отправьте SMS повторно после истечения таймера.
    </p>
</form>
