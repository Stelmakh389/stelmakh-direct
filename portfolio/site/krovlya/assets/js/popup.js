(function () {
  const popup = document.querySelector('[data-popup="consultation"]');
  if (!popup) return;

  const openButtons = document.querySelectorAll('[data-popup-open="consultation"]');
  const closeButtons = popup.querySelectorAll('[data-popup-close]');
  const form = popup.querySelector('[data-lead-form]');
  const firstInput = popup.querySelector('input[name="name"]');
  const phoneInput = popup.querySelector('input[name="phone"]');
  const message = popup.querySelector('[data-lead-message]');
  const submitButton = popup.querySelector('[type="submit"]');

  function openPopup(event) {
    if (event) event.preventDefault();
    if (message) {
      message.hidden = true;
      message.textContent = '';
      message.classList.remove('is-error');
    }
    if (submitButton) {
      submitButton.disabled = false;
    }
    popup.hidden = false;
    document.body.classList.add('popup-open');
    window.setTimeout(() => firstInput?.focus(), 0);
  }

  function closePopup() {
    popup.hidden = true;
    document.body.classList.remove('popup-open');
  }

  openButtons.forEach((button) => button.addEventListener('click', openPopup));
  closeButtons.forEach((button) => button.addEventListener('click', closePopup));

  function formatPhone(value) {
    const digits = value.replace(/\D/g, '').replace(/^8/, '7').replace(/^7/, '').slice(0, 10);
    let result = '+7';

    if (digits.length > 0) result += ` (${digits.slice(0, 3)}`;
    if (digits.length >= 3) result += ')';
    if (digits.length > 3) result += ` ${digits.slice(3, 6)}`;
    if (digits.length > 6) result += `-${digits.slice(6, 8)}`;
    if (digits.length > 8) result += `-${digits.slice(8, 10)}`;

    return result;
  }

  phoneInput?.addEventListener('input', () => {
    phoneInput.value = formatPhone(phoneInput.value);
  });

  phoneInput?.addEventListener('focus', () => {
    if (!phoneInput.value) phoneInput.value = '+7';
  });

  function escapeMarkdown(value) {
    return String(value || '').replace(/([_*[\]()~`>#+=|{}.!-])/g, '\\$1');
  }

  function setMessage(text, type) {
    if (!message) return;

    message.textContent = text;
    message.hidden = false;
    message.classList.toggle('is-error', type === 'error');
  }

  function getLeadType() {
    return document.querySelector('.extension-page') ? 'pristroyki_popup' : 'roof_popup';
  }

  function getThanksUrl() {
    const leadType = getLeadType();

    if (window.SiteAttribution?.appendToUrl) {
      return window.SiteAttribution.appendToUrl('thanks.php', { lead_type: leadType });
    }

    return `thanks.php?lead_type=${encodeURIComponent(leadType)}`;
  }

  function acceptAnalytics() {
    if (typeof window.acceptAnalyticsCookies === 'function') {
      window.acceptAnalyticsCookies();
    }
  }

  function sendMetrikaGoal() {
    return new Promise((resolve) => {
      const counters = window.CookieConsentConfig?.yandexCounters || [];
      const counter = counters[0]?.number;

      if (!counter || typeof window.ym !== 'function') {
        resolve();
        return;
      }

      let resolved = false;
      const done = () => {
        if (!resolved) {
          resolved = true;
          resolve();
        }
      };

      window.setTimeout(done, 500);

      try {
        window.ym(counter, 'reachGoal', 'lead_form_submit', {
          form: getLeadType(),
          page: window.location.href,
        }, done);
      } catch (error) {
        done();
      }
    });
  }

  async function redirectToThanks() {
    acceptAnalytics();
    await sendMetrikaGoal();
    window.location.assign(getThanksUrl());
  }

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const formData = new FormData(form);
    const name = formData.get('name') || 'Не указано';
    const phone = formData.get('phone') || 'Не указан';
    const title = form.dataset.leadTitle || 'Заявка с сайта';
    const attribution = window.SiteAttribution?.get?.() || {};
    const attributionRows = Object.entries(attribution).map(([key, value]) => `${key}: ${escapeMarkdown(value)}`);
    const text = [
      `*${escapeMarkdown(title)}*`,
      '',
      `Имя: ${escapeMarkdown(name)}`,
      `Телефон: ${escapeMarkdown(phone)}`,
      ...(attributionRows.length ? ['', '*Метки:*', ...attributionRows] : []),
      `Страница: ${escapeMarkdown(window.location.href)}`,
    ].join('\n');

    submitButton.disabled = true;
    setMessage('Отправляем заявку...', 'info');

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ text }),
      });

      if (!response.ok) {
        throw new Error('Request failed');
      }

      setMessage('Спасибо! Заявка отправлена, специалист скоро свяжется с вами.', 'success');
      form.reset();
      if (phoneInput) phoneInput.value = '';
      await redirectToThanks();
    } catch (error) {
      submitButton.disabled = false;
      setMessage('Не удалось отправить заявку. Попробуйте еще раз или позвоните нам.', 'error');
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !popup.hidden) {
      closePopup();
    }
  });
})();
