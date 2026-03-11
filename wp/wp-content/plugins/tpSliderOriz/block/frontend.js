document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.tp-slider-oriz.splide').forEach(function (el) {
    const images = el.querySelectorAll('img');
    let loaded = 0;

    if (images.length === 0) {
      initSplide(el);
      return;
    }

    images.forEach(img => {
      if (img.complete) {
        loaded++;
      } else {
        img.addEventListener('load', () => {
          loaded++;
          if (loaded === images.length) {
            initSplide(el);
          }
        });
        img.addEventListener('error', () => {
          loaded++;
          if (loaded === images.length) {
            initSplide(el);
          }
        });
      }
    });

    if (loaded === images.length) {
      initSplide(el);
    }

    function initSplide(target) {
      new Splide(target, {
        type: 'loop',
        autoWidth: true,
        gap: '1rem',
        arrows: false,
        pagination: false,
        drag: 'free',
        focus: 'center',
        autoScroll: {
          speed: 1,
          pauseOnHover: true,
          pauseOnFocus: false,
        },
      }).mount({ AutoScroll: window.splide.Extensions.AutoScroll });
    }
  });
});
