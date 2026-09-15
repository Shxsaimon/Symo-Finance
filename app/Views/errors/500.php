<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Erro 500 (Erro Interno do Servidor)
 */
http_response_code(500);
$appName = 'Symo';
if (isset($config['app']['name'])) {
    $appName = $config['app']['name'];
}
$isDebug = ($config['app']['debug'] ?? false) === true;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Erro no Servidor | <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= ASSET_URL ?>/css/custom.css">
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
            max-width: 640px;
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
        .debug-details {
            margin-top: 2rem;
            text-align: left;
            background: #f4f3f3;
            border-radius: 0.5rem;
            padding: 1rem;
            font-size: 0.85rem;
            border: 1px solid #ccc3da;
        }
        .debug-details summary {
            cursor: pointer;
            font-weight: 600;
            color: #5300c4;
            outline: none;
        }
        .debug-details pre {
            margin-top: 0.75rem;
            white-space: pre-wrap;
            word-break: break-all;
            color: #1a1c1c;
            font-family: Consolas, Monaco, monospace;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">500</div>
        <h1 class="error-title">Instabilidade Temporária</h1>
        <p class="error-description">
            Ocorreu uma falha inesperada ao processar sua solicitação. O incidente foi registrado para diagnóstico pela equipe técnica.
        </p>
        <div>
            <a href="<?= BASE_URL ?>/" class="btn-primary-action">
                Recarregar Início
            </a>
        </div>

        <?php if ($isDebug && isset($exception) && $exception instanceof Throwable): ?>
            <details class="debug-details">
                <summary>Detalhes Técnicos do Erro (Modo Depuração)</summary>
                <p style="margin: 8px 0 4px 0;"><strong>Exceção:</strong> <?= htmlspecialchars(get_class($exception), ENT_QUOTES, 'UTF-8') ?></p>
                <p style="margin: 4px 0;"><strong>Mensagem:</strong> <?= htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') ?></p>
                <p style="margin: 4px 0;"><strong>Arquivo:</strong> <?= htmlspecialchars($exception->getFile(), ENT_QUOTES, 'UTF-8') ?> (linha <?= $exception->getLine() ?>)</p>
                <pre><?= htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8') ?></pre>
            </details>
        <?php endif; ?>
    </div>
</body>
</html>
