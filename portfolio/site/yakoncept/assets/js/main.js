/* =========================================================
   Я — Концепт · демо-копия. Интерактив без зависимостей.
   ========================================================= */
(() => {
  "use strict";

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

  /* ---------- Безопасный localStorage ---------- */
  const store = {
    get(key) { try { return JSON.parse(localStorage.getItem(key)); } catch { return null; } },
    set(key, val) { try { localStorage.setItem(key, JSON.stringify(val)); } catch { /* приватный режим */ } },
  };

  /* ---------- Модальные окна (<dialog>) ---------- */
  const openModal = (dlg) => (typeof dlg.showModal === "function" ? dlg.showModal() : dlg.setAttribute("open", ""));
  const closeModal = (dlg) => (typeof dlg.close === "function" ? dlg.close() : dlg.removeAttribute("open"));
  $$("dialog.modal").forEach((dlg) => {
    dlg.addEventListener("click", (e) => {
      if (e.target === dlg || e.target.closest("[data-close]")) closeModal(dlg);
    });
  });

  /* ---------- Форма записи (демо: ничего не отправляет) ---------- */
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
      form.elements.source.value = btn.dataset.popup;
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

  form.addEventListener("input", (e) => {
    const box = e.target.closest(".field, .consent");
    if (box) box.classList.remove("is-invalid");
    if (!$(".is-invalid", form)) errorBox.hidden = true;
  });

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    const checks = [
      [form.elements.name.value.trim().length >= 2, form.elements.name.closest(".field"), "укажите имя"],
      [phone.value.replace(/\D/g, "").length === 10, phone.closest(".field"), "укажите телефон полностью"],
      [form.elements.consent.checked, form.elements.consent.closest(".consent"), "отметьте согласие на обработку данных"],
    ];
    const errors = [];
    checks.forEach(([ok, el, msg]) => {
      el.classList.toggle("is-invalid", !ok);
      if (!ok) errors.push(msg);
    });
    if (errors.length) {
      errorBox.textContent = "Пожалуйста, " + errors.join(", ") + ".";
      errorBox.hidden = false;
      return;
    }
    form.hidden = true;     // демо-режим: данные никуда не уходят
    success.hidden = false;
  });

  /* ---------- Линия этапов: прорисовывается один раз при появлении ---------- */
  const path = $(".path");
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (path && !reduceMotion && "IntersectionObserver" in window) {
    path.classList.add("is-pending");
    const io = new IntersectionObserver((entries) => {
      if (!entries.some((en) => en.isIntersecting)) return;
      path.classList.add("is-shown");
      requestAnimationFrame(() => path.classList.remove("is-pending"));
      io.disconnect();
    }, { threshold: 0.35 });
    io.observe(path);
  }

  /* ---------- Cookie-баннер ---------- */
  const COOKIE_KEY = "yakoncept-demo-cookie";
  const banner = $("#cookie");
  const cookieModal = $("#cookie-modal");
  const toggles = $$(".switch", cookieModal);

  const saveConsent = (prefs) => {
    store.set(COOKIE_KEY, { ...prefs, ts: Date.now() });
    banner.hidden = true;
    // В демо-копии аналитика и реклама не подключены вовсе.
  };

  if (!store.get(COOKIE_KEY)) banner.hidden = false;

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
