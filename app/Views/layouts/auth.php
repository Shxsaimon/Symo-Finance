<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Layout de Telas de Autenticação
 *
 * Estrutura visual centralizada, minimalista e focada em conversão e segurança,
 * seguindo estritamente as diretrizes do Design System do Symo.
 */
$pageTitle = $title ?? 'Autenticação — Symo';
$appName   = 'Symo';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/custom.css">
    <style>
        body {
            background-color: var(--symo-surface, #faf9f9);
            color: var(--symo-on-surface, #1a1c1c);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 1.5rem;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
        }
        .auth-wrapper {
            width: 100%;
            max-width: 440px;
            margin: 0 auto;
        }
        .auth-brand {
            text-align: center;
            margin-bottom: 2rem;
        }
        .auth-brand-badge {
            display: inline-block;
            padding: 6px 14px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            border-radius: 9999px;
            background-color: var(--symo-primary-fixed, #e9ddff);
            color: var(--symo-on-primary-fixed, #23005c);
            margin-bottom: 0.75rem;
        }
        .auth-brand-title {
            font-size: 1.85rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: var(--symo-primary-brand, #6f02ff);
            margin: 0 0 0.25rem 0;
        }
        .auth-brand-subtitle {
            font-size: 0.95rem;
            color: var(--symo-outline, #7b7489);
            margin: 0;
        }
        .auth-card {
            background: #ffffff;
            border: 1px solid #e3e2e2;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(111, 2, 255, 0.04), 0 2px 8px rgba(0, 0, 0, 0.04);
            padding: 2.25rem 2rem;
            box-sizing: border-box;
        }
        .auth-footer {
            text-align: center;
            margin-top: 1.75rem;
            font-size: 0.85rem;
            color: var(--symo-outline, #7b7489);
        }
        .auth-footer a {
            color: var(--symo-primary-brand, #6f02ff);
            text-decoration: none;
            font-weight: 600;
        }
        .auth-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-brand">
            <span class="auth-brand-badge">Gestão Financeira</span>
            <h1 class="auth-brand-title"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="auth-brand-subtitle">Controle pessoal e compartilhado</p>
        </div>

        <div class="auth-card">
            <?= FlashHelper::render() ?>
            <?= $content ?? '' ?>
        </div>

        <div class="auth-footer">
            &copy; <?= date('Y') ?> <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>. Ambiente Seguro e Criptografado.
        </div>
    </div>
</body>
</html>
