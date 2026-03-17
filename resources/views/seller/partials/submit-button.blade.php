{{-- resources/views/profile/partials/submit-button.blade.php --}}
<div class="flex justify-end">
    <button type="submit"
            class="relative overflow-hidden group px-6 py-3.5 bg-indigo-500/90 hover:bg-indigo-600 
                   text-white font-medium rounded-xl shadow-lg hover:shadow-xl 
                   transition-all duration-300 transform hover:-translate-y-0.5
                   flex items-center gap-2 backdrop-blur-sm border border-indigo-400/30">
        <span class="relative z-10 flex items-center gap-2">
            <i class="ri-save-3-line text-lg"></i>
            Сохранить изменения
        </span>
        <span class="absolute inset-0 bg-indigo-600 translate-y-full 
                     group-hover:translate-y-0 transition-transform duration-300"></span>
    </button>
</div>