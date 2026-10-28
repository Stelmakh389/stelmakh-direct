(function () {
  'use strict';

  var script = document.currentScript;
  var config = window.CookieConsentConfig || {};

  function parseJson(value, fallback) {
    if (!value) return fallback;

    try {
      return JSON.parse(value);
    } catch (error) {
      return fallback;
    }
  }

  if (script) {
    config.analyticsUrl =
      config.analyticsUrl ||
      script.getAttribute('data-analytics-url') ||
      '';

    config.yandexCounters =
      config.yandexCounters ||
      parseJson(script.getAttribute('data-yandex-counters'), []);

    config.storageKey =
      config.storageKey ||
      script.getAttribute('data-storage-key') ||
      'cookie_consent';

    config.version =
      config.version ||
      script.getAttribute('data-version') ||
      '1.0.0';
  }

  var storageKey = config.storageKey || 'cookie_consent';
  var consentVersion = config.version || '1.0.0';
  var analyticsLoaded = false;
  var yandexTagLoading = false;

  function createConsentStyles() {
    if (document.getElementById('cookie-consent-system-styles')) {
      return;
    }

    var style = document.createElement('style');

    style.id = 'cookie-consent-system-styles';
    style.textContent =
      '.cookie-consent[hidden] {' +
        'display: none !important;' +
      '}' +

      '.cookie-consent:not([hidden]) {' +
        'position: fixed !important;' +
        'right: 0 !important;' +
        'bottom: 0 !important;' +
        'left: 0 !important;' +
        'z-index: 2147483647 !important;' +
        'display: block !important;' +
        'box-sizing: border-box;' +
        'width: 100%;' +
        'height: auto;' +
        'padding: 16px;' +
        'background: transparent;' +
        'pointer-events: none;' +
      '}' +

      '.cookie-consent__panel {' +
        'position: relative;' +
        'z-index: 1;' +
        'width: 100%;' +
        'pointer-events: auto;' +
      '}';

    document.head.appendChild(style);
  }

  function readNoticeState() {
    try {
      return JSON.parse(window.localStorage.getItem(storageKey));
    } catch (error) {
      return null;
    }
  }

  function writeNoticeState() {
    var state = {
      version: consentVersion,
      acceptedAt: new Date().toISOString()
    };

    try {
      window.localStorage.setItem(storageKey, JSON.stringify(state));
    } catch (error) {
      // Уведомление продолжит работать даже при недоступном localStorage.
    }

    return state;
  }

  function noticeIsClosed(state) {
    return state && state.version === consentVersion;
  }

  function loadExternalScript(src) {
    if (!src) return;

    var externalScript = document.createElement('script');

    externalScript.src = src;
    externalScript.async = true;
    externalScript.dataset.cookieCategory = 'analytics';

    document.head.appendChild(externalScript);
  }

  function activateMarkedScripts() {
    var scripts = document.querySelectorAll(
      'script[data-cookie-script="analytics"]:not([data-cookie-activated])'
    );

    Array.prototype.forEach.call(scripts, function (originalScript) {
      var executableScript = document.createElement('script');
      var source =
        originalScript.getAttribute('data-src') ||
        originalScript.getAttribute('src');

      Array.prototype.forEach.call(originalScript.attributes, function (attribute) {
        if (
          attribute.name === 'type' ||
          attribute.name === 'src' ||
          attribute.name === 'data-src' ||
          attribute.name === 'data-cookie-script' ||
          attribute.name === 'data-cookie-activated'
        ) {
          return;
        }

        executableScript.setAttribute(attribute.name, attribute.value);
      });

      if (source) {
        executableScript.src = source;
      } else {
        executableScript.text = originalScript.textContent;
      }

      executableScript.async = originalScript.async;
      executableScript.dataset.cookieCategory = 'analytics';
      originalScript.dataset.cookieActivated = 'true';
      originalScript.parentNode.insertBefore(executableScript, originalScript.nextSibling);
    });
  }

  function loadYandexMetrika() {
    var counters = Array.isArray(config.yandexCounters)
      ? config.yandexCounters
      : [];

    if (!counters.length) return;

    window.ym = window.ym || function () {
      (window.ym.a = window.ym.a || []).push(arguments);
    };

    window.ym.l = window.ym.l || Number(new Date());

    counters.forEach(function (counter) {
      if (!counter || !counter.number) return;

      window.ym(counter.number, 'init', {
        ssr: Boolean(counter.ssr),
        webvisor: Boolean(counter.webvisor),
        clickmap: counter.clickmap !== false,
        ecommerce: counter.ecommerce || 'dataLayer',
        referrer: document.referrer,
        url: location.href,
        accurateTrackBounce: counter.accurateTrackBounce !== false,
        trackLinks: counter.trackLinks !== false
      });
    });

    if (!yandexTagLoading) {
      yandexTagLoading = true;

      loadExternalScript(
        'https://mc.yandex.ru/metrika/tag.js?id=' +
        counters.map(function (counter) {
          return counter.number;
        }).join(',')
      );
    }
  }

  function loadAnalytics() {
    if (analyticsLoaded) return;

    analyticsLoaded = true;
    activateMarkedScripts();
    loadExternalScript(config.analyticsUrl);
    loadYandexMetrika();
  }

  function createBanner() {
    if (document.querySelector('[data-cookie-consent]')) {
      return;
    }

    var banner = document.createElement('div');

    banner.className = 'cookie-consent';
    banner.setAttribute('data-cookie-consent', '');
    banner.hidden = true;
    banner.innerHTML =
      '<div class="cookie-consent__panel" role="region" aria-live="polite" aria-labelledby="cookie-consent-title" aria-describedby="cookie-consent-description">' +
        '<div class="cookie-consent__content">' +
          '<h2 class="cookie-consent__title" id="cookie-consent-title">Мы используем cookies</h2>' +
          '<p class="cookie-consent__text" id="cookie-consent-description">' +
            'На сайте используются cookies и сервисы аналитики для работы сайта, оценки посещаемости и улучшения сервиса. Продолжая пользоваться сайтом, вы соглашаетесь с использованием cookies.' +
          '</p>' +
          '<p class="cookie-consent__links">' +
            '<a href="politika-konfidencialnosti.php">Политика обработки персональных данных</a>' +
            '<a href="soglasie-na-obrabotku-personalnyh-dannyh.php">Согласие на обработку персональных данных</a>' +
            '<a href="polzovatelskoe-soglashenie.php">Пользовательское соглашение</a>' +
          '</p>' +
        '</div>' +
        '<div class="cookie-consent__actions">' +
          '<button class="cookie-consent__btn cookie-consent__btn--primary" type="button" data-cookie-accept>Понятно</button>' +
        '</div>' +
      '</div>';

    document.body.appendChild(banner);
  }

  function initBanner() {
    createConsentStyles();
    createBanner();

    var banner = document.querySelector('[data-cookie-consent]');
    var acceptButton = document.querySelector('[data-cookie-accept]');

    if (banner && !noticeIsClosed(readNoticeState())) {
      banner.hidden = false;
    }

    if (acceptButton) {
      acceptButton.addEventListener('click', function () {
        writeNoticeState();

        if (banner) {
          banner.hidden = true;
        }
      });
    }
  }

  window.acceptAnalyticsCookies = function () {
    loadAnalytics();
    writeNoticeState();

    var banner = document.querySelector('[data-cookie-consent]');

    if (banner) {
      banner.hidden = true;
    }
  };

  loadAnalytics();

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBanner);
  } else {
    initBanner();
  }
}());
