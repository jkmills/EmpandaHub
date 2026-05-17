/* EmpandaHub — App JS */

document.addEventListener('DOMContentLoaded', () => {

    /* ---- Mobile sidebar ---- */
    const sidebar  = document.getElementById('sidebar');
    const toggle   = document.getElementById('menuToggle');
    let overlay;

    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('open');
        overlay = overlay || createOverlay();
        overlay.classList.add('show');
    }
    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('show');
    }
    function createOverlay() {
        const el = document.createElement('div');
        el.className = 'sidebar-overlay';
        el.addEventListener('click', closeSidebar);
        document.body.appendChild(el);
        return el;
    }

    if (toggle) toggle.addEventListener('click', () =>
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar()
    );

    /* ---- Flash auto-dismiss ---- */
    document.querySelectorAll('.flash').forEach(el => {
        const close = el.querySelector('.flash-close');
        // Auto-dismiss after 6s
        const timer = setTimeout(() => {
            el.style.transition = 'opacity .4s, transform .4s';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-4px)';
            setTimeout(() => el.remove(), 400);
        }, 6000);
        if (close) close.addEventListener('click', () => {
            clearTimeout(timer);
            el.remove();
        });
    });

    /* ---- Confirm actions ---- */
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm || 'Are you sure?')) e.preventDefault();
        });
    });

    /* ---- Textarea character counter ---- */
    document.querySelectorAll('textarea[maxlength]').forEach(ta => {
        const max  = parseInt(ta.getAttribute('maxlength'));
        const wrap = document.createElement('div');
        wrap.style.cssText = 'display:flex;justify-content:flex-end;margin-top:.2rem';
        const counter = document.createElement('span');
        counter.style.cssText = 'font-size:.72rem;color:#9ca3af';
        wrap.appendChild(counter);
        ta.parentNode.insertBefore(wrap, ta.nextSibling);
        const update = () => {
            const remaining = max - ta.value.length;
            counter.textContent = `${ta.value.length} / ${max}`;
            counter.style.color = remaining < 50 ? '#f59e0b' : '#9ca3af';
        };
        ta.addEventListener('input', update);
        update();
    });

    /* ---- Table row click (if data-href) ---- */
    document.querySelectorAll('tr[data-href]').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', e => {
            if (e.target.closest('a,button,form,input,select,textarea')) return;
            window.location = row.dataset.href;
        });
    });

    /* ---- Details dropdown close on outside click ---- */
    document.addEventListener('click', e => {
        document.querySelectorAll('details[open]').forEach(d => {
            if (!d.contains(e.target)) d.removeAttribute('open');
        });
    });

    /* ---- Confirm typed input (data wipe) ---- */
    document.querySelectorAll('input[data-match]').forEach(input => {
        const btn = input.closest('form')?.querySelector('button[type=submit]');
        if (!btn) return;
        btn.disabled = true;
        btn.style.opacity = '.5';
        input.addEventListener('input', () => {
            const ok = input.value === input.dataset.match;
            btn.disabled = !ok;
            btn.style.opacity = ok ? '1' : '.5';
        });
    });

    /* ---- Live brand color preview ---- */
    const colorPicker = document.getElementById('primaryColorPicker');
    if (colorPicker) {
        colorPicker.addEventListener('input', () => {
            document.documentElement.style.setProperty('--brand', colorPicker.value);
        });
    }

    /* ---- Sidebar style live preview ---- */
    document.querySelectorAll('input[name="sidebar_style"]').forEach(radio => {
        radio.addEventListener('change', () => {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.remove('sidebar--light', 'sidebar--brand');
                if (radio.value !== 'dark') sidebar.classList.add('sidebar--' + radio.value);
            }
            document.querySelectorAll('.sidebar-style-opt').forEach(opt => {
                opt.classList.toggle('is-selected', opt.querySelector('input') === radio);
            });
        });
    });

    /* ---- Number format display (stat values) ---- */
    document.querySelectorAll('.stat-value[data-raw]').forEach(el => {
        const n = parseFloat(el.dataset.raw);
        if (!isNaN(n)) {
            el.textContent = n >= 1000
                ? (n >= 1000000 ? (n / 1000000).toFixed(1) + 'M' : (n / 1000).toFixed(1) + 'k')
                : el.textContent;
        }
    });
});
