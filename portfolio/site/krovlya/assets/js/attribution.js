(function () {
  'use strict';

  var KEY = 'attribution_v1';
  var TTL = 90 * 86400 * 1000;
  var FIELDS = [
    'yclid',
    'gclid',
    'utm_source',
    'utm_medium',
    'utm_campaign',
    'utm_term',
    'utm_content',
  ];

  function readStored() {
    try {
      var raw = window.localStorage.getItem(KEY);
      var data = raw ? JSON.parse(raw) : null;

      if (data && data.ts && Date.now() - data.ts < TTL) {
        return data;
      }
    } catch (error) {
      return {};
    }

    return {};
  }

  function writeStored(data) {
    try {
      window.localStorage.setItem(KEY, JSON.stringify(data));
    } catch (error) {
      // localStorage can be unavailable in strict browser modes.
    }
  }

  function collectFromUrl() {
    var params = new URLSearchParams(window.location.search);
    var hit = {};

    FIELDS.forEach(function (field) {
      var value = params.get(field);

      if (value) {
        hit[field] = value;
      }
    });

    return hit;
  }

  function saveCurrentAttribution() {
    var hit = collectFromUrl();

    if (!Object.keys(hit).length) {
      return;
    }

    var stored = readStored();
    var freshClick = Boolean(hit.yclid || hit.gclid);
    var next = freshClick ? {} : Object.assign({}, stored);

    FIELDS.forEach(function (field) {
      if (hit[field]) {
        next[field] = hit[field];
      } else if (stored[field]) {
        next[field] = stored[field];
      }
    });

    next.ts = Date.now();
    writeStored(next);
  }

  function getAttribution() {
    var stored = readStored();
    var result = {};

    FIELDS.forEach(function (field) {
      if (stored[field]) {
        result[field] = stored[field];
      }
    });

    return result;
  }

  function appendToUrl(url, extraParams) {
    var target = new URL(url, window.location.href);
    var attribution = getAttribution();
    var currentParams = new URLSearchParams(window.location.search);

    FIELDS.forEach(function (field) {
      var currentValue = currentParams.get(field);
      var savedValue = attribution[field];

      if (!target.searchParams.has(field) && (currentValue || savedValue)) {
        target.searchParams.set(field, currentValue || savedValue);
      }
    });

    Object.keys(extraParams || {}).forEach(function (key) {
      var value = extraParams[key];

      if (value !== undefined && value !== null && value !== '') {
        target.searchParams.set(key, value);
      }
    });

    return target.pathname + target.search + target.hash;
  }

  saveCurrentAttribution();

  window.SiteAttribution = {
    get: getAttribution,
    appendToUrl: appendToUrl,
  };
}());
