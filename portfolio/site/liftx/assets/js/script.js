const equipmentTypes = {
  scissor: {
    label: "Ножничные",
    order: 10,
    specs: ["height", "capacity", "engine"],
    filters: ["height"],
    hero: "Подберём ножничный подъемник за 20 секунд",
  },
  boom: {
    label: "Коленчатые",
    order: 20,
    specs: ["height", "capacity", "engine"],
    filters: ["height"],
    hero: "Подберём коленчатый подъемник за 20 секунд",
  },
  aerial: {
    label: "Автовышки",
    order: 30,
    specs: ["height", "boomReach", "capacity"],
    filters: ["height", "boomReach", "capacity"],
    hero: "Подберём автовышку за 20 секунд",
  },
  manipulator: {
    label: "Манипуляторы",
    order: 40,
    specs: ["boomCapacity", "boomReach", "bedCapacity"],
    filters: ["boomCapacity", "boomReach", "bedCapacity"],
    hero: "Подберём манипулятор за 20 секунд",
  },
  crane: {
    label: "Краны",
    order: 50,
    specs: ["capacity", "boomReach", "height"],
    filters: ["capacity", "boomReach", "height"],
    hero: "Подберём кран за 20 секунд",
  },
  longbed: {
    label: "Длиномеры",
    order: 60,
    specs: ["platformType", "capacity", "platformLength"],
    filters: ["platformType", "capacity", "platformLength"],
    hero: "Подберём длиномер за 20 секунд",
  },
  shalanda: {
    label: "Шаланды",
    order: 70,
    specs: ["platformType", "capacity", "platformLength"],
    filters: ["platformType", "capacity", "platformLength"],
    hero: "Подберём шаланду за 20 секунд",
  },
  backhoe: {
    label: "Экскаваторы-погрузчики",
    order: 80,
    specs: ["digDepth", "bucketVolume", "attachments"],
    filters: ["digDepth", "bucketVolume", "attachments"],
    hero: "Подберём экскаватор-погрузчик за 20 секунд",
  },
  excavator: {
    label: "Экскаваторы",
    order: 90,
    specs: ["digDepth", "bucketVolume", "attachments"],
    filters: ["digDepth", "bucketVolume", "attachments"],
    hero: "Подберём экскаватор за 20 секунд",
  },
};

const specDefinitions = {
  height: { label: "Высота подъема", shortLabel: "Высота", unit: "м", kind: "number" },
  capacity: { label: "Грузоподъемность", shortLabel: "Грузоподъемность", unit: "кг", kind: "number" },
  engine: { label: "Тип двигателя", shortLabel: "Двигатель", kind: "option" },
  boomReach: { label: "Вылет стрелы", shortLabel: "Вылет стрелы", unit: "м", kind: "number" },
  boomCapacity: { label: "Грузоподъемность стрелы", shortLabel: "Груз. стрелы", unit: "кг", kind: "number" },
  bedCapacity: { label: "Грузоподъемность борта", shortLabel: "Груз. борта", unit: "кг", kind: "number" },
  platformType: { label: "Тип платформы", shortLabel: "Платформа", kind: "option" },
  platformLength: { label: "Длина платформы", shortLabel: "Длина платформы", unit: "м", kind: "number" },
  digDepth: { label: "Глубина копания", shortLabel: "Глубина", unit: "м", kind: "number" },
  bucketVolume: { label: "Объем ковша", shortLabel: "Ковш", unit: "м3", kind: "number" },
  attachments: { label: "Навесное оборудование", shortLabel: "Навесное", kind: "option" },
};

const valueLabels = {
  diesel: "Дизель",
  electric: "Электро",
  onboard: "Бортовая",
  flatbed: "Открытая",
  lowLoader: "Низкорамная",
  container: "Контейнерная",
  forks: "Вилы",
  hammer: "Гидромолот",
  auger: "Ямобур",
  bucket: "Ковш",
};

const state = {
  type: "all",
  location: "all",
  engine: "all",
  ranges: {},
  options: {},
};

