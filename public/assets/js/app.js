/* =========================================================================
   FORJA — comportamiento de la interfaz
   Sin dependencias. Todo se anima con transform y opacity.
   ========================================================================= */
(function () {
    'use strict';

    var BASE = document.documentElement.getAttribute('data-base') || '';
    var root = document.documentElement;

    function $(sel, ctx) { return (ctx || document).querySelector(sel); }
    function $$(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }
    function store(key, val) {
        try {
            if (val === undefined) { return localStorage.getItem(key); }
            localStorage.setItem(key, val);
        } catch (e) { return null; }
    }

    /* ------------------------------------------------------------- Tema */
    /* La interfaz es oscura por diseño: si no hay elección guardada, el tema es
       oscuro, no el del sistema. */
    function currentTheme() {
        return root.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
    }
    /* El icono anuncia a dónde vas, no dónde estás: en oscuro se ve un sol. */
    function paintToggle() {
        var dark = currentTheme() === 'dark';
        $$('#themeToggle .t-light').forEach(function (n) { n.hidden = !dark; });
        $$('#themeToggle .t-dark').forEach(function (n) { n.hidden = dark; });
    }
    var themeBtn = $('#themeToggle');
    if (themeBtn) {
        paintToggle();
        themeBtn.addEventListener('click', function () {
            var next = currentTheme() === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            store('theme', next);
            paintToggle();
        });
    }

    /* --------------------------------------------------- Menú en móvil */
    var burger = $('#burger');
    var panel = $('#navPanel');
    if (burger && panel) {
        burger.addEventListener('click', function () {
            var open = burger.getAttribute('aria-expanded') === 'true';
            burger.setAttribute('aria-expanded', String(!open));
            panel.classList.toggle('is-open', !open);
            document.body.style.overflow = !open ? 'hidden' : '';
        });
        panel.addEventListener('click', function (ev) {
            if (ev.target.tagName === 'A') {
                burger.setAttribute('aria-expanded', 'false');
                panel.classList.remove('is-open');
                document.body.style.overflow = '';
            }
        });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && panel.classList.contains('is-open')) { burger.click(); }
        });
    }

    /* --------------------------------------- Sombra de la isla al hacer scroll */
    var nav = $('#nav');
    if (nav && 'IntersectionObserver' in window) {
        var sentinel = document.createElement('div');
        sentinel.setAttribute('aria-hidden', 'true');
        sentinel.style.cssText = 'position:absolute;top:0;height:1px;width:1px';
        document.body.prepend(sentinel);
        new IntersectionObserver(function (entries) {
            nav.classList.toggle('is-stuck', !entries[0].isIntersecting);
        }, { threshold: 0 }).observe(sentinel);
    }

    /* ------------------------------------------ Revelado al entrar en pantalla */
    var revealables = $$('.reveal');
    if (revealables.length) {
        if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            revealables.forEach(function (n) { n.classList.add('is-in'); });
        } else {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-in');
                        io.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
            revealables.forEach(function (n) { io.observe(n); });
        }
    }

    /* --------------------------------------------------------- Avisos */
    $$('.flash .x').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var el = btn.closest('.flash');
            el.style.transition = 'opacity .25s, transform .25s';
            el.style.opacity = '0';
            el.style.transform = 'translateY(8px)';
            setTimeout(function () { el.remove(); }, 250);
        });
    });
    setTimeout(function () {
        $$('.flash').forEach(function (el) {
            var x = $('.x', el);
            if (x) { x.click(); }
        });
    }, 7000);

    function toast(message, type) {
        var stack = $('#flashStack');
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'flash-stack';
            stack.id = 'flashStack';
            stack.setAttribute('role', 'status');
            document.body.appendChild(stack);
        }
        var el = document.createElement('div');
        el.className = 'flash ' + (type || 'ok');
        el.innerHTML = '<span class="ico"></span><span></span>';
        el.lastChild.textContent = message;
        stack.appendChild(el);
        setTimeout(function () {
            el.style.transition = 'opacity .25s, transform .25s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 250);
        }, 3200);
    }

    /* ------------------------------------------------ Copiar al portapapeles */
    $$('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var text = btn.getAttribute('data-copy');
            if (!text) {
                var target = btn.getAttribute('data-copy-target');
                var node = target ? $(target) : null;
                text = node ? (node.value !== undefined ? node.value : node.textContent) : '';
            }
            var done = function () { toast(btn.getAttribute('data-copy-msg') || 'Enlace copiado', 'ok'); };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(done, function () { fallbackCopy(text, done); });
            } else {
                fallbackCopy(text, done);
            }
        });
    });
    function fallbackCopy(text, done) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.cssText = 'position:fixed;top:-2000px';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); done(); } catch (e) { toast('No se pudo copiar', 'error'); }
        ta.remove();
    }

    /* --------------------------------------------------- Compartir nativo */
    $$('[data-share]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url = btn.getAttribute('data-share');
            var title = btn.getAttribute('data-share-title') || document.title;
            if (navigator.share) {
                navigator.share({ title: title, url: url }).catch(function () {});
            } else {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(url).then(function () { toast('Enlace copiado', 'ok'); });
                } else {
                    fallbackCopy(url, function () { toast('Enlace copiado', 'ok'); });
                }
            }
        });
    });

    /* ------------------------------------------------------- Favoritos */
    $$('[data-fav]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var type = btn.getAttribute('data-fav');
            var id = btn.getAttribute('data-fav-id');
            var token = btn.getAttribute('data-token');
            var body = new URLSearchParams();
            body.append('type', type);
            body.append('id', id);
            body.append('csrf_token', token);

            fetch(BASE + '/dashboard/favorites/toggle', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: body,
                credentials: 'same-origin'
            }).then(function (r) {
                if (r.status === 401 || r.redirected) { window.location.href = BASE + '/login'; return null; }
                return r.json();
            }).then(function (data) {
                if (!data || !data.ok) { return; }
                btn.classList.toggle('is-on', data.active);
                btn.setAttribute('aria-pressed', String(data.active));

                /* El botón grande cambia de texto y de peso visual: pasa de
                   invitar a la acción a confirmar que ya está hecha. */
                var label = btn.querySelector('[data-fav-label]');
                if (label) {
                    label.textContent = data.active
                        ? (btn.getAttribute('data-label-on') || 'En mi cuenta')
                        : (btn.getAttribute('data-label-off') || 'Agregar a mi cuenta');
                    btn.classList.toggle('btn-primary', !data.active);
                    btn.classList.toggle('btn-ghost', data.active);
                    var ico = btn.querySelector('.btn-ico svg');
                    if (ico) {
                        ico.innerHTML = data.active
                            ? '<path d="m4 12.5 5 5L20 7"/>'
                            : '<path d="M12 5v14M5 12h14"/>';
                    }
                }

                toast(data.active ? 'Agregado a tu cuenta' : 'Quitado de tu cuenta', 'ok');
            }).catch(function () { toast('No se pudo actualizar', 'error'); });
        });
    });

    /* ------------------------------------------------- Carga de archivos */
    $$('.dropzone').forEach(function (zone) {
        var input = $('input[type=file]', zone);
        var label = $('.d', zone);
        if (!input) { return; }
        var original = label ? label.textContent : '';

        zone.addEventListener('click', function () { input.click(); });
        zone.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); input.click(); }
        });
        ['dragenter', 'dragover'].forEach(function (evt) {
            zone.addEventListener(evt, function (ev) { ev.preventDefault(); zone.classList.add('is-over'); });
        });
        ['dragleave', 'drop'].forEach(function (evt) {
            zone.addEventListener(evt, function (ev) { ev.preventDefault(); zone.classList.remove('is-over'); });
        });
        zone.addEventListener('drop', function (ev) {
            if (ev.dataTransfer && ev.dataTransfer.files.length) {
                input.files = ev.dataTransfer.files;
                input.dispatchEvent(new Event('change'));
            }
        });
        input.addEventListener('change', function () {
            if (label) {
                label.textContent = input.files.length
                    ? input.files[0].name + ' · ' + Math.round(input.files[0].size / 1024) + ' KB'
                    : original;
            }
        });
    });

    /* ------------------------------------------- Filtros que se autoenvían */
    $$('[data-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { el.form.submit(); });
    });

    /* -------------------------------------- Confirmación de acciones críticas */
    $$('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            if (!window.confirm(form.getAttribute('data-confirm'))) { ev.preventDefault(); }
        });
    });

    /* =====================================================================
       El carrito de la tienda: agentes y habilidades elegidos por el usuario.
       Vive en localStorage para que sobreviva a la navegación.
       ===================================================================== */
    var CART_KEY = 'forja.cart.v1';

    function readCart() {
        try {
            var raw = store(CART_KEY);
            var data = raw ? JSON.parse(raw) : null;
            if (!data || typeof data !== 'object') { return { agents: [], skills: [] }; }
            return {
                agents: Array.isArray(data.agents) ? data.agents : [],
                skills: Array.isArray(data.skills) ? data.skills : []
            };
        } catch (e) { return { agents: [], skills: [] }; }
    }
    function writeCart(cart) {
        store(CART_KEY, JSON.stringify(cart));
        paintCart();
    }
    function inCart(kind, slug) {
        var cart = readCart();
        return (kind === 'agent' ? cart.agents : cart.skills).indexOf(slug) !== -1;
    }
    function toggleCart(kind, slug) {
        var cart = readCart();
        var list = kind === 'agent' ? cart.agents : cart.skills;
        var i = list.indexOf(slug);
        if (i === -1) { list.push(slug); } else { list.splice(i, 1); }
        writeCart(cart);
        return i === -1;
    }

    function paintCart() {
        var cart = readCart();
        var total = cart.agents.length + cart.skills.length;

        $$('[data-cart-toggle]').forEach(function (btn) {
            var kind = btn.getAttribute('data-cart-toggle');
            var slug = btn.getAttribute('data-slug');
            var on = inCart(kind, slug);
            btn.classList.toggle('is-on', on);
            btn.setAttribute('aria-pressed', String(on));
            var label = $('[data-cart-label]', btn);
            if (label) { label.textContent = on ? 'Quitar' : (btn.getAttribute('data-add-label') || 'Añadir'); }
        });

        var bar = $('#cartBar');
        if (bar) {
            bar.classList.toggle('is-visible', total > 0);
            var n = $('[data-cart-count]', bar);
            if (n) { n.textContent = String(total); }
            var detail = $('[data-cart-detail]', bar);
            if (detail) {
                detail.textContent = cart.agents.length + ' agente' + (cart.agents.length === 1 ? '' : 's')
                    + ' · ' + cart.skills.length + ' habilidad' + (cart.skills.length === 1 ? '' : 'es');
            }
            $$('input[name=agents]', bar).forEach(function (i) { i.value = cart.agents.join(','); });
            $$('input[name=skills]', bar).forEach(function (i) { i.value = cart.skills.join(','); });
        }

        $$('[data-cart-total]').forEach(function (n) { n.textContent = String(total); });
    }

    $$('[data-cart-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function (ev) {
            ev.preventDefault();
            var added = toggleCart(btn.getAttribute('data-cart-toggle'), btn.getAttribute('data-slug'));
            toast(added ? 'Añadido a tu paquete' : 'Quitado de tu paquete', 'ok');
        });
    });

    var clearCart = $('#cartClear');
    if (clearCart) {
        clearCart.addEventListener('click', function () {
            writeCart({ agents: [], skills: [] });
            toast('Paquete vaciado', 'info');
        });
    }

    paintCart();

    /* ---------------------------- Selección de habilidades en la ficha del agente */
    var skillPicker = $('#skillPicker');
    if (skillPicker) {
        var sync = function () {
            var chosen = $$('input[name="pick[]"]:checked', skillPicker).map(function (i) { return i.value; });
            $$('[data-skill-selection]').forEach(function (i) { i.value = chosen.join(','); });
            $$('[data-skill-count]').forEach(function (n) { n.textContent = String(chosen.length); });

            $$('a[data-zip-base]').forEach(function (a) {
                var base = a.getAttribute('data-zip-base');
                a.setAttribute('href', base + (chosen.length ? '&skills=' + encodeURIComponent(chosen.join(',')) : ''));
            });
        };
        $$('input[name="pick[]"]', skillPicker).forEach(function (i) { i.addEventListener('change', sync); });

        var all = $('#pickAll');
        if (all) {
            all.addEventListener('click', function () {
                var boxes = $$('input[name="pick[]"]:not(:disabled)', skillPicker);
                var every = boxes.every(function (b) { return b.checked; });
                boxes.forEach(function (b) { b.checked = !every; });
                sync();
            });
        }
        sync();
    }

    /* ---------------------------------- Pestañas de vista previa (md / json) */
    $$('[data-tabs]').forEach(function (group) {
        var buttons = $$('[data-tab]', group);
        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var name = btn.getAttribute('data-tab');
                buttons.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
                $$('[data-panel]', group).forEach(function (p) {
                    p.hidden = p.getAttribute('data-panel') !== name;
                });
            });
        });
    });

    /* ------------------------------ Contador de caracteres en campos con límite */
    $$('[data-counter]').forEach(function (field) {
        var out = $(field.getAttribute('data-counter'));
        if (!out) { return; }
        var max = field.getAttribute('maxlength');
        var paint = function () {
            out.textContent = field.value.length + (max ? ' / ' + max : '');
        };
        field.addEventListener('input', paint);
        paint();
    });
})();
