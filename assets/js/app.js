document.addEventListener('DOMContentLoaded', () => {
    /* ---- Required-field validation ---------------------------------- */
    document.querySelectorAll('form[data-validate]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            let isValid = true;

            form.querySelectorAll('[required]').forEach((input) => {
                input.setCustomValidity('');
                if (!input.value.trim()) {
                    input.setCustomValidity('This field is required.');
                    isValid = false;
                }
            });

            if (!isValid || !form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
            }
        });
    });

    /* ---- TRANSFER destination field toggling ------------------------ */
    const movementType = document.querySelector('#movement_type');
    const transferField = document.querySelector('#transfer-field');
    const destinationWarehouse = document.querySelector('#to_warehouse_id');

    if (movementType && transferField && destinationWarehouse) {
        const updateTransferField = () => {
            const isTransfer = movementType.value === 'TRANSFER';
            transferField.hidden = !isTransfer;
            destinationWarehouse.required = isTransfer;
            destinationWarehouse.disabled = !isTransfer;
        };

        movementType.addEventListener('change', updateTransferField);
        updateTransferField();
    }

    /* ---- Destructive-action confirmation ---------------------------- */
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });

    /* ---- Right-side drawer ------------------------------------------ */
    const drawer = document.querySelector('.drawer');

    if (drawer) {
        const panel = drawer.querySelector('.drawer__panel');
        const openers = document.querySelectorAll('[data-drawer-open]');

        const focusable = () =>
            panel.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled])'
            );

        const open = () => {
            drawer.classList.add('is-open');
            document.body.style.overflow = 'hidden';
            const first = focusable()[0];
            if (first) first.focus();
        };

        /* Any control that dismisses the drawer just follows its plain href,
           so this stays fully functional without JavaScript. */
        const close = () => {
            drawer.classList.remove('is-open');
            document.body.style.overflow = '';
        };

        openers.forEach((el) => el.addEventListener('click', open));
        drawer.querySelectorAll('[data-drawer-close]').forEach((el) =>
            el.addEventListener('click', close)
        );

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape' || !drawer.classList.contains('is-open')) return;
            close();
        });

        /* Trap focus inside the panel while it is open. */
        drawer.addEventListener('keydown', (event) => {
            if (event.key !== 'Tab' || !drawer.classList.contains('is-open')) return;
            const items = focusable();
            if (!items.length) return;
            const first = items[0];
            const last = items[items.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });
    }

    /* ---- Auto-dismiss flash alerts ---------------------------------- */
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach((alert) => {
        setTimeout(() => {
            alert.style.transition = 'opacity 300ms ease, margin 300ms ease';
            alert.style.opacity = '0';
            alert.style.marginBottom = '0';
            setTimeout(() => alert.remove(), 320);
        }, 4000);
    });
});
