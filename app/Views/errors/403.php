<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Erro 403 (Acesso Proibido)
 */
http_response_code(403);
$appName = 'Symo';
if (isset($config['app']['name'])) {
    $appName = $config['app']['name'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Acesso Proibido | <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/custom.css">
    <style>
        body {
            background-color: var(--symo-surface, #faf9f9);
            color: var(--symo-on-surface, #1a1c1c);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 1.5rem;
            box-sizing: border-box;
        }
        .error-card {
            background: #ffffff;
            border: 1px solid #e3e2e2;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            max-width: 520px;
            width: 100%;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .error-code {
            font-size: 5rem;
            font-weight: 800;
            line-height: 1;
            color: #ba1a1a;
            letter-spacing: -0.04em;
            margin: 0 0 0.5rem 0;
        }
        .error-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1c1c;
            margin: 0 0 1rem 0;
        }
        .error-description {
            font-size: 0.95rem;
            color: #7b7489;
            line-height: 1.5;
            margin: 0 0 2rem 0;
        }
        .btn-primary-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: var(--symo-primary-brand, #6f02ff);
            color: #ffffff;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            padding: 0.75rem 1.75rem;
            border-radius: 0.5rem;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }
        .btn-primary-action:hover {
            background-color: #5300c4;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">403</div>
        <h1 class="error-title">Acesso Restrito</h1>
        <p class="error-description">
            Você não possui autorização ou permissão suficiente para acessar este recurso ou executar esta operação.
        </p>
        <div>
            <a href="<?= BASE_URL ?>/dashboard" class="btn-primary-action">
                Voltar ao Início
            </a>
        </div>
    </div>
</body>
</html>
