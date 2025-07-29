document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('product-form');

    // Messaggi di errore
    const errorMessages = {
        product_type_id: 'Seleziona il tipo di prodotto.',
        short_name: 'Inserisci un nome breve, composto al massimo da 128 caratteri.',
        name: 'Inserisci il nome completo del prodotto, composto al massimo da 128 caratteri.',
        manufacturer: 'Inserisci il nome completo del produttore, composto al massimo da 100 caratteri.',
        aic_code: 'Inserisci un codice AIC valido, composto esattamente da 9 cifre numeriche.',
        format: 'Seleziona il formato del prodotto.',
        price: 'Inserisci il prezzo del prodotto, indicando un valore numerico maggiore di zero.',
        availability: 'Inserisci la quantità disponibile del prodotto, indicando un numero intero uguale o superiore a zero.'
    };

    // Definizione dei validatori
    const validators = {
        product_type_id: () => {
            const v = form.product_type_id.value;
            return (!v || !/^\d+$/.test(v)) ? errorMessages.product_type_id : '';
        },
        short_name: () => {
            const v = form.short_name.value.trim();
            return (v.length === 0 || v.length > 128) ? errorMessages.short_name : '';
        },
        name: () => {
            const v = form.name.value.trim();
            return (v.length === 0 || v.length > 128) ? errorMessages.name : '';
        },
        manufacturer: () => {
            const v = form.manufacturer.value.trim();
            return (v.length === 0 || v.length > 100) ? errorMessages.manufacturer : '';
        },
        aic_code: () => {
            const v = form.aic_code.value.trim();
            const valid = /^[0-9]{9}$/.test(v);
            return (!valid) ? errorMessages.aic_code : '';
        },
        format: () => {
            const v = form.format.value;
            return (!v) ? errorMessages.format : '';
        },
        price: () => {
            const v = parseFloat(form.price.value);
            return (isNaN(v) || v <= 0) ? errorMessages.price : '';
        },
        availability: () => {
            const v = form.availability.value;
            return (!/^\d+$/.test(v) || parseInt(v, 10) < 0) ? errorMessages.availability : '';
        }
    };

    // Funzione per mostrare l'errore
    function showError(key, message) {
        const id = 'error-' + key.replace(/_/g, '-');
        const el = document.getElementById(id);
        if (el) el.textContent = message;
    }

    function validateField(key) {
        const field = form.elements[key];
        const value = field ? field.value : '';
        const msg = validators[key]() || '';
        showError(key, msg);
        return msg === '';
    }

    // Validazione in real-time durante la digitazione e al cambio di valore
    Object.keys(validators).forEach(key => {
        const field = form.elements[key];
        if (!field) return;
        field.addEventListener('input', () => validateField(key));
        field.addEventListener('change', () => validateField(key));
        field.addEventListener('blur', () => validateField(key));
    });

    // Submit finale
    form.addEventListener('submit', (e) => {
        let isValid = true;
        Object.keys(validators).forEach(key => {
            if (!validateField(key)) isValid = false;
        });
        if (!isValid) {
            e.preventDefault();
            const firstErr = form.querySelector('.error-msg:not(:empty)');
            if (firstErr) firstErr.previousElementSibling.focus();
        }
    });
});
