/*
 * Лендинг «Яндекс.Директ для бизнеса» – логика страницы.
 * Квиз-чат с прогнозом и записью на онлайн-разбор, подтверждение через Telegram или Max,
 * кейсы, запись из блока внизу, cookie, цели Метрики.
 * Без зависимостей.
 */
(function () {
  'use strict';

  var CFG = window.SITE_CONFIG || {};
  var D = window.SITE_DATA || {};

  /* ===================== Утилиты ===================== */
  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
  function el(tag, attrs, children) {
    var n = document.createElement(tag);
    if (attrs) Object.keys(attrs).forEach(function (k) {
      var v = attrs[k];
      if (k === 'class') n.className = v;
      else if (k === 'text') n.textContent = v;
      else if (k.slice(0, 2) === 'on') n.addEventListener(k.slice(2), v);
      else if (v !== false && v != null) n.setAttribute(k, v === true ? '' : v);
    });
    (children || []).forEach(function (c) { if (c != null) n.appendChild(typeof c === 'string' ? document.createTextNode(c) : c); });
    return n;
  }
  function icon(id) {
    var ns = 'http://www.w3.org/2000/svg';
    var s = document.createElementNS(ns, 'svg');
    s.setAttribute('aria-hidden', 'true');
    var u = document.createElementNS(ns, 'use');
    u.setAttribute('href', '#' + id);
    s.appendChild(u);
    return s;
  }
  function fmt(n) { return Math.round(n).toLocaleString('ru-RU'); }
  function rnd(n, step) { return Math.max(step, Math.round(n / step) * step); }
  function uid() { return (Date.now().toString(36) + Math.random().toString(36).slice(2, 8)).slice(0, 14); }
  function safe(fn, fallback) { try { return fn(); } catch (e) { return fallback; } }
  function sget(k) { return safe(function () { return sessionStorage.getItem(k); }, null); }
  function sset(k, v) { safe(function () { sessionStorage.setItem(k, v); }); }
  function lget(k) { return safe(function () { return localStorage.getItem(k); }, null); }
  function lset(k, v) { safe(function () { localStorage.setItem(k, v); }); }
  function scrollToEl(node) { if (node) node.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
  function plural(n, one, few, many) { var a = n % 10, b = n % 100; return a === 1 && b !== 11 ? one : a >= 2 && a <= 4 && (b < 10 || b >= 20) ? few : many; }
  function isMobile() { return safe(function () { return matchMedia('(max-width: 640px)').matches; }, false); }
  /* Запись на разбор – общая для квиза и формы внизу: записался в одном месте, другое это знает */
  function getBooked() { return safe(function () { return JSON.parse(sget('booked') || 'null'); }, null); }
  function setBooked(v) { sset('booked', v ? JSON.stringify(v) : ''); safe(function () { document.dispatchEvent(new CustomEvent('booked')); }); }

  /* ===================== Анимации: бегущие цифры и появление блоков ===================== */
  var motionOK = 'IntersectionObserver' in window && 'requestAnimationFrame' in window &&
    !safe(function () { return matchMedia('(prefers-reduced-motion: reduce)').matches; }, false);
  var NUM = /\d{1,3}(?:[\u00a0\u202f ]\d{3})+|\d+/g;
  /* Числа в тексте узла бегут от нуля до своего значения; приставки и единицы («+», «−», «%», «₽», «лет») остаются на месте */
  function countUp(node, dur) {
    if (!motionOK || !node) return;
    var target = node.getAttribute('data-final') || node.textContent;
    if (!/\d/.test(target)) return;
    node.setAttribute('data-final', target);
    if (node._raf) cancelAnimationFrame(node._raf);
    var t0 = null;
    dur = dur || 1200;
    function frame(ts) {
      if (t0 === null) t0 = ts;
      var k = Math.min(1, (ts - t0) / dur), e = 1 - Math.pow(1 - k, 3);
      node.textContent = k < 1 ? target.replace(NUM, function (m) {
        var v = Math.round(parseInt(m.replace(/\D/g, ''), 10) * e);
        return /\D/.test(m) ? v.toLocaleString('ru-RU') : String(v);
      }) : target;
      node._raf = k < 1 ? requestAnimationFrame(frame) : null;
    }
    node._raf = requestAnimationFrame(frame);
  }
  var revealer = motionOK ? new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      var t = en.target;
      /* Блок, мимо которого уже проскроллили (переход по якорю), просто показываем */
      if (!en.isIntersecting && en.boundingClientRect.bottom > 0) return;
      revealer.unobserve(t);
      t.classList.add('is-in');
      if (t.hasAttribute('data-count')) countUp(t);
      $$('[data-count]', t).forEach(function (n) { countUp(n); });
    });
  }, { rootMargin: '0px 0px -8% 0px', threshold: 0 }) : null;
  function reveal(nodes, step) {
    if (!revealer) return;
    nodes.forEach(function (n, i) {
      n.classList.add('rv');
      n.style.setProperty('--d', Math.min(i, 6) * (step || 80) + 'ms');
      revealer.observe(n);
    });
  }

  var MESSENGERS = {
    telegram: { name: 'Telegram', link: function (id) { return 'https://t.me/' + CFG.telegramBot + '?start=' + encodeURIComponent(id); } },
    max: { name: 'Max', link: function (id) { return 'https://max.ru/' + CFG.maxBot + '?start=' + encodeURIComponent(id); } }
  };

  $$('[data-tg-direct]').forEach(function (a) { a.href = 'https://t.me/' + (CFG.telegramUser || CFG.telegramBot); });
  $$('[data-max-direct]').forEach(function (a) { a.href = CFG.maxContactUrl || ('https://max.ru/' + CFG.maxBot); });
  var year = $('#year'); if (year) year.textContent = new Date().getFullYear();

  /* Один id на визит: по нему бот находит и квиз, и запись */
  var leadId = sget('leadId') || uid();
  sset('leadId', leadId);
  $$('[data-lead-code]').forEach(function (n) { n.textContent = leadId; });

  /* ===================== Рабочие дни и слоты ===================== */
  var WD = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
  var MON = ['янв', 'фев', 'мар', 'апр', 'мая', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];
  var SLOTS = CFG.bookingSlots || ['10:00', '14:00', '16:00'];
  var PAGE = CFG.bookingDays || 5;
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function isoOf(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
  function mkDay(iso, slots) {
    var p = iso.split('-'), d = new Date(+p[0], +p[1] - 1, +p[2], 12);
    return { wd: WD[d.getDay()], date: d.getDate() + ' ' + MON[d.getMonth()], iso: iso, slots: slots };
  }
  /* Пока не пришло расписание с сервера – рабочие дни на месяц вперёд, начиная с завтра */
  var DAYS = (function () {
    var out = [], d = new Date();
    d.setHours(12, 0, 0, 0);
    for (var i = 0; i < 30; i++) {
      d.setDate(d.getDate() + 1);
      var w = d.getDay();
      if (w !== 0 && w !== 6) out.push(mkDay(isoOf(d), SLOTS.slice()));
    }
    return out;
  })();
  function findDay(iso) { for (var i = 0; i < DAYS.length; i++) if (DAYS[i].iso === iso) return DAYS[i]; return null; }
  /* Свободное время с сервера: прошедшие дни, выходные и занятые слоты туда не попадают */
  var onAvail = [];
  if (CFG.slotsEndpoint !== '') safe(function () {
    fetch(CFG.slotsEndpoint || 'api/slots.php', { cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (j) {
      if (!j || !j.ok || !Array.isArray(j.days) || !j.days.length) return;
      DAYS = j.days.map(function (x) { return mkDay(x.date, x.slots); });
      onAvail.forEach(function (f) { safe(f); });
    }).catch(function () {});
  });
  function dayLabel(d) { return d.wd + ', ' + d.date; }

  /* ===================== UTM и метки рекламы ===================== */
  var MARKS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'yclid'];
  var marks = {};
  (function () {
    var q = new URLSearchParams(location.search);
    var saved = safe(function () { return JSON.parse(sget('marks') || '{}'); }, {});
    MARKS.forEach(function (k) { marks[k] = q.get(k) || saved[k] || ''; });
    sset('marks', JSON.stringify(marks));
  })();

  /* ===================== Метрика (только после согласия) ===================== */
  var metrikaLoaded = false;
  function loadMetrika() {
    if (metrikaLoaded || !CFG.metrikaId) return;
    metrikaLoaded = true;
    (function (m, e, t, r, i, k, a) {
      m[i] = m[i] || function () { (m[i].a = m[i].a || []).push(arguments); };
      m[i].l = 1 * new Date();
      k = e.createElement(t); a = e.getElementsByTagName(t)[0];
      k.async = 1; k.src = r; a.parentNode.insertBefore(k, a);
    })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js', 'ym');
    window.ym(CFG.metrikaId, 'init', { webvisor: true, clickmap: true, referrer: document.referrer, url: location.href, accurateTrackBounce: true, trackLinks: true });
  }
  function goal(name, params) {
    if (metrikaLoaded && window.ym) safe(function () { window.ym(CFG.metrikaId, 'reachGoal', name, params || {}); });
  }

  /* ===================== Cookie ===================== */
  var cookieBar = $('#cookie');
  function applyConsent(v) {
    lset('cookie-consent', v);
    if (cookieBar) cookieBar.hidden = true;
    if (v === 'all') loadMetrika();
  }
  var cookieChoice = lget('cookie-consent');
  if (cookieChoice === 'all') loadMetrika();
  else if (!cookieChoice && cookieBar) cookieBar.hidden = false;
  $$('[data-cookie]').forEach(function (b) { b.addEventListener('click', function () { applyConsent(b.getAttribute('data-cookie')); }); });
  var cookieSettings = $('#cookie-settings');
  if (cookieSettings) cookieSettings.addEventListener('click', function () { if (cookieBar) cookieBar.hidden = false; });

  /* ===================== Отправка заявок ===================== */
  function sendLead(extra) {
    var p = {
      leadId: leadId,
      page: location.href.split('#')[0],
      referrer: document.referrer || '',
      marks: marks,
      ts: new Date().toISOString()
    };
    Object.keys(extra || {}).forEach(function (k) { p[k] = extra[k]; });
    if (!CFG.leadEndpoint) return;
    var body = JSON.stringify(p);
    /* sendBeacon переживает переход в мессенджер; fetch – запасной вариант */
    var sent = navigator.sendBeacon && navigator.sendBeacon(CFG.leadEndpoint, new Blob([body], { type: 'application/json' }));
    if (!sent) safe(function () { fetch(CFG.leadEndpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body, keepalive: true }).catch(function () {}); });
  }

  function consentLabel(input) {
    return el('label', { class: 'consent' }, [input, el('span', {}, [
      'Даю ',
      el('a', { href: 'soglasie.html', target: '_blank', text: 'согласие на обработку персональных данных' }),
      '. Как мы их обрабатываем – в ',
      el('a', { href: 'politika.html', target: '_blank', text: 'Политике' })
    ])]);
  }

  /* ===================== Квиз-чат ===================== */
  var quiz = (function () {
    var log = $('#chat-log');
    var input = $('#chat-input');
    if (!log || !input) return null;

    var N = D.niches, R = D.regions, S = D.starts, SI = D.sites, C = D.checks, B = D.budgets;
    function optsOf(map) { return Object.keys(map).map(function (k) { return { k: k, l: map[k].label }; }); }

    var QUESTIONS = 6;
    var STEPS = [
      { q: 'Привет! Я Андрей. Задам несколько коротких вопросов и по ходу соберу прогноз: сколько стоит заявка в вашей нише и с какого бюджета есть смысл стартовать. С чего начнём?',
        kind: 'start',
        opts: function () { return [
          { k: 'seg:fresh', l: 'Ещё не запускал Директ', t: 'Посчитаем бюджет и цену заявки с нуля', i: 'i-spark' },
          { k: 'seg:burned', l: 'Уже сливал бюджет', t: 'Найдём, куда утекали деньги', i: 'i-down' },
          { k: 'seg:nosite', l: 'Нет сайта', t: 'Учтём посадочную страницу', i: 'i-site' },
          { k: 'go', l: 'Просто посчитать прогноз', t: '6 вопросов о вашем бизнесе', i: 'i-chart' }
        ]; },
        react: function (k) {
          if (k === 'who') return 'Андрей Стельмах, 10 лет в интернет-рекламе. Стратегию и настройку рекламы веду сам, команда помогает с аналитикой, сайтами и документами. Берём не больше 8 проектов одновременно. Ну что, поехали?';
          if (k.indexOf('seg:') === 0) return (D.segments || {})[k.slice(4)] || null;
          return null;
        } },
      { q: 'Чем занимается ваш бизнес?', opts: function () { return optsOf(N); }, react: function (k) { return N[k].react; } },
      { q: 'Где ищете клиентов?', opts: function () { return optsOf(R); }, react: function (k) { return R[k].react; } },
      /* Если на старте уже сказали «ещё не запускал» или «нет сайта» – второй раз не спрашиваем */
      { q: 'Запускали уже рекламу в Директе?', q2: 'Кто вёл рекламу?',
        opts: function () { var o = optsOf(S); return st.answers[0] && st.answers[0].k === 'seg:burned' ? o.filter(function (x) { return x.k !== 'fresh'; }) : o; },
        auto: function (a) { return a[0] === 'seg:fresh' ? { k: 'fresh', l: S.fresh.label } : null; },
        react: function (k) { return S[k].react; } },
      { q: 'Есть сайт, куда вести рекламу?', opts: function () { return optsOf(SI); },
        auto: function (a) { return a[0] === 'seg:nosite' ? { k: 'none', l: SI.none.label } : null; },
        react: function (k) { return SI[k].react; } },
      { q: 'Сколько в среднем приносит один клиент?', opts: function () { return optsOf(C); }, react: function () { return null; } },
      { q: 'Какой бюджет на рекламу в месяц комфортен?', opts: function () { return optsOf(B); },
        react: function (k, a) {
          var pre = '';
          if (k === 'b0') pre = 'Для старта предлагаю около 60 тыс. ₽ в месяц – этого хватит, чтобы кампании обучились. ';
          else if (k === 'b1' && (a[1] === 'b2b' || a[1] === 'prod' || a[1] === 'build')) pre = 'Скажу честно: при таком бюджете в вашей нише заявок будет немного – начинать стоит с самых горячих запросов на поиске. ';
          var f = forecast();
          if (!f.cpl) return pre || null;
          return pre + 'Прогноз готов: ' + f.leads + ' ' + plural(+f.leads.split('–').pop(), 'заявка', 'заявки', 'заявок') + ' в месяц по ' + f.cpl + ' при бюджете ' + f.budgetShort + '.' + (f.verdict ? ' ' + f.verdict.short : '');
        } },
      { q: 'Следующий шаг – бесплатный онлайн-разбор: 30 минут в Zoom или Телемосте. Разберу вашу нишу, конкурентов и рекламу и покажу точные цифры. Выберите удобный день:',
        kind: 'day', opts: function () { return DAYS.slice(st.dayPage * PAGE, st.dayPage * PAGE + PAGE).map(function (d) { return { k: d.iso, l: dayLabel(d), d: d }; }); }, react: function () { return null; } },
      { q: 'Во сколько удобно? Время московское.', opts: function () { var d = st.answers[7] && findDay(st.answers[7].k); return (d ? d.slots : SLOTS).map(function (t) { return { k: t, l: t }; }); }, react: function () { return null; } }
    ];

    var st = { answers: [], typing: false, confirmed: null, consent: false, dayPage: 0, showAll: false, acted: false };
    var saved = safe(function () { return JSON.parse(sget('quiz') || 'null'); }, null);
    if (saved && Array.isArray(saved.answers) && saved.answers.length <= STEPS.length) { st.answers = saved.answers; st.confirmed = saved.confirmed || null; }
    /* Выбранное раньше время прошло или его заняли – просим выбрать заново */
    function dropStaleSlot() {
      if (st.answers.length < 8 || st.confirmed) return false;
      var d = findDay(st.answers[7].k);
      if (d && (st.answers.length < 9 || d.slots.indexOf(st.answers[8].k) >= 0)) return false;
      st.answers = st.answers.slice(0, 7); st.dayPage = 0; save();
      return true;
    }
    if (st.answers.length >= 8 && !st.confirmed && st.answers[7].k <= isoOf(new Date())) st.answers = st.answers.slice(0, 7);
    onAvail.push(function () { dropStaleSlot(); update(); });

    var rendered = [], typingNode = null, timer = null;

    function amap() { var a = {}; st.answers.forEach(function (x, i) { a[i] = x.k; }); return a; }
    function done() { return st.answers.length >= STEPS.length; }
    function save() { sset('quiz', JSON.stringify({ answers: st.answers, confirmed: st.confirmed })); }
    function slot() {
      if (st.answers.length < 9) return null;
      var day = st.answers[7], time = st.answers[8];
      return { date: day.k, time: time.k, label: day.l + ' в ' + time.l, tz: 'Europe/Moscow' };
    }

    function qOf(i) { return STEPS[i].q2 && st.answers[0] && st.answers[0].k === 'seg:burned' ? STEPS[i].q2 : STEPS[i].q; }
    /* Записался через форму внизу, а квиз не подтверждал */
    function bookedElsewhere() { var b = getBooked(); return !!(b && b.source === 'form' && !st.confirmed); }
    function messages() {
      var a = amap(), out = [];
      st.answers.forEach(function (x, i) {
        if (x.auto) return;
        out.push({ key: 'q' + i, me: false, text: qOf(i) });
        out.push({ key: 'a' + i + ':' + x.k, me: true, text: x.l });
        var r = STEPS[i].react(x.k, a);
        if (r) out.push({ key: 'r' + i + ':' + x.k, me: false, text: r });
      });
      if (!st.typing) {
        if (!done()) { if (!(st.answers.length === 7 && bookedElsewhere())) out.push({ key: 'q' + st.answers.length, me: false, text: qOf(st.answers.length) }); }
        else {
          out.push({ key: 'booked:' + slot().label, me: false, text: 'Записал вас на ' + slot().label + '. Остался один шаг – подтвердите запись в мессенджере. Туда же пришлю ссылку на созвон и напомню накануне и перед началом.' });
          if (st.confirmed) {
            var m = MESSENGERS[st.confirmed];
            out.push({ key: 'c-me:' + st.confirmed, me: true, text: 'Подтвердить в ' + m.name });
            out.push({ key: 'c-bot:' + st.confirmed, me: false, text: 'Открыл ' + m.name + '. Нажмите «Старт» в боте и подтвердите время. Если бот промолчит – отправьте ему код ' + leadId + '. До встречи!' });
          }
        }
      }
      return out;
    }

    function renderLog() {
      var list = messages(), i = 0;
      while (i < rendered.length && i < list.length && rendered[i].key === list[i].key) i++;
      for (var j = rendered.length - 1; j >= i; j--) rendered[j].node.remove();
      rendered = rendered.slice(0, i);
      if (typingNode) { typingNode.remove(); typingNode = null; }
      for (; i < list.length; i++) {
        var node = el('div', { class: 'msg ' + (list[i].me ? 'msg--me' : 'msg--bot'), text: list[i].text });
        log.appendChild(node);
        rendered.push({ key: list[i].key, node: node, me: list[i].me });
      }
      if (st.typing) {
        typingNode = el('div', { class: 'msg msg--bot msg--typing', 'aria-label': 'Андрей печатает' }, [el('i'), el('i'), el('i')]);
        log.appendChild(typingNode);
      }
      /* Текущий вопрос подсвечен, прошлые – приглушены */
      rendered.forEach(function (r, j) { r.node.classList.remove('msg--current'); });
      if (!st.typing && !done()) for (var c = rendered.length - 1; c >= 0; c--) { if (!rendered[c].me) { rendered[c].node.classList.add('msg--current'); break; } }
      /* На телефоне лента не прокручивается внутри: показываем последние 4 сообщения, остальное – по кнопке */
      var hide = isMobile() && !st.showAll ? Math.max(0, rendered.length - 4) : 0;
      rendered.forEach(function (r, j) { r.node.classList.toggle('is-old', j < hide); });
      var more = $('.chat__more', log);
      if (hide && !more) { more = el('button', { class: 'chat__more textbtn', type: 'button', text: 'Показать всю переписку', onclick: function () { st.showAll = true; renderLog(); } }); log.insertBefore(more, log.firstChild); }
      else if (!hide && more) more.remove();
      log.scrollTop = log.scrollHeight;
    }

    function messengerButton(key, primary, err, agree) {
      var m = MESSENGERS[key];
      return el('a', {
        class: 'btn ' + (primary ? 'btn--accent' : 'btn--line'), href: m.link(leadId), target: '_blank', rel: 'noopener',
        onclick: function (e) {
          if (!agree.checked) {
            e.preventDefault();
            err.textContent = 'Отметьте согласие на обработку данных – и откроем ' + m.name + '.';
            err.hidden = false; agree.focus();
            return;
          }
          sendLead({ type: 'quiz_booking', messenger: key, consent: true, slot: slot(), quiz: summary() });
          goal('booking_confirm', { messenger: key, source: 'quiz' });
          st.confirmed = key; save();
          setBooked({ source: 'quiz', messenger: key, label: slot().label, date: slot().date, time: slot().time });
          setTimeout(update, 50);
        }
      }, [icon(key === 'telegram' ? 'i-plane' : 'i-bubble'), 'Подтвердить в ' + m.name]);
    }

    function renderInput() {
      input.textContent = '';
      if (st.typing) return;
      var tools = el('div', { class: 'chat__tools' });
      var n = st.answers.length;
      if (!done() && n === 7 && bookedElsewhere()) {
        var b = getBooked(), bm = MESSENGERS[b.messenger] || MESSENGERS.telegram;
        input.appendChild(el('div', { class: 'confirm' }, [
          el('p', { class: 'confirm__booked' }, ['Вы уже записаны на ', el('b', { text: b.label }), ' – подтверждение ждёт в ' + bm.name + '.']),
          el('div', { class: 'confirm__row' }, [
            el('a', { class: 'btn btn--accent', href: bm.link(leadId), target: '_blank', rel: 'noopener' }, [icon(b.messenger === 'max' ? 'i-bubble' : 'i-plane'), 'Открыть ' + bm.name]),
            el('a', { class: 'btn btn--line', href: '#call', onclick: function () { safe(function () { document.dispatchEvent(new CustomEvent('booking:again')); }); } }, ['Перенести на другое время'])
          ])
        ]));
      } else if (!done()) {
        var step = STEPS[n];
        var box = el('div', { class: 'opts' });
        step.opts().forEach(function (o, i) {
          var b;
          if (step.kind === 'day') {
            b = el('button', { class: 'opt opt--day', type: 'button', 'aria-label': o.l, onclick: function () { answer(o.k, o.l); } }, [el('span', { text: o.d.wd }), el('small', { text: o.d.date })]);
            if (i === 0 && st.dayPage > 0) box.appendChild(el('button', { class: 'opt opt--more', type: 'button', text: '← Раньше', onclick: function () { st.dayPage--; renderInput(); } }));
          } else {
            b = el('button', { class: 'opt', type: 'button', style: step.kind === 'start' ? '--d:' + (i * 60) + 'ms' : null, text: o.l, onclick: function () { answer(o.k, o.l); } });
            if (step.kind === 'start') b.classList.add('opt--rise');
          }
          box.appendChild(b);
        });
        if (step.kind === 'start') box.appendChild(el('button', { class: 'opt opt--rise opt--soft', type: 'button', style: '--d:240ms', text: 'Сначала – кто вы?', onclick: function () { answer('who', 'Сначала – кто вы?'); } }));
        if (step.kind === 'day' && (st.dayPage + 1) * PAGE < DAYS.length) box.appendChild(el('button', { class: 'opt opt--more', type: 'button', text: 'Позже →', onclick: function () { st.dayPage++; renderInput(); } }));
        var pf = forecast();
        if (isMobile() && pf.cpc && !pf.cpl) input.appendChild(el('p', { class: 'chat__peek' }, ['Цена клика в нише: ', el('b', { text: pf.cpc }), ' · цена заявки – после вопроса о бюджете']));
        input.appendChild(el('p', { class: 'opts__hint', text: step.kind === 'day' ? 'Выберите день' : (n === 8 ? 'Выберите время' : 'Выберите ответ') }));
        input.appendChild(box);
        if (n === 8) tools.appendChild(el('button', { class: 'textbtn', type: 'button', text: '← Другой день', onclick: function () { back(1); } }));
        else if (n > 0) tools.appendChild(el('button', { class: 'textbtn', type: 'button', text: '← Назад', onclick: function () { back(1); } }));
      } else if (!st.confirmed) {
        var err = el('p', { class: 'form-error', hidden: true });
        var agree = el('input', { type: 'checkbox', id: 'q-consent', onchange: function () { st.consent = agree.checked; if (agree.checked) err.hidden = true; } });
        agree.checked = st.consent;
        input.appendChild(el('div', { class: 'confirm' }, [
          consentLabel(agree),
          err,
          el('div', { class: 'confirm__row' }, [messengerButton('telegram', true, err, agree), messengerButton('max', false, err, agree)]),
          el('p', { class: 'confirm__hint', text: 'Бот пришлёт подтверждение и напомнит накануне, за час и за 15 минут. Звонить не буду.' })
        ]));
        tools.appendChild(el('button', { class: 'textbtn', type: 'button', text: 'Перенести на другое время', onclick: function () { back(2); } }));
      } else {
        var m = MESSENGERS[st.confirmed], other = st.confirmed === 'telegram' ? 'max' : 'telegram';
        input.appendChild(el('div', { class: 'confirm__row' }, [
          el('a', { class: 'btn btn--accent', href: m.link(leadId), target: '_blank', rel: 'noopener' }, [icon(st.confirmed === 'telegram' ? 'i-plane' : 'i-bubble'), 'Открыть ' + m.name + ' ещё раз']),
          el('a', { class: 'btn btn--line', href: MESSENGERS[other].link(leadId), target: '_blank', rel: 'noopener',
            onclick: function () { sendLead({ type: 'quiz_booking', messenger: other, consent: true, slot: slot(), quiz: summary() }); st.confirmed = other; save(); setBooked({ source: 'quiz', messenger: other, label: slot().label, date: slot().date, time: slot().time }); setTimeout(update, 50); } },
            [icon(other === 'telegram' ? 'i-plane' : 'i-bubble'), 'Лучше в ' + MESSENGERS[other].name])
        ]));
        tools.appendChild(el('button', { class: 'textbtn', type: 'button', text: 'Перенести на другое время', onclick: function () { st.confirmed = null; setBooked(null); back(2); } }));
      }
      if (st.answers.length) tools.appendChild(el('button', { class: 'textbtn textbtn--quiet', type: 'button', text: 'Начать заново', onclick: function () { if (safe(function () { return confirm('Начать заново? Ответы сотрутся.'); }, true)) restart(); } }));
      if (tools.childNodes.length) input.appendChild(tools);
    }

    function answer(k, l) {
      if (st.typing || done()) return;
      st.answers.push({ k: k, l: l });
      autofill();
      st.typing = true; st.acted = true;
      var n = st.answers.length;
      if (n === 1) goal('quiz_start');
      if (n === 4) goal('quiz_step3');
      if (n === QUESTIONS + 1) goal('quiz_finish');
      if (n === STEPS.length) goal('quiz_slot');
      save(); update();
      clearTimeout(timer);
      timer = setTimeout(function () { st.typing = false; update(); }, 700);
    }
    function autofill() {
      while (!done()) { var nx = STEPS[st.answers.length], au = nx.auto && nx.auto(amap()); if (!au) break; st.answers.push({ k: au.k, l: au.l, auto: true }); }
    }
    /* Шаг назад пропускает ответы, которые квиз подставил сам */
    function back(n) {
      clearTimeout(timer); st.typing = false; st.dayPage = 0;
      for (var i = 0; i < n && st.answers.length; i++) {
        while (st.answers.length && st.answers[st.answers.length - 1].auto) st.answers.pop();
        st.answers.pop();
        while (st.answers.length && st.answers[st.answers.length - 1].auto) st.answers.pop();
      }
      save(); update();
    }
    function restart() { clearTimeout(timer); st.answers = []; st.typing = false; st.confirmed = null; st.showAll = false; save(); if (getBooked() && getBooked().source === 'quiz') setBooked(null); update(); }
    function preset(list) { clearTimeout(timer); st.answers = list; st.typing = false; st.confirmed = null; save(); update(); }
    function changeBudget() { if (st.confirmed || st.answers.length < 7) return; clearTimeout(timer); st.typing = false; st.dayPage = 0; st.answers = st.answers.slice(0, 6); save(); update(); goal('quiz_budget_change'); }

    /* ----- Прогноз ----- */
    function forecast() {
      var a = amap();
      var n = N[a[1]], r = R[a[2]], si = SI[a[4]], c = C[a[5]], b = B[a[6]];
      var mult = r ? r.mult : 1;
      var out = {
        niche: n && n.label,
        region: r && (r.label + ' · конкуренция ' + r.comp),
        cpc: n ? fmt(n.cpc[0] * mult) + '–' + fmt(n.cpc[1] * mult) + ' ₽' : null,
        start: S[a[3]] && S[a[3]].plan,
        site: si && si.plan,
        allowed: c && c.allowed,
        budget: b && (a[6] === 'b0' ? 'около 60 тыс. ₽ – подобрали' : b.label),
        budgetShort: b && (a[6] === 'b0' ? '≈ 60 тыс. ₽' : b.label),
        slot: slot() ? slot().label + ' (МСК)' : null,
        clicks: null, leads: null, cpl: null, verdict: null
      };
      if (n && b) {
        var budget = b.v;
        var cpc = (n.cpc[0] + n.cpc[1]) / 2 * mult;
        var clicks = budget / cpc;
        var cr = (n.cr[0] + n.cr[1]) / 2 * (si ? si.f : 1);
        var leads = clicks * cr;
        var lo = Math.max(1, Math.round(leads * 0.75)), hi = Math.max(lo + 1, Math.round(leads * 1.25));
        out.clicks = fmt(rnd(clicks * 0.85, 10)) + '–' + fmt(rnd(clicks * 1.15, 10));
        out.leads = lo + '–' + hi;
        out.cpl = fmt(rnd(budget / hi, 50)) + '–' + fmt(rnd(budget / lo, 50)) + ' ₽';
        if (c) {
          var mid = budget / leads;
          var lim = 'Допустимая цена заявки для вас – ' + c.allowed + ', прогноз – ' + out.cpl + '. ';
          if (mid <= c.max) out.verdict = { level: 'ok', text: lim + 'Укладывается – реклама окупится.', short: 'Это укладывается в допустимую цену заявки.' };
          else if (mid <= c.max * 1.4) out.verdict = { level: 'warn', text: lim + 'На грани окупаемости – начнём с самых горячих запросов на поиске.', short: 'Это на грани окупаемости – начнём с самых горячих запросов.' };
          else out.verdict = { level: 'bad', text: lim + 'Дороже допустимого – нужен другой подход, разберём его на онлайн-разборе.', short: 'Это дороже, чем для вас допустимо, – на разборе покажу, как снизить цену.' };
        }
      }
      return out;
    }
    function summary() {
      var f = forecast();
      return {
        answers: st.answers.slice(1, QUESTIONS + 1).map(function (x, i) { return { q: qOf(i + 1), a: x.l, key: x.k }; }),
        segment: (st.answers[0] && st.answers[0].k.indexOf('seg:') === 0) ? st.answers[0].k.slice(4) : '',
        forecast: { cpc: f.cpc, clicks: f.clicks, leads: f.leads, cpl: f.cpl, verdict: f.verdict && f.verdict.text }
      };
    }
    function progress() {
      var total = 0, answered = 0;
      for (var i = 1; i <= QUESTIONS; i++) {
        var x = st.answers[i];
        if (x && x.auto) continue;
        total++;
        if (x) answered++;
      }
      return { total: total, answered: answered, left: total - answered };
    }
    function renderPlan() {
      var f = forecast();
      $$('[data-plan]').forEach(function (node) {
        var v = f[node.getAttribute('data-plan')];
        node.removeAttribute('data-final');
        node.textContent = v || '–';
        node.classList.toggle('is-set', !!v);
        if (v && v !== node._v) countUp(node, 700);
        node._v = v;
      });
      var ctx = $('#plan-context');
      if (ctx) {
        var parts = [f.niche, f.region, f.cpc && 'клик ' + f.cpc].filter(Boolean);
        ctx.hidden = !parts.length; ctx.textContent = parts.join(' · ');
      }
      var wait = $('#plan-wait'); if (wait) wait.hidden = !!f.cpl;
      var verdict = $('#plan-verdict');
      if (verdict) {
        verdict.hidden = !f.verdict;
        verdict.className = 'plan__verdict' + (f.verdict ? ' plan__verdict--' + f.verdict.level : '');
        if (f.verdict) verdict.querySelector('span').textContent = f.verdict.text;
      }
      var bb = $('#plan-budget'); if (bb) bb.disabled = !(st.answers.length >= 7 && !st.confirmed);
      var n = st.answers.length, pr = progress();
      var label = n === 0 ? 'Прогноз' : pr.left > 0 ? 'Осталось ' + pr.left + ' ' + plural(pr.left, 'вопрос', 'вопроса', 'вопросов') : (st.confirmed ? 'Запись подтверждается' : (done() ? 'Подтвердите запись' : 'Прогноз готов'));
      var p = $('#chat-progress'); if (p) p.textContent = label;
      var pl = $('#plan-left'); if (pl) pl.textContent = pr.left > 0 ? label.toLowerCase() : 'предварительный';
      var bar = $('#chat-bar'); if (bar) bar.style.width = (n === 0 ? 0 : Math.round(pr.answered / Math.max(1, pr.total) * 100)) + '%';
      var status = $('#chat-status');
      if (status) status.textContent = st.typing ? 'печатает…' : 'на связи';
      if (row) row.classList.toggle('is-ready', !!f.cpl);
      var bf = $('#booking-forecast');
      if (bf) { bf.hidden = !f.cpl; if (f.cpl) bf.textContent = 'Ваш прогноз из квиза: ' + f.leads + ' заявок в месяц по ' + f.cpl + '. На разборе покажу, как сделать дешевле.'; }
    }

    var row = log.closest('.quiz__row');
    function update() {
      if (row) row.classList.toggle('is-idle', st.answers.length === 0);
      renderLog(); renderInput(); renderPlan();
      /* На телефоне после ответа докручиваем страницу, чтобы были видны новый вопрос и кнопки */
      if (st.acted && !st.typing && isMobile()) safe(function () {
        requestAnimationFrame(function () {
          var r = input.getBoundingClientRect();
          if (r.bottom > innerHeight - 8 || r.top < 0) window.scrollBy({ top: r.bottom - innerHeight + 16, behavior: 'smooth' });
        });
      });
    }
    var bbtn = $('#plan-budget'); if (bbtn) bbtn.addEventListener('click', changeBudget);
    document.addEventListener('booked', function () { update(); });
    update();

    return {
      start: function (k) {
        if (st.answers.length) return;
        if (st.typing) return;
        var o = STEPS[0].opts().filter(function (x) { return x.k === (k || 'go'); })[0] || STEPS[0].opts()[0];
        answer(o.k, o.l);
      },
      segment: function (key, label) { preset([]); answer('seg:' + key, label); },
      summary: summary,
      hasAnswers: function () { return st.answers.length > 1; }
    };
  })();

  /* Кнопки первого экрана, которые запускают квиз */
  var quizSection = $('#quiz');
  $$('[data-segment]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      if (!quiz) return;
      e.preventDefault();
      var title = a.querySelector('.segment__title');
      quiz.segment(a.getAttribute('data-segment'), title ? title.textContent : '');
      scrollToEl(quizSection);
    });
  });
  $$('[data-quiz-start]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      if (!quiz) return;
      e.preventDefault(); quiz.start('go');
      /* Ждём, пока появится медиаплан, и ставим квиз ровно в экран */
      setTimeout(function () { var r = $('.quiz__row') || quizSection; window.scrollTo({ top: r.getBoundingClientRect().top + window.pageYOffset - 16, behavior: 'smooth' }); }, 60);
    });
  });

  /* ===================== Кейсы ===================== */
  (function () {
    var tabs = $('#case-tabs'), panels = $('#case-panels');
    var list = D.cases || [];
    if (!tabs || !panels || !list.length) return;
    var current = 0;
    var buttons = list.map(function (c, i) {
      var b = el('button', { class: 'tab', type: 'button', role: 'tab', id: 'tab-' + c.id, 'aria-controls': 'case-panel', text: c.tab, onclick: function () { select(i, false); } });
      tabs.appendChild(b);
      return b;
    });
    tabs.addEventListener('keydown', function (e) {
      var d = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
      if (e.key === 'Home') { e.preventDefault(); select(0, true); }
      else if (e.key === 'End') { e.preventDefault(); select(list.length - 1, true); }
      else if (d) { e.preventDefault(); select((current + d + list.length) % list.length, true); }
    });
    function select(i, focus) {
      current = i;
      buttons.forEach(function (b, j) { b.setAttribute('aria-selected', j === i ? 'true' : 'false'); b.tabIndex = j === i ? 0 : -1; });
      if (focus) buttons[i].focus();
      var b = buttons[i];
      if (b.offsetLeft < tabs.scrollLeft) tabs.scrollLeft = b.offsetLeft - 8;
      else if (b.offsetLeft + b.offsetWidth > tabs.scrollLeft + tabs.clientWidth) tabs.scrollLeft = b.offsetLeft + b.offsetWidth - tabs.clientWidth + 8;
      render(list[i]);
    }
    function render(c) {
      var max = Math.max.apply(null, c.bars);
      var bars = c.bars.map(function (v, i) {
        var bar = el('i'); bar.style.height = (Math.round(v / max * 112) + 6) + 'px';
        return el('div', {}, [el('b', { text: String(v) }), bar, el('small', { text: String(i + 1) })]);
      });
      /* Порядок чтения: что получили → с чего начинали → что сделали → динамика */
      var card = el('article', { class: 'case', id: 'case-panel', role: 'tabpanel', 'aria-labelledby': 'tab-' + c.id, tabindex: '0' }, [
        el('h3', { text: c.title }),
        el('div', { class: 'results' }, c.results.map(function (r) { return el('div', {}, [el('b', { text: r[0] }), el('span', { text: r[1] })]); })),
        el('div', { class: 'case__body' }, [
          el('div', { class: 'case__main' }, [
            el('div', { class: 'case__block case__block--before' }, [el('p', { class: 'case__label', text: 'Было' }), el('p', { class: 'case__before', text: c.before })]),
            el('div', { class: 'case__block case__block--did' }, [el('p', { class: 'case__label', text: 'Что сделали' }),
              el('ul', { class: 'case__did' }, c.did.map(function (d) { return el('li', {}, [el('span', {}, [icon('i-check')]), el('span', { text: d })]); }))])
          ]),
          el('div', { class: 'case__side' }, [
            el('div', { class: 'chart' }, [
              el('div', { class: 'chart__head' }, [el('b', { text: c.chart }), el('small', { text: 'по месяцам работы' })]),
              el('div', { class: 'chart__bars', role: 'img', 'aria-label': c.chart + ' по месяцам: ' + c.bars.join(', ') }, bars),
              el('p', { class: 'chart__note', text: 'Скриншот отчёта Метрики с замазанными данными клиента' })
            ])
          ])
        ])
      ]);
      panels.textContent = '';
      panels.appendChild(card);
      $$('.results b', card).forEach(function (b) { b.setAttribute('data-count', ''); if (panels.classList.contains('is-in')) countUp(b); });
    }
    select(0, false);
    /* Вкладки не влезают – у края мягкое затухание, видно, что дальше есть ещё */
    function fade() {
      var more = tabs.scrollWidth > tabs.clientWidth + 2;
      tabs.classList.toggle('is-more-right', more && tabs.scrollLeft + tabs.clientWidth < tabs.scrollWidth - 4);
      tabs.classList.toggle('is-more-left', more && tabs.scrollLeft > 4);
    }
    tabs.addEventListener('scroll', fade, { passive: true });
    window.addEventListener('resize', fade);
    fade();
  })();

  /* ===================== Запись на онлайн-разбор (блок внизу) ===================== */
  (function () {
    var form = $('#booking-form');
    if (!form) return;
    var daysBox = $('#booking-days'), slotsBox = $('#booking-slots');
    var err = $('#booking-error');
    var agree = $('#b-consent'), site = $('#b-site');
    var doneBox = $('#booking-done'), reopen = $('#booking-reopen');
    var confirmBtns = $$('[data-confirm]', form);
    var steps = $$('.bstep', form), stepBtns = $$('[data-goto]', form);
    var stepNames = stepBtns.map(function (b) { return b.lastChild.textContent; });
    /* Три шага: дата → время → подтверждение. Следующий шаг открывается после выбора */
    var sel = { day: null, slot: null }, view = 1, page = 0;
    var nav = el('div', { class: 'days-nav' });
    daysBox.parentNode.insertBefore(nav, daysBox.nextSibling);

    function renderDays() {
      daysBox.textContent = ''; nav.textContent = '';
      DAYS.slice(page * PAGE, page * PAGE + PAGE).forEach(function (x) {
        daysBox.appendChild(el('button', { class: 'day', type: 'button', 'aria-pressed': sel.day && sel.day.iso === x.iso ? 'true' : 'false', 'aria-label': dayLabel(x),
          onclick: function () { sel.day = x; sel.slot = null; view = 2; renderAll(); paint(true); if (isMobile()) safe(function () { slotsHead.scrollIntoView({ behavior: 'smooth', block: 'center' }); }); } }, [el('span', { text: x.wd }), el('b', { text: x.date })]));
      });
      if (page > 0) nav.appendChild(el('button', { class: 'textbtn', type: 'button', text: '← Раньше', onclick: function () { page--; renderDays(); } }));
      if ((page + 1) * PAGE < DAYS.length) nav.appendChild(el('button', { class: 'textbtn days-nav__next', type: 'button', text: 'Другие даты →', onclick: function () { page++; renderDays(); } }));
    }
    var slotsHead = el('div', { class: 'slots__head' });
    slotsBox.parentNode.insertBefore(slotsHead, slotsBox);
    function renderSlots() {
      slotsBox.textContent = ''; slotsHead.textContent = '';
      if (!sel.day) return;
      slotsHead.appendChild(el('p', {}, [el('b', { text: dayLabel(sel.day) }), ' – выберите время']));
      slotsHead.appendChild(el('button', { class: 'textbtn', type: 'button', text: '← другой день', onclick: function () { view = 1; paint(true); } }));
      sel.day.slots.forEach(function (t) {
        slotsBox.appendChild(el('button', { class: 'slot', type: 'button', 'aria-pressed': t === sel.slot ? 'true' : 'false', text: t,
          onclick: function () { sel.slot = t; view = 3; renderSlots(); paint(true); } }));
      });
    }
    function renderAll() { renderDays(); renderSlots(); }
    renderAll();
    onAvail.push(function () {
      /* Пришло свежее расписание: если выбранное время заняли – возвращаем к выбору */
      if (sel.day) {
        var d = findDay(sel.day.iso);
        if (!d) { sel.day = null; sel.slot = null; view = 1; }
        else { sel.day = d; if (sel.slot && d.slots.indexOf(sel.slot) < 0) { sel.slot = null; if (view > 2) view = 2; } }
      }
      page = 0; renderAll(); paint(false);
    });
    stepBtns.forEach(function (b) {
      b.addEventListener('click', function () {
        var n = +b.getAttribute('data-goto');
        if (n <= reachable()) { view = n; paint(true); }
      });
    });
    function reachable() { return sel.day === null ? 1 : sel.slot === null ? 2 : 3; }
    function when() { return sel.slot === null ? '' : dayLabel(sel.day) + ' в ' + sel.slot; }
    function paint(moved) {
      steps.forEach(function (node) { node.hidden = +node.getAttribute('data-step') !== view; });
      var max = reachable();
      stepBtns.forEach(function (b, i) {
        var n = i + 1;
        var li = b.parentNode;
        li.classList.toggle('is-active', n === view);
        li.classList.toggle('is-done', n < max || (n === 3 && view === 3));
        b.disabled = n > max;
        if (n === view) b.setAttribute('aria-current', 'step'); else b.removeAttribute('aria-current');
        var value = n === 1 && sel.day !== null ? dayLabel(sel.day) : n === 2 && sel.slot !== null ? sel.slot : stepNames[i];
        b.lastChild.textContent = value;
      });
      var ready = sel.slot !== null && agree.checked;
      confirmBtns.forEach(function (a) {
        a.href = ready ? MESSENGERS[a.getAttribute('data-confirm')].link(leadId) : '#';
        a.setAttribute('aria-disabled', ready ? 'false' : 'true');
      });
      if (ready) err.hidden = true;
      if (moved) { var first = $('.bstep:not([hidden]) button, .bstep:not([hidden]) input', form); if (first && view < 3) first.focus({ preventScroll: true }); }
    }
    agree.addEventListener('change', function () { paint(false); });
    paint(false);

    confirmBtns.forEach(function (a) {
      a.addEventListener('click', function (e) {
        var key = a.getAttribute('data-confirm');
        var problems = [];
        if (sel.slot === null) problems.push('выберите время');
        if (!agree.checked) problems.push('отметьте согласие на обработку данных');
        if (problems.length) {
          e.preventDefault();
          err.textContent = 'Чтобы записаться, ' + problems.join(' и ') + '.';
          err.hidden = false;
          return;
        }
        sendLead({
          type: 'booking', messenger: key, consent: true, site: site.value.trim(),
          slot: { date: sel.day.iso, time: sel.slot, label: when(), tz: 'Europe/Moscow' },
          quiz: quiz && quiz.hasAnswers() ? quiz.summary() : null
        });
        goal('booking_confirm', { messenger: key, source: 'form' });
        setBooked({ source: 'form', messenger: key, label: when(), date: sel.day.iso, time: sel.slot });
        setTimeout(function () { showDone(getBooked()); doneBox.focus(); }, 50);
      });
    });
    var switchBtn = $('#booking-switch');
    function showDone(b) {
      if (!b) return;
      var other = b.messenger === 'telegram' ? 'max' : 'telegram';
      $('#booking-when').textContent = b.label + ' (МСК)';
      $('#booking-messenger').textContent = MESSENGERS[b.messenger].name;
      reopen.href = MESSENGERS[b.messenger].link(leadId);
      reopen.textContent = 'Открыть ' + MESSENGERS[b.messenger].name + ' ещё раз';
      if (switchBtn) { switchBtn.href = MESSENGERS[other].link(leadId); switchBtn.textContent = 'Лучше в ' + MESSENGERS[other].name; }
      form.hidden = true; doneBox.hidden = false;
    }
    if (switchBtn) switchBtn.addEventListener('click', function () {
      var b = getBooked(); if (!b) return;
      var other = b.messenger === 'telegram' ? 'max' : 'telegram';
      sendLead({ type: b.source === 'quiz' ? 'quiz_booking' : 'booking', messenger: other, consent: true, site: site.value.trim(),
        slot: { date: b.date, time: b.time, label: b.label, tz: 'Europe/Moscow' }, quiz: quiz && quiz.hasAnswers() ? quiz.summary() : null });
      b.messenger = other; setBooked(b);
    });
    function again() { setBooked(null); form.hidden = false; doneBox.hidden = true; sel.slot = null; view = sel.day ? 2 : 1; renderSlots(); paint(true); }
    $('#booking-again').addEventListener('click', again);
    document.addEventListener('booking:again', again);
    /* Записался в квизе – здесь показываем ту же запись */
    function syncBooked() { var b = getBooked(); if (b) showDone(b); else if (!doneBox.hidden) { form.hidden = false; doneBox.hidden = true; } }
    document.addEventListener('booked', syncBooked);
    syncBooked();
  })();

  /* ===================== Видео: играет прямо в карточке ===================== */
  (function () {
    var card = $('#video-card'), btn = $('#video-open');
    if (!card || !btn || !CFG.videoUrl) return;
    var video = null;
    function make(preload) {
      if (video) return video;
      video = el('video', { src: CFG.videoUrl, playsinline: true, preload: preload, controls: true, class: 'video-card__video', poster: (card.querySelector('img') || {}).src || null });
      video.volume = 0.7;
      card.appendChild(video);
      return video;
    }
    /* Подгружаем заранее, когда блок близко: на компьютере – целиком, на телефоне – только начало */
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (en) {
        if (!en[0].isIntersecting) return;
        io.disconnect();
        make(safe(function () { return matchMedia('(hover: hover)').matches; }, false) ? 'auto' : 'metadata');
      }, { rootMargin: '300px 0px' });
      io.observe(card);
    }
    btn.addEventListener('click', function () {
      var v = make('auto');
      card.classList.add('is-playing');
      safe(function () { var p = v.play(); if (p && p.catch) p.catch(function () {}); });
      goal('video_open');
    });
  })();
  /* Фото команды грузим заранее, когда до блока ~800 px: в горизонтальной ленте отложенная загрузка срабатывает на ходу */
  (function () {
    var track = $('#crew-track');
    if (!track || !('IntersectionObserver' in window)) return;
    var io = new IntersectionObserver(function (en) {
      if (!en[0].isIntersecting) return;
      io.disconnect();
      $$('img[data-crew-img]', track).forEach(function (img) { img.loading = 'eager'; img.decoding = 'async'; });
    }, { rootMargin: '800px 0px' });
    io.observe(track);
  })();
  /* Плавающая кнопка на телефоне: видна, когда первый экран ушёл, а квиза и записи на экране нет */
  (function () {
    var fc = $('#float-cta'), hero = $('#top');
    if (!fc || !hero || !('IntersectionObserver' in window)) return;
    var vis = {};
    var io = new IntersectionObserver(function (en) {
      en.forEach(function (e) { vis[e.target.id] = e.isIntersecting; });
      var cookieOpen = cookieBar && !cookieBar.hidden;
      fc.hidden = !isMobile() || vis.top || vis.quiz || vis.call || cookieOpen;
    }, { threshold: 0 });
    ['top', 'quiz', 'call'].forEach(function (id) { var n = document.getElementById(id); if (n) io.observe(n); });
  })();
  /* ===================== Команда: слайдер ===================== */
  (function () {
    var track = $('#crew-track');
    if (!track) return;
    var prev = $('[data-crew="-1"]'), next = $('[data-crew="1"]');
    function stepW() {
      var c = $('.member', track);
      return c ? c.getBoundingClientRect().width + parseFloat(getComputedStyle(track).columnGap || 16) : 300;
    }
    function sync() {
      var max = track.scrollWidth - track.clientWidth - 2;
      if (prev) prev.disabled = track.scrollLeft <= 2;
      if (next) next.disabled = track.scrollLeft >= max;
    }
    [prev, next].forEach(function (b) {
      if (b) b.addEventListener('click', function () { track.scrollBy({ left: +b.getAttribute('data-crew') * stepW(), behavior: 'smooth' }); });
    });
    track.addEventListener('scroll', sync, { passive: true });
    window.addEventListener('resize', sync);
    sync();
    /* Перетаскивание мышью на компьютере */
    var down = false, moved = false, x0 = 0, s0 = 0;
    track.addEventListener('pointerdown', function (e) {
      if (e.pointerType !== 'mouse' || e.button !== 0) return;
      down = true; moved = false; x0 = e.clientX; s0 = track.scrollLeft;
    });
    window.addEventListener('pointermove', function (e) {
      if (!down) return;
      var dx = e.clientX - x0;
      if (!moved && Math.abs(dx) > 5) { moved = true; track.classList.add('is-drag'); }
      if (moved) track.scrollLeft = s0 - dx;
    });
    window.addEventListener('pointerup', function () {
      if (!down) return;
      down = false;
      if (moved) {
        track.classList.remove('is-drag');
        /* Доводим до ближайшей карточки */
        var w = stepW();
        track.scrollTo({ left: Math.round(track.scrollLeft / w) * w, behavior: 'smooth' });
      }
    });
    track.addEventListener('click', function (e) { if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; } }, true);
  })();

  /* ===================== Тема (цвет статус-бара под тёмную/светлую страницу) ===================== */
  (function () {
    var root = document.documentElement;
    var m = $('meta[name="theme-color"]');
    if (m) m.setAttribute('content', root.getAttribute('data-theme') === 'light' ? '#F5F6FA' : '#050915');
  })();

  /* ===================== Появление блоков при скролле ===================== */
  if (revealer) {
    document.documentElement.classList.add('anim');
    $$('.stats b').forEach(function (b) { b.setAttribute('data-count', ''); });
    $$('.section-head').forEach(function (h) { reveal($$(':scope > *', h)); });
    reveal($$('.quiz__row .chat'));
    reveal([$('#case-tabs'), $('#case-panels')].filter(Boolean), 120);
    reveal($$('.video-card'));
    reveal($$('.stats li'));
    reveal($$('.principles li'));
    reveal($$('.member'));
    reveal($$('.call__text > *'));
    reveal($$('.booking'));
  }
})();