const fallbackEquipment = [
  {
    id: 1,
    title: "Genie GS-3369 RT 4x4",
    type: "scissor",
    height: 12,
    capacity: 450,
    engine: "diesel",
    price: 2900,
    available: true,
    image: "",
  },
  {
    id: 2,
    title: "Автовышка 22 м",
    type: "aerial",
    height: 22,
    boomReach: 12,
    capacity: 250,
    engine: "diesel",
    price: 12000,
    available: true,
    image: "",
  },
];

const pageSize = 20;
const defaultHeroTitle = "Подберём подъемник <span>за 20 секунд</span>";
const heroTitleNode = document.querySelector("#hero-title");
const cardsNode = document.querySelector("#cards");
const summaryNode = document.querySelector("#result-summary");
const availableCountNode = document.querySelector("#available-count");
const loadMoreWrap = document.querySelector("#load-more-wrap");
const loadMoreButton = document.querySelector("#load-more");
const typeTabsNode = document.querySelector(".type-tabs");
const dynamicFiltersNode = document.querySelector("#dynamic-filters");
const locationFilterNode = document.querySelector(".filter-group--location");
const engineFilterNode = document.querySelector(".filter-group--engine");
const leadModal = document.querySelector("#lead-modal");
const leadForm = document.querySelector("#lead-form");
const leadPhone = document.querySelector("#lead-phone");
const leadStatus = document.querySelector("#lead-status");
const leadTitle = document.querySelector("#lead-title");
const leadDescription = document.querySelector("#lead-description");
const leadConsent = document.querySelector("#lead-consent");
const cookieConsent = document.querySelector("#cookie-consent");
const cookieAccept = document.querySelector("#cookie-accept");
const mobileCta = document.querySelector(".mobile-cta");

let equipment = [];
let siteConfig = { email: "get@podemnik-v-arendu.ru", pages: [] };
let leadVariant = "";
let visibleCount = pageSize;
let lockedType = "";
let liftCatalogContext = false;
let activeFilterKeys = [];

init();

async function init() {
  [equipment, siteConfig] = await Promise.all([loadEquipment(), loadSiteConfig()]);
  equipment = equipment.map(normalizeEquipmentItem);
  applySiteConfig();
  applyPageConfig();
  bindControls();
  syncButtons("type");
  syncButtons("location");
  syncButtons("engine");
  syncPageTypeLock();
  syncTypeTabsVisibility();
  syncLiftOnlyFilters();
  syncEngineAvailability();
  initCookieBanner();
  initMobileCtaVisibility();
  rebuildDynamicFilters();
  render();
}

async function loadEquipment() {
  try {
    const response = await fetch("equipment.json", { cache: "no-store" });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return await response.json();
  } catch (error) {
    console.warn("Не удалось загрузить equipment.json, использованы резервные данные.", error);
    return fallbackEquipment;
  }
}

async function loadSiteConfig() {
  try {
    const response = await fetch("site-config.json", { cache: "no-store" });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const config = await response.json();
    return {
      email: config.email || siteConfig.email,
      pages: Array.isArray(config.pages) ? config.pages : [],
    };
  } catch (error) {
    console.warn("Не удалось загрузить site-config.json, использованы значения по умолчанию.", error);
    return siteConfig;
  }
}

function normalizeEquipmentItem(item) {
  const normalized = { ...item };

  Object.keys(specDefinitions).forEach((key) => {
    if (specDefinitions[key].kind === "number" && normalized[key] !== undefined && normalized[key] !== "") {
      normalized[key] = Number(normalized[key]);
    }
  });

  normalized.available = normalized.available !== false;
  return normalized;
}

function applySiteConfig() {
  document.querySelectorAll("[data-site-email]").forEach((link) => {
    link.textContent = siteConfig.email;
    link.href = `mailto:${siteConfig.email}`;
  });
}

