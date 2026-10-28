(function () {
  const carousels = Array.from(document.querySelectorAll('[data-projects-carousel]'));

  if (!carousels.length) {
    return;
  }

  const initCarousel = (root) => {
    const viewport = root.querySelector('[data-projects-viewport]');
    const track = root.querySelector('[data-projects-track]');
    const slides = Array.from(root.querySelectorAll('.projects-carousel__slide'));
    const dots = Array.from(root.querySelectorAll('[data-projects-dot]'));
    const prev = root.querySelector('[data-projects-prev]');
    const next = root.querySelector('[data-projects-next]');

    if (!viewport || !track || !slides.length) {
      return;
    }

    let activeIndex = 0;
    let startX = null;

    const setActive = (index) => {
      activeIndex = (index + slides.length) % slides.length;

      slides.forEach((slide, slideIndex) => {
        slide.classList.toggle('is-active', slideIndex === activeIndex);
      });

      dots.forEach((dot, dotIndex) => {
        dot.classList.toggle('is-active', dotIndex === activeIndex);
      });

      const activeSlide = slides[activeIndex];
      const slideCenter = activeSlide.offsetLeft + activeSlide.offsetWidth / 2;
      const viewportCenter = viewport.clientWidth / 2;

      track.style.transform = `translateX(${viewportCenter - slideCenter}px)`;
    };

    prev?.addEventListener('click', () => setActive(activeIndex - 1));
    next?.addEventListener('click', () => setActive(activeIndex + 1));

    dots.forEach((dot) => {
      dot.addEventListener('click', () => setActive(Number(dot.dataset.projectsDot)));
    });

    viewport.addEventListener('pointerdown', (event) => {
      startX = event.clientX;
    });

    viewport.addEventListener('pointerup', (event) => {
      if (startX === null) {
        return;
      }

      const distance = event.clientX - startX;
      startX = null;

      if (Math.abs(distance) < 40) {
        return;
      }

      setActive(activeIndex + (distance < 0 ? 1 : -1));
    });

    window.addEventListener('resize', () => setActive(activeIndex));
    requestAnimationFrame(() => setActive(slides.length > 2 ? 1 : 0));
  };

  carousels.forEach(initCarousel);
}());
