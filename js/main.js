/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: js/main.js
 * 
 * Global Client-Side JavaScript Handlers
 * Author: G. Praveen
 */

document.addEventListener('DOMContentLoaded', () => {
    // Auto-dismiss standard alerts after 6 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 6000);
    });

    // Password visibility toggle helpers
    const toggleBtns = document.querySelectorAll('[data-toggle="password"]');
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (input) {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                btn.innerHTML = isPassword ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
            }
        });
    });
});
