(function () {
  const root = document.querySelector('[data-faq]');

  if (!root) {
    return;
  }

  const items = Array.from(root.querySelectorAll('.faq-item'));

  const setItemState = (item, isOpen) => {
    const button = item.querySelector('[data-faq-button]');
    const contentId = button?.getAttribute('aria-controls');
    const content = contentId ? document.getElementById(contentId) : null;

    item.classList.toggle('is-open', isOpen);

    if (button) {
      button.setAttribute('aria-expanded', String(isOpen));
    }

    if (content) {
      content.hidden = !isOpen;
    }
  };

  root.addEventListener('click', (event) => {
    const button = event.target.closest('[data-faq-button]');

    if (!button || !root.contains(button)) {
      return;
    }

    const activeItem = button.closest('.faq-item');
    const shouldOpen = button.getAttribute('aria-expanded') !== 'true';

    items.forEach((item) => {
      setItemState(item, item === activeItem && shouldOpen);
    });
  });
}());
