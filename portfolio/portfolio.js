(function () {
  'use strict';

  /* Переключатель шагов квиза */
  document.querySelectorAll('[data-steps]').forEach(function (box) {
    var tabs = box.querySelectorAll('[data-step-tab]');
    var imgs = box.querySelectorAll('[data-step-img]');
    var cap = box.querySelector('[data-step-caption]');
    function show(i) {
      tabs.forEach(function (t, k) { t.setAttribute('aria-selected', k === i ? 'true' : 'false'); });
      imgs.forEach(function (im, k) { im.hidden = k !== i; });
      if (cap) cap.textContent = tabs[i].getAttribute('data-caption') || '';
    }
    tabs.forEach(function (t, i) { t.addEventListener('click', function () { show(i); }); });
    show(0);
  });

  /* Лайтбокс для скриншотов */
  var box = document.createElement('div');
  box.className = 'lightbox';
  box.hidden = true;
  box.innerHTML = '<button type="button" aria-label="Закрыть">×</button><img alt="">';
  document.body.appendChild(box);
  var big = box.querySelector('img');
  function close() { box.hidden = true; big.removeAttribute('src'); document.body.style.overflow = ''; }
  box.addEventListener('click', close);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !box.hidden) close(); });
  document.addEventListener('click', function (e) {
    var img = e.target.closest('img[data-zoom]');
    if (!img) return;
    big.src = img.getAttribute('data-zoom') || img.currentSrc || img.src;
    big.alt = img.alt;
    box.hidden = false;
    box.scrollTop = 0;
    document.body.style.overflow = 'hidden';
  });
})();
