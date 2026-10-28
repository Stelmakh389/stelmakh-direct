<div class="popup" data-popup="consultation" hidden>
  <div class="popup__overlay" data-popup-close></div>

  <div class="popup__dialog" role="dialog" aria-modal="true" aria-labelledby="consultation-title">
    <button class="popup__close" type="button" data-popup-close aria-label="Закрыть">×</button>

    <h2 class="popup__title" id="consultation-title">Заявка на консультацию</h2>
    <p class="popup__subtitle">Оставьте заявку ниже и в течение 10 минут с вами свяжется наш специалист и ответит на все вопросы</p>

    <form class="popup__form" action="send-max.php" method="post" data-lead-form data-lead-title="Заявка на консультацию">
      <label class="popup__field">
        <span class="popup__field-label">Ваше имя</span>
        <input type="text" name="name" placeholder="Ваше имя" autocomplete="name">
      </label>

      <label class="popup__field popup__field--phone">
        <span class="popup__field-label">Телефон</span>
        <span class="popup__phone-prefix" aria-hidden="true">🇷🇺</span>
        <input type="tel" name="phone" placeholder="+7 (000) 000-00-00" autocomplete="tel" required>
      </label>

      <label class="popup__consent">
        <input type="checkbox" name="consent" required>
        <span>
          Я даю <a href="soglasie-na-obrabotku-personalnyh-dannyh.php">согласие на обработку персональных данных</a> и подтверждаю, что ознакомлен с <a href="politika-konfidencialnosti.php">Политикой в отношении обработки персональных данных</a>
        </span>
      </label>

      <button class="popup__submit" type="submit">Получить консультацию</button>
      <p class="popup__message" data-lead-message hidden></p>
    </form>
  </div>
</div>
