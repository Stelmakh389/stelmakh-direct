<?php
$roofProjectSlides = [
    ['src' => 'assets/img/projects/roof-work-01.webp', 'alt' => 'Выполненная кровля на частном доме'],
    ['src' => 'assets/img/projects/roof-work-02.webp', 'alt' => 'Дом с новой кровлей из металлочерепицы'],
    ['src' => 'assets/img/projects/roof-work-03.webp', 'alt' => 'Дом с кровлей и верандой на участке'],
    ['src' => 'assets/img/projects/roof-work-04.webp', 'alt' => 'Монтаж кровли на частном доме'],
    ['src' => 'assets/img/projects/roof-work-05.webp', 'alt' => 'Кровля частного дома с ограждением'],
    ['src' => 'assets/img/projects/roof-work-06.webp', 'alt' => 'Готовая кровля из профлиста'],
    ['src' => 'assets/img/projects/roof-work-07.webp', 'alt' => 'Дом с темной кровлей'],
    ['src' => 'assets/img/projects/roof-work-08.webp', 'alt' => 'Фальцевая кровля частного дома'],
    ['src' => 'assets/img/projects/roof-work-09.webp', 'alt' => 'Кровля дома из газобетона'],
];
?>

<section class="projects-section" data-projects-carousel>
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
        <?php foreach ($roofProjectSlides as $index => $slide): ?>
          <figure class="projects-carousel__slide<?= $index === 0 ? ' is-active' : '' ?>">
            <img src="<?= htmlspecialchars($slide['src'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($slide['alt'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
          </figure>
        <?php endforeach; ?>
      </div>
    </div>
    <button class="projects-carousel__arrow projects-carousel__arrow--next" type="button" aria-label="Следующее фото" data-projects-next></button>
  </div>

  <div class="projects-carousel__dots" aria-label="Переключение фото" data-projects-dots>
    <?php foreach ($roofProjectSlides as $index => $slide): ?>
      <button class="projects-carousel__dot<?= $index === 0 ? ' is-active' : '' ?>" type="button" aria-label="Показать фото <?= $index + 1 ?>" data-projects-dot="<?= $index ?>"></button>
    <?php endforeach; ?>
  </div>
</section>
