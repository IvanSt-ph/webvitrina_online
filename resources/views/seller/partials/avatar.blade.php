{{-- resources/views/profile/partials/avatar.blade.php --}}
<div x-data="avatarCropper()" class="relative group">
    <!-- Текущий аватар -->
    <div class="relative">
        <img x-ref="avatarImage"
             src="{{ Auth::user()->avatar ? Storage::url(Auth::user()->avatar) : 'https://ui-avatars.com/api/?name='.urlencode(Auth::user()->name).'&color=7F9CF5&background=EBF4FF' }}"
             alt="Аватар"
             class="w-32 h-32 rounded-2xl border-4 border-white shadow-lg object-cover">
        
        <!-- Оверлей при наведении -->
        <div class="absolute inset-0 rounded-2xl bg-black/40 opacity-0 group-hover:opacity-100 
                    transition-opacity flex items-center justify-center cursor-pointer">
            <div class="text-white text-center">
                <i class="ri-camera-line text-2xl mb-1 block"></i>
                <span class="text-xs font-medium">Изменить</span>
            </div>
        </div>
    </div>

    
    
    <!-- Кнопка загрузки -->
    <label class="absolute -bottom-2 -right-2 bg-white border border-gray-200 shadow-lg 
                  rounded-full w-10 h-10 flex items-center justify-center cursor-pointer 
                  hover:bg-indigo-50 hover:border-indigo-200 transition-all duration-200 
                  group-hover:scale-110 z-10">
        <i class="ri-camera-line text-gray-600 group-hover:text-indigo-600"></i>
        <input type="file" 
               x-ref="fileInput"
               @change="openCropper($event)"
               class="hidden" 
               accept="image/*">
    </label>
    
    <!-- Модальное окно обрезки -->
    <div x-show="showCropper" 
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
            <!-- Заголовок -->
            <div class="flex items-center justify-between p-4 border-b">
                <h3 class="text-lg font-semibold text-gray-900">Обрезка аватара</h3>
                <button @click="closeCropper()" class="text-gray-400 hover:text-gray-600">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>
            
            <!-- Контейнер для обрезки -->
            <div class="p-4">
                <div class="bg-gray-100 rounded-lg overflow-hidden" style="height: 300px;">
                    <img :src="imageToCrop"
                         x-ref="cropImage"
                         class="max-w-full max-h-full">
                </div>
            </div>
            
            <!-- Кнопки -->
            <div class="border-t p-4 flex justify-end gap-3">
                <button type="button"
                        @click="closeCropper()"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 
                               transition-colors">
                    Отмена
                </button>
                
                <button type="button"
                        @click="cropAndSave()"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 
                               transition-colors flex items-center gap-2">
                    <i class="ri-crop-line"></i>
                    Обрезать и сохранить
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function avatarCropper() {
    return {
        currentAvatar: '',
        imageToCrop: '',
        showCropper: false,
        cropper: null,
        
        init() {
            console.log('✅ Avatar cropper initialized');
            this.currentAvatar = this.$refs.avatarImage.src;
        },
        
        openCropper(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            console.log('File selected:', file.name);
            
            if (!file.type.startsWith('image/')) {
                alert('Пожалуйста, выберите изображение');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = (e) => {
                this.imageToCrop = e.target.result;
                this.showCropper = true;
                
                this.$nextTick(() => {
                    if (this.cropper) {
                        this.cropper.destroy();
                    }
                    
                    const image = this.$refs.cropImage;
                    if (image && typeof Cropper !== 'undefined') {
                        this.cropper = new Cropper(image, {
                            aspectRatio: 1,
                            viewMode: 1,
                            autoCropArea: 1,
                            background: false,
                        });
                        console.log('✅ Cropper ready');
                    } else {
                        console.error('Cropper not available');
                    }
                });
            };
            reader.readAsDataURL(file);
        },
        
        closeCropper() {
            this.showCropper = false;
            if (this.cropper) {
                this.cropper.destroy();
                this.cropper = null;
            }
            this.$refs.fileInput.value = '';
        },
        
        cropAndSave() {
            if (!this.cropper) {
                alert('Ошибка: обрезчик не инициализирован');
                return;
            }
            
            const canvas = this.cropper.getCroppedCanvas({
                width: 400,
                height: 400,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });
            
            // Показываем индикатор загрузки
            this.showNotification('Загрузка...', 'info');
            
            canvas.toBlob((blob) => {
                const formData = new FormData();
                formData.append('avatar', blob, 'avatar.jpg');
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('_method', 'PATCH');
                
                // Отправляем на сервер
                fetch('{{ route("profile.update") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.message || 'Ошибка при сохранении');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Server response:', data);
                    
                    if (data.success) {
                        this.showNotification('Аватар успешно обновлён!', 'success');
                        
                        // Обновляем изображение - используем URL от сервера
                        if (data.avatar_url) {
                            this.currentAvatar = data.avatar_url;
                        } else {
                            this.currentAvatar = URL.createObjectURL(blob);
                        }
                        
                        this.closeCropper();
                        
                        // Перезагружаем страницу через 1.5 секунды
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        throw new Error(data.message || 'Неизвестная ошибка');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.showNotification(error.message || 'Ошибка при загрузке', 'error');
                    this.closeCropper();
                });
            }, 'image/jpeg', 0.95);
        },
        
        showNotification(message, type = 'success') {
            // Удаляем предыдущее уведомление если есть
            const oldNotification = document.querySelector('.avatar-notification');
            if (oldNotification) {
                oldNotification.remove();
            }
            
            const notification = document.createElement('div');
            notification.className = `avatar-notification fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2 
                ${type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'} text-white`;
            notification.innerHTML = `
                <i class="ri-${type === 'success' ? 'check-line' : type === 'error' ? 'error-warning-line' : 'information-line'}"></i>
                <span>${message}</span>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    }
}
</script>