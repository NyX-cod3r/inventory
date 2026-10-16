document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-validate]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const requiredInputs = form.querySelectorAll('[required]');
            let isValid = true;

            requiredInputs.forEach((input) => {
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
});
