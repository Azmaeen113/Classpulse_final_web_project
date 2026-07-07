/**
 * Theme toggle — trusts server-rendered data-theme; syncs plain cp_theme cookie + server.
 * (cp_theme must NOT be Laravel-encrypted so JS can read/write it.)
 */
(function () {
    'use strict';

    var COOKIE = 'cp_theme';

    function readCookie(name) {
        var m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
        if (!m) return null;
        var v = decodeURIComponent(m[1]);
        return (v === 'light' || v === 'dark') ? v : null;
    }

    function writeCookie(name, value) {
        document.cookie = name + '=' + encodeURIComponent(value) + '; path=/; max-age=31536000; SameSite=Lax';
    }

    function apply(theme) {
        var value = theme === 'light' ? 'light' : 'dark';
        var cls = value === 'light' ? 'theme-light' : 'theme-dark';
        var other = value === 'light' ? 'theme-dark' : 'theme-light';

        document.documentElement.setAttribute('data-theme', value);
        document.documentElement.classList.remove(other);
        document.documentElement.classList.add(cls);

        if (document.body) {
            document.body.setAttribute('data-theme', value);
            document.body.classList.remove(other);
            document.body.classList.add(cls);
        }

        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            btn.setAttribute('aria-pressed', value === 'light' ? 'true' : 'false');
            btn.setAttribute('title', 'Switch to ' + (value === 'light' ? 'dark' : 'light') + ' mode');
            var label = btn.querySelector('[data-theme-label]');
            if (label) label.textContent = value === 'light' ? 'Light' : 'Dark';
            var icon = btn.querySelector('i');
            if (icon) icon.className = value === 'light' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
            var form = btn.closest('form');
            var hidden = form ? form.querySelector('input[name="theme"]') : null;
            if (hidden) hidden.value = value === 'light' ? 'dark' : 'light';
        });

        writeCookie(COOKIE, value);
        try { localStorage.setItem(COOKIE, value); } catch (e) {}
        return value;
    }

    function current() {
        var attr = document.documentElement.getAttribute('data-theme');
        if (attr === 'light' || attr === 'dark') return attr;
        return readCookie(COOKIE) || 'dark';
    }

    function persist(url, value, form) {
        if (!url) {
            if (form) form.submit();
            return;
        }
        var token = document.querySelector('meta[name="csrf-token"]');
        var zone = document.documentElement.getAttribute('data-zone') || '';
        var headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
        if (token) headers['X-CSRF-TOKEN'] = token.getAttribute('content');
        if (zone && zone !== 'guest') headers['X-ClassPulse-Zone'] = zone;
        fetch(url, {
            method: 'POST',
            headers: headers,
            credentials: 'same-origin',
            body: JSON.stringify({ theme: value })
        }).then(function (res) {
            // Theme already applied locally; ignore non-OK (do not full-page submit — that caused 419)
            if (!res.ok) {
                console.warn('Theme persist failed', res.status);
            }
        }).catch(function () {
            // Cookie + UI already updated; server sync is best-effort
        });
    }

    function onClick(e) {
        var btn = e.target.closest('[data-theme-toggle]');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        var next = current() === 'light' ? 'dark' : 'light';
        apply(next);
        persist(btn.getAttribute('data-theme-url'), next, btn.closest('form'));
    }

    // Plain cookie wins; otherwise trust server-rendered data-theme (never parse encrypted junk)
    var saved = readCookie(COOKIE);
    var server = document.documentElement.getAttribute('data-theme');
    if (saved) {
        apply(saved);
    } else if (server === 'light' || server === 'dark') {
        apply(server);
    }

    document.addEventListener('click', onClick, true);

    window.ClassPulseTheme = { apply: apply, current: current };
})();
