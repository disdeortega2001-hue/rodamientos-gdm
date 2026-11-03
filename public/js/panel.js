(function () {
    'use strict';

    document.addEventListener('submit', function (event) {
        if (event.defaultPrevented) { return; }
        event.target.querySelectorAll('button:not([type="button"])').forEach(function (button) {
            button.disabled = true;
        });
    });

    window.addEventListener('pageshow', function () {
        document.querySelectorAll('form button[disabled]').forEach(function (button) {
            button.disabled = false;
        });
    });
})();
