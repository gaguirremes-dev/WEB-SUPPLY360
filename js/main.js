/* ============================================================
   Supply360 — Main JS
   Stack: Lenis · GSAP · ScrollTrigger · SplitText
   ============================================================ */

/* ── Smooth Scroll (Lenis) ──────────────────────────────── */
let lenis;

function initLenis() {
  if (typeof Lenis === 'undefined') return;
  lenis = new Lenis({
    duration: 1.15,
    easing: t => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
    smoothWheel: true,
  });
  function raf(time) {
    lenis.raf(time);
    requestAnimationFrame(raf);
  }
  requestAnimationFrame(raf);

  // Sync with GSAP ticker
  gsap.ticker.add(t => lenis && lenis.raf(t * 1000));
  gsap.ticker.lagSmoothing(0);
  lenis.on('scroll', ScrollTrigger.update);
}

/* ── GSAP ───────────────────────────────────────────────── */
gsap.registerPlugin(ScrollTrigger);
if (typeof SplitText !== 'undefined') gsap.registerPlugin(SplitText);

/* ── Scroll Progress Bar ────────────────────────────────── */
function initProgressBar() {
  const bar = document.getElementById('scroll-progress');
  if (!bar) return;
  window.addEventListener('scroll', () => {
    const scrolled = window.scrollY;
    const total    = document.body.scrollHeight - window.innerHeight;
    bar.style.width = (scrolled / total * 100) + '%';
  }, { passive: true });
}


/* ── Magnetic Buttons ───────────────────────────────────── */
function initMagnetic() {
  document.querySelectorAll('.magnetic').forEach(wrap => {
    const inner = wrap.querySelector('.btn');
    if (!inner) return;

    wrap.addEventListener('mousemove', e => {
      const r  = wrap.getBoundingClientRect();
      const dx = (e.clientX - r.left - r.width  / 2) * .38;
      const dy = (e.clientY - r.top  - r.height / 2) * .38;
      gsap.to(inner, { x: dx, y: dy, duration: .4, ease: 'power2.out' });
    });

    wrap.addEventListener('mouseleave', () => {
      gsap.to(inner, { x: 0, y: 0, duration: .6, ease: 'elastic.out(1,.5)' });
    });
  });
}

/* ── Navbar ─────────────────────────────────────────────── */
function initNav() {
  const nav     = document.getElementById('navbar');
  const ham     = document.querySelector('.nav-ham');
  const menu    = document.getElementById('mobile-menu');
  const overlay = document.querySelector('.mob-overlay');
  if (!nav) return;

  window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 60);
  }, { passive: true });

  function toggleMenu(state) {
    ham.classList.toggle('open', state);
    menu.classList.toggle('open', state);
    overlay.classList.toggle('active', state);
    document.body.style.overflow = state ? 'hidden' : '';
  }

  ham.addEventListener('click', () => toggleMenu(!menu.classList.contains('open')));
  overlay.addEventListener('click', () => toggleMenu(false));
  menu.querySelectorAll('.mob-link, .btn').forEach(el =>
    el.addEventListener('click', () => toggleMenu(false))
  );
}

