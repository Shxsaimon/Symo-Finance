<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Autocadastro (Criar Conta)
 */
?>
<div class="auth-header" style="margin-bottom: 1.5rem;">
    <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--symo-on-surface, #1a1c1c); margin: 0 0 0.35rem 0;">
        Criar Conta
    </h2>
    <p style="font-size: 0.9rem; color: var(--symo-outline, #7b7489); margin: 0;">
        Cadastre-se para começar a organizar suas finanças no Symo.
    </p>
</div>

<form action="<?= BASE_URL ?>/registrar" method="POST" autocomplete="on">
    <?= CsrfHelper::inputField() ?>

    <div style="margin-bottom: 1.25rem;">
        <label for="nome" style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--symo-on-surface, #1a1c1c); margin-bottom: 0.4rem;">
            Nome completo
        </label>
        <input
            type="text"
            id="nome"
            name="nome"
            value="<?= htmlspecialchars($oldNome ?? '', ENT_QUOTES, 'UTF-8') ?>"
            required
            autofocus
            minlength="3"
            placeholder="Seu nome completo"
            style="width: 100%; box-sizing: border-box; padding: 0.75rem 0.9rem; font-size: 0.95rem; font-family: inherit; border: 1px solid #ccc3da; border-radius: 0.5rem; background-color: #ffffff; color: #1a1c1c; outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
            onfocus="this.style.borderColor='var(--symo-primary-brand, #6f02ff)'; this.style.boxShadow='0 0 0 3px rgba(111, 2, 255, 0.15)';"
            onblur="this.style.borderColor='#ccc3da'; this.style.boxShadow='none';"
        >
    </div>

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
            placeholder="seu.email@exemplo.com.br"
            style="width: 100%; box-sizing: border-box; padding: 0.75rem 0.9rem; font-size: 0.95rem; font-family: inherit; border: 1px solid #ccc3da; border-radius: 0.5rem; background-color: #ffffff; color: #1a1c1c; outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
            onfocus="this.style.borderColor='var(--symo-primary-brand, #6f02ff)'; this.style.boxShadow='0 0 0 3px rgba(111, 2, 255, 0.15)';"
            onblur="this.style.borderColor='#ccc3da'; this.style.boxShadow='none';"
        >
    </div>

    <div style="margin-bottom: 1.25rem;">
        <label for="senha" style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--symo-on-surface, #1a1c1c); margin-bottom: 0.4rem;">
            Senha
        </label>
        <input
            type="password"
            id="senha"
            name="senha"
            required
            minlength="8"
            pattern="(?=.*[A-Za-z])(?=.*\d).{8,}"
            title="Mínimo de 8 caracteres, incluindo letras e números"
            placeholder="Mínimo 8 caracteres, com letras e números"
            style="width: 100%; box-sizing: border-box; padding: 0.75rem 0.9rem; font-size: 0.95rem; font-family: inherit; border: 1px solid #ccc3da; border-radius: 0.5rem; background-color: #ffffff; color: #1a1c1c; outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
            onfocus="this.style.borderColor='var(--symo-primary-brand, #6f02ff)'; this.style.boxShadow='0 0 0 3px rgba(111, 2, 255, 0.15)';"
            onblur="this.style.borderColor='#ccc3da'; this.style.boxShadow='none';"
        >
    </div>

    <div style="margin-bottom: 1.25rem;">
        <label for="senha_confirmacao" style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--symo-on-surface, #1a1c1c); margin-bottom: 0.4rem;">
            Confirmar senha
        </label>
        <input
            type="password"
            id="senha_confirmacao"
            name="senha_confirmacao"
            required
            minlength="8"
            placeholder="Repita a senha informada"
            style="width: 100%; box-sizing: border-box; padding: 0.75rem 0.9rem; font-size: 0.95rem; font-family: inherit; border: 1px solid #ccc3da; border-radius: 0.5rem; background-color: #ffffff; color: #1a1c1c; outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
            onfocus="this.style.borderColor='var(--symo-primary-brand, #6f02ff)'; this.style.boxShadow='0 0 0 3px rgba(111, 2, 255, 0.15)';"
            onblur="this.style.borderColor='#ccc3da'; this.style.boxShadow='none';"
        >
    </div>

    <button
        type="submit"
        style="width: 100%; box-sizing: border-box; padding: 0.85rem; font-size: 1rem; font-weight: 700; font-family: inherit; background-color: var(--symo-primary-brand, #6f02ff); color: #ffffff; border: none; border-radius: 0.5rem; cursor: pointer; transition: background-color 0.2s, transform 0.1s; margin-top: 0.5rem;"
        onmouseover="this.style.backgroundColor='#5300c4';"
        onmouseout="this.style.backgroundColor='var(--symo-primary-brand, #6f02ff)';"
    >
        Criar minha conta
    </button>
</form>

<div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--symo-outline, #7b7489);">
    Já tem uma conta?
    <a href="<?= BASE_URL ?>/login" style="color: var(--symo-primary-brand, #6f02ff); text-decoration: none; font-weight: 600;">
        Entrar
    </a>
</div>
