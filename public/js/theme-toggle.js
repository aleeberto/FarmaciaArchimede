document.addEventListener('DOMContentLoaded', () => {
    const btn      = document.getElementById('theme-toggle');
    const sunIcon  = document.getElementById('icon-sun');
    const moonIcon = document.getElementById('icon-moon');
    const saved    = localStorage.getItem('theme') || 'light';

    // Imposta tema iniziale
    document.documentElement.setAttribute('data-theme', saved);
    sunIcon.style.display  = saved === 'dark' ? 'block' : 'none';
    moonIcon.style.display = saved === 'dark' ? 'none'  : 'block';

    btn.addEventListener('click', e => {
        e.preventDefault();

        const current = document.documentElement.getAttribute('data-theme');
        const next    = current === 'dark' ? 'light' : 'dark';

        // Cambia tema e salva
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);

        // Aggiorna visibilità delle icone
        sunIcon.style.display  = next === 'dark' ? 'block' : 'none';
        moonIcon.style.display = next === 'dark' ? 'none'  : 'block';

        // Rimuove il focus dal toggle per evitare il colore di :focus persistente
        btn.blur();
    });
});

const btn = document.getElementById('hamburger-toggle');
const nav = document.querySelector('header nav');
btn.addEventListener('click', () => {
    nav.classList.toggle('open');
});

