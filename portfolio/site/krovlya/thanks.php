<!DOCTYPE html>
<html lang="ru">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta charset="UTF-8">
  <title>Спасибо за заявку</title>
  <meta name="description" content="Спасибо за заявку на расчет или консультацию по монтажу кровли. Специалист свяжется с вами в ближайшее время.">
  <link rel="preload" as="image" href="assets/img/section_full.webp" fetchpriority="high">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/header.css">
  <link rel="stylesheet" href="assets/css/hero.css?v=20260710-6">
  <link rel="stylesheet" href="assets/css/thanks.css?v=20260710-6">
  <link rel="stylesheet" href="assets/css/popup.css">
  <link rel="stylesheet" href="assets/css/cookie-consent.css">
  <?php include __DIR__ . '/components/tracking-head.php'; ?>
  <script src="assets/js/attribution.js?v=20260713-1"></script>
</head>
<body id="_">
  <?php include __DIR__ . '/components/tracking-noscript.php'; ?>
  <main class="thanks-page">
    <section class="thanks-hero">
      <?php include __DIR__ . '/components/header.php'; ?>

      <div class="thanks-hero__inner">
        <div class="thanks-hero__content">
          <p class="thanks-hero__eyebrow">Заявка отправлена</p>
          <h1 class="thanks-hero__title">Спасибо!<br>Мы уже получили вашу заявку</h1>
          <p class="thanks-hero__text">Специалист свяжется с вами в ближайшее время, уточнит детали по кровле и ответит на вопросы.</p>
          <div class="thanks-hero__actions">
            <a class="thanks-hero__button" href="index.php">Вернуться на главную</a>
            <a class="thanks-hero__phone" href="tel:+79855193686">+7(985)519-36-86</a>
          </div>
        </div>
      </div>
    </section>
  </main>
  <?php include __DIR__ . '/components/popup.php'; ?>
  <script src="assets/js/popup.js?v=20260713-1"></script>
  <script src="assets/js/cookie-consent.js?v=20260715-2" data-storage-key="roof_cookie_consent" data-version="1.0.0"></script>
  <script src="assets/js/thanks-goal.js?v=20260715-1"></script>
</body>
</html>
