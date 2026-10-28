<section class="services-section">
  <h2 class="services-section__title">
    Строим <span>пристройки любого типа</span> и назначения
  </h2>

  <div class="services-section__grid">
    <article class="service-card service-card--extension-living">
      <div class="service-card__content">
        <h3 class="service-card__title">Жилая пристройка</h3>
        <p class="service-card__text">Комната, кухня, спальня, санузел, котельная — с отоплением и коммуникациями</p>
      </div>
    </article>

    <article class="service-card service-card--extension-terrace">
      <div class="service-card__content">
        <h3 class="service-card__title">Веранда и терраса</h3>
        <p class="service-card__text">Открытые и закрытые, летние и утеплённые на весь год</p>
      </div>
    </article>

    <article class="service-card service-card--extension-frame">
      <div class="service-card__content">
        <h3 class="service-card__title">Каркасная пристройка</h3>
        <p class="service-card__text">Быстрый монтаж, лёгкий фундамент, тёплый контур</p>
      </div>
    </article>

    <article class="service-card service-card--extension-block">
      <div class="service-card__content">
        <h3 class="service-card__title">Пристройка из газобетона / бруса</h3>
        <p class="service-card__text">Капитальная пристройка под общую крышу с домом</p>
      </div>
    </article>

    <article class="service-card service-card--extension-floor">
      <div class="service-card__content">
        <h3 class="service-card__title">Второй этаж и мансарда</h3>
        <p class="service-card__text">Надстройка над пристройкой или существующей частью дома</p>
      </div>
    </article>

    <article class="service-card service-card--extension-garage">
      <div class="service-card__content">
        <h3 class="service-card__title">Гараж и хозблок</h3>
        <p class="service-card__text">Пристроенный гараж, мастерская, тамбур, крыльцо</p>
      </div>
    </article>
  </div>
</section>

<section class="extension-situations">
  <div class="extension-section-head">
    <h2>Пристройка решает проблему без переезда и большой стройки</h2>
  </div>
  <div class="extension-card-grid extension-card-grid--four">
    <article class="extension-situation-card"><span class="extension-situation-card__number">01</span><h3>Стало тесно</h3><p>Родился ребёнок или съехались с родителями — добавляем жилую комнату или спальню.</p></article>
    <article class="extension-situation-card"><span class="extension-situation-card__number">02</span><h3>Маленькая кухня</h3><p>Выносим кухню-столовую в пристройку и освобождаем пространство основного дома.</p></article>
    <article class="extension-situation-card"><span class="extension-situation-card__number">03</span><h3>Нет санузла / котельной</h3><p>Пристраиваем мокрую зону с подводкой коммуникаций и утеплённым контуром.</p></article>
    <article class="extension-situation-card"><span class="extension-situation-card__number">04</span><h3>Негде хранить / ставить машину</h3><p>Делаем гараж, мастерскую или хозблок под одной крышей с домом.</p></article>
  </div>
  <div class="extension-cta-strip">
    <p>Пристройка дешевле нового дома и не требует переезда на время работ. Рассчитаем ваш вариант за 5 минут.</p>
    <a class="extension-button" href="#quiz">Рассчитать стоимость</a>
  </div>
</section>

<section class="materials-section materials-section--extensions">
  <div class="materials-section__inner">
    <h2 class="materials-section__title">
      Строим по проверенным технологиям <span>со сроком службы до 80 лет</span>
    </h2>

    <div class="materials-section__grid">
      <?php
      $materials = [
          ['Брус', 'assets/img/extensions/material-timber.webp', 'брус для строительства пристройки'],
          ['Газобетон', 'assets/img/extensions/material-aerated-concrete.webp', 'газобетонные блоки для пристройки'],
          ['Каркас', 'assets/img/extensions/material-frame.webp', 'каркасная технология строительства пристройки'],
          ['Кирпич', 'assets/img/extensions/material-brick.webp', 'кирпич для строительства пристройки'],
          ['Пеноблок', 'assets/img/extensions/material-foam-block.webp', 'пеноблоки для строительства пристройки'],
          ['СИП-панели', 'assets/img/extensions/material-sip-panels.webp', 'СИП-панели для строительства пристройки'],
      ];
      foreach ($materials as $material):
      ?>
        <article class="material-card">
          <img class="material-card__image" src="<?= htmlspecialchars($material[1], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($material[2], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
          <h3 class="material-card__title"><?= htmlspecialchars($material[0], ENT_QUOTES, 'UTF-8') ?></h3>
        </article>
      <?php endforeach; ?>
    </div>

    <img class="materials-section__bottom-image materials-section__bottom-image--extensions" src="assets/img/extensions/materials-bottom-house.webp" alt="" loading="lazy" decoding="async">
  </div>
