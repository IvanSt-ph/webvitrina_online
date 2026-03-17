{{-- resources/views/profile/partials/email-verification.blade.php --}}
@if (!Auth::user()->hasVerifiedEmail())
    <div class="border-t border-gray-100 pt-8">
        <div class="bg-gradient-to-r from-orange-50 to-red-50 rounded-xl p-5 border border-orange-200">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0">
                    <i class="ri-mail-warning-line text-orange-600 text-xl"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900">Подтвердите email</h4>
                    <p class="text-sm text-gray-600 mt-1 mb-3">
                        Для полного доступа к функциям платформы необходимо подтвердить email
                    </p>
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit"
                                class="px-5 py-2.5 bg-gradient-to-r from-orange-500 to-red-500 
                                       hover:from-orange-600 hover:to-red-600 text-white font-medium 
                                       rounded-lg shadow-sm hover:shadow-md transition-all duration-200 
                                       flex items-center gap-2">
                            <i class="ri-mail-send-line"></i>
                            Отправить письмо подтверждения
                        </button>
                    </form>
                </div>
            </div>
            
            @if (session('status') === 'verification-link-sent')
                <div class="mt-4 p-3 bg-green-100 text-green-800 text-sm rounded-lg flex items-center gap-2">
                    <i class="ri-check-double-line"></i>
                    Письмо отправлено! Проверьте вашу почту.
                </div>
            @endif
        </div>
    </div>
@endif