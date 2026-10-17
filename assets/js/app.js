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

    /* ---- Page title flies letter-by-letter into the topbar ---------- */
    const crumb = document.querySelector('[data-page-crumb]');
    const heading = document.querySelector('.page .page-title');
    const topbar = document.querySelector('.topbar');

    if (crumb && heading && topbar) {
        const crumbTextEl = crumb.querySelector('.topbar__crumb-text');
        const rawText = heading.textContent.trim();
        const LETTER_STEP = 18;   // stagger between letters (ms)
        const FLIGHT = 420;       // per-letter flight duration (ms)

        /* Build the crumb's own per-letter target spans (decorative only —
           the real <h1> keeps its text for layout and screen readers). */
        crumbTextEl.textContent = '';
        const crumbLetters = [...rawText].map((ch) => {
            const span = document.createElement('span');
            span.className = 'topbar__crumb-letter';
            span.textContent = ch === ' ' ? '\u00A0' : ch;
            crumbTextEl.appendChild(span);
            return span;
        });

        /* Measure each character of the real heading via the Range API so we
           never have to split the <h1> DOM (keeps its a11y intact). */
        const headingCharRects = () => {
            const node = heading.firstChild;
            const rects = [];
            if (!node || node.nodeType !== Node.TEXT_NODE) return rects;
            const range = document.createRange();
            for (let i = 0; i < node.textContent.length; i++) {
                range.setStart(node, i);
                range.setEnd(node, i + 1);
                rects.push(range.getBoundingClientRect());
            }
            return rects;
        };

        /* Show/hide the crumb instantly (no fade) so the arriving letters
           don't double-up with a fading copy. */
        const setCrumbVisible = (visible) => {
            const prev = crumb.style.transition;
            crumb.style.transition = 'none';
            crumb.classList.toggle('is-visible', visible);
            void crumb.offsetWidth; // flush so the change applies immediately
            crumb.style.transition = prev;
        };

        let docked = false;
        let flying = false;

        const fly = (toDock) => {
            if (flying) return;
            flying = true;

            /* Measure both ends. Crumb is forced visible while measuring so
               its rects match where the letters will truly rest. */
            setCrumbVisible(true);
            crumbLetters.forEach((l) => { l.style.visibility = 'hidden'; });

            const fromRects = toDock ? headingCharRects() : crumbLetters.map((l) => l.getBoundingClientRect());
            const toRects = toDock ? crumbLetters.map((l) => l.getBoundingClientRect()) : headingCharRects();

            const headingCS = getComputedStyle(heading);
            const crumbCS = getComputedStyle(crumbTextEl);
            const headingSize = parseFloat(headingCS.fontSize);
            const crumbSize = parseFloat(crumbCS.fontSize);

            const layer = document.createElement('div');
            layer.className = 'flight-layer';

            const clones = [];
            crumbLetters.forEach((letter, i) => {
                const from = fromRects[i];
                const to = toRects[i];
                if (!from || !to || (!from.width && !from.height)) return;

                const clone = document.createElement('span');
                clone.className = 'flight-letter';
                clone.textContent = letter.textContent;
                clone.style.left = from.left + 'px';
                clone.style.top = from.top + 'px';
                clone.style.fontSize = (toDock ? headingSize : crumbSize) + 'px';
                clone.style.fontFamily = toDock ? headingCS.fontFamily : crumbCS.fontFamily;
                clone.style.fontWeight = toDock ? headingCS.fontWeight : crumbCS.fontWeight;
                clone.style.letterSpacing = toDock ? headingCS.letterSpacing : crumbCS.letterSpacing;
                clone.style.color = toDock ? headingCS.color : crumbCS.color;
                layer.appendChild(clone);
                clones.push({ clone, from, to });
            });

            /* Hide the real heading + crumb letters while their clones fly. */
            heading.style.visibility = 'hidden';
            document.body.appendChild(layer);

            void layer.offsetWidth; // flush starting styles before transitioning

            clones.forEach(({ clone, from, to }, i) => {
                const dx = to.left - from.left;
                const dy = to.top - from.top;
                const scale = (toDock ? crumbSize : headingSize) / (toDock ? headingSize : crumbSize);
                clone.style.transitionDelay = i * LETTER_STEP + 'ms';
                clone.style.transform = 'translate(' + dx + 'px,' + dy + 'px) scale(' + scale + ')';
                clone.style.color = toDock ? crumbCS.color : headingCS.color;
            });

            const total = (clones.length - 1) * LETTER_STEP + FLIGHT;
            setTimeout(() => {
                layer.remove();
                crumbLetters.forEach((l) => { l.style.visibility = ''; });
                /* Exactly one copy is ever visible: the crumb when docked,
                   the page heading when not. visibility (not display) keeps
                   layout stable so scroll measurements stay correct. */
                heading.style.visibility = toDock ? 'hidden' : '';
                setCrumbVisible(toDock);
                docked = toDock;
                flying = false;
            }, total + 40);
        };

        const update = () => {
            if (flying) return;
            const topbarHeight = topbar.offsetHeight;
            const shouldDock = heading.getBoundingClientRect().top <= topbarHeight;
            if (shouldDock !== docked) fly(shouldDock);
        };

        /* Start already-docked if the page loads mid-scroll. */
        if (heading.getBoundingClientRect().top <= topbar.offsetHeight) {
            docked = true;
            heading.style.visibility = 'hidden';
            setCrumbVisible(true);
        }

        window.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
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