</section>

<section class="extension-estimate">
  <div class="extension-estimate__content">
    <h2>Цена в договоре — итоговая. Без «доплатите ещё»</h2>
    <div class="extension-estimate__list">
      <p><b>Смета фиксируется до старта</b> — стоимость работ и материалов прописана в договоре.</p>
      <p><b>Оплата по этапам</b> — платите за факт выполненного, а не всё вперёд.</p>
      <p><b>Гарантия 5 лет</b> на конструктив и узел примыкания к дому.</p>
      <p><b>Один подрядчик на всё</b> — фундамент, стены, крыша, отделка.</p>
    </div>
    <button class="extension-button" type="button" data-popup-open="consultation">Получить смету с фиксированной ценой</button>
  </div>
  <div class="extension-estimate__visual">
    <img class="extension-estimate__image" src="assets/img/extensions/contract-document.webp" alt="Договор подряда на строительство" loading="lazy" decoding="async">
    <div class="extension-estimate__caption">
      <span>Договор</span>
      <strong>Фиксированная смета</strong>
      <p>Работы, материалы, сроки, гарантия</p>
    </div>
  </div>
</section>

<?php
$extensionProjectSlides = [
    ['src' => 'assets/img/projects/extension-work-04.webp', 'alt' => 'Кирпичная пристройка с панорамным остеклением'],
    ['src' => 'assets/img/projects/extension-work-11.webp', 'alt' => 'Деревянная пристройка к загородному дому'],
    ['src' => 'assets/img/projects/extension-work-02.webp', 'alt' => 'Кирпичная веранда с теплым остеклением'],
    ['src' => 'assets/img/projects/extension-work-08.webp', 'alt' => 'Гаражный блок с тремя воротами'],
    ['src' => 'assets/img/projects/extension-work-01.webp', 'alt' => 'Каркас веранды у деревянного дома'],
    ['src' => 'assets/img/projects/extension-work-13.webp', 'alt' => 'Жилая пристройка с общей кровлей'],
    ['src' => 'assets/img/projects/extension-work-06.webp', 'alt' => 'Остекленная веранда к деревянному дому'],
    ['src' => 'assets/img/projects/extension-work-10.webp', 'alt' => 'Современный гаражный хозблок'],
    ['src' => 'assets/img/projects/extension-work-03.webp', 'alt' => 'Деревянная пристройка с черепичной кровлей'],
    ['src' => 'assets/img/projects/extension-work-05.webp', 'alt' => 'Светлая веранда с выходом в сад'],
    ['src' => 'assets/img/projects/extension-work-12.webp', 'alt' => 'Каркасная пристройка с большими окнами'],
    ['src' => 'assets/img/projects/extension-work-09.webp', 'alt' => 'Темный гараж с навесом'],
    ['src' => 'assets/img/projects/extension-work-07.webp', 'alt' => 'Открытая веранда с белыми перилами'],
];
?>

<section class="projects-section extension-projects" data-projects-carousel>
  <div class="projects-section__head">
    <h2 class="projects-section__title">
      Посмотрите наши <span>выполненные работы</span>
    </h2>
    <p class="projects-section__subtitle">Реальные фото с объектов ваших соседей</p>
  </div>

  <div class="projects-carousel">
    <button class="projects-carousel__arrow projects-carousel__arrow--prev" type="button" aria-label="Предыдущее фото" data-projects-prev></button>
    <div class="projects-carousel__viewport" data-projects-viewport>
      <div class="projects-carousel__track" data-projects-track>
        <?php foreach ($extensionProjectSlides as $index => $slide): ?>
          <figure class="projects-carousel__slide<?= $index === 0 ? ' is-active' : '' ?>">
            <img src="<?= htmlspecialchars($slide['src'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($slide['alt'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
          </figure>
        <?php endforeach; ?>
      </div>
    </div>
    <button class="projects-carousel__arrow projects-carousel__arrow--next" type="button" aria-label="Следующее фото" data-projects-next></button>
  </div>

  <div class="projects-carousel__dots" aria-label="Переключение фото" data-projects-dots>
    <?php foreach ($extensionProjectSlides as $index => $slide): ?>
      <button class="projects-carousel__dot<?= $index === 0 ? ' is-active' : '' ?>" type="button" aria-label="Показать фото <?= $index + 1 ?>" data-projects-dot="<?= $index ?>"></button>
    <?php endforeach; ?>
  </div>
</section>

