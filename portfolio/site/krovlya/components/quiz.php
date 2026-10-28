<section class="quiz-section" id="quiz">
  <h2 class="quiz-section__title">
    Получите <span>расчёт стоимости </span> монтажа вашей кровли
  </h2>

  <div class="roof-quiz" data-quiz data-quiz-lead-type="roof_quiz" data-quiz-goal="lead_form_submit">
    <div class="roof-quiz__main">
      <div class="roof-quiz__progress" aria-live="polite">
        <div class="roof-quiz__progress-track">
          <span class="roof-quiz__progress-fill" data-quiz-progress></span>
        </div>
        <span class="roof-quiz__progress-text" data-quiz-progress-text>Расчет пройден на 17%</span>
      </div>

      <article class="roof-quiz__step is-active" data-quiz-step="0">
        <span class="roof-quiz__badge">Вопрос 1</span>
        <h3 class="roof-quiz__question">Какая услуга вам нужна?</h3>

        <div class="roof-quiz__cards roof-quiz__cards--three">
          <button class="roof-quiz__card is-selected" type="button" data-quiz-option>
            <span class="roof-quiz__placeholder">
              <img src="assets/img/quiz/service-install.webp" alt="Монтаж кровли" loading="lazy" decoding="async">
              <span class="roof-quiz__check" aria-hidden="true"></span>
            </span>
            <strong>Монтаж кровли</strong>
          </button>
          <button class="roof-quiz__card" type="button" data-quiz-option>
            <span class="roof-quiz__placeholder">
              <img src="assets/img/quiz/service-repair.webp" alt="Ремонт кровли" loading="lazy" decoding="async">
              <span class="roof-quiz__check" aria-hidden="true"></span>
            </span>
            <strong>Ремонт кровли</strong>
          </button>
          <button class="roof-quiz__card" type="button" data-quiz-option>
            <span class="roof-quiz__placeholder">
              <img src="assets/img/quiz/service-turnkey.webp" alt="Крыша под ключ" loading="lazy" decoding="async">
              <span class="roof-quiz__check" aria-hidden="true"></span>
            </span>
            <strong>Крыша под ключ</strong>
          </button>
        </div>
      </article>

      <article class="roof-quiz__step" data-quiz-step="1" hidden>
        <span class="roof-quiz__badge">Вопрос 2</span>
        <h3 class="roof-quiz__question">Какой материал кровли?</h3>

        <div class="roof-quiz__cards">
          <button class="roof-quiz__card is-selected" type="button" data-quiz-option>
            <span class="roof-quiz__placeholder">
              <img src="assets/img/quiz/material-metal.webp" alt="Металлочерепица" loading="lazy" decoding="async">
              <span class="roof-quiz__check" aria-hidden="true"></span>
            </span>
            <strong>Металлочерепица</strong>
          </button>
          <button class="roof-quiz__card" type="button" data-quiz-option>
            <span class="roof-quiz__placeholder">
              <img src="assets/img/quiz/material-tile.webp" alt="Черепица" loading="lazy" decoding="async">
              <span class="roof-quiz__check" aria-hidden="true"></span>
            </span>
            <strong>Черепица</strong>
          </button>
          <button class="roof-quiz__card" type="button" data-quiz-option>
            <span class="roof-quiz__placeholder">
              <img src="assets/img/quiz/material-faltz.webp" alt="Фальцевая кровля" loading="lazy" decoding="async">
              <span class="roof-quiz__check" aria-hidden="true"></span>
            </span>
            <strong>Фальцевая</strong>
          </button>
          <button class="roof-quiz__card" type="button" data-quiz-option>
            <span class="roof-quiz__placeholder">
              <img src="assets/img/quiz/material-soft.webp" alt="Мягкая кровля" loading="lazy" decoding="async">
              <span class="roof-quiz__check" aria-hidden="true"></span>
            </span>
            <strong>Мягкая кровля</strong>
          </button>
          <button class="roof-quiz__card" type="button" data-quiz-option>
            <span class="roof-quiz__placeholder">
              <img src="assets/img/quiz/material-profnastil.webp" alt="Профнастил" loading="lazy" decoding="async">
              <span class="roof-quiz__check" aria-hidden="true"></span>
            </span>
            <strong>Профнастил</strong>
          </button>
          <button class="roof-quiz__card" type="button" data-quiz-option>
            <span class="roof-quiz__placeholder">
              <img src="assets/img/quiz/material-ondulin.webp" alt="Ондулин" loading="lazy" decoding="async">
              <span class="roof-quiz__check" aria-hidden="true"></span>
            </span>
            <strong>Ондулин</strong>
          </button>
        </div>
      </article>

      <article class="roof-quiz__step" data-quiz-step="2" hidden>
        <span class="roof-quiz__badge">Вопрос 3</span>
        <h3 class="roof-quiz__question">Какой тип у вас кровли?</h3>

        <div class="roof-quiz__choices">
          <button class="roof-quiz__choice is-selected" type="button" data-quiz-option><span></span>Плоская</button>
          <button class="roof-quiz__choice" type="button" data-quiz-option><span></span>Двускатная</button>
          <button class="roof-quiz__choice" type="button" data-quiz-option><span></span>Односкатная</button>
          <button class="roof-quiz__choice" type="button" data-quiz-option><span></span>Ломаная мансардная</button>
          <button class="roof-quiz__choice" type="button" data-quiz-option><span></span>Вальмовая</button>
          <button class="roof-quiz__choice" type="button" data-quiz-option><span></span>Другая</button>
        </div>
      </article>

      <article class="roof-quiz__step" data-quiz-step="3" hidden>
        <span class="roof-quiz__badge">Вопрос 4</span>
        <h3 class="roof-quiz__question">Укажите примерную площадь крыши</h3>

        <div class="roof-quiz__field-group">
          <label class="roof-quiz__field">
            <span>Площадь крыши</span>
            <input type="number" min="1" placeholder="Например: 190" data-quiz-required>
            <em>м2</em>
          </label>
          <label class="roof-quiz__skip">
            <input type="checkbox" data-quiz-skip>
            <span>Пропустить вопрос</span>
          </label>
        </div>
      </article>

      <article class="roof-quiz__step" data-quiz-step="4" hidden>
        <span class="roof-quiz__badge">Вопрос 5</span>
        <h3 class="roof-quiz__question">Как скоро необходимо начать?</h3>

        <div class="roof-quiz__choices">
          <button class="roof-quiz__choice is-selected" type="button" data-quiz-option><span></span>Еще вчера</button>
          <button class="roof-quiz__choice" type="button" data-quiz-option><span></span>В течение 1 недели</button>
          <button class="roof-quiz__choice" type="button" data-quiz-option><span></span>В течение 1 месяца</button>
          <button class="roof-quiz__choice" type="button" data-quiz-option><span></span>Еще не определились</button>
        </div>
      </article>

      <article class="roof-quiz__step roof-quiz__step--final" data-quiz-step="5" hidden>
        <span class="roof-quiz__badge">Расчет готов</span>
        <h3 class="roof-quiz__question">Где вам удобней получить расчет стоимости?</h3>

        <form class="roof-quiz__form" action="send-max.php" method="post" data-quiz-form>
          <div class="roof-quiz__messengers" role="group" aria-label="Способ связи">
            <button class="roof-quiz__messenger is-selected" type="button" data-quiz-option>
              <span>✈</span>
              Telegram
            </button>
            <button class="roof-quiz__messenger" type="button" data-quiz-option>
              <span>М</span>
              MAX
            </button>
            <button class="roof-quiz__messenger" type="button" data-quiz-option>
              <span>@</span>
              E-mail
            </button>
            <button class="roof-quiz__messenger" type="button" data-quiz-option>
              <span>☎</span>
              Звонок
            </button>
          </div>

          <input class="roof-quiz__input" type="text" name="name" placeholder="Ваше имя" required>
          <input class="roof-quiz__input" type="tel" name="phone" placeholder="+7 (___) ___-__-__" required data-quiz-phone>

          <label class="roof-quiz__consent">
            <input type="checkbox" required checked>
            <span>
              Я даю <a href="soglasie-na-obrabotku-personalnyh-dannyh.php">согласие на обработку персональных данных</a>
              и подтверждаю, что ознакомлен с <a href="politika-konfidencialnosti.php">Политикой в отношении обработки персональных данных</a>
            </span>
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
      <p class="roof-quiz__side-text">
        Отвечайте на вопросы и получите скидку + подробную смету
      </p>
      <div class="roof-quiz__side-bottom">
        <strong>В конце получите:</strong>
        <span class="roof-quiz__bonus">Подробная смета <i aria-hidden="true">🔒</i></span>
      </div>
    </aside>
  </div>
</section>
