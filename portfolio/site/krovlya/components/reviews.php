<?php
$reviews = [
    [
        'name' => 'Ирина Вавилова',
        'avatar' => 'И',
        'date' => '17.03.2026 г.',
        'text' => 'Обращались после зимы: на крыше появились протечки возле трубы и по ендове. Ребята быстро нашли причину, заменили проблемные участки и аккуратно все герметизировали. Работу приняли без замечаний.',
    ],
    [
        'name' => 'Семён Туртуев',
        'avatar' => 'С',
        'date' => '29.03.2026 г.',
        'text' => 'Заказывал монтаж гибкой черепицы на частном доме. Помогли с подбором материала, заранее согласовали смету и сроки. Бригада работала спокойно, без суеты, участок после себя оставили чистым.',
    ],
    [
        'name' => 'Анна Липская',
        'avatar' => 'А',
        'date' => '14.04.2026 г.',
        'text' => 'Делали кровлю на гараже и небольшом складе. Приехали на замер в удобное время, объяснили варианты по покрытию и водостоку. Монтаж выполнили в срок, результат выглядит очень аккуратно.',
    ],
    [
        'name' => 'Дарья Пушкина',
        'avatar' => 'Д',
        'date' => '22.04.2026 г.',
        'text' => 'Нужен был срочный ремонт кровли после сильного ветра. Созвонились утром, на следующий день мастера уже были на объекте. Все поврежденные листы заменили, крепеж проверили по всей крыше.',
    ],
    [
        'name' => 'Лиза Самойлова',
        'avatar' => 'Л',
        'date' => '08.05.2026 г.',
        'text' => 'Заказывала крышу для бани. Понравилось, что не навязывали самый дорогой вариант, а подробно объяснили плюсы разных материалов. Сделали ровно, водосток поставили аккуратно.',
    ],
    [
        'name' => 'Дарина Сергеева',
        'avatar' => 'Д',
        'date' => '19.05.2026 г.',
        'text' => 'Меняли старый профлист на металлочерепицу. Сначала оформили договор и закупили материал, потом бригада без задержек приступила к работе. Крыша стала выглядеть совсем по-другому.',
    ],
];
?>

<section class="reviews-section" id="reviews">
  <h2 class="reviews-section__title">Главный показатель качества нашей работы — это ваши благодарные отзывы!</h2>

  <div class="reviews-platforms" aria-label="Площадки с отзывами">
    <span class="reviews-platforms__text">Больше отзывов на</span>
    <img src="assets/img/rating.svg" alt="Рейтинг">
    <img src="assets/img/yandexicon.svg" alt="Яндекс">
    <img src="assets/img/rating2.svg" alt="Рейтинг">
    <img src="assets/img/2gis.svg" alt="2ГИС">
  </div>

  <div class="reviews-grid">
    <?php foreach ($reviews as $review): ?>
      <article class="review-card">
        <header class="review-card__header">
          <div class="review-card__person">
            <span class="review-card__avatar" aria-hidden="true"><?= htmlspecialchars($review['avatar'], ENT_QUOTES, 'UTF-8') ?></span>
            <h3 class="review-card__name"><?= htmlspecialchars($review['name'], ENT_QUOTES, 'UTF-8') ?></h3>
          </div>
          <time class="review-card__date"><?= htmlspecialchars($review['date'], ENT_QUOTES, 'UTF-8') ?></time>
        </header>

        <p class="review-card__text"><?= htmlspecialchars($review['text'], ENT_QUOTES, 'UTF-8') ?></p>

        <footer class="review-card__footer">
          <img class="review-card__stars" src="assets/img/stars.svg" alt="5 из 5">
          <img class="review-card__source" src="assets/img/yandex.svg" alt="Яндекс">
        </footer>
      </article>
    <?php endforeach; ?>
  </div>
</section>