function applyPageConfig() {
  const currentPath = normalizePagePath(window.location.pathname);
  const page = siteConfig.pages.find((item) => normalizePagePath(item.url) === currentPath);

  if (!page) {
    liftCatalogContext = true;
    state.type = "all";
    heroTitleNode.innerHTML = defaultHeroTitle;
    return;
  }

  if (page.title) {
    heroTitleNode.textContent = page.title;
    document.title = page.title;
  }

  if (page.type === "all" || equipmentTypes[page.type]) {
    state.type = page.type;
    liftCatalogContext = page.type === "scissor" || page.type === "boom";
    lockedType = page.type === "all" || liftCatalogContext ? "" : page.type;
  }
}

function syncPageTypeLock() {
  if (!lockedType) return;

  document.querySelectorAll('[data-filter="type"]').forEach((button) => {
    button.disabled = button.dataset.value !== lockedType;
  });
}

function syncTypeTabsVisibility() {
  if (!typeTabsNode) return;
  typeTabsNode.hidden = !liftCatalogContext;
}

function normalizePagePath(path) {
  path = String(path || "").replace(/^\/portfolio\/site\/liftx(?=\/|$)/, "");
  const clean = `/${String(path || "").replace(/^\/+|\/+$/g, "")}/`;
  return clean === "//" ? "/" : clean;
}

function bindControls() {
  document.querySelectorAll("[data-filter]").forEach((button) => {
    button.addEventListener("click", () => {
      if (button.disabled) return;

      const filter = button.dataset.filter;
      if (filter === "type" && lockedType) return;

      state[filter] = button.dataset.value;

      if (state.location === "indoor" && state.engine === "diesel") {
        state.engine = "all";
      }

      if (filter === "type") {
        state.ranges = {};
        state.options = {};
        if (!usesLiftOnlyFilters()) {
          state.location = "all";
          state.engine = "all";
          syncButtons("location");
          syncButtons("engine");
        }
        rebuildDynamicFilters();
        syncLiftOnlyFilters();
        updateHeroForType();
      }

      syncButtons(filter);
      syncEngineAvailability();
      resetVisibleCount();
      render();
    });
  });

  dynamicFiltersNode.addEventListener("input", (event) => {
    const input = event.target.closest("[data-range-key]");
    if (!input) return;
    updateRangeFromInput(input);
    syncRange(input.dataset.rangeKey);
    resetVisibleCount();
    render();
  });

  dynamicFiltersNode.addEventListener("click", (event) => {
    const button = event.target.closest("[data-option-key]");
    if (!button) return;

    const key = button.dataset.optionKey;
    state.options[key] = button.dataset.value;
    syncOptionButtons(key);
    resetVisibleCount();
    render();
  });

  loadMoreButton.addEventListener("click", () => {
    visibleCount += pageSize;
    render();
  });

  document.querySelectorAll("[data-open-lead]").forEach((button) => {
    button.addEventListener("click", () => openLeadModal(""));
  });

  document.querySelectorAll("[data-close-lead]").forEach((button) => {
    button.addEventListener("click", closeLeadModal);
  });

  cardsNode.addEventListener("click", (event) => {
    const button = event.target.closest("[data-order-id]");
    if (!button) return;

    const item = equipment.find((equipmentItem) => String(equipmentItem.id) === button.dataset.orderId);
    if (!item) return;

    openLeadModal(buildOrderVariant(item));
  });

  leadPhone.addEventListener("input", () => {
    leadPhone.value = formatPhoneInput(leadPhone.value);
  });

  leadForm.addEventListener("submit", submitLead);

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && leadModal.classList.contains("is-open")) {
      closeLeadModal();
    }
  });
}

function initCookieBanner() {
  if (!cookieConsent || !cookieAccept) return;

  const storageKey = "liftx_cookie_accepted";
  const accepted = localStorage.getItem(storageKey) === "true";

  if (!accepted) {
    cookieConsent.hidden = false;
    document.body.classList.add("cookie-open");
  }

  cookieAccept.addEventListener("click", () => {
    localStorage.setItem(storageKey, "true");
    cookieConsent.hidden = true;
    document.body.classList.remove("cookie-open");
  });
}

