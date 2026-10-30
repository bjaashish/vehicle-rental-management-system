/* ===== Vehicle Rental Management System - Client-side scripts ===== */
document.addEventListener('DOMContentLoaded', () => {

    // ---------- Mobile navigation ----------
    const toggle = document.querySelector('.nav-toggle');
    const links = document.querySelector('.nav-links');
    if (toggle && links) {
        toggle.addEventListener('click', () => {
            links.classList.toggle('open');
            toggle.setAttribute('aria-expanded', links.classList.contains('open'));
        });
    }

    // ---------- Close / auto-hide alerts ----------
    document.querySelectorAll('.alert').forEach(alert => {
        alert.querySelector('.alert-close')?.addEventListener('click', () => alert.remove());
        setTimeout(() => { alert.style.transition = 'opacity .5s'; alert.style.opacity = '0'; setTimeout(() => alert.remove(), 500); }, 6000);
    });

    // ---------- Confirm dangerous actions ----------
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm)) e.preventDefault();
        });
    });

    // ---------- Form validation ----------
    // Usage: <form class="validate"> and <input data-rules="required|email|min:6|max:50|phone|match:fieldName|future|after:fieldName|number|positive">
    const today = () => { const d = new Date(); d.setHours(0, 0, 0, 0); return d; };

    const validators = {
        required: v => v.trim() !== '' || 'This field is required.',
        email: v => !v || /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v) || 'Enter a valid email address.',
        phone: v => !v || /^[6-9]\d{9}$/.test(v) || 'Enter a valid 10-digit mobile number.',
        name: v => !v || /^[A-Za-z][A-Za-z .'-]{1,99}$/.test(v) || 'Name can contain letters, spaces and . \' - only.',
        min: (v, n) => !v || v.length >= +n || `Must be at least ${n} characters.`,
        max: (v, n) => !v || v.length <= +n || `Must be at most ${n} characters.`,
        number: v => !v || !isNaN(v) || 'Must be a number.',
        positive: v => !v || +v > 0 || 'Must be greater than zero.',
        strong: v => !v || (/[A-Za-z]/.test(v) && /\d/.test(v)) || 'Password must contain letters and numbers.',
        regno: v => !v || /^[A-Z]{2}\d{1,2}[A-Z]{0,3}\d{4}$/.test(v.toUpperCase().replace(/\s/g, '')) || 'Format like DL01AB1234.',
        match: (v, other, form) => v === (form.elements[other]?.value || '') || 'Values do not match.',
        future: v => !v || new Date(v) >= today() || 'Date cannot be in the past.',
        after: (v, other, form) => {
            const o = form.elements[other]?.value;
            return !v || !o || new Date(v) >= new Date(o) || 'End date must be on or after start date.';
        }
    };

    function validateField(field) {
        const rules = (field.dataset.rules || '').split('|').filter(Boolean);
        let error = '';
        for (const rule of rules) {
            const [name, arg] = rule.split(':');
            const fn = validators[name];
            if (!fn) continue;
            const result = fn(field.value, arg, field.form);
            if (result !== true) { error = result; break; }
        }
        let msg = field.parentElement.querySelector('.error-msg');
        if (!msg) {
            msg = document.createElement('small');
            msg.className = 'error-msg';
            field.insertAdjacentElement('afterend', msg);
        }
        msg.textContent = error;
        field.classList.toggle('invalid', !!error);
        field.classList.toggle('valid', !error && field.value !== '');
        return !error;
    }

    document.querySelectorAll('form.validate').forEach(form => {
        const fields = form.querySelectorAll('[data-rules]');
        fields.forEach(f => {
            f.addEventListener('blur', () => validateField(f));
            f.addEventListener('input', () => { if (f.classList.contains('invalid')) validateField(f); });
        });
        form.addEventListener('submit', e => {
            let ok = true;
            fields.forEach(f => { if (!validateField(f)) ok = false; });
            if (!ok) {
                e.preventDefault();
                form.querySelector('.invalid')?.focus();
            }
        });
    });

    // ---------- Booking price calculator ----------
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        const rate = parseFloat(bookingForm.dataset.rate);
        const start = bookingForm.elements['start_date'];
        const end = bookingForm.elements['end_date'];
        const daysEl = document.getElementById('sumDays');
        const totalEl = document.getElementById('sumTotal');
        const fmt = n => '₹' + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const calc = () => {
            if (start.value) end.min = start.value;
            if (start.value && end.value && new Date(end.value) >= new Date(start.value)) {
                const days = Math.round((new Date(end.value) - new Date(start.value)) / 86400000) + 1;
                daysEl.textContent = days;
                totalEl.textContent = fmt(days * rate);
            } else {
                daysEl.textContent = '0';
                totalEl.textContent = fmt(0);
            }
        };
        start.addEventListener('change', calc);
        end.addEventListener('change', calc);
        calc();
    }

    // ---------- Instant table search (admin tables) ----------
    document.querySelectorAll('[data-table-search]').forEach(input => {
        const table = document.querySelector(input.dataset.tableSearch);
        if (!table) return;
        input.addEventListener('input', () => {
            const q = input.value.toLowerCase();
            table.querySelectorAll('tbody tr').forEach(tr => {
                tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    });

    // ---------- Image preview on upload ----------
    const imgInput = document.getElementById('imageInput');
    const imgPreview = document.getElementById('imagePreview');
    if (imgInput && imgPreview) {
        imgInput.addEventListener('change', () => {
            const file = imgInput.files[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) { alert('Image must be under 2 MB.'); imgInput.value = ''; return; }
            imgPreview.src = URL.createObjectURL(file);
            imgPreview.style.display = 'block';
        });
    }

    // ---------- Auto-submit filter selects ----------
    document.querySelectorAll('.auto-submit').forEach(sel => sel.addEventListener('change', () => sel.form.submit()));
});
