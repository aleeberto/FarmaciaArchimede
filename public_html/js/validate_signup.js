document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('signup-form');
    if (!form) return;

    const errorMessages = {
        first_name: 'Inserisci il nome.',
        last_name: 'Inserisci il cognome.',
        tax_code: 'Codice fiscale non valido (16 caratteri alfanumerici).',
        username: 'Il nome utente deve essere lungo 3–30 caratteri e può contenere lettere, numeri, punto, underscore o trattino.',
        email: 'Inserisci un’email valida.',
        password: 'La password deve avere almeno 8 caratteri.',
        password_confirm: 'Le password non coincidono.',
    };

    const getField = (name) => form.elements?.[name] || null;
    const hasField  = (name) => !!getField(name);

    const validators = {
        first_name: () => {
            const f = getField('first_name'); if (!f) return '';
            return f.value.trim().length === 0 ? errorMessages.first_name : '';
        },
        last_name: () => {
            const f = getField('last_name'); if (!f) return '';
            return f.value.trim().length === 0 ? errorMessages.last_name : '';
        },
        tax_code: () => {
            const f = getField('tax_code'); if (!f) return '';
            const v = f.value.trim().toUpperCase();
            if (v.length === 0) return ''; // consenti vuoto in realtime; il server resta il guardiano finale
            return /^[A-Z0-9]{16}$/.test(v) ? '' : errorMessages.tax_code;
        },
        username: () => {
            const f = getField('username'); if (!f) return '';
            const v = f.value.trim();
            if (v.length === 0) return errorMessages.username;
            return /^[a-zA-Z0-9_.-]{3,30}$/.test(v) ? '' : errorMessages.username;
        },
        email: () => {
            const f = getField('email'); if (!f) return '';
            const v = f.value.trim();
            const basic = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
            return basic ? '' : errorMessages.email;
        },
        password: () => {
            const f = getField('password'); if (!f) return '';
            return f.value.length < 8 ? errorMessages.password : '';
        },
        password_confirm: () => {
            const p1 = getField('password'); const p2 = getField('password_confirm');
            if (!p1 || !p2) return '';
            return p1.value !== p2.value ? errorMessages.password_confirm : '';
        },
    };

    function showError(key, message) {
        const id = 'error-' + key.replace(/_/g, '-');
        const el = document.getElementById(id);
        if (el) {
            el.textContent = message;
            el.hidden = message === '';
        }
        const field = getField(key);
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

    // Real-time: attacca solo ai campi che ESISTONO
    Object.keys(validators).forEach(key => {
        const field = getField(key);
        if (!field) return;
        const handler = () => validateField(key);
        field.addEventListener('input', handler);
        field.addEventListener('change', handler);
        field.addEventListener('blur', handler);
    });

    // Submit sicuro
    form.addEventListener('submit', (e) => {
        e.preventDefault();

        // 1) normalizza CF se presente
        const cf = getField('tax_code');
        if (cf) cf.value = cf.value.toUpperCase();

        // 2) valida
        let isValid = true;
        for (const key of Object.keys(validators)) {
            if (!validateField(key)) isValid = false;
        }

        if (!isValid) {
            for (const key of Object.keys(validators)) {
                const field = getField(key);
                const errEl = document.getElementById('error-' + key.replace(/_/g, '-'));
                if (field && errEl && !errEl.hidden && errEl.textContent.trim() !== '') {
                    field.focus();
                    break;
                }
            }
            return; // non inviare
        }

        form.submit();
    });
});