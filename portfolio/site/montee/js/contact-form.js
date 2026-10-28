// Добавляем класс open-popup ко всем кнопкам "Взять в аренду" без этого класса
document.addEventListener('DOMContentLoaded', function() {
    // Функция для добавления класса open-popup ко всем кнопкам "Взять в аренду"
    function addOpenPopupClass() {
        const rentButtons = document.querySelectorAll('.catalog__item-btn');
        rentButtons.forEach(button => {
            if (button.textContent.trim() === 'Взять в аренду' && !button.classList.contains('open-popup')) {
                button.classList.add('open-popup');
            }
        });
    }

    // Добавляем класс при загрузке страницы
    addOpenPopupClass();

    // Добавляем обработчик для переключения табов
    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(tabButton => {
        tabButton.addEventListener('click', function() {
            // Небольшая задержка, чтобы таб успел переключиться
            setTimeout(() => {
                addOpenPopupClass();
            }, 100);
        });
    });

    // Инициализация маски телефона для попапа
    const popupPhoneInput = document.querySelector('.popup__input.phone');
    if (popupPhoneInput && typeof IMask !== 'undefined') {
        const popupPhoneMask = new IMask(popupPhoneInput, {
            mask: "+{7}(000)000-00-00"
        });

        // Валидация формы попапа
        popupPhoneInput.addEventListener('input', function() {
            const submitBtn = document.querySelector('.popup__submit');
            const checkbox = document.querySelector('.popup__checkbox');
            
            if (popupPhoneMask.masked.isComplete && checkbox.checked) {
                submitBtn.classList.add('btn--active');
                submitBtn.disabled = false;
            } else {
                submitBtn.classList.remove('btn--active');
                submitBtn.disabled = true;
            }
        });

        // Проверка чекбокса
        const checkbox = document.querySelector('.popup__checkbox');
        if (checkbox) {
            checkbox.addEventListener('change', function() {
                const submitBtn = document.querySelector('.popup__submit');
                
                if (popupPhoneMask.masked.isComplete && this.checked) {
                    submitBtn.classList.add('btn--active');
                    submitBtn.disabled = false;
                } else {
                    submitBtn.classList.remove('btn--active');
                    submitBtn.disabled = true;
                }
            });
        }
    }

    // Инициализация маски телефона для основной формы
    const mainPhoneInput = document.querySelector('.contact__input');
    if (mainPhoneInput && typeof IMask !== 'undefined') {
        const mainPhoneMask = new IMask(mainPhoneInput, {
            mask: "+{7}(000)000-00-00"
        });

        // Валидация основной формы
        mainPhoneInput.addEventListener('input', function() {
            const submitBtn = document.querySelector('.contact__btn');
            
            if (mainPhoneMask.masked.isComplete) {
                submitBtn.classList.add('btn--active');
                submitBtn.disabled = false;
            } else {
                submitBtn.classList.remove('btn--active');
                submitBtn.disabled = true;
            }
        });
    }

    // Обработка отправки попап формы
    const popupForm = document.getElementById('contact-form');
    if (popupForm) {
        popupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const phone = this.querySelector('input[name="phone"]').value;
            const privacyChecked = this.querySelector('input[name="privacy"]').checked;
            
            if (!phone || !privacyChecked) {
                alert('Пожалуйста, заполните все поля и согласитесь с условиями обработки персональных данных');
                return;
            }

            // Отправка данных в Telegram
            sendToTelegram(phone, 'Попап форма');
        });
    }

    // Обработка отправки основной формы
    const mainForm = document.querySelector('.contact__form');
    if (mainForm) {
        mainForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const phone = this.querySelector('input[type="tel"]').value;
            
            if (!phone) {
                alert('Пожалуйста, введите номер телефона');
                return;
            }

            // Отправка данных в Telegram
            sendToTelegram(phone, 'Основная форма');
        });
    }
});

// Демо-копия для портфолио: заявка никуда не отправляется.
async function sendToTelegram(phone, formType) {
    handleSuccessfulSubmission();
}

// Функция обработки успешной отправки
function handleSuccessfulSubmission() {
    // Закрываем попап если он открыт
    const popupBg = document.querySelector('.popup__bg');
    const popup = document.querySelector('.popup');
    if (popupBg && popupBg.classList.contains('active')) {
        popupBg.classList.remove('active');
        popup.classList.remove('active');
        document.body.style.overflowY = 'auto';
    }
    
    // Очищаем форму
    const form = document.getElementById('contact-form') || document.querySelector('.contact__form');
    if (form) {
        form.reset();
        // Сбрасываем состояние кнопки отправки
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.classList.remove('btn--active');
            submitBtn.disabled = true;
        }
    }
    
    // Перенаправляем на страницу благодарности
    setTimeout(() => {
        window.location.href = 'thank-you.html';
    }, 500); // Небольшая задержка для плавности
}

