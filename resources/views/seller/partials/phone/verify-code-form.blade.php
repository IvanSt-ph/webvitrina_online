{{-- resources/views/profile/partials/phone/verify-code-form.blade.php --}}
<form method="POST" 
      action="{{ route('shop.phone.verify') }}" 
      class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-5 border border-green-200"
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
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <p class="font-medium text-gray-900">Введите 6-значный код</p>
            <div class="flex items-center gap-1 text-sm text-green-600 font-medium">
                <i class="ri-time-line"></i>
                <span x-text="formattedTime"></span>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="flex-1">
                <input type="text" 
                       name="code" 
                       placeholder="000000"
                       maxlength="6"
                       autocomplete="off"
                       class="w-full text-center text-2xl font-bold tracking-widest 
                              py-3 rounded-xl border border-gray-300 
                              focus:border-green-500 focus:ring-2 focus:ring-green-100"
                       required>
            </div>
            <button type="submit"
                    class="px-5 py-3 bg-gradient-to-r from-green-500 to-emerald-500 
                           hover:from-green-600 hover:to-emerald-600 text-white font-medium 
                           rounded-xl shadow-sm hover:shadow-md transition-all duration-200 
                           flex items-center gap-2">
                <i class="ri-check-line"></i>
                Подтвердить
            </button>
        </div>
        <p class="text-xs text-gray-500 text-center">Код отправлен на указанный номер</p>
    </div>
</form>