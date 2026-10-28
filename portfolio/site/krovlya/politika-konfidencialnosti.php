<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Политика в отношении обработки персональных данных</title>
  <meta name="description" content="Политика в отношении обработки персональных данных на сайте stroj-klyuch.ru.">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/header.css">
  <link rel="stylesheet" href="assets/css/footer.css">
  <link rel="stylesheet" href="assets/css/legal.css">
  <link rel="stylesheet" href="assets/css/cookie-consent.css">
  <?php include __DIR__ . '/components/tracking-head.php'; ?>
  <script src="assets/js/attribution.js?v=20260713-1"></script>
</head>
<body class="legal-page">
  <?php include __DIR__ . '/components/tracking-noscript.php'; ?>
  <?php include __DIR__ . '/components/header.php'; ?>
  <main class="legal-document">
    <?php
      require __DIR__ . '/components/legal-document.php';
      render_legal_document('politika-konfidencialnosti.md');
    ?>
  </main>
  <?php include __DIR__ . '/components/footer.php'; ?>
  <script src="assets/js/cookie-consent.js?v=20260715-2" data-storage-key="roof_cookie_consent" data-version="1.0.0"></script>
</body>
</html>
