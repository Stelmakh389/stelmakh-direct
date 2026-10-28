<link rel="icon" href="favicon.svg" type="image/svg+xml">
<link rel="shortcut icon" href="favicon.svg" type="image/svg+xml">
<meta name="theme-color" content="#25ae34">
<meta name="robots" content="noindex, nofollow">
<?php /* Демо-копия для портфолио: аналитика и коллтрекинг клиента отключены, заявки никуда не отправляются. */ ?>
<script>
  window.CookieConsentConfig = window.CookieConsentConfig || {};
  window.CookieConsentConfig.storageKey = 'demo_roof_cookie_consent';
  window.CookieConsentConfig.version = '1.0.0';
  window.CookieConsentConfig.yandexCounters = [];
</script>
<style>
  .demo-ribbon{position:relative;z-index:1000;display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:4px 14px;padding:8px 16px;background:#050915;color:#EEF2FF;font:500 13px/1.4 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;text-align:center}
  .demo-ribbon b{color:#C6F25E;font-weight:600}
  .demo-ribbon a{color:#C6F25E;text-decoration:underline;text-underline-offset:2px}
</style>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var bar = document.createElement('div');
    bar.className = 'demo-ribbon';
    var caseUrl = /pristroyki/.test(location.pathname) || document.querySelector('.extension-page') ? '/portfolio/pristroyki/' : '/portfolio/krovlya/';
    bar.innerHTML = '<span><b>Демо-копия</b> сайта из портфолио Андрея Стельмаха. Заявки отсюда не отправляются.</span><a href="' + caseUrl + '">Разбор проекта →</a>';
    document.body.insertBefore(bar, document.body.firstChild);
  });
</script>
