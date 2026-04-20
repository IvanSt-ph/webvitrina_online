/**
 * 🌸 Модуль каталога товаров
 * Файл: resources/js/catalog.js
 * Версия: 2.0
 */

// ============================================================
// 🌿 АНИМАЦИЯ КАРТОЧЕК (IntersectionObserver)
// ============================================================

function initFadeCards() {
    const cards = document.querySelectorAll('.fade-card:not(.visible)');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    cards.forEach(card => observer.observe(card));
    
    return observer;
}

let cardObserver = null;
let mutationObserver = null;

// ============================================================
// 🔄 AJAX ФИЛЬТРАЦИЯ (Alpine компонент)
// ============================================================

document.addEventListener('alpine:init', () => {
    Alpine.data('filtersAjax', () => ({
        isLoading: false,
        
        async apply() {
            if (this.isLoading) return;
            
            this.isLoading = true;
            
            // Меняем состояние кнопки
            const form = this.$el;
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.classList.add('opacity-50', 'cursor-wait');
                submitBtn.disabled = true;
            }
            
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            
            try {
                const response = await fetch(`${window.location.pathname}?${params.toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const html = await response.text();
                const container = document.getElementById('products-container');
                
                if (container) {
                    container.innerHTML = html;
                    
                    // Перезапускаем анимации
                    if (cardObserver) cardObserver.disconnect();
                    cardObserver = initFadeCards();
                    
                    // Плавный скролл
                    container.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
                
                // Закрываем панель фильтров (через событие, без __x)
                this.$dispatch('close-filters-panel');
                
            } catch (error) {
                console.error('❌ Ошибка фильтрации:', error);
            } finally {
                this.isLoading = false;
                if (submitBtn) {
                    submitBtn.classList.remove('opacity-50', 'cursor-wait');
                    submitBtn.disabled = false;
                }
            }
        }
    }));
});

// ============================================================
// 🚀 ИНИЦИАЛИЗАЦИЯ ПРИ ЗАГРУЗКЕ
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
    // Запускаем анимации
    cardObserver = initFadeCards();
    
    // Наблюдаем за контейнером
    const productsContainer = document.getElementById('products-container');
    if (productsContainer) {
        mutationObserver = new MutationObserver(() => {
            if (cardObserver) cardObserver.disconnect();
            cardObserver = initFadeCards();
        });
        mutationObserver.observe(productsContainer, { childList: true, subtree: true });
    }
});

// ============================================================
// 🧹 ОЧИСТКА ПРИ УХОДЕ
// ============================================================

window.addEventListener('beforeunload', () => {
    if (cardObserver) cardObserver.disconnect();
    if (mutationObserver) mutationObserver.disconnect();
});