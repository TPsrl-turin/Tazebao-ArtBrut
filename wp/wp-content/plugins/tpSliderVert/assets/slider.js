document.addEventListener('DOMContentLoaded', function () {
  const tracks = document.querySelectorAll('.tp-slide-track');

  tracks.forEach(track => {
    const slides = Array.from(track.children);
    slides.forEach(slide => {
      const clone = slide.cloneNode(true);
      track.appendChild(clone);
    });
  });
});
