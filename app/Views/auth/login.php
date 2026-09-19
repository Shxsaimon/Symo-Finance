<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Login
 */
?>
<div class="auth-header" style="margin-bottom: 1.5rem;">
    <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--symo-on-surface, #1a1c1c); margin: 0 0 0.35rem 0;">
        Acessar Painel
    </h2>
    <p style="font-size: 0.9rem; color: var(--symo-outline, #7b7489); margin: 0;">
        Informe suas credenciais para gerenciar suas finanças.
    </p>
</div>

<form action="<?= BASE_URL ?>/login" method="POST" autocomplete="on">
    <?= CsrfHelper::inputField() ?>

    <div style="margin-bottom: 1.25rem;">
        <label for="email" style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--symo-on-surface, #1a1c1c); margin-bottom: 0.4rem;">
            E-mail
        </label>
        <input 
            type="email" 
            id="email" 
            name="email" 
            value="<?= htmlspecialchars($oldEmail ?? '', ENT_QUOTES, 'UTF-8') ?>" 
            required 
            autofocus 
            placeholder="seu.email@exemplo.com.br"
            style="width: 100%; box-sizing: border-box; padding: 0.75rem 0.9rem; font-size: 0.95rem; font-family: inherit; border: 1px solid #ccc3da; border-radius: 0.5rem; background-color: #ffffff; color: #1a1c1c; outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
            onfocus="this.style.borderColor='var(--symo-primary-brand, #6f02ff)'; this.style.boxShadow='0 0 0 3px rgba(111, 2, 255, 0.15)';"
            onblur="this.style.borderColor='#ccc3da'; this.style.boxShadow='none';"
        >
    </div>

    <div style="margin-bottom: 1.25rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
            <label for="senha" style="font-size: 0.85rem; font-weight: 600; color: var(--symo-on-surface, #1a1c1c);">
                Senha
            </label>
            <a href="<?= BASE_URL ?>/esqueci-senha" style="font-size: 0.8rem; color: var(--symo-primary-brand, #6f02ff); text-decoration: none; font-weight: 600;">
                Esqueceu a senha?
            </a>
        </div>
        <div style="position: relative;">
            <input 
                type="password" 
                id="senha" 
                name="senha" 
                required 
                placeholder="Sua senha de acesso"
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
    </div>

    <button 
        type="submit" 
        style="width: 100%; box-sizing: border-box; padding: 0.85rem; font-size: 1rem; font-weight: 700; font-family: inherit; background-color: var(--symo-primary-brand, #6f02ff); color: #ffffff; border: none; border-radius: 0.5rem; cursor: pointer; transition: background-color 0.2s, transform 0.1s; margin-top: 0.5rem;"
        onmouseover="this.style.backgroundColor='#5300c4';"
        onmouseout="this.style.backgroundColor='var(--symo-primary-brand, #6f02ff)';"
    >
        Entrar no Sistema
    </button>
</form>

<div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--symo-outline, #7b7489);">
    Ainda não tem uma conta?
    <a href="<?= BASE_URL ?>/registrar" style="color: var(--symo-primary-brand, #6f02ff); text-decoration: none; font-weight: 600;">
        Criar conta
    </a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('btn-toggle-senha');
        const passInput = document.getElementById('senha');

        if (toggleBtn && passInput) {
            toggleBtn.addEventListener('click', function() {
                const isPassword = passInput.getAttribute('type') === 'password';
                passInput.setAttribute('type', isPassword ? 'text' : 'password');
                toggleBtn.textContent = isPassword ? 'Ocultar' : 'Ver';
            });
        }
    });
</script>
