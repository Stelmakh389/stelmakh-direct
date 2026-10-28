(function () {
  const quiz = document.querySelector('[data-quiz]');

  if (!quiz) {
    return;
  }

  const steps = Array.from(quiz.querySelectorAll('[data-quiz-step]'));
  const progress = quiz.querySelector('[data-quiz-progress]');
  const progressText = quiz.querySelector('[data-quiz-progress-text]');
  const prevButton = quiz.querySelector('[data-quiz-prev]');
  const nextButton = quiz.querySelector('[data-quiz-next]');
  const form = quiz.querySelector('[data-quiz-form]');
  const phoneInput = quiz.querySelector('[data-quiz-phone]');
  const success = quiz.querySelector('[data-quiz-success]');
  const messageTitle = quiz.dataset.quizTitle || 'Заявка из квиза расчета кровли';
  const leadType = quiz.dataset.quizLeadType || (messageTitle.toLowerCase().includes('пристрой') ? 'pristroyki_quiz' : 'roof_quiz');
  const metrikaGoal = quiz.dataset.quizGoal || 'lead_form_submit';
  let currentStep = 0;

  const escapeMarkdown = (value) => String(value || '').replace(/([_*[\]()~`>#+=|{}.!-])/g, '\\$1');

  const updateProgress = () => {
    const percent = Math.round(((currentStep + 1) / steps.length) * 100);

    progress.style.width = `${percent}%`;
    progressText.textContent = currentStep === steps.length - 1
      ? 'Расчет пройден на 100%'
      : `Расчет пройден на ${percent}%`;
  };

  const showStep = (index) => {
    currentStep = Math.max(0, Math.min(index, steps.length - 1));

    steps.forEach((step, stepIndex) => {
      const isActive = stepIndex === currentStep;

      step.hidden = !isActive;
      step.classList.toggle('is-active', isActive);
    });

    prevButton.hidden = currentStep === 0;
    nextButton.hidden = currentStep === steps.length - 1;
    updateProgress();
  };

  const markSelected = (option) => {
    const step = option.closest('[data-quiz-step]');

    step.querySelectorAll('[data-quiz-option]').forEach((item) => {
      item.classList.toggle('is-selected', item === option);
    });
  };

  const canGoNext = () => {
    const step = steps[currentStep];
    const requiredInput = step.querySelector('[data-quiz-required]');
    const skipInput = step.querySelector('[data-quiz-skip]');

    if (!requiredInput || (skipInput && skipInput.checked)) {
      return true;
    }

    const isValid = requiredInput.value.trim().length > 0;
    requiredInput.classList.toggle('is-invalid', !isValid);

    if (!isValid) {
      requiredInput.focus();
    }

    return isValid;
  };

  const formatPhone = (value) => {
    const digits = value.replace(/\D/g, '').replace(/^8/, '7').slice(0, 11);
    const normalized = digits.startsWith('7') ? digits : `7${digits}`;
    const parts = [
      normalized.slice(1, 4),
      normalized.slice(4, 7),
      normalized.slice(7, 9),
      normalized.slice(9, 11),
    ];

    let result = '+7';

    if (parts[0]) {
      result += ` (${parts[0]}`;
    }

    if (parts[0].length === 3) {
      result += ')';
    }

    if (parts[1]) {
      result += ` ${parts[1]}`;
    }

    if (parts[2]) {
      result += `-${parts[2]}`;
    }

    if (parts[3]) {
      result += `-${parts[3]}`;
    }

    return result;
  };

  const getSelectedAnswer = (step) => {
    const selected = step.querySelector('[data-quiz-option].is-selected');

    if (selected) {
      return selected.textContent.trim().replace(/\s+/g, ' ');
    }

    const requiredInput = step.querySelector('[data-quiz-required]');
    const skipInput = step.querySelector('[data-quiz-skip]');

    if (skipInput?.checked) {
      return 'Вопрос пропущен';
    }

    return requiredInput?.value ? `${requiredInput.value} м2` : 'Не указано';
  };

  const buildQuizMessage = () => {
    const formData = new FormData(form);
    const name = formData.get('name') || 'Не указано';
    const phone = formData.get('phone') || 'Не указан';
    const contactMethod = steps[steps.length - 1].querySelector('[data-quiz-option].is-selected')?.textContent.trim().replace(/\s+/g, ' ') || 'Не указан';
    const attribution = window.SiteAttribution?.get?.() || {};
    const attributionRows = Object.entries(attribution).map(([key, value]) => `${key}: ${escapeMarkdown(value)}`);
    const answers = steps.slice(0, -1).map((step, index) => {
      const question = step.querySelector('.roof-quiz__question')?.textContent.trim() || `Вопрос ${index + 1}`;
      const answer = getSelectedAnswer(step);

      return `${index + 1}. ${escapeMarkdown(question)}: ${escapeMarkdown(answer)}`;
    });

    return [
      `*${escapeMarkdown(messageTitle)}*`,
      '',
      `Имя: ${escapeMarkdown(name)}`,
      `Телефон: ${escapeMarkdown(phone)}`,
      `Удобный способ связи: ${escapeMarkdown(contactMethod)}`,
      '',
      '*Ответы:*',
      ...answers,
      '',
      ...(attributionRows.length ? ['*Метки:*', ...attributionRows, ''] : []),
      `Страница: ${escapeMarkdown(window.location.href)}`,
    ].join('\n');
  };

  const getThanksUrl = () => {
    if (window.SiteAttribution?.appendToUrl) {
      return window.SiteAttribution.appendToUrl('thanks.php', { lead_type: leadType });
    }

    return `thanks.php?lead_type=${encodeURIComponent(leadType)}`;
  };

  const acceptAnalytics = () => {
    if (typeof window.acceptAnalyticsCookies === 'function') {
      window.acceptAnalyticsCookies();
    }
  };

  const sendMetrikaGoal = () => new Promise((resolve) => {
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
      window.ym(counter, 'reachGoal', metrikaGoal, {
        form: leadType,
        page: window.location.href,
      }, done);
    } catch (error) {
      done();
    }
  });

  const redirectToThanks = async () => {
    acceptAnalytics();
    await sendMetrikaGoal();
    window.location.assign(getThanksUrl());
  };

  quiz.addEventListener('click', (event) => {
    const option = event.target.closest('[data-quiz-option]');

    if (option && quiz.contains(option)) {
      markSelected(option);
    }
  });

  prevButton.addEventListener('click', () => {
    showStep(currentStep - 1);
  });

  nextButton.addEventListener('click', () => {
    if (canGoNext()) {
      showStep(currentStep + 1);
    }
  });

  quiz.addEventListener('input', (event) => {
    if (event.target.matches('[data-quiz-required]')) {
      event.target.classList.remove('is-invalid');
    }
  });

  if (phoneInput) {
    phoneInput.addEventListener('input', () => {
      phoneInput.value = formatPhone(phoneInput.value);
    });
  }

  if (form) {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const submitButton = form.querySelector('[type="submit"]');

      submitButton.disabled = true;
      success.textContent = 'Отправляем заявку...';
      success.hidden = false;

      try {
        const response = await fetch(form.action, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ text: buildQuizMessage() }),
        });

        if (!response.ok) {
          throw new Error('Request failed');
        }

        success.textContent = 'Спасибо! Заявка принята, специалист скоро свяжется с вами.';
        await redirectToThanks();
      } catch (error) {
        submitButton.disabled = false;
        success.textContent = 'Не удалось отправить заявку. Попробуйте еще раз или позвоните нам.';
      }
    });
  }

  showStep(0);
}());
