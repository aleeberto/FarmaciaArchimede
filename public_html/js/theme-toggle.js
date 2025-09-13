document.addEventListener('DOMContentLoaded', () => {
    // === Toggle tema (lascia la tua versione com'è) ===
    const themeBtn  = document.getElementById('theme-toggle');
    const sunIcon   = document.getElementById('icon-sun');
    const moonIcon  = document.getElementById('icon-moon');
    const saved     = localStorage.getItem('theme') || 'light';

    document.documentElement.setAttribute('data-theme', saved);
    if (themeBtn) {
        themeBtn.setAttribute('aria-pressed', saved === 'dark' ? 'true' : 'false');
    }
    if (sunIcon && moonIcon) {
        sunIcon.style.display  = saved === 'dark' ? 'block' : 'none';
        moonIcon.style.display = saved === 'dark' ? 'none'  : 'block';
    }
    themeBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        const current = document.documentElement.getAttribute('data-theme');
        const next    = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        if (sunIcon && moonIcon) {
            sunIcon.style.display  = next === 'dark' ? 'block' : 'none';
            moonIcon.style.display = next === 'dark' ? 'none'  : 'block';
        }
        themeBtn.setAttribute('aria-pressed', next === 'dark' ? 'true' : 'false');
        themeBtn.blur();
    });

    // === Hamburger / Nav ===
    const burger = document.getElementById('hamburger-toggle');
    const nav    = document.getElementById('main-nav');
    if (!burger || !nav) return;

    // Stato iniziale aria
    burger.setAttribute('aria-expanded', 'false');
    burger.setAttribute('aria-label', 'Apri menu');

    // Media query per desktop vs mobile
    const mql = window.matchMedia('(min-width: 769px)');
    let lastFocused = null;

    const FOCUSABLE = 'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])';
    const getFirstFocusable = () => nav.querySelector(FOCUSABLE);

    function lockScroll(lock) {
        if (lock) {
            // salva overflow corrente per sicurezza
            const prev = document.body.style.overflow;
            document.body.dataset.prevOverflow = prev || '';
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = document.body.dataset.prevOverflow || '';
            delete document.body.dataset.prevOverflow;
        }
    }

    function openNav() {
        lastFocused = document.activeElement;
        nav.classList.add('open');
        nav.hidden = false;
        burger.setAttribute('aria-expanded', 'true');
        burger.setAttribute('aria-label', 'Chiudi menu');
        lockScroll(true);

        // Sposta il focus al primo link del menu
        const first = getFirstFocusable();
        if (first) first.focus();
    }

    function closeNav() {
        nav.classList.remove('open');
        nav.hidden = true;
        burger.setAttribute('aria-expanded', 'false');
        burger.setAttribute('aria-label', 'Apri menu');
        lockScroll(false);

        // Ritorna il focus al trigger
        if (lastFocused && typeof lastFocused.focus === 'function') {
            lastFocused.focus();
        } else {
            burger.focus();
        }
    }

    function syncWithMQ(e) {
        if (e.matches) {
            // Desktop: nav sempre visibile, niente overlay, niente scroll lock
            nav.classList.remove('open');
            nav.hidden = false;
            burger.setAttribute('aria-expanded', 'false');
            burger.setAttribute('aria-label', 'Apri menu');
            lockScroll(false);
        } else {
            // Mobile: nav chiuso di default
            nav.classList.remove('open');
            nav.hidden = true;
            burger.setAttribute('aria-expanded', 'false');
            burger.setAttribute('aria-label', 'Apri menu');
            lockScroll(false);
        }
    }

    // Init + listener mql (compat vecchi browser)
    if (typeof mql.addEventListener === 'function') {
        mql.addEventListener('change', syncWithMQ);
    } else {
        mql.addListener(syncWithMQ);
    }
    syncWithMQ(mql);

    // Toggle con il pulsante
    burger.addEventListener('click', () => {
        const isOpen = burger.getAttribute('aria-expanded') === 'true';
        if (isOpen) {
            closeNav();
        } else {
            openNav();
        }
    });

    // Chiudi con ESC solo se aperto
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && burger.getAttribute('aria-expanded') === 'true') {
            e.preventDefault();
            closeNav();
        }
    });

    // Chiudi cliccando sul backdrop (solo se clicchi lo sfondo dell’overlay)
    nav.addEventListener('click', (e) => {
        if (e.target === nav && burger.getAttribute('aria-expanded') === 'true') {
            closeNav();
        }
    });

    // Chiudi quando clicchi un link o un bottone con data-close-nav
    nav.querySelectorAll('a, button[data-close-nav]').forEach((el) => {
        el.addEventListener('click', () => closeNav());
    });
});