<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Layout de Telas de Autenticação
 *
 * Estrutura visual centralizada, minimalista e focada em conversão e segurança,
 * utilizando exclusivamente assets locais do Design System Symo.
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
    
    <!-- Favicon — declarado antes dos CSS para ser processado com prioridade máxima -->
    <link rel="shortcut icon" href="<?= ASSET_URL ?>/img/favicon.png" type="image/png">
    <link rel="icon" type="image/svg+xml" href="<?= ASSET_URL ?>/img/favicon.svg">

    <!-- Assets Locais -->
    <link rel="stylesheet" href="<?= ASSET_URL ?>/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ASSET_URL ?>/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= ASSET_URL ?>/css/custom.css">


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
        .auth-brand img {
            height: 48px;
            margin-bottom: 0.75rem;
            filter: drop-shadow(0 4px 12px rgba(111, 2, 255, 0.2));
        }
        .auth-card {
            background: #ffffff;
            border: 1px solid #e3e2e2;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(111, 2, 255, 0.05), 0 2px 8px rgba(0, 0, 0, 0.04);
            padding: 2.25rem 2rem;
            box-sizing: border-box;
        }
        .auth-footer {
            text-align: center;
            margin-top: 1.75rem;
            font-size: 0.85rem;
            color: var(--symo-outline, #7b7489);
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-brand">
            <a href="<?= BASE_URL ?>/login" class="d-inline-block text-decoration-none">
                <div style="background: linear-gradient(135deg, #4a2ec2 0%, #260058 100%); padding: 10px 20px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 16px rgba(74, 46, 194, 0.25);">
                    <img src="<?= ASSET_URL ?>/img/logo.svg" alt="Symo Financeiro" style="margin: 0; height: 32px;">
                </div>
            </a>
            <p style="color: var(--symo-outline); margin: 0.75rem 0 0 0; font-size: 0.9rem;">
                Gestão Financeira Pessoal e Compartilhada
            </p>
        </div>

        <div class="auth-card">
            <?= FlashHelper::render() ?>
            <?= $content ?? '' ?>
        </div>

        <div class="auth-footer">
            &copy; <?= date('Y') ?> Symo. Ambiente Seguro e Criptografado.
        </div>
    </div>

    <!-- Scripts Locais -->
    <script src="<?= ASSET_URL ?>/js/bootstrap.bundle.min.js"></script>
    <script src="<?= ASSET_URL ?>/js/app.js"></script>
</body>
</html>
