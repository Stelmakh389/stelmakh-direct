let popupBg = document.querySelector('.popup__bg'); // Фон попап окна
let popupBgQuiz = document.querySelector('.popup__bg__quiz'); // Фон попап окна
let popup = document.querySelector('.popup'); // Само окно
let quiz = document.querySelector('.quiz'); // Само окно
let openQuizButtons = document.querySelectorAll('.open-quiz'); // Кнопки для показа окна
// Используем делегирование событий вместо прямого назначения
let closePopupButton = document.querySelector('.close-popup.popup-popup'); // Кнопка для скрытия окна
let closePopupButtonQuiz = document.querySelector('.close-popup.popup-quiz'); // Кнопка для скрытия окна



// Используем делегирование событий для кнопок open-popup
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('open-popup')) {
        e.preventDefault(); // Предотвращаем дефолтное поведение браузера
        popupBg?.classList.add('active'); // Добавляем класс 'active' для фона
        popup.classList.add('active'); // И для самого окна
        document.body.style.overflowY = 'hidden';
        console.log('Попап открыт через кнопку:', e.target.textContent.trim());
    }
});

openQuizButtons.forEach((button) => { // Перебираем все кнопки
    button.addEventListener('click', (e) => { // Для каждой вешаем обработчик событий на клик
        e.preventDefault(); // Предотвращаем дефолтное поведение браузера
        popupBgQuiz.classList.add('active'); // Добавляем класс 'active' для фона
        quiz.classList.add('active'); // И для самого окна
        document.body.style.overflowY = 'hidden';
    })
});


closePopupButton?.addEventListener('click',() => { // Вешаем обработчик на крестик
    popupBg.classList.remove('active'); // Убираем активный класс с фона
    popup.classList.remove('active'); // И с окна
    document.body.style.overflowY = 'auto';
});

closePopupButtonQuiz?.addEventListener('click',() => { // Вешаем обработчик на крестик
    popupBgQuiz.classList.remove('active'); // Убираем активный класс с фона
    quiz.classList.remove('active'); // И с окна
    document.body.style.overflowY = 'auto';
});

document.addEventListener('click', (e) => { // Вешаем обработчик на весь документ
    if(e.target === popupBg) { // Если цель клика - фот, то:
        popupBg.classList.remove('active'); // Убираем активный класс с фона
        popup.classList.remove('active'); // И с окна
        document.body.style.overflowY = 'auto';
    }
});

document.addEventListener('click', (e) => { // Вешаем обработчик на весь документ
    if(e.target === popupBgQuiz) { // Если цель клика - фот, то:
        popupBgQuiz.classList.remove('active'); // Убираем активный класс с фона
        quiz.classList.remove('active'); // И с окна
        document.body.style.overflowY = 'auto';
    }
});


