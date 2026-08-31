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
        Instruções para redefinição de acesso ao painel compartilhado.
    </p>
</div>

<div style="background-color: #f4f3f3; border: 1px solid #e3e2e2; border-radius: 0.5rem; padding: 1.25rem; margin-bottom: 1.5rem; font-size: 0.9rem; color: #4a4457; line-height: 1.5;">
    <p style="margin: 0 0 0.75rem 0; font-weight: 600; color: #1a1c1c;">
        Como funciona a redefinição no Symo:
    </p>
    <p style="margin: 0 0 0.5rem 0;">
        Como este é um ambiente compartilhado, por motivos de segurança a redefinição de senha para membros operacionais é realizada diretamente pelo <strong>Administrador</strong> do sistema no painel de gestão de membros.
    </p>
    <p style="margin: 0;">
        Caso você seja o Administrador e não lembre a senha principal, entre em contato com o suporte técnico responsável pelo ambiente.
    </p>
</div>

<a 
    href="<?= BASE_URL ?>/login" 
    style="display: block; width: 100%; box-sizing: border-box; padding: 0.85rem; font-size: 0.95rem; font-weight: 700; font-family: inherit; background-color: var(--symo-primary-brand, #6f02ff); color: #ffffff; text-align: center; text-decoration: none; border-radius: 0.5rem; transition: background-color 0.2s;"
    onmouseover="this.style.backgroundColor='#5300c4';"
    onmouseout="this.style.backgroundColor='var(--symo-primary-brand, #6f02ff)';"
>
    Voltar para o Login
</a>