<?php
$extensionReviews = [
    [
        'name' => 'Ирина Вавилова',
        'avatar' => 'И',
        'date' => '17.02.2026 г.',
        'text' => 'Пристроили каркасную комнату к дачному дому. Аккуратно свели крышу с основной, стык не течёт, зимой тепло. Смету дали заранее и по ней закрыли работы.',
    ],
    [
        'name' => 'Семён Туртуев',
        'avatar' => 'С',
        'date' => '12.03.2026 г.',
        'text' => 'Делали закрытую веранду с остеклением. На замере сразу объяснили по фундаменту и материалам, построили без доплат, участок после себя убрали.',
    ],
    [
        'name' => 'Анна Липская',
        'avatar' => 'А',
        'date' => '28.04.2026 г.',
        'text' => 'Заказывали пристройку кухни из газобетона к кирпичному дому. Помогли с фундаментом, всё сделали в срок, коммуникации вывели аккуратно.',
    ],
    [
        'name' => 'Дарья Пушкина',
        'avatar' => 'Д',
        'date' => '11.05.2026 г.',
        'text' => 'Нужен был тёплый тамбур и небольшая котельная. Бригада приехала вовремя, всё согласовали по договору, примыкание к дому сделали очень аккуратно.',
    ],
    [
        'name' => 'Лиза Самойлова',
        'avatar' => 'Л',
        'date' => '24.05.2026 г.',
        'text' => 'Строили пристройку под санузел и кладовую. Понравилось, что не навязывали лишнее, а предложили понятный вариант по бюджету и срокам.',
    ],
    [
        'name' => 'Дарина Сергеева',
        'avatar' => 'Д',
        'date' => '08.06.2026 г.',
        'text' => 'Делали гараж с хозблоком рядом с домом. Материалы привезли сами, работы закрывали по этапам, итоговая стоимость совпала со сметой.',
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
    <?php foreach ($extensionReviews as $review): ?>
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

<?php
$faqItems = [
    ['question' => 'Нужно ли разрешение на пристройку к дому?', 'answer' => ['Зависит от типа и площади. Некапитальные решения вроде веранды или крыльца часто обходятся без разрешения, а капитальную жилую пристройку обычно оформляют уведомлением.', 'На замере подскажем, какой порядок подходит именно для вашего участка и типа дома.']],
    ['question' => 'Как узаконить пристройку?', 'answer' => ['Поможем подготовить базовые данные и подскажем порядок: от техплана до внесения изменений в ЕГРН. Сложность зависит от назначения пристройки, площади и статуса участка.']],
    ['question' => 'Какой фундамент нужен для пристройки?', 'answer' => ['Подбираем под грунт и вес: свайный — для лёгкого каркаса, ленту или плиту — под газобетон и кирпич. Отдельно смотрим, как связать пристройку с фундаментом дома.']],
    ['question' => 'Можно ли строить зимой?', 'answer' => ['Каркасные пристройки и свайный фундамент можно делать круглый год. Для мокрых процессов подбираем технологию и материалы под сезон.']],
    ['question' => 'Как пристроить к старому или деревянному дому?', 'answer' => ['Делаем деформационный шов и правильный узел примыкания, чтобы дом и пристройка не повели друг друга. Это особенно важно для старых деревянных и брусовых домов.']],
    ['question' => 'Сколько стоит пристройка к дому?', 'answer' => ['Цена зависит от площади, материала, фундамента, утепления, кровли и отделки. Ориентир — от 35 000 ₽/м², точную смету даём после замера.']],
];
?>
<section class="faq-section extension-faq" data-faq>
  <h2 class="faq-section__title"><span>Ответы</span> на частые вопросы о пристройках</h2>
  <div class="faq-list">
    <?php foreach ($faqItems as $index => $item): ?>
      <?php $isOpen = $index === 0; $buttonId = 'extension-faq-button-' . $index; $panelId = 'extension-faq-panel-' . $index; ?>
      <article class="faq-item<?= $isOpen ? ' is-open' : '' ?>">
        <button class="faq-item__button" id="<?= $buttonId ?>" type="button" aria-expanded="<?= $isOpen ? 'true' : 'false' ?>" aria-controls="<?= $panelId ?>" data-faq-button>
          <span><?= htmlspecialchars($item['question'], ENT_QUOTES, 'UTF-8') ?></span>
          <span class="faq-item__icon" aria-hidden="true"></span>
        </button>
        <div class="faq-item__content" id="<?= $panelId ?>" role="region" aria-labelledby="<?= $buttonId ?>" <?= $isOpen ? '' : 'hidden' ?>>
          <?php foreach ($item['answer'] as $paragraph): ?>
            <p><?= htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endforeach; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
