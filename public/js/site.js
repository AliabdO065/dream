(function () {
    var root = document.documentElement;
    root.classList.add('js'); // enables the scroll-reveal styles; without JS everything is simply visible

    // Navigation: collapse into the hamburger menu whenever the links do not fit on one line
    var header = document.querySelector('.site-header');
    var btn = document.querySelector('[data-menu-btn]');
    var bar = document.querySelector('.site-header .bar');
    function fitNav() {
        if (!header || !bar) return;
        header.classList.remove('compact', 'menu-open');
        if (bar.scrollWidth > bar.clientWidth + 1) header.classList.add('compact');
        if (btn) btn.setAttribute('aria-expanded', 'false');
    }
    fitNav();
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(fitNav);
    var resizeTimer;
    window.addEventListener('resize', function () { clearTimeout(resizeTimer); resizeTimer = setTimeout(fitNav, 120); });

    // Menu button
    if (header && btn) {
        btn.addEventListener('click', function () {
            var open = header.classList.toggle('menu-open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        header.addEventListener('click', function (e) {
            if (e.target.closest('.nav a')) { header.classList.remove('menu-open'); btn.setAttribute('aria-expanded', 'false'); }
        });
    }

    // Language switcher: a native <details>, so it works with no JS at all; this just makes it behave
    // like a normal dropdown (closes on an outside click or Escape) instead of staying open until re-clicked.
    // Two instances can exist on one page (a bar-level one next to the hamburger, and the one inline in
    // the desktop nav) — each opens/closes independently, so all of them get this treatment.
    var langSwitches = document.querySelectorAll('[data-lang-switch]');
    if (langSwitches.length) {
        document.addEventListener('click', function (e) {
            langSwitches.forEach(function (el) { if (el.open && !el.contains(e.target)) el.removeAttribute('open'); });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            langSwitches.forEach(function (el) { if (el.open) el.removeAttribute('open'); });
        });
    }

    // Generic marquee: any .gallery/.stats a theme opts in via `--marquee:1` (CSS, not JS) gets its
    // children cloned once and set auto-scrolling in a seamless loop. Skipped entirely under reduced motion.
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reducedMotion) {
        document.querySelectorAll('.gallery, .stats').forEach(function (el) {
            if (getComputedStyle(el).getPropertyValue('--marquee').trim() !== '1') return;
            var kids = Array.prototype.slice.call(el.children);
            if (kids.length < 2) return;
            kids.forEach(function (k) { el.appendChild(k.cloneNode(true)); });
            el.classList.add('is-marquee');
        });
    }

    // Generic carousel: any .gallery/.items a theme opts in via `--carousel:1` becomes a one-at-a-time,
    // auto-advancing slider with a dot pager, built from its existing children — same data, no new markup.
    document.querySelectorAll('.gallery, .items').forEach(function (el) {
        if (getComputedStyle(el).getPropertyValue('--carousel').trim() !== '1') return;
        var slides = Array.prototype.slice.call(el.children);
        if (slides.length < 2) return;
        el.classList.add('is-carousel');

        var dots = document.createElement('div');
        dots.className = 'carousel-dots';
        var idx = 0;
        function go(i) {
            idx = (i + slides.length) % slides.length;
            slides.forEach(function (s, n) { s.classList.toggle('on', n === idx); });
            Array.prototype.slice.call(dots.children).forEach(function (d, n) { d.classList.toggle('on', n === idx); });
        }
        slides.forEach(function (_, i) {
            var d = document.createElement('button');
            d.type = 'button';
            d.setAttribute('aria-label', 'Slide ' + (i + 1));
            d.addEventListener('click', function () { go(i); });
            dots.appendChild(d);
        });
        el.insertAdjacentElement('afterend', dots);
        go(0);

        if (!reducedMotion) {
            var interval = parseInt(getComputedStyle(el).getPropertyValue('--carousel-interval'), 10) || 5000;
            var timer;
            function start() { timer = setInterval(function () { go(idx + 1); }, interval); }
            function stop() { clearInterval(timer); }
            el.addEventListener('mouseenter', stop);
            el.addEventListener('mouseleave', start);
            el.addEventListener('focusin', stop);
            el.addEventListener('focusout', start);
            start();
        }
    });

    // Scroll reveal
    var els = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
        els.forEach(function (el) { io.observe(el); });
    } else {
        els.forEach(function (el) { el.classList.add('in'); });
    }

    // After a contact-form submit the server redirects to ?sent=<section id>.
    // The page itself is cached and cookie-free, so the thank-you state is switched on here.
    var m = location.search.match(/[?&]sent=(\d+)/);
    if (m) {
        var thanks = document.querySelector('[data-thanks="' + m[1] + '"]');
        var form = document.querySelector('[data-form="' + m[1] + '"]');
        if (thanks) thanks.hidden = false;
        if (form) form.style.display = 'none';
    }
})();
