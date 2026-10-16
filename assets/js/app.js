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
});
