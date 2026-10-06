<?php
declare(strict_types=1);

/**
 * Symo — Gestão Financeira Pessoal e Compartilhada
 * Ponto de Entrada Único (Front Controller)
 */

// Servidor embutido do PHP (desenvolvimento local): entrega assets estáticos de public/.
if (PHP_SAPI === 'cli-server') {
    $caminhoRequisicao = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
    if (str_starts_with($caminhoRequisicao, '/public/') && strtolower(pathinfo($caminhoRequisicao, PATHINFO_EXTENSION)) !== 'php') {
        $arquivoEstatico = realpath(__DIR__ . $caminhoRequisicao);
        $raizPublica = realpath(__DIR__ . '/public');
        if ($arquivoEstatico !== false && $raizPublica !== false && is_file($arquivoEstatico)
            && str_starts_with($arquivoEstatico, $raizPublica . DIRECTORY_SEPARATOR)) {
            return false;
        }
    }
}

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
    ini_set('session.cookie_secure', ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '1' : '0'));

    require_once APP_ROOT . '/config/database.php';
    require_once APP_ROOT . '/app/Helpers/DatabaseSessionHandler.php';
    try {
        $sessionDatabase = Database::getConnection();
        session_set_save_handler(new DatabaseSessionHandler($sessionDatabase, (int) ($config['app']['session_ttl'] ?? 3600)), true);
    } catch (Throwable $sessionException) {
        error_log('Sessão persistente indisponível: ' . $sessionException->getMessage());
    }
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

define('ASSET_URL', (getenv('VERCEL') === '1' || getenv('VERCEL_ENV') !== false)
    ? BASE_URL
    : BASE_URL . '/public');

// 6. Autoloader Simples do Core MVC (Controllers, Models, Helpers, Config)
spl_autoload_register(function (string $class): void {
    $directories = [
        APP_ROOT . '/config/',
        APP_ROOT . '/app/Helpers/',
        APP_ROOT . '/app/Controllers/',
        APP_ROOT . '/app/Models/',
    ];

    $fileNames = [
        'Database' => 'database.php',
    ];

    foreach ($directories as $dir) {
        $fileName = $fileNames[$class] ?? ($class . '.php');
        $file = $dir . $fileName;
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
// Arquivos privados armazenados no Vercel Blob
Router::get('/media/avatar/{id}', [BlobController::class, 'avatar']);
Router::get('/media/logo', [BlobController::class, 'logo']);
Router::get('/feedback/imagem/{id}/{index}', [BlobController::class, 'feedbackImage']);

// Rotas de Autenticação e Gestão de Acesso
Router::get('/login', [AuthController::class, 'showLogin']);
Router::post('/login', [AuthController::class, 'authenticate']);
Router::post('/logout', [AuthController::class, 'logout']);
Router::get('/esqueci-senha', [AuthController::class, 'showForgotPassword']);
Router::get('/registrar', [AuthController::class, 'showRegister']);
Router::post('/registrar', [AuthController::class, 'register']);

// Rota de Dashboard (Protegida por Autenticação com Layout Mestre)
Router::get('/dashboard', [DashboardController::class, 'index']);
Router::post('/feedback/salvar', [FeedbackController::class, 'store']);

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
Router::post('/meios-pagamento/inativar-global/{id}', [PaymentMethodController::class, 'deactivateGlobally']);
Router::post('/meios-pagamento/reativar/{id}', [PaymentMethodController::class, 'activateGlobally']);

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

// -------------------------------------------------------------
// FASE 12: MÓDULOS ADMINISTRATIVOS E PERFIL
// -------------------------------------------------------------
// Gestão de Usuários (Exclusivo dev)
Router::get('/usuarios', [UserController::class, 'index']);
Router::post('/usuarios/salvar', [UserController::class, 'store']);
Router::post('/usuarios/atualizar/{id}', [UserController::class, 'update']);
Router::post('/usuarios/toggle-status/{id}', [UserController::class, 'toggleStatus']);
Router::post('/usuarios/redefinir-senha/{id}', [UserController::class, 'resetPassword']);
Router::post('/usuarios/excluir/{id}', [UserController::class, 'delete']);

// Trilha de Auditoria (Exclusivo dev)
Router::get('/auditoria', [AuditController::class, 'index']);
Router::get('/auditoria/detalhes/{id}', [AuditController::class, 'detail']);

// Lixeira isolada por usuário; dev possui visão global
Router::get('/lixeira', [TrashController::class, 'index']);
Router::post('/lixeira/restaurar', [TrashController::class, 'restore']);
Router::post('/lixeira/expurgar', [TrashController::class, 'purge']);

// Preferências pessoais; identidade visual e banco exclusivos do dev
Router::get('/configuracoes', [SettingsController::class, 'index']);
Router::post('/configuracoes/salvar', [SettingsController::class, 'update']);
Router::post('/configuracoes/upload-logo', [SettingsController::class, 'uploadLogo']);
Router::post('/configuracoes/executar-migracoes', [SettingsController::class, 'runMigrations']);
Router::get('/configuracoes/github-actions/status', [SettingsController::class, 'githubActionStatus']);
Router::get('/configuracoes/github-actions/logs/{id}', [SettingsController::class, 'githubActionLog']);

// Perfil Pessoal (Qualquer Usuário Autenticado)
Router::get('/perfil', [ProfileController::class, 'index']);
Router::post('/perfil/salvar', [ProfileController::class, 'update']);
Router::get('/feedbacks', [FeedbackController::class, 'index']);
Router::post('/feedbacks/status/{id}', [FeedbackController::class, 'updateStatus']);

// Rota de Teste de Permissão RBAC (Exclusiva para Administradores)
Router::get('/admin/teste-rbac', function (): void {
    AuthHelper::requireDev();
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
        $healthStatement = $db->prepare('SELECT 1');
        $healthStatement->execute();
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
        ],
        'core' => [
            'mvc'     => 'operational',
            'router'  => 'operational',
            'session' => session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive',
            'csrf'    => !empty(CsrfHelper::getToken()) ? 'ready' : 'error',
        ],
        'auth' => [
            'authenticated' => AuthHelper::check(),
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
