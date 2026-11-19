<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Recuperação de Senha
 */
?>
<div class="auth-header" style="margin-bottom: 1.5rem;">
    <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--symo-on-surface, #1a1c1c); margin: 0 0 0.35rem 0;">
        Recuperação de Senha
    </h2>
    <p style="font-size: 0.9rem; color: var(--symo-outline, #7b7489); margin: 0;">
        Informe seu e-mail para receber um link seguro de redefinição de acesso.
    </p>
</div>

<form action="<?= BASE_URL ?>/esqueci-senha" method="POST" autocomplete="on">
    <?= CsrfHelper::inputField() ?>

    <div style="margin-bottom: 1.5rem;">
        <label for="email" style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--symo-on-surface, #1a1c1c); margin-bottom: 0.4rem;">
            E-mail cadastrado
        </label>
        <input
            type="email"
            id="email"
            name="email"
            required
            autofocus
            placeholder="seu.email@exemplo.com.br"
            style="width: 100%; box-sizing: border-box; padding: 0.75rem 0.9rem; font-size: 0.95rem; font-family: inherit; border: 1px solid #ccc3da; border-radius: 0.5rem; background-color: #ffffff; color: #1a1c1c; outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
            onfocus="this.style.borderColor='var(--symo-primary-brand, #6f02ff)'; this.style.boxShadow='0 0 0 3px rgba(111, 2, 255, 0.15)';"
            onblur="this.style.borderColor='#ccc3da'; this.style.boxShadow='none';"
        >
        <p style="font-size: 0.75rem; color: var(--symo-outline, #7b7489); margin: 0.4rem 0 0 0;">
            O link de redefinição será enviado para este e-mail e terá validade de 60 minutos.
        </p>
    </div>

    <button
        type="submit"
        style="width: 100%; padding: 0.85rem; font-size: 0.95rem; font-weight: 700; font-family: inherit; background-color: var(--symo-primary-brand, #6f02ff); color: #ffffff; border: none; border-radius: 0.5rem; cursor: pointer; transition: background-color 0.2s; box-shadow: 0 4px 12px rgba(111, 2, 255, 0.2);"
        onmouseover="this.style.backgroundColor='#5300c4';"
        onmouseout="this.style.backgroundColor='var(--symo-primary-brand, #6f02ff)';"
    >
        Enviar Link de Redefinição
    </button>
</form>

<div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #e3e2e2; text-align: center;">
    <p style="font-size: 0.85rem; color: var(--symo-outline, #7b7489); margin: 0;">
        Lembrou da sua senha?
        <a href="<?= BASE_URL ?>/login" style="color: var(--symo-primary-brand, #6f02ff); font-weight: 600; text-decoration: none;">
            Voltar para o Login
        </a>
    </p>
</div>
