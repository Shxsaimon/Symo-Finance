<?php
declare(strict_types=1);

/**
 * Symo — Gestão Financeira Pessoal e Compartilhada
 * Ponto de Entrada Único (Front Controller)
 */

define('APP_RUNNING', true);
define('APP_ROOT', __DIR__);

// Cabeçalhos de Segurança HTTP Obrigatórios
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Carregar Configurações do Sistema
$config = require_once __DIR__ . '/config/config.php';

// Configurar fuso horário
date_default_timezone_set($config['app']['timezone'] ?? 'America/Sao_Paulo');

// Exibição de erros condicionada ao ambiente
if (($config['app']['debug'] ?? false) === true) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
}

// Calcular URL base dinamicamente se não especificada
if (empty($config['app']['base_url'])) {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $baseUrl = rtrim($scheme . '://' . $host . $scriptDir, '/');
    define('BASE_URL', $baseUrl);
} else {
    define('BASE_URL', rtrim($config['app']['base_url'], '/'));
}

// Resposta provisória da Fase 1 de preparação da infraestrutura
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['app']['name'], ENT_QUOTES, 'UTF-8') ?> — Terreno Preparado</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/custom.css">
    <style>
        .hero-box {
            max-width: 640px;
            margin: 80px auto;
            padding: 32px;
            text-align: center;
        }
        .badge-symo {
            display: inline-block;
            padding: 6px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 9999px;
            background-color: var(--symo-primary-fixed);
            color: var(--symo-on-primary-fixed);
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="hero-box symo-card">
        <span class="badge-symo">Fase 1 Concluída</span>
        <h1 style="color: var(--symo-primary-brand); margin-top: 0;">Symo Financeiro</h1>
        <p style="color: var(--symo-outline); font-size: 16px; line-height: 1.5;">
            Infraestrutura, proteções de diretório e estrutura base do projeto devidamente inicializadas.
        </p>
        <p class="label-caps" style="margin-top: 24px;">Próximo Passo</p>
        <p style="font-weight: 600; color: var(--symo-on-surface);">Fase 2: Banco de Dados, Migrations e Carga Inicial</p>
    </div>
</body>
</html>

