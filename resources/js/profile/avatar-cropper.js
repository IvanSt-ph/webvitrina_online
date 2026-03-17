// public/js/profile/avatar-cropper.js
function avatarCropper() {
    return {
        currentAvatar: '',
        imageToCrop: '',
        showCropper: false,
        cropper: null,
        
        init() {
            // Получаем URL аватара из meta тега
            const avatarMeta = document.querySelector('meta[name="user-avatar"]');
            this.currentAvatar = avatarMeta?.content || '/default-avatar.png';
            
            console.log('AvatarCropper initialized with:', this.currentAvatar); // Для отладки
        },
        
        openCropper(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            console.log('File selected:', file); // Для отладки
            
            if (!file.type.match('image.*')) {
                alert('Пожалуйста, выберите изображение');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = (e) => {
                this.imageToCrop = e.target.result;
                this.showCropper = true;
                
                console.log('Image loaded, showing cropper'); // Для отладки
                
                this.$nextTick(() => {
                    if (this.cropper) {
                        this.cropper.destroy();
                    }
                    
                    const image = this.$refs.cropImage;
                    if (image) {
                        this.cropper = new Cropper(image, {
                            aspectRatio: 1,
                            viewMode: 1,
                            autoCropArea: 0.8,
                            responsive: true,
                            background: false,
                        });
                        console.log('Cropper initialized'); // Для отладки
                    }
                });
            };
            reader.readAsDataURL(file);
        },
        
        closeCropper() {
            this.showCropper = false;
            this.imageToCrop = '';
            if (this.cropper) {
                this.cropper.destroy();
                this.cropper = null;
            }
            this.$refs.fileInput.value = '';
        },
        
        cropAndSave() {
            if (!this.cropper) {
                console.log('No cropper instance');
                return;
            }
            
            console.log('Cropping and saving...'); // Для отладки
            
            const canvas = this.cropper.getCroppedCanvas({
                width: 400,
                height: 400,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });
            
            canvas.toBlob((blob) => {
                const formData = new FormData();
                formData.append('avatar', blob, 'avatar.jpg');
                formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content);
                formData.append('_method', 'PATCH');
                
                // Отправляем
                fetch('/profile', {
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
                    console.log('Upload successful:', data); // Для отладки
                    this.showNotification('Аватар успешно обновлён', 'success');
                    
                    // Обновляем изображение
                    this.currentAvatar = URL.createObjectURL(blob);
                    
                    // Обновляем meta тег
                    const avatarMeta = document.querySelector('meta[name="user-avatar"]');
                    if (avatarMeta) {
                        avatarMeta.content = this.currentAvatar;
                    }
                    
                    this.closeCropper();
                    
                    // Перезагружаем страницу через секунду
                    setTimeout(() => location.reload(), 1500);
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.showNotification(error.message || 'Ошибка при сохранении аватара', 'error');
                });
            }, 'image/jpeg', 0.95);
        },
        
        showNotification(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 px-4 py-2 rounded-lg shadow-lg z-50 flex items-center gap-2 ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            } text-white animate__animated animate__fadeIn`;
            toast.innerHTML = `
                <i class="ri-${type === 'success' ? 'check-line' : 'error-warning-line'}"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('animate__fadeOut');
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }
    }
}

// Делаем функцию доступной глобально
window.avatarCropper = avatarCropper;