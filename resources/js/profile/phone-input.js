// public/js/profile/phone-input.js
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация всех полей ввода телефона
    const phoneInputs = [
        { id: 'shop-phone-input', form: 'shop-phone-save-form' },
        { id: 'update-phone-input', form: 'update-phone-form' },
        { id: 'change-phone-input', form: 'change-phone-form' }
    ];
    
    phoneInputs.forEach(config => {
        const input = document.querySelector(`#${config.id}`);
        if(input){
            const iti = window.intlTelInput(input, {
                initialCountry: "md",
                preferredCountries: ["md", "ru", "ro", "ua"],
                separateDialCode: true,
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js",
            });
            
            const form = document.querySelector(`#${config.form}`);
            if(form){
                form.addEventListener('submit', function(e){
                    input.value = iti.getNumber();
                });
            }
        }
    });
    
    // Форма верификации
    const verifyForm = document.querySelector('#shop-phone-verify-form');
    if(verifyForm){
        verifyForm.addEventListener('submit', function(e){
            // Для формы верификации можно добавить логику если нужно
            console.log('Verification form submitted');
        });
    }
});