function initMobileCtaVisibility() {
  if (!mobileCta) return;

  const threshold = 100;
  let ticking = false;

  const update = () => {
    const shouldShow = window.scrollY >= threshold;
    mobileCta.classList.toggle("is-visible", shouldShow);
    ticking = false;
  };

  update();

  window.addEventListener(
    "scroll",
    () => {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(update);
    },
    { passive: true },
  );
}

function rebuildDynamicFilters() {
  activeFilterKeys = getActiveFilterKeys();

  dynamicFiltersNode.innerHTML = activeFilterKeys.length
    ? activeFilterKeys.map(createFilterMarkup).join("")
    : '<p class="filter-card__hint">Выберите категорию, чтобы уточнить параметры техники.</p>';

  activeFilterKeys.forEach((key) => {
    const definition = specDefinitions[key];
    if (definition.kind === "number") syncRange(key);
    if (definition.kind === "option") syncOptionButtons(key);
  });
}

function getActiveFilterKeys() {
  if (state.type !== "all" && equipmentTypes[state.type]) {
    return equipmentTypes[state.type].filters;
  }

  if (liftCatalogContext) {
    return ["height"];
  }

  return [];
}

function createFilterMarkup(key) {
  const definition = specDefinitions[key];
  if (!definition) return "";

  if (definition.kind === "option") {
    return createOptionFilterMarkup(key, definition);
  }

  return createRangeFilterMarkup(key, definition);
}

function createRangeFilterMarkup(key, definition) {
  const bounds = getRangeBounds(key);
  state.ranges[key] = state.ranges[key] || { min: bounds.min, max: bounds.max };
  state.ranges[key].min = clamp(state.ranges[key].min, bounds.min, bounds.max);
  state.ranges[key].max = clamp(state.ranges[key].max, bounds.min, bounds.max);

  if (state.ranges[key].min > state.ranges[key].max) {
    state.ranges[key] = { min: bounds.min, max: bounds.max };
  }

  const disabled = bounds.min === bounds.max ? "disabled" : "";
  const step = getRangeStep(key);

  return `
    <div class="range-filter" data-filter-block="${escapeHtml(key)}">
      <h2>${escapeHtml(definition.label)}</h2>
      <div class="range-filter__values">
        <output data-range-label="${escapeHtml(key)}" data-side="min">0</output>
        <output data-range-label="${escapeHtml(key)}" data-side="max">0</output>
      </div>
      <div class="range-slider">
        <div class="range-slider__track"></div>
        <div class="range-slider__fill" data-range-fill="${escapeHtml(key)}"></div>
        <input data-range-key="${escapeHtml(key)}" data-side="min" type="range" min="${bounds.min}" max="${bounds.max}" value="${state.ranges[key].min}" step="${step}" ${disabled} aria-label="Минимум: ${escapeHtml(definition.label)}">
        <input data-range-key="${escapeHtml(key)}" data-side="max" type="range" min="${bounds.min}" max="${bounds.max}" value="${state.ranges[key].max}" step="${step}" ${disabled} aria-label="Максимум: ${escapeHtml(definition.label)}">
      </div>
      <div class="range-filter__scale" aria-hidden="true">${createRangeScaleMarkup(key)}</div>
    </div>
  `;
}

function createOptionFilterMarkup(key, definition) {
  const values = getOptionValues(key);
  state.options[key] = state.options[key] || "all";

  const buttons = [
    `<button class="is-active" type="button" data-option-key="${escapeHtml(key)}" data-value="all">Не важно</button>`,
    ...values.map((value) => (
      `<button type="button" data-option-key="${escapeHtml(key)}" data-value="${escapeHtml(value)}">${escapeHtml(formatOptionValue(value))}</button>`
    )),
  ].join("");

  return `
    <div class="filter-group" data-filter-block="${escapeHtml(key)}">
      <h2>${escapeHtml(definition.label)}</h2>
      <div class="segmented segmented--dynamic" role="radiogroup" aria-label="${escapeHtml(definition.label)}">
        ${buttons}
      </div>
    </div>
  `;
}

function getFilteredSourceForBounds() {
  return getAvailableEquipment().filter((item) => matchesTypeFilter(item));
}

