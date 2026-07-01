(function() {
  function initCarousel(el) {
    var raw = el.getAttribute('data-images');
    if (!raw) return;

    var images;
    try { images = JSON.parse(raw); } catch (e) { return; }
    if (!Array.isArray(images) || images.length === 0) return;

    var img = el.querySelector('.img-carousel-img');
    var prev = el.querySelector('.img-carousel-prev');
    var next = el.querySelector('.img-carousel-next');
    var counter = el.querySelector('.img-carousel-counter');
    var idx = 0;

    function show(i) {
      idx = (i + images.length) % images.length;
      if (img) {
        img.src = images[idx];
        img.alt = img.alt || '';
      }
      if (counter) counter.textContent = (idx + 1) + '/' + images.length;
      el.dispatchEvent(new CustomEvent('carouselchange', {
        bubbles: true,
        detail: { index: idx, src: images[idx] }
      }));
    }

    if (prev) {
      prev.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        show(idx - 1);
      });
    }

    if (next) {
      next.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        show(idx + 1);
      });
    }

    show(0);
    el._carouselShow = show;
  }

  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.img-carousel[data-images]').forEach(initCarousel);
  });
})();
