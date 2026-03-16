document.addEventListener("DOMContentLoaded", () => {

  // =========================
  // TESTIMONIAL SLIDER (.slide)
  // =========================
  const slides = document.querySelectorAll("#testimonialSlider .slide");
  const prevBtn = document.getElementById("prevBtn");
  const nextBtn = document.getElementById("nextBtn");

  let index = 0;
  let timer = null;

  function showSlide(i) {
    slides.forEach(s => s.classList.remove("active"));
    slides[i].classList.add("active");
  }

  function nextSlide() {
    index = (index + 1) % slides.length;
    showSlide(index);
  }

  function prevSlide() {
    index = (index - 1 + slides.length) % slides.length;
    showSlide(index);
  }

  function startAuto() {
    if (timer) clearInterval(timer);
    timer = setInterval(nextSlide, 4500);
  }

  if (slides.length) {
    showSlide(index);
    startAuto();

    nextBtn && nextBtn.addEventListener("click", () => {
      nextSlide();
      startAuto();
    });

    prevBtn && prevBtn.addEventListener("click", () => {
      prevSlide();
      startAuto();
    });
  }

  // =========================
  // FADE-IN ON SCROLL (.reveal)
  // =========================
  const items = document.querySelectorAll(".reveal");

  if (items.length) {
    items.forEach(el => el.classList.add("js-ready")); // hide only after JS starts

    const io = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) entry.target.classList.add("show");
      });
    }, { threshold: 0.15 });

    items.forEach(el => io.observe(el));
  }

});