function getRangeBounds(key) {
  const values = getFilteredSourceForBounds()
    .map((item) => Number(item[key]))
    .filter(Number.isFinite);

  if (!values.length) return { min: 0, max: 0 };

  return {
    min: Math.min(...values),
    max: Math.max(...values),
  };
}

function getRangeStep(key) {
  if (["height", "boomReach", "platformLength", "digDepth", "bucketVolume"].includes(key)) {
    return "0.1";
  }
  return "1";
}

function createRangeScaleMarkup(key) {
  const values = getFilteredSourceForBounds()
    .map((item) => Number(item[key]))
    .filter(Number.isFinite);
  const uniqueValues = [...new Set(values)].sort((a, b) => a - b);

  if (!uniqueValues.length) return "<span>0</span>";

  const maxVisiblePoints = 5;
  const scalePoints = uniqueValues.length <= maxVisiblePoints
    ? uniqueValues
    : uniqueValues.filter((_, index) => (
      index === 0 ||
      index === uniqueValues.length - 1 ||
      index % Math.ceil(uniqueValues.length / (maxVisiblePoints - 1)) === 0
    ));

  return scalePoints.map((point) => `<span>${escapeHtml(formatSpecValue(key, point, false))}</span>`).join("");
}

function getOptionValues(key) {
  const values = getFilteredSourceForBounds()
    .map((item) => item[key])
    .filter((value) => value !== undefined && value !== null && String(value).trim() !== "")
    .flatMap((value) => splitOptionValues(value));

  return [...new Set(values)].sort((a, b) => formatOptionValue(a).localeCompare(formatOptionValue(b), "ru"));
}

function splitOptionValues(value) {
  return String(value)
    .split(/[;,]/)
    .map((part) => part.trim())
    .filter(Boolean);
}

function updateRangeFromInput(input) {
  const key = input.dataset.rangeKey;
  const side = input.dataset.side;
  const bounds = getRangeBounds(key);
  const range = state.ranges[key] || { min: bounds.min, max: bounds.max };
  const step = Number(getRangeStep(key));
  const gap = bounds.min === bounds.max ? 0 : step;

  if (side === "min") {
    range.min = Math.min(Number(input.value), range.max - gap);
  } else {
    range.max = Math.max(Number(input.value), range.min + gap);
  }

  range.min = clamp(roundRangeValue(range.min, step), bounds.min, bounds.max);
  range.max = clamp(roundRangeValue(range.max, step), bounds.min, bounds.max);
  state.ranges[key] = range;

  dynamicFiltersNode.querySelectorAll(`[data-range-key="${key}"]`).forEach((rangeInput) => {
    rangeInput.value = String(state.ranges[key][rangeInput.dataset.side]);
  });
}

function syncRange(key) {
  const bounds = getRangeBounds(key);
  const range = state.ranges[key] || { min: bounds.min, max: bounds.max };
  const minLabel = dynamicFiltersNode.querySelector(`[data-range-label="${key}"][data-side="min"]`);
  const maxLabel = dynamicFiltersNode.querySelector(`[data-range-label="${key}"][data-side="max"]`);
  const fill = dynamicFiltersNode.querySelector(`[data-range-fill="${key}"]`);

  if (minLabel) minLabel.textContent = formatSpecValue(key, range.min);
  if (maxLabel) maxLabel.textContent = formatSpecValue(key, range.max);

  if (!fill) return;

  const span = bounds.max - bounds.min;
  const left = span ? ((range.min - bounds.min) / span) * 100 : 0;
  const right = span ? ((range.max - bounds.min) / span) * 100 : 100;

  fill.style.setProperty("--range-left", `${left}%`);
  fill.style.setProperty("--range-right", `${right}%`);
}

function syncOptionButtons(key) {
  dynamicFiltersNode.querySelectorAll(`[data-option-key="${key}"]`).forEach((button) => {
    button.classList.toggle("is-active", button.dataset.value === state.options[key]);
  });
}

function getAvailableEquipment() {
  return equipment.filter((item) => item.available);
}

