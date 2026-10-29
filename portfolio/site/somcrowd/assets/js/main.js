/* =========================================================
   СОМКРАУД — демо-копия. Интерактив без зависимостей.
   ========================================================= */
(() => {
  "use strict";

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

  /* ---------- Безопасный localStorage (может быть недоступен) ---------- */
  const store = {
    get(key) { try { return JSON.parse(localStorage.getItem(key)); } catch { return null; } },
    set(key, val) { try { localStorage.setItem(key, JSON.stringify(val)); } catch { /* приватный режим */ } },
  };

  /* ---------- Мобильное меню ---------- */
  const burger = $(".burger");
  const nav = $("#nav");
  const setMenu = (open) => {
    burger.setAttribute("aria-expanded", String(open));
    burger.setAttribute("aria-label", open ? "Закрыть меню" : "Открыть меню");
    nav.classList.toggle("is-open", open);
  };
  burger.addEventListener("click", () => setMenu(burger.getAttribute("aria-expanded") !== "true"));
  nav.addEventListener("click", (e) => { if (e.target.closest("a")) setMenu(false); });

  /* ---------- Модальные окна (<dialog>) ---------- */
  const openModal = (dlg) => {
    if (typeof dlg.showModal === "function") dlg.showModal();
    else dlg.setAttribute("open", "");
  };
  const closeModal = (dlg) => {
    if (typeof dlg.close === "function") dlg.close();
    else dlg.removeAttribute("open");
  };
  $$("dialog.modal").forEach((dlg) => {
    dlg.addEventListener("click", (e) => {
      if (e.target === dlg || e.target.closest("[data-close]")) closeModal(dlg);
    });
  });

  /* ---------- Форма заявки (демо: ничего не отправляет) ---------- */
  const leadModal = $("#lead-modal");
  const form = $(".lead-form", leadModal);
  const success = $(".lead-success", leadModal);
  const errorBox = $(".lead-form__error", form);
  const phone = form.elements.phone;

  const resetForm = () => {
    form.reset();
    form.hidden = false;
    success.hidden = true;
    errorBox.hidden = true;
    $$(".is-invalid", form).forEach((el) => el.classList.remove("is-invalid"));
  };

  $$("[data-popup]").forEach((btn) => {
    btn.addEventListener("click", () => {
      resetForm();
      form.elements.source.value = btn.dataset.popup; // какая кнопка открыла форму
      setMenu(false);
      openModal(leadModal);
      setTimeout(() => form.elements.name.focus(), 50);
    });
  });

  // Маска телефона: (000) 000-00-00
  const formatPhone = (value) => {
    let d = value.replace(/\D/g, "");
    if (d.length > 10 && (d[0] === "7" || d[0] === "8")) d = d.slice(1);
    d = d.slice(0, 10);
    let out = "";
    if (d.length > 0) out = "(" + d.slice(0, 3);
    if (d.length >= 3) out += ") " + d.slice(3, 6);
    if (d.length >= 6) out += "-" + d.slice(6, 8);
    if (d.length >= 8) out += "-" + d.slice(8, 10);
    return out;
  };
  phone.addEventListener("input", () => { phone.value = formatPhone(phone.value); });

  // Убираем подсветку ошибки, как только поле поправили
  form.addEventListener("input", (e) => {
    const box = e.target.closest(".field, .consent");
    if (box) box.classList.remove("is-invalid");
    if (!$(".is-invalid", form)) errorBox.hidden = true;
  });

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    const errors = [];
    const nameField = form.elements.name.closest(".field");
    const phoneField = phone.closest(".field");
    const consent = form.elements.consent.closest(".consent");

    const nameOk = form.elements.name.value.trim().length >= 2;
    const phoneOk = phone.value.replace(/\D/g, "").length === 10;
    const consentOk = form.elements.consent.checked;

    nameField.classList.toggle("is-invalid", !nameOk);
    phoneField.classList.toggle("is-invalid", !phoneOk);
    consent.classList.toggle("is-invalid", !consentOk);

    if (!nameOk) errors.push("укажите имя");
    if (!phoneOk) errors.push("укажите телефон полностью");
    if (!consentOk) errors.push("отметьте согласие на обработку данных");

    if (errors.length) {
      errorBox.textContent = "Пожалуйста, " + errors.join(", ") + ".";
      errorBox.hidden = false;
      return;
    }
    // Демо-режим: данные никуда не уходят
    form.hidden = true;
    success.hidden = false;
  });

  /* ---------- FAQ: открыт только один пункт ---------- */
  const faqItems = $$(".faq-item");
  faqItems.forEach((item) => {
    item.addEventListener("toggle", () => {
      if (item.open) faqItems.forEach((other) => { if (other !== item) other.open = false; });
    });
  });

  /* ---------- Слайдер видеоотзывов ---------- */
  const slider = $(".reviews__slider");
  const [prevBtn, nextBtn] = $$(".slider-nav__btn");
  const step = () => {
    const card = $(".video-card", slider);
    return card ? card.getBoundingClientRect().width + 10 : 260;
  };
  const updateArrows = () => {
    const max = slider.scrollWidth - slider.clientWidth - 2;
    prevBtn.disabled = slider.scrollLeft <= 2;
    nextBtn.disabled = slider.scrollLeft >= max;
  };
  [prevBtn, nextBtn].forEach((btn) => {
    btn.addEventListener("click", () => {
      slider.scrollBy({ left: Number(btn.dataset.slide) * step(), behavior: "smooth" });
    });
  });
  slider.addEventListener("scroll", updateArrows, { passive: true });
  window.addEventListener("resize", updateArrows);
  updateArrows();

  // Видео VK подгружаем, только когда блок появился на экране
  const mountVideos = () => {
    $$(".video-card[data-vk]", slider).forEach((card) => {
      const [oid, id] = card.dataset.vk.split("_");
      const iframe = document.createElement("iframe");
      iframe.src = `https://vk.com/video_ext.php?oid=${oid}&id=${id}&hd=2`;
      iframe.title = "Видеоотзыв клиента";
      iframe.loading = "lazy";
      iframe.allow = "autoplay; encrypted-media; fullscreen; picture-in-picture; screen-wake-lock";
      iframe.allowFullscreen = true;
      card.append(iframe);
      card.removeAttribute("data-vk");
    });
    updateArrows();
  };
  if ("IntersectionObserver" in window) {
    const io = new IntersectionObserver((entries) => {
      if (entries.some((en) => en.isIntersecting)) { mountVideos(); io.disconnect(); }
    }, { rootMargin: "300px" });
    io.observe(slider);
  } else {
    mountVideos();
  }

  /* ---------- Cookie-баннер ---------- */
  const COOKIE_KEY = "somcrowd-demo-cookie";
  const banner = $("#cookie");
  const cookieModal = $("#cookie-modal");
  const toggles = $$(".switch", cookieModal);

  const saveConsent = (prefs) => {
    store.set(COOKIE_KEY, { ...prefs, ts: Date.now() });
    banner.hidden = true;
    // Здесь подключались бы счётчики, если бы пользователь их разрешил.
    // В демо-копии аналитика и реклама не подключены вовсе.
  };

  const saved = store.get(COOKIE_KEY);
  if (!saved) banner.hidden = false;

  $$("[data-cookie]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const action = btn.dataset.cookie;
      if (action === "all") saveConsent({ analytics: true, ads: true, other: true });
      if (action === "none") saveConsent({ analytics: false, ads: false, other: false });
      if (action === "settings") {
        const cur = store.get(COOKIE_KEY) || {};
        toggles.forEach((t) => { t.checked = Boolean(cur[t.name]); });
        openModal(cookieModal);
      }
      if (action === "save") {
        const prefs = {};
        toggles.forEach((t) => { prefs[t.name] = t.checked; });
        saveConsent(prefs);
        closeModal(cookieModal);
      }
    });
  });
})();
