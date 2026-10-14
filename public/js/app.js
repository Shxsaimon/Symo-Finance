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

    // 5. Controle de visibilidade dinâmica da barra de rolagem (Auto-hide scrollbar)
    const activeScrollTimers = new WeakMap();
    let currentHoverTarget = null;
    let hoverTimer = null;

    const deactivateScrollbar = (element) => {
        if (!element || !element.classList) return;
        element.classList.remove('is-scrolling');
        const timer = activeScrollTimers.get(element);
        if (timer) {
            clearTimeout(timer);
            activeScrollTimers.delete(element);
        }
    };

    const activateScrollbar = (element, duration = 1200) => {
        if (!element || !element.classList) return;
        element.classList.add('is-scrolling');

        const existingTimer = activeScrollTimers.get(element);
        if (existingTimer) {
            clearTimeout(existingTimer);
        }

        const newTimer = setTimeout(() => {
            if (currentHoverTarget !== element) {
                element.classList.remove('is-scrolling');
                activeScrollTimers.delete(element);
            }
        }, duration);

        activeScrollTimers.set(element, newTimer);
    };

    // Ativa a scrollbar ao rolar a página ou container
    window.addEventListener('scroll', (event) => {
        const target = event.target === document ? document.documentElement : event.target;
        activateScrollbar(target, 1200);
    }, { capture: true, passive: true });

    // Ativa a scrollbar instantaneamente ao girar a roda do mouse
    window.addEventListener('wheel', (event) => {
        let el = event.target;
        while (el && el !== document) {
            if (el.scrollHeight > el.clientHeight || el.scrollWidth > el.clientWidth) {
                activateScrollbar(el, 1200);
                break;
            }
            el = el.parentElement;
        }
    }, { passive: true });

    // Localiza o ancestral rolável
    const findScrollableAncestor = (target) => {
        let el = target;
        while (el && el !== document) {
            if (el.scrollHeight > el.clientHeight) {
                return el;
            }
            el = el.parentElement;
        }
        return null;
    };

    // Monitora a posição do cursor: ativa apenas se o mouse repousar imóvel por pelo menos 1s (1000ms) na calha da barra
    document.addEventListener('mousemove', (event) => {
        const scrollable = findScrollableAncestor(event.target);
        let isDirectlyOverScrollbar = false;

        if (scrollable) {
            const rect = scrollable.getBoundingClientRect();
            // A calha ocupa a borda direita (últimos 12px)
            isDirectlyOverScrollbar = (
                event.clientX >= rect.right - 12 &&
                event.clientX <= rect.right &&
                event.clientY >= rect.top &&
                event.clientY <= rect.bottom
            );
        }

        if (isDirectlyOverScrollbar && scrollable) {
            currentHoverTarget = scrollable;

            // Se a barra já estiver ativada e o mouse estiver na calha, mantém sem reiniciar
            if (scrollable.classList.contains('is-scrolling')) {
                return;
            }

            // O mouse está se movendo na calha: reseta o temporizador de 1s
            // Qualquer movimento contínuo cancela o disparo; só ativa se o cursor parar imóvel por 1s
            if (hoverTimer) {
                clearTimeout(hoverTimer);
            }
            hoverTimer = setTimeout(() => {
                if (currentHoverTarget === scrollable) {
                    activateScrollbar(scrollable, 2500);
                }
                hoverTimer = null;
            }, 300); // 0.3 segundo (300ms) de repouso ininterrupto
        } else {
            // Saiu da calha da scrollbar: cancela timer pendente imediatamente
            if (hoverTimer) {
                clearTimeout(hoverTimer);
                hoverTimer = null;
            }
            if (currentHoverTarget) {
                const prev = currentHoverTarget;
                currentHoverTarget = null;
                activateScrollbar(prev, 150); // Inicia desvanecimento suave imediato
            }
        }
    }, { passive: true });

    // Cancela e esconde ao sair da janela
    document.addEventListener('mouseleave', () => {
        if (hoverTimer) {
            clearTimeout(hoverTimer);
            hoverTimer = null;
        }
        if (currentHoverTarget) {
            deactivateScrollbar(currentHoverTarget);
            currentHoverTarget = null;
        }
    });
});
