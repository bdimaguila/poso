document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('ticket-form');
    const submitBtn = document.getElementById('submit-btn');

    form.addEventListener('input', function () {
        // Enable submit button only if all required inputs are filled
        const isFormValid = form.checkValidity();
        submitBtn.disabled = !isFormValid;
    });
});
