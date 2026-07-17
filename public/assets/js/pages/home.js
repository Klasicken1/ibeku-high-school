/* ============================================================
   IBEKU HIGH SCHOOL — HOMEPAGE JS
   File: public/assets/js/pages/home.js

   Hero carousel: auto-rotates through all .hero__slide elements
   and their matching .hero__dot buttons. Works with any number
   of slides (driven by hero_slides table via index.php), not
   hardcoded to a fixed count.
   ============================================================ */

'use strict';

(function initHeroCarousel() {
  var slides = document.querySelectorAll('.hero__slide');
  var dots   = document.querySelectorAll('.hero__dot');

  if (!slides.length || !dots.length) return;

  var current      = 0;
  var intervalMs   = 7000;
  var timer        = null;

  function goToSlide(index) {
    if (index === current) return;

    slides[current].classList.remove('active');
    slides[current].setAttribute('aria-hidden', 'true');
    dots[current].classList.remove('active');
    dots[current].setAttribute('aria-selected', 'false');

    current = index;

    slides[current].classList.add('active');
    slides[current].setAttribute('aria-hidden', 'false');
    dots[current].classList.add('active');
    dots[current].setAttribute('aria-selected', 'true');
  }

  function nextSlide() {
    var next = (current + 1) % slides.length;
    goToSlide(next);
  }

  function startAutoRotate() {
    stopAutoRotate();
    if (slides.length > 1) {
      timer = setInterval(nextSlide, intervalMs);
    }
  }

  function stopAutoRotate() {
    if (timer) {
      clearInterval(timer);
      timer = null;
    }
  }

  dots.forEach(function (dot, index) {
    dot.addEventListener('click', function () {
      goToSlide(index);
      startAutoRotate(); // reset the timer so it doesn't jump right after a manual click
    });
  });

  // Pause on hover/focus within the hero — resumes on mouse leave
  var heroSection = document.querySelector('.hero');
  if (heroSection) {
    heroSection.addEventListener('mouseenter', stopAutoRotate);
    heroSection.addEventListener('mouseleave', startAutoRotate);
  }

  startAutoRotate();
}());