document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('filterForm');
    const prodottiDiv = document.getElementById('prodotti');
    const paginationDiv = document.getElementById('pagination-container');

    function fetchProducts(params) {
        const url = 'prodotti.php?' + params + '&ajax=1';
        fetch(url)
            .then(resp => resp.json())
            .then(data => {
                prodottiDiv.innerHTML = data.items;
                paginationDiv.innerHTML = data.pagination;
                document.querySelector('.pagination-info').textContent = data.info;
                attachPaginationEvents();
            });
    }

    form.addEventListener('submit', e => {
        e.preventDefault();
        const params = new URLSearchParams(new FormData(form)).toString();
        fetchProducts(params);
    });

    function attachPaginationEvents() {
        paginationDiv.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                const params = link.getAttribute('href').split('?')[1].replace(/&?ajax=1/, '');
                fetchProducts(params);
            });
        });
    }

    // Inizializza eventi su caricamento
    attachPaginationEvents();
});
