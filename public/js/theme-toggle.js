document.addEventListener('DOMContentLoaded', () => {
    const themeBtn  = document.getElementById('theme-toggle');
    const sunIcon   = document.getElementById('icon-sun');
    const moonIcon  = document.getElementById('icon-moon');
    const saved     = localStorage.getItem('theme') || 'light';

    // Imposta tema iniziale
    document.documentElement.setAttribute('data-theme', saved);
    themeBtn.setAttribute('aria-pressed', saved === 'dark' ? 'true' : 'false');
    sunIcon.style.display  = saved === 'dark' ? 'block' : 'none';
    moonIcon.style.display = saved === 'dark' ? 'none'  : 'block';

    // Gestione toggle tema
    themeBtn.addEventListener('click', e => {
        e.preventDefault();

        const current = document.documentElement.getAttribute('data-theme');
        const next    = current === 'dark' ? 'light' : 'dark';

        // Cambia tema e salva
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);

        // Aggiorna icone
        sunIcon.style.display  = next === 'dark' ? 'block' : 'none';
        moonIcon.style.display = next === 'dark' ? 'none'  : 'block';

        // Aggiorna aria-pressed
        themeBtn.setAttribute('aria-pressed', next === 'dark' ? 'true' : 'false');

        // Evita focus persistente
        themeBtn.blur();
    });


    // === Gestione hamburger menu ===
    const menuBtn = document.getElementById('hamburger-toggle');
    const nav     = document.getElementById('main-nav');

    menuBtn.addEventListener('click', () => {
        const isOpen = nav.classList.toggle('open');
        menuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
});
