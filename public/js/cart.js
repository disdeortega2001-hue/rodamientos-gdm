(function () {
    'use strict';

    var KEY = 'gdm.cart';
    var TOKEN_KEY = 'gdm.cart.token';
    var memory = {};

    function read(key) {
        try {
            var value = localStorage.getItem(key);
            return value === null ? memory[key] || null : value;
        } catch (e) {
            return memory[key] || null;
        }
    }

    function write(key, value) {
        memory[key] = value;
        try {
            if (value === null) {
                localStorage.removeItem(key);
            } else {
                localStorage.setItem(key, value);
            }
        } catch (e) { /* the in-memory copy keeps the cart until reload */ }
    }

    function getCart() {
        try {
            var parsed = JSON.parse(read(KEY) || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) {
            return [];
        }
    }

    function setCart(cart) {
        write(KEY, JSON.stringify(cart));
        write(TOKEN_KEY, null); // a changed cart is a new order
        updateCount();
    }

    function updateCount() {
        var count = getCart().reduce(function (sum, line) { return sum + line.quantity; }, 0);
        document.querySelectorAll('[data-cart-count]').forEach(function (el) {
            if (el.textContent !== String(count) && el.dataset.ready) {
                el.classList.remove('cart-bump');
                void el.offsetWidth; // restart the animation
                el.classList.add('cart-bump');
            }
            el.dataset.ready = '1';
            el.textContent = count;
        });
    }

    function addToCart(productId, quantity) {
        var cart = getCart();
        var line = cart.find(function (l) { return l.product_id === productId; });
        if (line) {
            line.quantity = Math.min(999, line.quantity + quantity);
        } else {
            cart.push({ product_id: productId, quantity: Math.min(999, quantity) });
        }
        setCart(cart);
    }

    var toastTimer;

    function showToast(quantity) {
        var toast = document.querySelector('[data-cart-toast]');
        if (!toast) { return; }
        toast.querySelector('[data-cart-toast-text]').textContent = quantity === 1 ? 'Agregado a tu pedido' : quantity + ' agregados a tu pedido';
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toast.hidden = true; }, 3500);
    }

    function uuid() {
        if (window.crypto && crypto.randomUUID) {
            return crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-8xxx-xxxxxxxxxxxx'.replace(/x/g, function () {
            return Math.floor(Math.random() * 16).toString(16);
        });
    }

    function token() {
        var current = read(TOKEN_KEY);
        if (!current) {
            current = uuid();
            write(TOKEN_KEY, current);
        }
        return current;
    }

    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(body)
        });
    }

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) { node.className = className; }
        if (text !== undefined && text !== null) { node.textContent = text; }
        return node;
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-add-to-cart]');
        if (!button) { return; }
        var quantity = 1;
        if (button.hasAttribute('data-qty-source')) {
            var input = document.querySelector('[data-qty-input]');
            quantity = Math.max(1, Math.min(999, parseInt(input.value, 10) || 1));
            input.value = quantity;
        }
        addToCart(Number(button.getAttribute('data-add-to-cart')), quantity);
        showToast(quantity);
        button.classList.add('is-added');
        setTimeout(function () { button.classList.remove('is-added'); }, 700);
    });

    document.addEventListener('click', function (event) {
        var step = event.target.closest('[data-qty-step]');
        if (!step) { return; }
        var input = step.closest('[data-qty]').querySelector('[data-qty-input]');
        if (input.disabled) { return; }
        var next = (parseInt(input.value, 10) || 1) + Number(step.getAttribute('data-qty-step'));
        input.value = Math.max(1, Math.min(999, next));
    });

    updateCount();

    var page = document.querySelector('[data-cart-page]');
    if (!page) { return; }

    var empty = page.querySelector('[data-cart-empty]');
    var content = page.querySelector('[data-cart-content]');
    var list = page.querySelector('[data-cart-list]');
    var notice = page.querySelector('[data-cart-notice]');
    var form = page.querySelector('[data-cart-form]');
    var errorBox = page.querySelector('[data-cart-error]');
    var submit = page.querySelector('[data-cart-submit]');

    function changeQuantity(productId, quantity) {
        var cart = getCart();
        if (quantity <= 0) {
            cart = cart.filter(function (l) { return l.product_id !== productId; });
        } else {
            cart.forEach(function (l) {
                if (l.product_id === productId) { l.quantity = Math.min(999, quantity); }
            });
        }
        setCart(cart);
        render();
    }

    function renderLine(item) {
        var row = el('li', 'cart-line');
        var thumb = el('a', 'cart-thumb');
        thumb.href = '/catalogo/' + encodeURIComponent(item.slug);
        thumb.setAttribute('tabindex', '-1');
        thumb.setAttribute('aria-hidden', 'true');
        if (item.image_url) {
            var image = el('img');
            image.src = item.image_url;
            image.alt = '';
            thumb.appendChild(image);
        }
        row.appendChild(thumb);
        var info = el('div', 'cart-line-info');
        var name = el('a', 'cart-line-code', item.code);
        name.href = '/catalogo/' + encodeURIComponent(item.slug);
        info.appendChild(name);
        if (item.measures) { info.appendChild(el('div', 'cart-line-measures', item.measures)); }
        if (item.availability === 'out') { info.appendChild(el('div', 'availability availability-out', 'Agotado')); }
        row.appendChild(info);

        var controls = el('div', 'cart-line-controls');
        var stepper = el('div', 'qty');
        var minus = el('button', 'qty-btn', '−');
        minus.type = 'button';
        minus.disabled = item.quantity <= 1;
        minus.setAttribute('aria-label', 'Quitar uno de ' + item.code);
        minus.addEventListener('click', function () { changeQuantity(item.id, item.quantity - 1); });
        var input = el('input', 'qty-input');
        input.type = 'number';
        input.min = '1';
        input.max = '999';
        input.value = item.quantity;
        input.setAttribute('aria-label', 'Cantidad de ' + item.code);
        input.addEventListener('change', function () {
            var value = parseInt(input.value, 10);
            changeQuantity(item.id, isNaN(value) || value < 1 ? 1 : value);
        });
        var plus = el('button', 'qty-btn', '+');
        plus.type = 'button';
        plus.setAttribute('aria-label', 'Agregar uno de ' + item.code);
        plus.addEventListener('click', function () { changeQuantity(item.id, item.quantity + 1); });
        stepper.appendChild(minus);
        stepper.appendChild(input);
        stepper.appendChild(plus);
        controls.appendChild(stepper);
        controls.appendChild(el('strong', 'cart-line-total', item.line_text));
        var remove = el('button', 'btn btn-sm btn-link cart-remove', 'Quitar');
        remove.type = 'button';
        remove.addEventListener('click', function () { changeQuantity(item.id, 0); });
        controls.appendChild(remove);
        row.appendChild(controls);
        return row;
    }

    function render() {
        var cart = getCart();
        notice.hidden = true;
        if (!cart.length) {
            empty.hidden = false;
            content.hidden = true;
            return;
        }
        return post('/carrito/resumen', { items: cart }).then(function (r) {
            if (!r.ok) { throw new Error('summary'); }
            return r.json();
        }).then(function (data) {
            if (data.missing.length) {
                setCart(getCart().filter(function (l) { return data.missing.indexOf(l.product_id) === -1; }));
                notice.textContent = 'Quitamos de tu pedido un producto que ya no está disponible.';
                notice.hidden = false;
            }
            empty.hidden = data.items.length > 0;
            content.hidden = data.items.length === 0;
            list.classList.toggle('cart-list-enter', !list.dataset.rendered);
            list.dataset.rendered = '1';
            list.textContent = '';
            data.items.forEach(function (item) { list.appendChild(renderLine(item)); });
            page.querySelector('[data-cart-total]').textContent = data.total_text;
            page.querySelector('[data-cart-total-bs]').textContent = data.total_bs_text || '';
        }).catch(function () {
            notice.textContent = 'No pudimos cargar tu pedido. Revisa tu conexión e inténtalo de nuevo.';
            notice.hidden = false;
        });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        errorBox.hidden = true;
        submit.disabled = true;
        var data = new FormData(form);
        post('/pedido', {
            name: data.get('name'),
            phone: data.get('phone'),
            note: data.get('note'),
            token: token(),
            items: getCart()
        }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (body) { return { status: r.status, body: body }; });
        }).then(function (res) {
            if (res.status === 200) {
                write(KEY, '[]');
                write(TOKEN_KEY, null);
                updateCount();
                window.location.href = res.body.whatsapp_url;
                return;
            }
            var message = 'No pudimos enviar tu pedido. Inténtalo de nuevo.';
            if (res.status === 419) {
                window.location.reload();
                return;
            }
            if (res.status === 422) {
                render();
            }
            if (res.status === 429) {
                message = 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.';
            } else if (res.body.errors) {
                message = Object.values(res.body.errors)[0][0];
            }
            errorBox.textContent = message;
            errorBox.hidden = false;
            submit.disabled = false;
        }).catch(function () {
            errorBox.textContent = 'No pudimos enviar tu pedido. Revisa tu conexión e inténtalo de nuevo.';
            errorBox.hidden = false;
            submit.disabled = false;
        });
    });

    render();
})();
