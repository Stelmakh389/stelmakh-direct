(function () {
  'use strict';

  var isThanksPage = /(^|\/)thanks(?:\.php)?$/i.test(window.location.pathname);
  var params = new URLSearchParams(window.location.search);
  var leadType = params.get('lead_type');

  if (!isThanksPage && !leadType) {
    return;
  }

  function getCounterNumber() {
    var counters = window.CookieConsentConfig && window.CookieConsentConfig.yandexCounters;
    var firstCounter = Array.isArray(counters) ? counters[0] : null;

    return firstCounter && firstCounter.number;
  }

  function loadAnalytics() {
    if (typeof window.acceptAnalyticsCookies === 'function') {
      window.acceptAnalyticsCookies();
    }
  }

  function waitForMetrika(callback, attemptsLeft) {
    var counter = getCounterNumber();

    if (counter && typeof window.ym === 'function') {
      callback(counter);
      return;
    }

    if (attemptsLeft <= 0) {
      return;
    }

    window.setTimeout(function () {
      waitForMetrika(callback, attemptsLeft - 1);
    }, 200);
  }

  function sendThanksHit(counter) {
    var goalSentKey = 'thanks_goal_sent:' + window.location.href;

    try {
      if (window.sessionStorage.getItem(goalSentKey)) {
        return;
      }

      window.sessionStorage.setItem(goalSentKey, '1');
    } catch (error) {
      // sessionStorage can be unavailable, the hit is still safe to send.
    }

    try {
      window.ym(counter, 'hit', window.location.href, {
        title: document.title,
        referer: document.referrer,
      });

      window.ym(counter, 'reachGoal', 'lead_thanks_visit', {
        lead_type: leadType || 'unknown',
        page: window.location.href,
      });
    } catch (error) {
      // Metrika failures must not affect the thank-you page.
    }
  }

  loadAnalytics();
  waitForMetrika(sendThanksHit, 30);
}());