function syncButtons(filterName) {
  document.querySelectorAll(`[data-filter="${filterName}"]`).forEach((button) => {
    button.classList.toggle("is-active", button.dataset.value === state[filterName]);
  });
}

function syncEngineAvailability() {
  const dieselButton = document.querySelector('[data-filter="engine"][data-value="diesel"]');
  if (!dieselButton) return;

  const shouldDisableDiesel = usesLiftOnlyFilters() && state.location === "indoor";
  dieselButton.disabled = shouldDisableDiesel;
  dieselButton.classList.toggle("is-active", !shouldDisableDiesel && state.engine === "diesel");

  if (shouldDisableDiesel) {
    syncButtons("engine");
  }
}

function usesLiftOnlyFilters(type = state.type) {
  return liftCatalogContext || type === "scissor" || type === "boom";
}

function syncLiftOnlyFilters() {
  const shouldShow = usesLiftOnlyFilters();

  [locationFilterNode, engineFilterNode].forEach((node) => {
    if (!node) return;
    node.hidden = !shouldShow;
  });
}

function updateHeroForType() {
  if (lockedType) return;
  if (state.type !== "all" && equipmentTypes[state.type]?.hero) {
    heroTitleNode.textContent = equipmentTypes[state.type].hero;
    return;
  }
  heroTitleNode.innerHTML = defaultHeroTitle;
}

function render() {
  const filtered = getAvailableEquipment().filter((item) => {
    const matchesType = matchesTypeFilter(item);
    const matchesEngine = !usesLiftOnlyFilters() || state.engine === "all" || item.engine === state.engine;
    const matchesLocation = !usesLiftOnlyFilters() || state.location !== "indoor" || item.engine !== "diesel";
    const matchesRanges = activeFilterKeys.every((key) => matchesRangeFilter(item, key));
    const matchesOptions = activeFilterKeys.every((key) => matchesOptionFilter(item, key));

    return matchesType && matchesEngine && matchesLocation && matchesRanges && matchesOptions;
  });

  const visibleItems = filtered.slice(0, visibleCount);
  const hasMore = filtered.length > visibleItems.length;
  const availableCount = filtered.filter((item) => item.available).length;

  availableCountNode.textContent = pluralizeMachines(availableCount);
  summaryNode.textContent = buildSummary(filtered.length, visibleItems.length);
  cardsNode.innerHTML = visibleItems.length
    ? visibleItems.map(createCardMarkup).join("")
    : '<div class="empty-state">Подходящая техника не найдена. Измените параметры или выберите другую категорию.</div>';

  loadMoreWrap.hidden = !hasMore;
  loadMoreButton.textContent = `Показать еще ${Math.min(pageSize, filtered.length - visibleItems.length)}`;
}

function matchesTypeFilter(item) {
  if (liftCatalogContext && state.type === "all") {
    return item.type === "scissor" || item.type === "boom";
  }

  return state.type === "all" || item.type === state.type;
}

function matchesRangeFilter(item, key) {
  const definition = specDefinitions[key];
  if (!definition || definition.kind !== "number") return true;

  const value = Number(item[key]);
  if (!Number.isFinite(value)) return true;

  const bounds = getRangeBounds(key);
  const range = state.ranges[key] || { min: bounds.min, max: bounds.max };
  return value >= range.min && value <= range.max;
}

function matchesOptionFilter(item, key) {
  const definition = specDefinitions[key];
  if (!definition || definition.kind !== "option") return true;

  const selected = state.options[key] || "all";
  if (selected === "all") return true;

  return splitOptionValues(item[key]).includes(selected);
}

function resetVisibleCount() {
  visibleCount = pageSize;
}

function createCardMarkup(item) {
  const image = item.image
    ? `<img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.title)}" loading="lazy">`
    : "";
  const specs = getCardSpecKeys(item).map((key) => createSpecMarkup(item, key)).join("");

  return `
    <article class="equipment-card">
      <div class="equipment-card__image">${image}</div>
      <div class="equipment-card__info">
        <span class="badge">Свободна, привезем сегодня</span>
        <h3>${escapeHtml(item.title)}</h3>
        <div class="specs">
          ${specs}
        </div>
      </div>
      <div class="equipment-card__order">
        <div class="price">от <b>${formatPrice(item.price)} ₽</b> в сутки</div>
        <button class="order-button" type="button" data-order-id="${escapeHtml(item.id)}">Заказать</button>
      </div>
    </article>
  `;
}

