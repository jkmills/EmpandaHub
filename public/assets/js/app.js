// EmpandaHub — Vanilla JS

document.addEventListener('DOMContentLoaded', () => {
    // Mobile sidebar toggle
    const toggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // Auto-dismiss flash messages after 5s
    document.querySelectorAll('.flash').forEach(el => {
        setTimeout(() => el.remove(), 5000);
    });

    // Confirm delete buttons
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
            if (!confirm(el.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

    // Dynamic character counters on textareas with maxlength
    document.querySelectorAll('textarea[maxlength]').forEach(ta => {
        const max = parseInt(ta.getAttribute('maxlength'));
        const counter = document.createElement('span');
        counter.className = 'text-muted';
        counter.style.cssText = 'font-size:.78rem;display:block;text-align:right;margin-top:.2rem';
        ta.parentNode.insertBefore(counter, ta.nextSibling);
        const update = () => counter.textContent = `${ta.value.length} / ${max}`;
        ta.addEventListener('input', update);
        update();
    });
});
