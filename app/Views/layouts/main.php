<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Layout Principal Base (Fase 3: Core MVC)
 */
$pageTitle = $title ?? 'Symo — Gestão Financeira';
$appName = 'Symo';
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
            padding: 0;
        }
        .main-container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }
    </style>
</head>
<body>
    <main class="main-container">
        <?= FlashHelper::render() ?>
        <?= $content ?? '' ?>
    </main>
</body>
</html>
