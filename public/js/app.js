/**
 * Symo — Script Principal da Aplicação (Vanilla JS)
 * Gerencia interações de interface, responsividade, máscaras e modais.
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Inicialização de Tooltips do Bootstrap (quando existirem)
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }

    // 2. Controle de alternância de visibilidade de senha
    const togglePassButtons = document.querySelectorAll('.btn-toggle-password');
    togglePassButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (input) {
                const isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');
                const icon = button.querySelector('i');
                if (icon) {
                    icon.classList.toggle('bi-eye', !isPassword);
                    icon.classList.toggle('bi-eye-slash', isPassword);
                }
            }
        });
    });

    // 3. Máscara de Moeda Brasileira (R$ 0,00) em campos monetários
    const moneyInputs = document.querySelectorAll('.money-mask');
    moneyInputs.forEach(input => {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value === '') {
                e.target.value = '';
                return;
            }
            value = (Number(value) / 100).toFixed(2) + '';
            value = value.replace('.', ',');
            value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            e.target.value = 'R$ ' + value;
        });
    });

    // 4. Auto-dismiss amigável para alertas flash após 8 segundos
    const flashAlerts = document.querySelectorAll('.flash-messages-container .alert');
    flashAlerts.forEach(alert => {
        setTimeout(() => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                if (bsAlert) {
                    bsAlert.close();
                }
            }
        }, 8000);
    });
});
