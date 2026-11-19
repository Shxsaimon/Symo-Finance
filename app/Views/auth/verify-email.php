<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Confirmação de E-mail (Código de 6 dígitos)
 */
?>
<div class="auth-header" style="margin-bottom: 1.5rem; text-align: center;">
    <div style="width: 52px; height: 52px; margin: 0 auto 1rem auto; border-radius: 50%; background-color: #f5efff; display: flex; align-items: center; justify-content: center; color: var(--symo-primary-brand, #6f02ff); font-size: 1.6rem;">
        ✉️
    </div>
    <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--symo-on-surface, #1a1c1c); margin: 0 0 0.35rem 0;">
        Verifique seu E-mail
    </h2>
    <p style="font-size: 0.9rem; color: var(--symo-outline, #7b7489); margin: 0; line-height: 1.4;">
        Enviamos um código de 6 dígitos para:<br>
        <strong style="color: var(--symo-on-surface, #1a1c1c);"><?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
    </p>
</div>

<form action="<?= BASE_URL ?>/verificar-email" method="POST" autocomplete="off">
    <?= CsrfHelper::inputField() ?>

    <div style="margin-bottom: 1.5rem;">
        <label for="codigo" style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--symo-on-surface, #1a1c1c); margin-bottom: 0.5rem; text-align: center;">
            Digite o código de 6 dígitos
        </label>
        <input 
            type="text" 
            id="codigo" 
            name="codigo" 
            maxlength="6" 
            inputmode="numeric" 
            pattern="[0-9]{6}" 
            required 
            autofocus 
            placeholder="000000"
            style="width: 100%; box-sizing: border-box; padding: 0.85rem 0.5rem; font-size: 1.85rem; font-family: monospace; font-weight: 800; letter-spacing: 0.65rem; text-align: center; border: 2px solid #ccc3da; border-radius: 0.5rem; background-color: #ffffff; color: #1a1c1c; outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
            onfocus="this.style.borderColor='var(--symo-primary-brand, #6f02ff)'; this.style.boxShadow='0 0 0 3px rgba(111, 2, 255, 0.15)';"
            onblur="this.style.borderColor='#ccc3da'; this.style.boxShadow='none';"
            oninput="this.value = this.value.replace(/[^0-9]/g, '');"
        >
        <p style="font-size: 0.75rem; color: var(--symo-outline, #7b7489); text-align: center; margin: 0.4rem 0 0 0;">
            O código é válido por 15 minutos.
        </p>
    </div>

    <button 
        type="submit" 
        style="width: 100%; padding: 0.85rem; font-size: 0.95rem; font-weight: 700; font-family: inherit; background-color: var(--symo-primary-brand, #6f02ff); color: #ffffff; border: none; border-radius: 0.5rem; cursor: pointer; transition: background-color 0.2s; box-shadow: 0 4px 12px rgba(111, 2, 255, 0.2);"
        onmouseover="this.style.backgroundColor='#5300c4';"
        onmouseout="this.style.backgroundColor='var(--symo-primary-brand, #6f02ff)';"
    >
        Confirmar Código
    </button>
</form>

<div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #e3e2e2; text-align: center;">
    <p style="font-size: 0.85rem; color: var(--symo-outline, #7b7489); margin: 0 0 0.5rem 0;">
        Não recebeu o e-mail ou o código expirou?
    </p>
    <form action="<?= BASE_URL ?>/verificar-email/reenviar" method="POST" style="margin: 0; display: inline-block;">
        <?= CsrfHelper::inputField() ?>
        <button 
            type="submit" 
            style="background: none; border: none; padding: 0; color: var(--symo-primary-brand, #6f02ff); font-size: 0.85rem; font-weight: 700; cursor: pointer; text-decoration: underline;"
        >
            Reenviar novo código
        </button>
    </form>
</div>

<div style="margin-top: 1.25rem; text-align: center;">
    <a href="<?= BASE_URL ?>/login" style="font-size: 0.85rem; color: var(--symo-outline, #7b7489); text-decoration: none;">
        ← Voltar para o Login
    </a>
</div>
