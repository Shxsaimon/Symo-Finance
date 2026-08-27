<?php
declare(strict_types=1);

/**
 * Symo — Gestão Financeira Pessoal e Compartilhada
 * Ponto de Entrada Único (Front Controller)
 */

define('APP_RUNNING', true);
define('APP_ROOT', __DIR__);

// 1. Cabeçalhos de Segurança HTTP Obrigatórios
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// 2. Carregar Configurações Técnicas
$configFile = APP_ROOT . '/config/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    die('Arquivo de configuração config/config.php não encontrado.');
}
$config = require $configFile;

// 3. Fuso Horário e Exibição de Erros
date_default_timezone_set($config['app']['timezone'] ?? 'America/Sao_Paulo');

$isDebug = ($config['app']['debug'] ?? false) === true;
if ($isDebug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
}

// 4. Configuração Segura de Sessão
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

// 5. Cálculo Dinâmico da BASE_URL
if (empty($config['app']['base_url'])) {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $baseUrl = rtrim($scheme . '://' . $host . $scriptDir, '/');
    define('BASE_URL', $baseUrl);
} else {
    define('BASE_URL', rtrim($config['app']['base_url'], '/'));
}

// 6. Autoloader Simples do Core MVC (Controllers, Models, Helpers, Config)
spl_autoload_register(function (string $class): void {
    $directories = [
        APP_ROOT . '/config/',
        APP_ROOT . '/app/Helpers/',
        APP_ROOT . '/app/Controllers/',
        APP_ROOT . '/app/Models/',
    ];

    foreach ($directories as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// 7. Manipuladores Globais de Erros e Exceções com Fallback
set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function (Throwable $exception) use ($config): void {
    // Registra erro no banco de dados com contingência em arquivo
    LoggerHelper::error($exception, [
        'context' => 'GlobalExceptionHandler',
        'url'     => $_SERVER['REQUEST_URI'] ?? '/',
        'method'  => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'
    ]);

    http_response_code(500);

    $view500 = APP_ROOT . '/app/Views/errors/500.php';
    if (file_exists($view500)) {
        require $view500;
    } else {
        echo '<h1>500 — Erro Interno do Servidor</h1>';
        echo '<p>Ocorreu uma instabilidade inesperada. O incidente foi registrado.</p>';
    }
    exit;
});

register_shutdown_function(function () use ($config): void {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        $fatalException = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
        LoggerHelper::error($fatalException, ['context' => 'ShutdownFatalError']);

        http_response_code(500);
        $view500 = APP_ROOT . '/app/Views/errors/500.php';
        if (file_exists($view500)) {
            $exception = $fatalException;
            require $view500;
        } else {
            echo '<h1>500 — Erro Fatal do Sistema</h1>';
        }
    }
});

// 8. Definição das Rotas Fundamentais do Core
Router::get('/', function () use ($config): void {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($config['app']['name'], ENT_QUOTES, 'UTF-8') ?> — Núcleo MVC Ativo</title>
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
            .hero-box {
                background: #ffffff;
                border: 1px solid #e3e2e2;
                border-radius: 1rem;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
                max-width: 640px;
                width: 100%;
                padding: 2.5rem 2rem;
                text-align: center;
            }
            .badge-symo {
                display: inline-block;
                padding: 6px 16px;
                font-size: 13px;
                font-weight: 600;
                border-radius: 9999px;
                background-color: var(--symo-primary-fixed, #e9ddff);
                color: var(--symo-on-primary-fixed, #23005c);
                margin-bottom: 16px;
            }
            .btn-action {
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
                margin-top: 1.5rem;
            }
            .btn-action:hover {
                background-color: #5300c4;
            }
            .core-features {
                text-align: left;
                background: #f4f3f3;
                border-radius: 0.5rem;
                padding: 1rem 1.5rem;
                margin-top: 1.5rem;
                font-size: 0.9rem;
                color: #4a4457;
            }
            .core-features ul {
                margin: 0.5rem 0 0 0;
                padding-left: 1.25rem;
            }
            .core-features li {
                margin-bottom: 0.35rem;
            }
        </style>
    </head>
    <body>
        <div class="hero-box">
            <span class="badge-symo">Fase 3 Concluída com Sucesso</span>
            <h1 style="color: var(--symo-primary-brand, #6f02ff); margin: 0 0 0.5rem 0; font-weight: 700;">Symo Financeiro</h1>
            <p style="color: var(--symo-outline, #7b7489); font-size: 1rem; line-height: 1.5; margin: 0;">
                Núcleo da aplicação (Core MVC, Helpers, Roteador e Sistema de Logs) operacional.
            </p>
            
            <div class="core-features">
                <strong>Componentes do Núcleo Ativos:</strong>
                <ul>
                    <li>✓ <code>Router</code> com suporte a GET/POST, parâmetros dinâmicos e 404</li>
                    <li>✓ <code>Controller</code> e <code>Model</code> base com PDO Singleton e Auditoria</li>
                    <li>✓ <code>LoggerHelper</code> com logs no BD e contingência em arquivo</li>
                    <li>✓ <code>CsrfHelper</code>, <code>FlashHelper</code> e <code>FormatHelper</code></li>
                    <li>✓ Telas de erro amigáveis (403, 404, 500)</li>
                </ul>
            </div>

            <a href="<?= BASE_URL ?>/api/health" class="btn-action">
                Verificar Diagnóstico do Sistema (Health Check)
            </a>
        </div>
    </body>
    </html>
    <?php
});

// Endpoint de diagnóstico de integridade do sistema
Router::get('/api/health', function (): void {
    $dbStatus = 'OK';
    $dbError = null;

    try {
        $db = Database::getConnection();
        $db->query('SELECT 1');
    } catch (Throwable $e) {
        $dbStatus = 'ERRO';
        $dbError = $e->getMessage();
    }

    $response = [
        'status'    => $dbStatus === 'OK' ? 'online' : 'degraded',
        'timestamp' => date('c'),
        'timezone'  => date_default_timezone_get(),
        'database'  => [
            'status' => $dbStatus,
            'error'  => $dbError,
        ],
        'core' => [
            'mvc'     => 'operational',
            'router'  => 'operational',
            'session' => session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive',
            'csrf'    => !empty(CsrfHelper::getToken()) ? 'ready' : 'error',
        ]
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
});

// 9. Despacho da Requisição
Router::dispatch();