function getCardSpecKeys(item) {
  const configured = equipmentTypes[item.type]?.specs || [];
  const existing = configured.filter((key) => item[key] !== undefined && item[key] !== null && String(item[key]) !== "");

  if (existing.length) return existing.slice(0, 3);

  return ["height", "capacity", "engine"].filter((key) => item[key] !== undefined && item[key] !== null && String(item[key]) !== "");
}

function createSpecMarkup(item, key) {
  const definition = specDefinitions[key];
  if (!definition) return "";

  return `
    <div class="spec">
      <b>${escapeHtml(formatSpecValue(key, item[key]))}</b>
      <span>${escapeHtml(definition.shortLabel)}</span>
    </div>
  `;
}

function openLeadModal(variant) {
  leadVariant = variant;
  leadTitle.textContent = variant ? "Заказать технику" : "Подобрать технику";
  leadDescription.textContent = variant
    ? "Оставьте телефон, передадим менеджеру выбранную модель и параметры."
    : "Оставьте телефон, менеджер уточнит задачу и подберет подходящую технику.";
  leadStatus.textContent = "";
  leadForm.reset();
  leadModal.classList.add("is-open");
  leadModal.setAttribute("aria-hidden", "false");
  document.body.classList.add("modal-open");
  setTimeout(() => leadPhone.focus(), 50);
}

function closeLeadModal() {
  leadModal.classList.remove("is-open");
  leadModal.setAttribute("aria-hidden", "true");
  document.body.classList.remove("modal-open");
}

async function submitLead(event) {
  event.preventDefault();

  const phone = leadPhone.value.trim();
  const digits = phone.replace(/\D/g, "");

  if (digits.length < 10) {
    leadStatus.textContent = "Введите корректный телефон.";
    return;
  }

  if (!leadConsent.checked) {
    leadStatus.textContent = "Подтвердите согласие на обработку персональных данных.";
    return;
  }

  const submitButton = leadForm.querySelector("button");
  submitButton.disabled = true;
  leadStatus.textContent = "Отправляем...";

  try {
    const response = await fetch("lead.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        phone,
        variant: leadVariant,
        consent: leadConsent.checked,
        url: window.location.href,
        ua: navigator.userAgent,
      }),
    });
    const data = await response.json().catch(() => ({}));

    if (!response.ok || !data.ok) throw new Error(data.error || "Submit failed");

    window.location.assign("spasibo/");
  } catch (error) {
    console.error(error);
    leadStatus.textContent = "Не удалось отправить. Попробуйте еще раз.";
  } finally {
    submitButton.disabled = false;
  }
}

function buildOrderVariant(item) {
  const specs = getCardSpecKeys(item).map((key) => {
    const definition = specDefinitions[key];
    return `${definition.label.toLowerCase()}: ${formatSpecValue(key, item[key])}`;
  });

  return [
    `${item.title}`,
    getTypeLabel(item.type),
    ...specs,
    `цена от ${formatPrice(item.price)} ₽/сутки`,
    `искал: ${buildSearchDescription()}`,
  ].join("; ");
}

function buildSearchDescription() {
  const parts = [];
  parts.push(`тип техники: ${state.type === "all" ? "все" : getTypeLabel(state.type)}`);

  activeFilterKeys.forEach((key) => {
    const definition = specDefinitions[key];
    if (definition.kind === "number") {
      const bounds = getRangeBounds(key);
      const range = state.ranges[key] || { min: bounds.min, max: bounds.max };
      parts.push(`${definition.label.toLowerCase()} ${formatSpecValue(key, range.min)}-${formatSpecValue(key, range.max)}`);
    } else if (state.options[key] && state.options[key] !== "all") {
      parts.push(`${definition.label.toLowerCase()}: ${formatOptionValue(state.options[key])}`);
    }
  });

  if (usesLiftOnlyFilters()) {
    parts.push(`двигатель: ${state.engine === "all" ? "не важно" : formatOptionValue(state.engine)}`);
    parts.push(`место работы: ${getLocationLabel(state.location)}`);
  }
  return parts.join(", ");
}