/* ── Hero ────────────────────────────────────────────────── */
function initHero() {
  const titleEl = document.querySelector('.hero-title');
  if (!titleEl) return;

  const tl = gsap.timeline({ defaults: { ease: 'power4.out' } });

  // Split title into lines
  if (typeof SplitText !== 'undefined') {
    const split = new SplitText(titleEl, { type: 'lines', linesClass: 'line' });
    split.lines.forEach(line => {
      const wrap = document.createElement('div');
      wrap.style.overflow = 'hidden';
      line.parentNode.insertBefore(wrap, line);
      wrap.appendChild(line);
    });
    tl.from(split.lines, { yPercent: 108, duration: 1.1, stagger: .1 }, '.3');
  } else {
    tl.from(titleEl, { y: 40, opacity: 0, duration: 1 }, '.3');
  }

  tl
    .from('.hero-badge', { opacity: 0, y: 20, duration: .7 }, 0)
    .from('.hero-sub',   { opacity: 0, y: 24, duration: .8 }, .5)
    .from('.hero-ctas .btn', { opacity: 0, y: 20, stagger: .1, duration: .6 }, .7)
    .from('.hero-dash',  { opacity: 0, x: 30, y: 20, duration: 1, ease: 'power3.out' }, .6)
    .from('.hero-scroll', { opacity: 0, duration: .5 }, 1.1);

  // Animate hero dashboard bars after load
  setTimeout(() => {
    document.querySelectorAll('.dash-bar-fill').forEach(el => el.classList.add('loaded'));
  }, 1400);
}

/* ── Flujo de Operación — Alternating Journey ───────────── */
function initStorytelling() {
  const rows = document.querySelectorAll('.fj-row');
  if (!rows.length) return;

  rows.forEach(row => {
    const text  = row.querySelector('.fj-text');
    const photo = row.querySelector('.fj-photo');
    const badge = row.querySelector('.fj-icon-badge');
    const isAlt = row.classList.contains('fj-row--alt');

    // Set initial hidden state
    gsap.set(text,  { opacity: 0, x: isAlt ? 60 : -60 });
    gsap.set(photo, { opacity: 0, x: isAlt ? -60 : 60 });

    ScrollTrigger.create({
      trigger: row,
      start: 'top 78%',
      once: true,
      onEnter() {
        gsap.to(text,  { opacity:1, x:0, duration:1,   ease:'power3.out' });
        gsap.to(photo, { opacity:1, x:0, duration:1.1, ease:'power3.out', delay:.08 });
        if (badge) {
          gsap.fromTo(badge,
            { scale:.3, opacity:0, rotate:-20 },
            { scale:1,  opacity:1, rotate:0,  duration:.85, ease:'back.out(1.8)', delay:.3 }
          );
          setTimeout(() => badge.classList.add('is-active'), 1000);
        }
      }
    });
  });
}

/* ── Services ───────────────────────────────────────────── */
function initServices() {
  const cards = document.querySelectorAll('.svc-card');
  if (!cards.length) return;

  gsap.set(cards, { opacity: 0, y: 64 });

  ScrollTrigger.create({
    trigger: '.svc-grid',
    start: 'top 80%',
    once: true,
    onEnter() {
      gsap.to(cards, {
        opacity: 1, y: 0, duration: .9, stagger: .14, ease: 'power3.out'
      });
    }
  });
}

/* ── Platform / Dashboard section ──────────────────────── */
function initPlatform() {
  const tl = gsap.timeline({
    scrollTrigger: { trigger: '#plataforma', start: 'top 64%' }
  });
  tl
    .from('.plat-text',   { x: -48, opacity: 0, duration: .9, ease: 'power3.out', clearProps: 'transform,opacity' })
    .from('.os-panel',    { x:  48, opacity: 0, duration: .9, ease: 'power3.out', clearProps: 'transform,opacity' }, '<.1')
    .from('.os-kpi',      { y: 20, opacity: 0, stagger: .08, duration: .6, clearProps: 'transform,opacity' }, '-=.4')
    .from('.os-bar-fill', {
      scaleX: 0, stagger: .12, duration: 1.1, ease: 'power3.out'
    }, '-=.4');
}

/* ── KPI Counters ───────────────────────────────────────── */
function initKPIs() {
  const kpiItems = document.querySelectorAll('.kpi-item');
  gsap.set(kpiItems, { opacity: 0, y: 36 });
  ScrollTrigger.create({
    trigger: '#kpis', start: 'top 80%', once: true,
    onEnter() {
      gsap.to(kpiItems, { opacity: 1, y: 0, stagger: .1, duration: .8, ease: 'power3.out' });
    }
  });

  document.querySelectorAll('.kpi-count').forEach(el => {
    const target = +el.dataset.target;
    ScrollTrigger.create({
      trigger: el, start: 'top 85%', once: true,
      onEnter() {
        gsap.to({ val: 0 }, {
          val: target, duration: 2.2, ease: 'power2.out',
          onUpdate() { el.textContent = Math.round(this.targets()[0].val).toLocaleString(); }
        });
      }
    });
  });
}

