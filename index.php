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

// 8. Checagem Global de Inatividade de Sessão (60 minutos)
AuthHelper::checkSessionTimeout();

// 9. Definição das Rotas da Aplicação

// Rota raiz (redireciona para /dashboard se autenticado ou /login)
Router::get('/', function (): void {
    if (AuthHelper::check()) {
        header('Location: ' . BASE_URL . '/dashboard');
    } else {
        header('Location: ' . BASE_URL . '/login');
    }
    exit;
});

// Rotas de Autenticação e Gestão de Acesso
Router::get('/login', [AuthController::class, 'showLogin']);
Router::post('/login', [AuthController::class, 'authenticate']);
Router::get('/logout', [AuthController::class, 'logout']);
Router::post('/logout', [AuthController::class, 'logout']);
Router::get('/esqueci-senha', [AuthController::class, 'showForgotPassword']);

// Rota de Dashboard (Protegida por Autenticação)
Router::get('/dashboard', function () use ($config): void {
    AuthHelper::requireLogin();
    $user = AuthHelper::user();
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard — <?= htmlspecialchars($config['app']['name'], ENT_QUOTES, 'UTF-8') ?></title>
        <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/custom.css">
        <style>
            body {
                background-color: var(--symo-surface, #faf9f9);
                color: var(--symo-on-surface, #1a1c1c);
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                margin: 0;
                padding: 2rem 1rem;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                box-sizing: border-box;
            }
            .dashboard-card {
                background: #ffffff;
                border: 1px solid #e3e2e2;
                border-radius: 1rem;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
                max-width: 680px;
                width: 100%;
                padding: 2.5rem 2rem;
                text-align: center;
            }
            .role-badge {
                display: inline-block;
                padding: 4px 12px;
                font-size: 0.8rem;
                font-weight: 700;
                text-transform: uppercase;
                border-radius: 9999px;
                background-color: var(--symo-primary-fixed, #e9ddff);
                color: var(--symo-on-primary-fixed, #23005c);
                margin-bottom: 1rem;
            }
            .user-info {
                text-align: left;
                background: #f4f3f3;
                border-radius: 0.5rem;
                padding: 1.25rem;
                margin: 1.5rem 0;
                font-size: 0.9rem;
                color: #4a4457;
            }
            .user-info p {
                margin: 0.35rem 0;
            }
            .btn-group {
                display: flex;
                gap: 0.75rem;
                justify-content: center;
                flex-wrap: wrap;
            }
            .btn-primary-action {
                background-color: var(--symo-primary-brand, #6f02ff);
                color: #ffffff;
                text-decoration: none;
                font-weight: 600;
                font-size: 0.95rem;
                padding: 0.75rem 1.5rem;
                border-radius: 0.5rem;
                transition: background-color 0.2s;
            }
            .btn-primary-action:hover {
                background-color: #5300c4;
            }
            .btn-secondary-action {
                background-color: #e9e8e8;
                color: #1a1c1c;
                text-decoration: none;
                font-weight: 600;
                font-size: 0.95rem;
                padding: 0.75rem 1.5rem;
                border-radius: 0.5rem;
                transition: background-color 0.2s;
            }
            .btn-secondary-action:hover {
                background-color: #dadada;
            }
        </style>
    </head>
    <body>
        <div class="dashboard-card">
            <?= FlashHelper::render() ?>
            <span class="role-badge">Perfil: <?= htmlspecialchars($user['perfil'], ENT_QUOTES, 'UTF-8') ?></span>
            <h1 style="color: var(--symo-primary-brand, #6f02ff); margin: 0 0 0.5rem 0; font-size: 1.75rem; font-weight: 700;">
                Painel do Symo
            </h1>
            <p style="color: var(--symo-outline, #7b7489); margin: 0; font-size: 0.95rem;">
                Autenticação estabelecida com sucesso e controle de sessão ativo.
            </p>

            <div class="user-info">
                <p><strong>Usuário:</strong> <?= htmlspecialchars($user['nome'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>E-mail:</strong> <?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>ID da Sessão:</strong> <?= session_id() ?></p>
                <p><strong>Expiração de Inatividade:</strong> 60 minutos</p>
            </div>

            <div class="btn-group">
                <a href="<?= BASE_URL ?>/admin/teste-rbac" class="btn-secondary-action">
                    Testar Permissão Admin (RBAC)
                </a>
                <a href="<?= BASE_URL ?>/logout" class="btn-primary-action">
                    Sair da Conta (Logout)
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
});

// Rota de Teste de Permissão RBAC (Exclusiva para Administradores)
Router::get('/admin/teste-rbac', function (): void {
    AuthHelper::requireRole('admin');
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Área Restrita do Administrador — Symo</title>
        <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/custom.css">
    </head>
    <body style="background-color: var(--symo-surface); font-family: Inter, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0;">
        <div style="background: white; border: 1px solid #e3e2e2; border-radius: 1rem; padding: 2.5rem; max-width: 540px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
            <div style="font-size: 3rem; margin-bottom: 0.5rem;">🛡️</div>
            <h1 style="color: #6f02ff; font-size: 1.5rem; margin: 0 0 0.5rem 0;">Acesso Autorizado (Admin RBAC)</h1>
            <p style="color: #7b7489; font-size: 0.95rem; margin-bottom: 1.5rem;">
                Você possui permissões administrativas completas conforme a matriz RBAC do FSD.
            </p>
            <a href="<?= BASE_URL ?>/dashboard" style="background: #6f02ff; color: white; text-decoration: none; padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600;">
                Voltar ao Painel
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
        ],
        'auth' => [
            'authenticated' => AuthHelper::check(),
            'user'          => AuthHelper::user(),
        ]
    ];

    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
});

// 10. Despacho da Requisição
Router::dispatch();
