(() => {
  const triggers = [...document.querySelectorAll("[data-site-phone]")];
  if (!triggers.length) return;

  const dialog = document.createElement("dialog");
  dialog.className = "phone-confirm";
  dialog.setAttribute("aria-labelledby", "phone-confirm-title");
  dialog.innerHTML = `
    <button class="phone-confirm__close" type="button" aria-label="Закрыть">&times;</button>
    <h2 id="phone-confirm-title">Показать телефон</h2>
    <p id="phone-confirm-description">Потяните стрелку вправо до конца, чтобы открыть номер.</p>
    <div class="phone-swipe" aria-busy="true">
      <div class="phone-swipe__fill" aria-hidden="true"></div>
      <span class="phone-swipe__label" aria-hidden="true">Потяните вправо</span>
      <div class="phone-swipe__thumb" role="slider" tabindex="0" aria-label="Подтверждение показа телефона"
        aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-disabled="true"
        aria-describedby="phone-confirm-description phone-confirm-keyboard">&rarr;</div>
    </div>
    <span class="phone-confirm__keyboard" id="phone-confirm-keyboard">Удерживайте стрелку вправо на клавиатуре до подтверждения.</span>
    <p class="phone-confirm__status" role="status" aria-live="polite"></p>
  `;
  document.body.append(dialog);

  const track = dialog.querySelector(".phone-swipe");
  const thumb = dialog.querySelector(".phone-swipe__thumb");
  const label = dialog.querySelector(".phone-swipe__label");
  const status = dialog.querySelector(".phone-confirm__status");
  const closeButton = dialog.querySelector(".phone-confirm__close");
  let token = "";
  let opener = null;
  let pointer = null;
  let startX = 0;
  let startTime = 0;
  let progress = 0;
  let movements = 0;
  let pending = false;
  let generation = 0;
  let keyboardStart = 0;
  let goalSent = false;

  fetch("/portfolio/site/liftx/site-config.json", { cache: "no-store" })
    .then((response) => response.ok ? response.json() : null)
    .then((config) => {
      if (typeof config?.phoneMasked !== "string") return;
      document.querySelectorAll("[data-phone-label]").forEach((node) => {
        if (node.closest("button[data-site-phone]")) node.textContent = config.phoneMasked;
      });
    })
    .catch(() => {});

  function setProgress(value) {
    progress = Math.max(0, Math.min(1, value));
    const distance = Math.max(1, track.clientWidth - thumb.offsetWidth - 10);
    thumb.style.transform = `translateX(${progress * distance}px)`;
    track.style.setProperty("--swipe-progress", `${progress * 100}%`);
    thumb.setAttribute("aria-valuenow", String(Math.round(progress * 100)));
    label.style.opacity = String(1 - progress);
  }

  function setReady(ready) {
    thumb.setAttribute("aria-disabled", String(!ready));
    track.setAttribute("aria-busy", String(!ready));
  }

  function resetGesture() {
    pointer = null;
    keyboardStart = 0;
    movements = 0;
    track.classList.remove("is-dragging", "is-complete");
    setProgress(0);
  }

  async function prepareChallenge() {
    const current = ++generation;
    token = "";
    resetGesture();
    setReady(false);
    status.textContent = "Загружаем подтверждение...";
    try {
      const response = await fetch("/portfolio/site/liftx/phone.php", { credentials: "same-origin", cache: "no-store" });
      const data = await response.json();
      if (current !== generation || !dialog.open) return;
      if (!response.ok || !data.token) throw new Error(data.error || "Не удалось загрузить подтверждение.");
      token = data.token;
      status.textContent = "";
      setReady(true);
    } catch (error) {
      if (current !== generation || !dialog.open) return;
      status.textContent = `${error.message} Закройте окно и попробуйте снова.`;
    }
  }

  function closeDialog() {
    ++generation;
    token = "";
    resetGesture();
    dialog.close();
    opener?.focus({ preventScroll: true });
  }

  async function revealPhone() {
    if (!token || pending) return;
    pending = true;
    const current = generation;
    const requestToken = token;
    token = "";
    setReady(false);
    track.classList.add("is-complete");
    status.textContent = "Открываем номер...";
    try {
      const response = await fetch("/portfolio/site/liftx/phone.php", {
        method: "POST",
        credentials: "same-origin",
        cache: "no-store",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ token: requestToken }),
      });
      const data = await response.json();
      if (current !== generation || !dialog.open) return;
      if (!response.ok || !/^tel:\+\d{11}$/.test(data.href) || typeof data.phone !== "string") {
        throw new Error(data.error || "Не удалось открыть номер.");
      }
      let restoredFocus = null;
      document.querySelectorAll("button[data-site-phone]").forEach((button) => {
        const link = document.createElement("a");
        link.className = button.className.replace("phone-reveal-trigger", "").trim();
        link.dataset.sitePhone = "";
        link.href = data.href;
        link.textContent = data.phone;
        if (button === opener) restoredFocus = link;
        button.replaceWith(link);
      });
      opener = restoredFocus;
      closeDialog();
      // This goal records a completed reveal, never a click on the masked number.
      if (!goalSent) {
        goalSent = true;
        
      }
    } catch (error) {
      if (current !== generation || !dialog.open) return;
      resetGesture();
      status.textContent = `${error.message} Закройте окно и повторите подтверждение.`;
    } finally {
      pending = false;
    }
  }

  triggers.forEach((button) => {
    button.addEventListener("click", () => {
      opener = button;
      dialog.showModal();
      thumb.focus();
      prepareChallenge();
    });
  });

  closeButton.addEventListener("click", closeDialog);
  dialog.addEventListener("cancel", (event) => {
    event.preventDefault();
    closeDialog();
  });
  dialog.addEventListener("click", (event) => {
    if (event.target !== dialog) return;
    const bounds = dialog.getBoundingClientRect();
    if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) closeDialog();
  });

  thumb.addEventListener("pointerdown", (event) => {
    if (!event.isTrusted || !event.isPrimary || event.button !== 0 || !token || pending || pointer !== null) return;
    event.preventDefault();
    thumb.focus();
    resetGesture();
    pointer = event.pointerId;
    startX = event.clientX;
    startTime = performance.now();
    thumb.setPointerCapture(pointer);
    track.classList.add("is-dragging");
    status.textContent = "";
  });

  thumb.addEventListener("pointermove", (event) => {
    if (!event.isTrusted || event.pointerId !== pointer) return;
    movements += 1;
    setProgress((event.clientX - startX) / Math.max(1, track.clientWidth - thumb.offsetWidth - 10));
  });

  thumb.addEventListener("pointerup", (event) => {
    if (!event.isTrusted || event.pointerId !== pointer) return;
    const completed = progress >= 0.97 && movements >= 4 && performance.now() - startTime >= 250;
    pointer = null;
    track.classList.remove("is-dragging");
    if (completed) revealPhone();
    else {
      resetGesture();
      status.textContent = "Потяните стрелку плавно до правого края.";
    }
  });

  ["pointercancel", "lostpointercapture"].forEach((type) => {
    thumb.addEventListener(type, () => {
      if (pointer !== null) resetGesture();
    });
  });

  thumb.addEventListener("keydown", (event) => {
    if (!event.isTrusted || !token || pending || pointer !== null) return;
    if (!["ArrowRight", "ArrowLeft", "Home"].includes(event.key)) return;
    event.preventDefault();
    if (event.key !== "ArrowRight") {
      resetGesture();
      return;
    }
    if (!keyboardStart) keyboardStart = performance.now();
    setProgress(progress + 0.05);
    if (progress >= 1 && performance.now() - keyboardStart >= 250) revealPhone();
  });
  thumb.addEventListener("keyup", () => {
    if (!pending) resetGesture();
  });
  window.addEventListener("resize", () => {
    if (dialog.open && !pending) resetGesture();
  });
})();
