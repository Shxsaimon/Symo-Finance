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

<div style="display: flex; align-items: center; margin: 1.25rem 0 1rem 0;">
    <div style="flex: 1; border-bottom: 1px solid #e2d9ec;"></div>
    <span style="padding: 0 0.75rem; color: var(--symo-outline, #7b7489); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">ou</span>
    <div style="flex: 1; border-bottom: 1px solid #e2d9ec;"></div>
</div>

<a href="<?= BASE_URL ?>/auth/google"
   style="display: flex; align-items: center; justify-content: center; gap: 0.65rem; width: 100%; box-sizing: border-box; padding: 0.75rem 1rem; font-size: 0.95rem; font-weight: 600; font-family: inherit; color: var(--symo-on-surface, #1a1c1c); background-color: #ffffff; border: 1px solid #ccc3da; border-radius: 0.5rem; text-decoration: none; transition: background-color 0.2s, border-color 0.2s, box-shadow 0.2s;"
   onmouseover="this.style.backgroundColor='#f8f7fa'; this.style.borderColor='#7b7489';"
   onmouseout="this.style.backgroundColor='#ffffff'; this.style.borderColor='#ccc3da';"
>
    <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
        <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.616z" fill="#4285F4"/>
        <path d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z" fill="#34A853"/>
        <path d="M3.964 10.707c-.18-.54-.282-1.117-.282-1.707s.102-1.167.282-1.707V4.961H.957C.347 6.173 0 7.547 0 9s.348 2.827.957 4.039l3.007-2.332z" fill="#FBBC05"/>
        <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.961L3.964 7.293C4.672 5.166 6.656 3.58 9 3.58z" fill="#EA4335"/>
    </svg>
    Cadastrar com o Google
</a>

<div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--symo-outline, #7b7489);">
    Já tem uma conta?
    <a href="<?= BASE_URL ?>/login" style="color: var(--symo-primary-brand, #6f02ff); text-decoration: none; font-weight: 600;">
        Entrar
    </a>
</div>
