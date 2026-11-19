<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Redefinição de Senha (Nova Senha)
 */
?>
<div class="auth-header" style="margin-bottom: 1.5rem;">
    <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--symo-on-surface, #1a1c1c); margin: 0 0 0.35rem 0;">
        Criar Nova Senha
    </h2>
    <p style="font-size: 0.9rem; color: var(--symo-outline, #7b7489); margin: 0;">
        Defina uma nova senha segura para a sua conta.
    </p>
</div>

<?php if (empty($isValid)): ?>
    <div style="background-color: #fff4f4; border: 1px solid #ffd4d4; border-radius: 0.5rem; padding: 1.25rem; margin-bottom: 1.5rem; text-align: center;">
        <div style="font-size: 2rem; margin-bottom: 0.5rem;">⚠️</div>
        <h3 style="font-size: 1.05rem; font-weight: 700; color: #ba1a1a; margin: 0 0 0.5rem 0;">
            Link Inválido ou Expirado
        </h3>
        <p style="font-size: 0.85rem; color: #4a4457; line-height: 1.5; margin: 0 0 1.25rem 0;">
            O link de redefinição de senha utilizado não é mais válido ou o prazo de 60 minutos expirou.
        </p>
        <a 
            href="<?= BASE_URL ?>/esqueci-senha" 
            style="display: inline-block; padding: 0.75rem 1.25rem; font-size: 0.9rem; font-weight: 700; background-color: var(--symo-primary-brand, #6f02ff); color: #ffffff; text-decoration: none; border-radius: 0.5rem;"
        >
            Solicitar Novo Link
        </a>
    </div>
    <div style="text-align: center;">
        <a href="<?= BASE_URL ?>/login" style="font-size: 0.85rem; color: var(--symo-outline, #7b7489); text-decoration: none;">
            ← Voltar para o Login
        </a>
    </div>
<?php else: ?>
    <form action="<?= BASE_URL ?>/redefinir-senha" method="POST" autocomplete="off">
        <?= CsrfHelper::inputField() ?>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <div style="margin-bottom: 1.25rem;">
            <label for="senha" style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--symo-on-surface, #1a1c1c); margin-bottom: 0.4rem;">
                Nova Senha
            </label>
            <div style="position: relative;">
                <input 
                    type="password" 
                    id="senha" 
                    name="senha" 
                    required 
                    autofocus
                    placeholder="Mínimo 8 caracteres (letras e números)"
                    style="width: 100%; box-sizing: border-box; padding: 0.75rem 2.75rem 0.75rem 0.9rem; font-size: 0.95rem; font-family: inherit; border: 1px solid #ccc3da; border-radius: 0.5rem; background-color: #ffffff; color: #1a1c1c; outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
                    onfocus="this.style.borderColor='var(--symo-primary-brand, #6f02ff)'; this.style.boxShadow='0 0 0 3px rgba(111, 2, 255, 0.15)';"
                    onblur="this.style.borderColor='#ccc3da'; this.style.boxShadow='none';"
                >
                <button 
                    type="button" 
                    id="btn-toggle-senha" 
                    aria-label="Alternar visibilidade da senha"
                    style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #7b7489; font-size: 0.85rem; font-weight: 600; padding: 4px;"
                >
                    Ver
                </button>
            </div>
            <p style="font-size: 0.75rem; color: var(--symo-outline, #7b7489); margin: 0.4rem 0 0 0;">
                Deve conter pelo menos 8 caracteres, combinando letras e números.
            </p>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label for="senha_confirmacao" style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--symo-on-surface, #1a1c1c); margin-bottom: 0.4rem;">
                Confirme a Nova Senha
            </label>
            <div style="position: relative;">
                <input 
                    type="password" 
                    id="senha_confirmacao" 
                    name="senha_confirmacao" 
                    required 
                    placeholder="Repita a nova senha"
                    style="width: 100%; box-sizing: border-box; padding: 0.75rem 2.75rem 0.75rem 0.9rem; font-size: 0.95rem; font-family: inherit; border: 1px solid #ccc3da; border-radius: 0.5rem; background-color: #ffffff; color: #1a1c1c; outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
                    onfocus="this.style.borderColor='var(--symo-primary-brand, #6f02ff)'; this.style.boxShadow='0 0 0 3px rgba(111, 2, 255, 0.15)';"
                    onblur="this.style.borderColor='#ccc3da'; this.style.boxShadow='none';"
                >
                <button 
                    type="button" 
                    id="btn-toggle-senha-conf" 
                    aria-label="Alternar visibilidade da confirmação de senha"
                    style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #7b7489; font-size: 0.85rem; font-weight: 600; padding: 4px;"
                >
                    Ver
                </button>
            </div>
        </div>

        <button 
            type="submit" 
            style="width: 100%; padding: 0.85rem; font-size: 0.95rem; font-weight: 700; font-family: inherit; background-color: var(--symo-primary-brand, #6f02ff); color: #ffffff; border: none; border-radius: 0.5rem; cursor: pointer; transition: background-color 0.2s; box-shadow: 0 4px 12px rgba(111, 2, 255, 0.2);"
            onmouseover="this.style.backgroundColor='#5300c4';"
            onmouseout="this.style.backgroundColor='var(--symo-primary-brand, #6f02ff)';"
        >
            Atualizar Minha Senha
        </button>
    </form>

    <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #e3e2e2; text-align: center;">
        <a href="<?= BASE_URL ?>/login" style="font-size: 0.85rem; color: var(--symo-outline, #7b7489); text-decoration: none;">
            ← Voltar para o Login
        </a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function setupToggle(btnId, inputId) {
                const btn = document.getElementById(btnId);
                const input = document.getElementById(inputId);
                if (!btn || !input) return;
                btn.addEventListener('click', function() {
                    const isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';
                    btn.textContent = isPassword ? 'Ocultar' : 'Ver';
                });
            }
            setupToggle('btn-toggle-senha', 'senha');
            setupToggle('btn-toggle-senha-conf', 'senha_confirmacao');
        });
    </script>
<?php endif; ?>
