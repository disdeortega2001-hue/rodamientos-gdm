(function () {
    'use strict';

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
        return;
    }

    var selector = '.section-head, .section-title, .product-card, .value-strip li, .help-split img, .help-split-inner > div, .steps li, .guide-grid > *, .guide-table, .spec-table > div, .contact-list, .help-block p, .help-block .btn, .empty-state';
    var items = Array.prototype.slice.call(document.querySelectorAll(selector));
    var viewportBottom = window.innerHeight;

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) { return; }
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, { rootMargin: '0px 0px -6% 0px', threshold: 0.05 });

    items.forEach(function (item) {
        // What is already on screen stays put, so the page never loads with blank areas.
        if (item.getBoundingClientRect().top < viewportBottom) { return; }

        var siblings = item.parentElement ? Array.prototype.indexOf.call(item.parentElement.children, item) : 0;
        item.style.setProperty('--reveal-delay', Math.min(siblings % 4, 3) * 70 + 'ms');
        item.classList.add('reveal');
        observer.observe(item);
    });
})();