/* ── Use Cases ──────────────────────────────────────────── */
function initCasos() {
  document.querySelectorAll('.caso-block').forEach(block => {
    gsap.set(block, { opacity: 0, y: 48 });
    ScrollTrigger.create({
      trigger: block, start: 'top 80%', once: true,
      onEnter() {
        gsap.to(block, { opacity: 1, y: 0, duration: .9, ease: 'power3.out' });
      }
    });
  });
}

/* ── CTA Final ──────────────────────────────────────────── */
function initCTA() {
  const ctaH = document.querySelector('.cta-inner h2');
  if (!ctaH) return;

  if (typeof SplitText !== 'undefined') {
    const split = new SplitText(ctaH, { type: 'lines', linesClass: 'line' });
    split.lines.forEach(line => {
      const wrap = document.createElement('div');
      wrap.style.overflow = 'hidden';
      line.parentNode.insertBefore(wrap, line);
      wrap.appendChild(line);
    });
    gsap.from(split.lines, {
      yPercent: 108, duration: 1, stagger: .09, ease: 'power4.out',
      scrollTrigger: { trigger: '.cta-inner', start: 'top 76%' }
    });
  } else {
    gsap.from(ctaH, {
      y: 40, opacity: 0, duration: 1, ease: 'power3.out',
      scrollTrigger: { trigger: '.cta-inner', start: 'top 76%' }
    });
  }

  gsap.from(['.cta-inner p', '.cta-actions'], {
    y: 28, opacity: 0, stagger: .14, duration: .8, ease: 'power3.out',
    scrollTrigger: { trigger: '.cta-inner', start: 'top 68%' }
  });
}

/* ── Generic section reveals ────────────────────────────── */
function initReveal() {
  document.querySelectorAll('.js-up').forEach(el => {
    gsap.to(el, {
      y: 0, opacity: 1, duration: .85, ease: 'power3.out',
      scrollTrigger: { trigger: el, start: 'top 82%' }
    });
  });

  document.querySelectorAll('.js-fade').forEach(el => {
    gsap.to(el, {
      opacity: 1, duration: .8, ease: 'power2.out',
      scrollTrigger: { trigger: el, start: 'top 82%' }
    });
  });
}

/* ── Parallax decorations ───────────────────────────────── */
function initParallax() {
  gsap.utils.toArray('[data-parallax]').forEach(el => {
    const speed = +(el.dataset.parallax) || .2;
    gsap.to(el, {
      yPercent: speed * -100,
      ease: 'none',
      scrollTrigger: { trigger: el, start: 'top bottom', end: 'bottom top', scrub: true }
    });
  });
}

/* ── Nav active link highlight ──────────────────────────── */
function initNavActive() {
  const sections = document.querySelectorAll('section[id]');
  const links    = document.querySelectorAll('.nav-link');

  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const id = entry.target.id;
        links.forEach(link => {
          link.style.color = link.getAttribute('href') === '#' + id
            ? '#F65726' : '';
        });
      }
    });
  }, { threshold: .35 });

  sections.forEach(s => observer.observe(s));
}

/* ── Init ────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  initLenis();
  initProgressBar();
  initMagnetic();
  initNav();
});

window.addEventListener('load', () => {
  initHero();
  initStorytelling();
  initServices();
  initPlatform();
  initKPIs();
  initCasos();
  initCTA();
  initReveal();
  initParallax();
  initNavActive();
  ScrollTrigger.refresh();
});
