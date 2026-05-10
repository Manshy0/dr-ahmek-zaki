/*!
 * Dr. Ahmed Zaki — Counter animation + Elementor invisible reveal
 * Standalone replacement for Elementor's frontend.js animations
 * (works on the static HTML export without jQuery / Elementor JS)
 */
(function () {
  'use strict';

  if (typeof window === 'undefined' || typeof document === 'undefined') return;

  // ---------- helpers ----------
  function easeOutCubic(t) {
    return 1 - Math.pow(1 - t, 3);
  }

  function formatNumber(value, delimiter) {
    var sep = delimiter || '';
    var v = Math.round(value);
    if (!sep) return String(v);
    return String(v).replace(/\B(?=(\d{3})+(?!\d))/g, sep);
  }

  function animateNumber(el, from, to, duration, delimiter) {
    if (el.dataset.izRunning === '1' || el.dataset.izDone === '1') return;
    el.dataset.izRunning = '1';

    var start = null;
    var dur = Math.max(200, Number(duration) || 2000);

    function step(ts) {
      if (start === null) start = ts;
      var elapsed = ts - start;
      var progress = Math.min(1, elapsed / dur);
      var eased = easeOutCubic(progress);
      var current = from + (to - from) * eased;
      el.textContent = formatNumber(current, delimiter);
      if (progress < 1) {
        window.requestAnimationFrame(step);
      } else {
        el.textContent = formatNumber(to, delimiter);
        el.dataset.izRunning = '0';
        el.dataset.izDone = '1';
      }
    }
    window.requestAnimationFrame(step);
  }

  // ---------- Elementor counters ----------
  function initElementorCounter(el) {
    var to = parseFloat(el.getAttribute('data-to-value')) || 0;
    var from = parseFloat(el.getAttribute('data-from-value')) || 0;
    var duration = parseInt(el.getAttribute('data-duration'), 10) || 2000;
    var delimiter = el.getAttribute('data-delimiter') || '';
    el.textContent = formatNumber(from, delimiter);
    animateNumber(el, from, to, duration, delimiter);
  }

  // ---------- Jet/Crocoblock fun-fact + JKit fun-fact ----------
  function initJkitNumber(el) {
    var to = parseFloat(el.getAttribute('data-value') || el.getAttribute('data-to-value')) || 0;
    var duration = parseInt(el.getAttribute('data-animation-duration') || el.getAttribute('data-duration'), 10) || 2000;
    el.textContent = '0';
    animateNumber(el, 0, to, duration, '');
  }

  // ---------- Elementor invisible reveal ----------
  // Elementor adds the class `elementor-invisible` and expects its JS
  // to add the chosen entrance animation class (fadeInUp, etc.).
  // We just remove `elementor-invisible` and add `animated` + the
  // animation name from `data-settings._animation` if present.
  function revealElementorInvisible(el) {
    if (!el.classList.contains('elementor-invisible')) return;
    var animationName = '';
    var raw = el.getAttribute('data-settings');
    if (raw) {
      try {
        var decoded = raw.replace(/&quot;/g, '"');
        var settings = JSON.parse(decoded);
        if (settings && settings._animation) animationName = String(settings._animation);
      } catch (e) { /* ignore */ }
    }
    el.classList.remove('elementor-invisible');
    if (animationName) {
      el.classList.add('animated', animationName);
    }
  }

  // ---------- main ----------
  function start() {
    var counters = document.querySelectorAll('.elementor-counter-number');
    var jkitNumbers = document.querySelectorAll('.jkit-fun-fact .number, .jeg-elementor-kit .number');
    var invisibles = document.querySelectorAll('.elementor-invisible');

    var hasIO = 'IntersectionObserver' in window;

    if (!hasIO) {
      // Fallback: animate immediately
      counters.forEach(initElementorCounter);
      jkitNumbers.forEach(initJkitNumber);
      invisibles.forEach(revealElementorInvisible);
      return;
    }

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var t = entry.target;
        if (t.classList.contains('elementor-counter-number')) {
          initElementorCounter(t);
        } else if (
          t.classList.contains('number') &&
          (t.closest && (t.closest('.jkit-fun-fact') || t.closest('.jeg-elementor-kit')))
        ) {
          initJkitNumber(t);
        } else if (t.classList.contains('elementor-invisible')) {
          revealElementorInvisible(t);
        }
        io.unobserve(t);
      });
    }, { threshold: 0.2, rootMargin: '0px 0px -10% 0px' });

    counters.forEach(function (el) { io.observe(el); });
    jkitNumbers.forEach(function (el) { io.observe(el); });
    invisibles.forEach(function (el) { io.observe(el); });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
