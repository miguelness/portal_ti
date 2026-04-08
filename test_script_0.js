
        // Notification Logic

        function handleNotificationClick(el) {
            // Se já foi lido por 4s, apenas alterna expansão normalmente
            if (el.classList.contains('completed')) {
                el.classList.toggle('expanded');
                return;
            }

            // Ativa expansão
            el.classList.add('expanded');
            el.classList.remove('pulse-attention');

            // Inicia timer se ainda não começou
            if (!el.dataset.timerStarted) {
                el.dataset.timerStarted = "true";
                
                // Força o reflow para a animação CSS da barra de progresso iniciar
                const bar = el.querySelector('.notification-timer-bar');
                void bar.offsetWidth; 

                setTimeout(() => {
                    el.classList.add('completed');
                    el.querySelector('.notification-close').classList.remove('locked');
                    const timerContainer = el.querySelector('.notification-timer-container');
                    if(timerContainer) timerContainer.classList.add('completed');
                }, 4000); // 4 segundos
            }
        }

        function dismissAlertForever(event, alertId) {
            event.stopPropagation();
            
            // Salva no localStorage
            let dismissed = JSON.parse(localStorage.getItem('dismissedAlerts') || '[]');
            if (!dismissed.includes(alertId)) {
                dismissed.push(alertId);
            }
            localStorage.setItem('dismissedAlerts', JSON.stringify(dismissed));
            
            // Remove o elemento da tela com efeito
            const item = document.querySelector(`.notification-item[data-alert-id="${alertId}"]`);
            if (item) {
                item.style.transform = 'translateX(100%)';
                item.style.opacity = '0';
                setTimeout(() => item.remove(), 300);
            }
        }

        // Check for dismissed alerts on load
        document.addEventListener('DOMContentLoaded', function() {
            const dismissed = JSON.parse(localStorage.getItem('dismissedAlerts') || '[]');
            dismissed.forEach(id => {
                const item = document.querySelector(`.notification-item[data-alert-id="${id}"]`);
                if (item) item.remove();
            });
        });

    <?php if(isset($treinamentos)): ?>
    