document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('signup-form');
    if (!form) return;

    const errorMessages = {
        first_name: 'Inserisci il nome.',
        last_name: 'Inserisci il cognome.',
        tax_code: 'Codice fiscale non valido (16 caratteri alfanumerici).',
        email: 'Inserisci un’email valida.',
        password: 'La password deve avere almeno 8 caratteri.',
        password_confirm: 'Le password non coincidono.',
        terms: 'Devi accettare i termini e l’informativa privacy.'
    };

    const validators = {
        first_name: () => {
            const v = form.first_name.value.trim();
            return (v.length === 0) ? errorMessages.first_name : '';
        },
        last_name: () => {
            const v = form.last_name.value.trim();
            return (v.length === 0) ? errorMessages.last_name : '';
        },
        tax_code: () => {
            const v = form.tax_code.value.trim().toUpperCase();
            if (v.length === 0) return '';
            return /^[A-Z0-9]{16}$/.test(v) ? '' : errorMessages.tax_code;
        },
        email: () => {
            const v = form.email.value.trim();
            const basic = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
            return basic ? '' : errorMessages.email;
        },
        password: () => {
            const v = form.password.value;
            return (v.length < 8) ? errorMessages.password : '';
        },
        password_confirm: () => {
            const v1 = form.password.value;
            const v2 = form.password_confirm.value;
            return (v1 !== v2) ? errorMessages.password_confirm : '';
        },
        terms: () => form.terms.checked ? '' : errorMessages.terms
    };

    function showError(key, message) {
        const id = 'error-' + key.replace(/_/g, '-');
        const el = document.getElementById(id);
        if (el) {
            el.textContent = message;
            el.hidden = message === '';
        }
        const field = form.elements[key];
        if (field) {
            field.setAttribute('aria-invalid', message ? 'true' : 'false');
            field.classList.toggle('is-invalid', !!message);
        }
    }

    function validateField(key) {
        if (!validators[key]) return true;
        const msg = validators[key]() || '';
        showError(key, msg);
        return msg === '';
    }

    // Real-time
    Object.keys(validators).forEach(key => {
        const field = form.elements[key];
        if (!field) return;
        const handler = () => validateField(key);
        field.addEventListener('input', handler);
        field.addEventListener('change', handler);
        field.addEventListener('blur', handler);
    });

    // Submit
    form.addEventListener('submit', (e) => {
        // normalizza CF in uppercase per coerenza col server
        if (form.tax_code) form.tax_code.value = form.tax_code.value.toUpperCase();

        let isValid = true;
        Object.keys(validators).forEach(key => {
            if (!validateField(key)) isValid = false;
        });

        if (!isValid) {
            e.preventDefault();
            // focus sul primo errore
            for (const key of Object.keys(validators)) {
                const field = form.elements[key];
                const errEl = document.getElementById('error-' + key.replace(/_/g, '-'));
                if (field && errEl && !errEl.hidden && errEl.textContent.trim() !== '') {
                    field.focus();
                    break;
                }
            }
        }
    });
});
