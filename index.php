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

// Rota de Dashboard (Protegida por Autenticação com Layout Mestre)
Router::get('/dashboard', [DashboardController::class, 'index']);

// -------------------------------------------------------------
// FASE 6: CADASTROS BÁSICOS (Contas, Categorias, Meios de Pagamento)
// -------------------------------------------------------------
// 1. Contas Bancárias e Carteiras
Router::get('/contas', [AccountController::class, 'index']);
Router::post('/contas/salvar', [AccountController::class, 'store']);
Router::post('/contas/atualizar/{id}', [AccountController::class, 'update']);
Router::post('/contas/status/{id}', [AccountController::class, 'toggle']);
Router::post('/contas/excluir/{id}', [AccountController::class, 'delete']);

// 2. Categorias de Receitas e Despesas
Router::get('/categorias', [CategoryController::class, 'index']);
Router::post('/categorias/salvar', [CategoryController::class, 'store']);
Router::post('/categorias/atualizar/{id}', [CategoryController::class, 'update']);
Router::post('/categorias/status/{id}', [CategoryController::class, 'toggle']);
Router::post('/categorias/excluir/{id}', [CategoryController::class, 'delete']);

// 3. Meios de Pagamento
Router::get('/meios-pagamento', [PaymentMethodController::class, 'index']);
Router::post('/meios-pagamento/salvar', [PaymentMethodController::class, 'store']);
Router::post('/meios-pagamento/atualizar/{id}', [PaymentMethodController::class, 'update']);
Router::post('/meios-pagamento/status/{id}', [PaymentMethodController::class, 'toggle']);
Router::post('/meios-pagamento/excluir/{id}', [PaymentMethodController::class, 'delete']);

// -------------------------------------------------------------
// FASE 7: CARTÕES DE CRÉDITO E FATURAS
// -------------------------------------------------------------
Router::get('/cartoes', [CreditCardController::class, 'index']);
Router::get('/cartoes/detalhes/{id}', [CreditCardController::class, 'show']);
Router::get('/faturas/{id}', [CreditCardController::class, 'invoice']);
Router::post('/cartoes/salvar', [CreditCardController::class, 'store']);
Router::post('/cartoes/atualizar/{id}', [CreditCardController::class, 'update']);
Router::post('/cartoes/status/{id}', [CreditCardController::class, 'toggle']);
Router::post('/cartoes/excluir/{id}', [CreditCardController::class, 'delete']);
Router::post('/faturas/fechar/{id}', [CreditCardController::class, 'closeInvoice']);
Router::post('/faturas/reabrir/{id}', [CreditCardController::class, 'reopenInvoice']);
Router::post('/faturas/pagar/{id}', [CreditCardController::class, 'payInvoice']);

// -------------------------------------------------------------
// FASE 8: TRANSAÇÕES FINANCEIRAS (Receitas, Despesas e Parcelamento)
// -------------------------------------------------------------
Router::get('/transacoes', [TransactionController::class, 'index']);
Router::get('/transacoes/obter/{id}', [TransactionController::class, 'getJson']);
Router::post('/transacoes/salvar', [TransactionController::class, 'store']);
Router::post('/transacoes/atualizar/{id}', [TransactionController::class, 'update']);
Router::post('/transacoes/status/{id}', [TransactionController::class, 'toggle']);
Router::post('/transacoes/excluir/{id}', [TransactionController::class, 'delete']);

// -------------------------------------------------------------
// FASE 9: TRANSFERÊNCIAS ENTRE CONTAS
// -------------------------------------------------------------
Router::get('/transferencias', [TransferController::class, 'index']);
Router::post('/transferencias/salvar', [TransferController::class, 'store']);
Router::post('/transferencias/excluir/{id}', [TransferController::class, 'delete']);

// -------------------------------------------------------------
// FASE 11: RELATÓRIOS ANALÍTICOS E EXPORTAÇÃO CSV
// -------------------------------------------------------------
Router::get('/relatorios', [ReportController::class, 'index']);
Router::get('/relatorios/exportar-csv', [ReportController::class, 'exportCsv']);

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
