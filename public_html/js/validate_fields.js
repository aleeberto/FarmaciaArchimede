// js/validate_fields.js
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('product-form');
    if (!form) return;

    const $ = (name) => form.elements[name];

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

    // Helpers di normalizzazione
    const normalizeDecimal = (raw) => {
        if (typeof raw !== 'string') return '';
        let v = raw.trim();
        const hasComma = v.includes(',');
        const hasDot = v.includes('.');
        // se ha sia virgola che punto, i punti sono separatori di migliaia → rimuovili
        if (hasComma && hasDot) v = v.replace(/\./g, '');
        // un’unica virgola come separatore decimale → sostituisci col punto
        v = v.replace(',', '.');
        // rimuovi caratteri non numerici (tranne il punto decimale)
        v = v.replace(/[^\d.]/g, '');
        return v;
    };

    const normalizeInt = (raw) => {
        if (typeof raw !== 'string') return '';
        return raw.trim().replace(/[^\d]/g, '');
    };

    function setError(key, message) {
        const id = 'error-' + key.replace(/_/g, '-');
        const errEl = document.getElementById(id);
        if (errEl) errEl.textContent = message || '';

        const field = $(key);
        if (field) {
            if (message) field.setAttribute('aria-invalid', 'true');
            else field.setAttribute('aria-invalid', 'false');
            // assicurati che error-id sia in aria-describedby
            const current = (field.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
            if (!current.includes(id)) current.push(id);
            field.setAttribute('aria-describedby', current.join(' '));
        }
    }

    const validators = {
        product_type_id: () => {
            const v = $.product_type_id?.value;
            return (!v || !/^\d+$/.test(v)) ? errorMessages.product_type_id : '';
        },
        short_name: () => {
            const v = $.short_name?.value.trim() || '';
            return (v.length === 0 || v.length > 128) ? errorMessages.short_name : '';
        },
        name: () => {
            const v = $.name?.value.trim() || '';
            return (v.length === 0 || v.length > 128) ? errorMessages.name : '';
        },
        manufacturer: () => {
            const v = $.manufacturer?.value.trim() || '';
            return (v.length === 0 || v.length > 100) ? errorMessages.manufacturer : '';
        },
        aic_code: () => {
            const v = $.aic_code?.value.trim() || '';
            return (/^[0-9]{9}$/.test(v)) ? '' : errorMessages.aic_code;
        },
        format: () => {
            const v = $.format?.value;
            return v ? '' : errorMessages.format;
        },
        price: () => {
            const raw = $.price?.value ?? '';
            const vStr = normalizeDecimal(raw);
            const v = parseFloat(vStr);
            return (!vStr || isNaN(v) || v <= 0) ? errorMessages.price : '';
        },
        availability: () => {
            const raw = $.availability?.value ?? '';
            const vStr = normalizeInt(raw);
            const v = vStr === '' ? NaN : parseInt(vStr, 10);
            return (isNaN(v) || v < 0) ? errorMessages.availability : '';
        }
    };

    function validateField(key) {
        const msg = validators[key] ? validators[key]() : '';
        setError(key, msg);
        return msg === '';
    }

    $.price?.addEventListener('input', (e) => {
        const before = e.target.value;
        const after = before.replace(',', '.');
        if (after !== before) e.target.value = after;
        validateField('price');
    });

    $.availability?.addEventListener('input', (e) => {
        const before = e.target.value;
        const after = before.replace(/[^\d]/g, '');
        if (after !== before) e.target.value = after;
        validateField('availability');
    });

    Object.keys(validators).forEach(key => {
        const field = $(key);
        if (!field) return;
        field.addEventListener('change', () => validateField(key));
        field.addEventListener('blur', () => validateField(key));
    });


    form.addEventListener('submit', (e) => {
        if ($('price')) $('price').value = normalizeDecimal($('price').value);
        if ($('availability')) $('availability').value = normalizeInt($('availability').value);

        let isValid = true;
        Object.keys(validators).forEach(key => { if (!validateField(key)) isValid = false; });

        if (!isValid) {
            e.preventDefault();
            // focus sul primo campo con errore
            for (const key of Object.keys(validators)) {
                const id = 'error-' + key.replace(/_/g, '-');
                const errEl = document.getElementById(id);
                if (errEl && errEl.textContent.trim() !== '') {
                    $(key)?.focus();
                    break;
                }
            }
        }
    });
});
