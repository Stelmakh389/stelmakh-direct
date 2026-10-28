<section class="quiz-section extension-quiz" id="quiz">
  <h2 class="quiz-section__title">
    Получите <span>расчёт стоимости</span> вашей пристройки за 5 шагов
  </h2>

  <div class="roof-quiz" data-quiz data-quiz-title="Заявка из квиза расчета пристройки" data-quiz-lead-type="pristroyki_quiz" data-quiz-goal="lead_form_submit">
    <div class="roof-quiz__main">
      <div class="roof-quiz__progress" aria-live="polite">
        <div class="roof-quiz__progress-track">
          <span class="roof-quiz__progress-fill" data-quiz-progress></span>
        </div>
        <span class="roof-quiz__progress-text" data-quiz-progress-text>Расчет пройден на 17%</span>
      </div>

      <article class="roof-quiz__step is-active" data-quiz-step="0">
        <span class="roof-quiz__badge">Вопрос 1</span>
        <h3 class="roof-quiz__question">Какой тип пристройки нужен?</h3>
        <div class="roof-quiz__cards extension-quiz__cards--four">
          <button class="roof-quiz__card is-selected" type="button" data-quiz-option>
            <span class="roof-quiz__placeholder"><img src="assets/img/extensions/quiz-living-room.webp" alt="Жилая комната, кухня или санузел в пристройке" loading="lazy" decoding="async"><span class="roof-quiz__check" aria-hidden="true"></span></span>
            <strong>Жилая комната / кухня / санузел</strong>
          </button>
          <button class="roof-quiz__card" type="button" data-quiz-option>
            <span class="roof-quiz__placeholder"><img src="assets/img/extensions/quiz-veranda-terrace.webp" alt="Пристройка веранды или террасы" loading="lazy" decoding="async"><span class="roof-quiz__check" aria-hidden="true"></span></span>
            <strong>Веранда или терраса</strong>
          </button>
          <button class="roof-quiz__card" type="button" data-quiz-option>
            <span class="roof-quiz__placeholder"><img src="assets/img/extensions/quiz-garage-utility.webp" alt="Пристройка гаража, хозблока или котельной" loading="lazy" decoding="async"><span class="roof-quiz__check" aria-hidden="true"></span></span>
            <strong>Гараж / хозблок / котельная</strong>
          </button>
          <button class="roof-quiz__card" type="button" data-quiz-option>
            <span class="roof-quiz__placeholder"><img src="assets/img/extensions/quiz-undecided.webp" alt="Подбор типа пристройки к дому" loading="lazy" decoding="async"><span class="roof-quiz__check" aria-hidden="true"></span></span>
            <strong>Пока не определился</strong>
          </button>
        </div>
      </article>

      <article class="roof-quiz__step" data-quiz-step="1" hidden>
        <span class="roof-quiz__badge">Вопрос 2</span>
        <h3 class="roof-quiz__question">Из какого материала строим?</h3>
        <div class="roof-quiz__choices">
          <button class="roof-quiz__choice is-selected" type="button" data-quiz-option><span></span>Каркас</button>
          <button class="roof-quiz__choice" type="button" data-quiz-option><span></span>Газобетон / пеноблок</button>
          <button class="roof-quiz__choice" type="button" data-quiz-option><span></span>Брус</button>
          <button class="roof-quiz__choice" type="button" data-quiz-option><span></span>Посоветуйте оптимальный</button>
        </div>
      </article>

      <article class="roof-quiz__step" data-quiz-step="2" hidden>
        <span class="roof-quiz__badge">Вопрос 3</span>
        <h3 class="roof-quiz__question">К какому дому пристраиваем?</h3>
        <div class="roof-quiz__choices">
          <button class="roof-quiz__choice is-selected" type="button" data-quiz-option><span></span>Деревянный / брусовой</button>
          <button class="roof-quiz__choice" type="button" data-quiz-option><span></span>Кирпичный / блочный</button>
          <button class="roof-quiz__choice" type="button" data-quiz-option><span></span>Каркасный</button>
          <button class="roof-quiz__choice" type="button" data-quiz-option><span></span>Дачный / щитовой</button>
        </div>
      </article>

      <article class="roof-quiz__step" data-quiz-step="3" hidden>
        <span class="roof-quiz__badge">Вопрос 4</span>
        <h3 class="roof-quiz__question">Примерная площадь пристройки?</h3>
        <div class="roof-quiz__choices">
          <button class="roof-quiz__choice is-selected" type="button" data-quiz-option><span></span>До 15 м²</button>
          <button class="roof-quiz__choice" type="button" data-quiz-option><span></span>15–30 м²</button>
          <button class="roof-quiz__choice" type="button" data-quiz-option><span></span>30–50 м²</button>
          <button class="roof-quiz__choice" type="button" data-quiz-option><span></span>Больше 50 м²</button>
        </div>
      </article>

      <article class="roof-quiz__step" data-quiz-step="4" hidden>
        <span class="roof-quiz__badge">Вопрос 5</span>
        <h3 class="roof-quiz__question">Когда планируете начать?</h3>
        <div class="roof-quiz__choices extension-quiz__choices--three">
          <button class="roof-quiz__choice is-selected" type="button" data-quiz-option><span></span>В ближайший месяц</button>
          <button class="roof-quiz__choice" type="button" data-quiz-option><span></span>В этом сезоне</button>
          <button class="roof-quiz__choice" type="button" data-quiz-option><span></span>Пока прицениваюсь</button>
        </div>
      </article>

      <article class="roof-quiz__step roof-quiz__step--final" data-quiz-step="5" hidden>
        <span class="roof-quiz__badge">Расчет готов</span>
        <h3 class="roof-quiz__question">Где вам удобней получить расчет стоимости?</h3>
        <form class="roof-quiz__form" action="send-max.php" method="post" data-quiz-form>
          <div class="roof-quiz__messengers" role="group" aria-label="Способ связи">
            <button class="roof-quiz__messenger is-selected" type="button" data-quiz-option><span>✈</span>Telegram</button>
            <button class="roof-quiz__messenger" type="button" data-quiz-option><span>М</span>MAX</button>
            <button class="roof-quiz__messenger" type="button" data-quiz-option><span>@</span>E-mail</button>
            <button class="roof-quiz__messenger" type="button" data-quiz-option><span>☎</span>Звонок</button>
          </div>
          <input class="roof-quiz__input" type="text" name="name" placeholder="Ваше имя" required>
          <input class="roof-quiz__input" type="tel" name="phone" placeholder="+7 (___) ___-__-__" required data-quiz-phone>
          <label class="roof-quiz__consent">
            <input type="checkbox" required checked>
            <span>Я даю <a href="soglasie-na-obrabotku-personalnyh-dannyh.php">согласие на обработку персональных данных</a> и подтверждаю, что ознакомлен с <a href="politika-konfidencialnosti.php">Политикой в отношении обработки персональных данных</a></span>
          </label>
          <button class="roof-quiz__submit" type="submit">Получить расчет</button>
          <p class="roof-quiz__success" data-quiz-success hidden>Спасибо! Заявка принята, специалист скоро свяжется с вами.</p>
        </form>
      </article>

      <div class="roof-quiz__nav">
        <button class="roof-quiz__button roof-quiz__button--back" type="button" data-quiz-prev hidden>Назад</button>
        <button class="roof-quiz__button roof-quiz__button--next" type="button" data-quiz-next>Далее</button>
      </div>
    </div>

    <aside class="roof-quiz__side">
      <div class="roof-quiz__status">Онлайн</div>
      <img class="roof-quiz__manager" src="assets/img/quiz-manager.webp" alt="Станислав Резников" loading="lazy" decoding="async">
      <div class="roof-quiz__name">Станислав Резников</div>
      <p class="roof-quiz__side-text">Отвечайте на вопросы и получите скидку + подробную смету</p>
      <div class="roof-quiz__side-bottom">
        <strong>В конце получите:</strong>
        <span class="roof-quiz__bonus">Подробная смета <i aria-hidden="true">🔒</i></span>
      </div>
    </aside>
  </div>
</section>
