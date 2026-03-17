{{-- resources/views/profile/partials/submit-button.blade.php --}}
<div class="flex justify-end">
    <button type="submit"
            class="relative overflow-hidden group px-6 py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 
                   hover:from-indigo-700 hover:to-purple-700 text-white font-medium rounded-xl 
                   shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5
                   flex items-center gap-2">
        <span class="relative z-10 flex items-center gap-2">
            <i class="ri-save-3-line text-lg"></i>
            Сохранить изменения
        </span>
        <span class="absolute inset-0 bg-gradient-to-r from-indigo-700 to-purple-700 translate-y-full 
                     group-hover:translate-y-0 transition-transform duration-300"></span>
    </button>
</div>
