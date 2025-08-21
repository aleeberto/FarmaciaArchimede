document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('profile-edit-form');
    if (!form) return;

    // Helpers
    const getField = (name) => form.elements?.[name] || null;
    const showError = (key, message) => {
        const id = 'error-' + key.replace(/_/g, '-');
        const el = document.getElementById(id);
        if (el) {
            el.textContent = message || '';
            el.hidden = !message;
        }
        const field = getField(key);
        if (field) {
            field.setAttribute('aria-invalid', message ? 'true' : 'false');
            field.classList.toggle('is-invalid', !!message);
        }
    };

    const M = {
        first_name: 'Inserisci il nome.',
        last_name: 'Inserisci il cognome.',
        email: 'Inserisci un’email valida.',
        tax_code: 'Codice fiscale non valido (16 caratteri alfanumerici).',
        nothing_changed: 'Nessuna modifica apportata.'
    };

    // Rileva modifiche ai dati profilo (confronto con data-original)
    function isProfileChanged() {
        const keys = ['first_name','last_name','email','tax_code'];
        return keys.some(k => {
            const f = getField(k);
            if (!f) return false;
            const current = (f.value ?? '').trim();
            const original = (f.dataset.original ?? '').trim();
            if (k === 'tax_code') return current.toUpperCase() !== original.toUpperCase();
            return current !== original;
        });
    }

    // Se l’utente ha toccato i campi password, lasciamo tutto al server
    function isPasswordFlow() {
        const fields = ['current_password','new_password','new_password_confirm','confirm_with_password']
            .map(getField).filter(Boolean);
        return fields.some(f => (f.value ?? '').length > 0);
    }

    // Validazioni SOLO profilo
    function validateFirstName() {
        const f = getField('first_name'); if (!f) return true;
        const msg = f.value.trim().length === 0 ? M.first_name : '';
        showError('first_name', msg); return !msg;
    }
    function validateLastName() {
        const f = getField('last_name'); if (!f) return true;
        const msg = f.value.trim().length === 0 ? M.last_name : '';
        showError('last_name', msg); return !msg;
    }
    function validateEmail() {
        const f = getField('email'); if (!f) return true;
        const v = f.value.trim();
        const basic = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
        const msg = basic ? '' : M.email;
        showError('email', msg); return !msg;
    }
    function validateTaxCode() {
        const f = getField('tax_code'); if (!f) return true;
        const v = f.value.trim().toUpperCase();
        const msg = v.length === 0 ? '' : (/^[A-Z0-9]{16}$/.test(v) ? '' : M.tax_code);
        showError('tax_code', msg); return !msg;
    }

    // Binding live SOLO ai campi profilo
    [['first_name', validateFirstName],
        ['last_name',  validateLastName],
        ['email',      validateEmail],
        ['tax_code',   validateTaxCode]].forEach(([name, fn]) => {
        const f = getField(name); if (!f) return;
        const h = () => fn();
        f.addEventListener('input', h);
        f.addEventListener('change', h);
        f.addEventListener('blur', h);
    });

    // Submit
    form.addEventListener('submit', (e) => {
        e.preventDefault();

        // Normalizza CF
        const cf = getField('tax_code');
        if (cf) cf.value = cf.value.toUpperCase();

        const changed = isProfileChanged();
        const passwordFlow = isPasswordFlow();

        // Se niente è cambiato e non stai usando i campi password -> blocca
        if (!changed && !passwordFlow) {
            // riutilizzo l’area errori di conferma per non aggiungere UI
            showError('confirm_with_password', M.nothing_changed);
            const title = document.querySelector('h1, h2');
            if (title && typeof title.focus === 'function') title.focus();
            return;
        }

        // Se stai usando il flusso password, non validiamo nulla qui (fa tutto il server)
        // Se stai cambiando solo profilo, valida i 4 campi
        if (!passwordFlow && changed) {
            let ok = true;
            ok = validateFirstName() && ok;
            ok = validateLastName()  && ok;
            ok = validateEmail()     && ok;
            ok = validateTaxCode()   && ok;

            if (!ok) {
                const errorEls = form.querySelectorAll('.form-error:not([hidden])');
                if (errorEls.length > 0) {
                    const first = errorEls[0];
                    const forId = first.id.replace(/^error-/, '').replace(/-/g, '_');
                    const field = getField(forId);
                    if (field && typeof field.focus === 'function') field.focus();
                }
                return;
            }
        }

        // In tutti gli altri casi lascia che il server gestisca (soprattutto password)
        form.submit();
    });
});
