/**
 * Digital Document Repository - Custom JavaScript
 * Handles client-side form validation feedback and small UX enhancements
 */

document.addEventListener('DOMContentLoaded', function () {

    // ----- Auto-dismiss alerts after 5 seconds -----
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // ----- Bootstrap client-side validation styling -----
    const forms = document.querySelectorAll('form[novalidate]');
    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // ----- File input: show selected file name / basic size check -----
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            if (this.files.length > 0) {
                const sizeMB = (this.files[0].size / (1024 * 1024)).toFixed(2);
                console.log(this.files[0].name + ' selected (' + sizeMB + ' MB)');
            }
        });
    });

    // ----- Password confirm match live check (register / reset password) -----
    const pass = document.querySelector('input[name="password"], input[name="new_password"]');
    const confirm = document.querySelector('input[name="confirm_password"]');
    if (pass && confirm) {
        confirm.addEventListener('input', function () {
            if (confirm.value !== pass.value) {
                confirm.setCustomValidity('Passwords do not match');
            } else {
                confirm.setCustomValidity('');
            }
        });
    }
});
