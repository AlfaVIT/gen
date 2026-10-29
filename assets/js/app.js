(function () {
    'use strict';

    var THEME_KEY = 'gen.theme';
    var COOKIE_KEY = 'gen.cookies';

    function currentTheme() {
        return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    }

    function applyTheme(theme) {
        try { localStorage.setItem(THEME_KEY, theme); } catch (e) {}
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.setAttribute('data-bs-theme', theme === 'dark' ? 'dark' : 'light');
        var icon = document.querySelector('#themeToggle i');
        if (icon) {
            icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
        }
    }

    function initThemeToggle() {
        var btn = document.getElementById('themeToggle');
        if (!btn) return;
        var icon = btn.querySelector('i');
        if (icon) icon.className = currentTheme() === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
        btn.addEventListener('click', function () {
            applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
        });
    }

    function initCookieBanner() {
        var banner = document.getElementById('cookieBanner');
        if (!banner) return;
        var accepted = false;
        try { accepted = localStorage.getItem(COOKIE_KEY) === '1'; } catch (e) {}
        if (accepted) return;
        setTimeout(function () { banner.classList.add('show'); }, 900);
        var accept = document.getElementById('cookieAccept');
        if (accept) {
            accept.addEventListener('click', function () {
                try { localStorage.setItem(COOKIE_KEY, '1'); } catch (e) {}
                banner.classList.remove('show');
            });
        }
    }

    function initDataConfirm() {
        $(document).on('submit', 'form[data-confirm]', function (e) {
            var msg = $(this).attr('data-confirm');
            if (!window.confirm(msg)) {
                e.preventDefault();
                return false;
            }
        });
        $(document).on('click', '[data-confirm-click]', function (e) {
            var msg = $(this).attr('data-confirm-click');
            if (!window.confirm(msg)) {
                e.preventDefault();
                return false;
            }
        });
    }

    function initAutoAlerts() {
        window.setTimeout(function () {
            $('.alert-dismissible').fadeOut(500, function () { $(this).remove(); });
        }, 7000);
    }

    function initInputs() {
        $(document).on('change', 'input[type=file][data-preview]', function () {
            var target = $($(this).attr('data-preview'));
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function (ev) { target.attr('src', ev.target.result); };
                reader.readAsDataURL(this.files[0]);
            }
        });
        $(document).on('click', '.social-row .row-remove, .spouse-row .row-remove', function () {
            $(this).closest('.social-row, .spouse-row').fadeOut(200, function () { $(this).remove(); });
        });
    }

    function init() {
        initThemeToggle();
        initCookieBanner();
        initDataConfirm();
        initAutoAlerts();
        initInputs();
    }

    $(document).ready(init);
})();