function getLocationLabel(location) {
  if (location === "indoor") return "в помещении";
  if (location === "outdoor") return "на улице";
  return "не важно";
}

function formatPhoneInput(value) {
  let digits = value.replace(/\D/g, "");

  if (digits.startsWith("8")) digits = `7${digits.slice(1)}`;
  if (!digits.startsWith("7")) digits = `7${digits}`;

  digits = digits.slice(0, 11);
  const body = digits.slice(1);
  let result = "+7";

  if (body.length > 0) result += ` (${body.slice(0, 3)}`;
  if (body.length >= 3) result += ")";
  if (body.length > 3) result += ` ${body.slice(3, 6)}`;
  if (body.length > 6) result += `-${body.slice(6, 8)}`;
  if (body.length > 8) result += `-${body.slice(8, 10)}`;

  return result;
}

function buildSummary(count, shownCount = count) {
  const active = [];

  if (state.type !== "all") active.push(getTypeLabel(state.type));
  activeFilterKeys.forEach((key) => {
    const definition = specDefinitions[key];
    if (definition.kind === "number") {
      const bounds = getRangeBounds(key);
      const range = state.ranges[key] || { min: bounds.min, max: bounds.max };
      active.push(`${definition.shortLabel.toLowerCase()} ${formatSpecValue(key, range.min)}-${formatSpecValue(key, range.max)}`);
    } else if (state.options[key] && state.options[key] !== "all") {
      active.push(`${definition.shortLabel.toLowerCase()}: ${formatOptionValue(state.options[key])}`);
    }
  });
  if (usesLiftOnlyFilters()) {
    if (state.location === "indoor") active.push("для помещения");
    if (state.location === "outdoor") active.push("для улицы");
    if (state.engine !== "all") active.push(formatOptionValue(state.engine).toLowerCase());
  }

  const shown = count > shownCount ? `Показано ${shownCount} из ${count}. ` : "";
  return `${shown}${pluralizeFound(count)}: ${active.join(", ") || "все категории"}`;
}

function pluralizeMachines(count) {
  return `${count} ${pluralize(count, ["машина", "машины", "машин"])}`;
}

function pluralizeFound(count) {
  return `${count} ${pluralize(count, ["вариант", "варианта", "вариантов"])}`;
}

function pluralize(count, words) {
  const mod10 = count % 10;
  const mod100 = count % 100;

  if (mod10 === 1 && mod100 !== 11) return words[0];
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return words[1];
  return words[2];
}

function getTypeLabel(type) {
  return equipmentTypes[type]?.label || type;
}

function formatSpecValue(key, value, withUnit = true) {
  if (key === "engine" || specDefinitions[key]?.kind === "option") {
    return formatOptionValue(value);
  }

  const numericValue = Number(value);
  const formatted = Number.isFinite(numericValue)
    ? new Intl.NumberFormat("ru-RU", { maximumFractionDigits: 1 }).format(numericValue)
    : String(value || "");
  const unit = specDefinitions[key]?.unit;

  return withUnit && unit ? `${formatted} ${unit}` : formatted;
}

function formatOptionValue(value) {
  const raw = String(value || "").trim();
  if (!raw) return "Не указано";

  const parts = splitOptionValues(raw);
  if (parts.length > 1) {
    return parts.map((part) => valueLabels[part] || part).join(", ");
  }

  return valueLabels[raw] || raw;
}

function formatPrice(value) {
  return new Intl.NumberFormat("ru-RU").format(value);
}

function clamp(value, min, max) {
  return Math.min(Math.max(value, min), max);
}

function roundRangeValue(value, step) {
  if (step >= 1) return Math.round(value);
  return Math.round(value * 10) / 10;
}

function escapeHtml(value) {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}
