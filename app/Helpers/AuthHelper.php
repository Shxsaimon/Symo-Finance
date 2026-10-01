<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Helper de Autenticação, Gestão de Sessão e Controle de Acesso (RBAC)
 *
 * Centraliza as regras de autenticação, verificação de papéis (admin/operador),
 * expiração automática de sessão por inatividade e prevenção contra ataques de força bruta.
 */
class AuthHelper
{
    private const SESSION_USER_ID       = 'user_id';
    private const SESSION_USER_NAME     = 'user_name';
    private const SESSION_USER_EMAIL    = 'user_email';
    private const SESSION_USER_ROLE     = 'user_role';
    private const SESSION_USER_AVATAR   = 'user_avatar';
    private const SESSION_LAST_ACTIVITY = 'last_activity';
    private static bool $userStatusChecked = false;
    private static bool $activeUserStatus = false;

    /**
     * Inicia a sessão autenticada do usuário, aplicando regeneração de ID para proteção contra fixação de sessão.
     *
     * @param array<string, mixed> $usuario Dados do usuário (id, nome, email, perfil, avatar)
     * @return void
     */
    public static function login(array $usuario): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        // Regenera ID de sessão prevenindo ataques de Session Fixation
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION[self::SESSION_USER_ID]       = (int) $usuario['id'];
        $_SESSION[self::SESSION_USER_NAME]     = (string) $usuario['nome'];
        $_SESSION[self::SESSION_USER_EMAIL]    = (string) $usuario['email'];
        $_SESSION[self::SESSION_USER_ROLE]     = (string) $usuario['perfil'];
        $_SESSION[self::SESSION_USER_AVATAR]   = $usuario['avatar'] ?? null;
        $_SESSION[self::SESSION_LAST_ACTIVITY] = time();
        self::$userStatusChecked = true;
        self::$activeUserStatus = true;

        LoggerHelper::security('LOGIN_SUCCESS', [
            'usuario_id' => $usuario['id'],
            'perfil'     => $usuario['perfil']
        ], (string) $usuario['email']);
    }

    /**
     * Encerra a sessão do usuário de forma segura, limpando todos os dados e invalidando o cookie de sessão.
     *
     * @return void
     */
    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $userId = $_SESSION[self::SESSION_USER_ID] ?? null;
        $userEmail = $_SESSION[self::SESSION_USER_EMAIL] ?? null;

        if ($userId) {
            LoggerHelper::security('LOGOUT', ['usuario_id' => $userId], $userEmail);
        }

        $_SESSION = [];
        self::$userStatusChecked = false;
        self::$activeUserStatus = false;

        if (session_status() === PHP_SESSION_ACTIVE) {
            if (ini_get('session.use_cookies') && !headers_sent()) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            session_destroy();
        }
    }

    /**
     * Verifica se o usuário atual está autenticado e se a sessão não expirou.
     *
     * @return bool
     */
    public static function check(): bool
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (empty($_SESSION[self::SESSION_USER_ID])) {
            return false;
        }

        // Checa se a sessão expirou por inatividade
        if (self::isExpired()) {
            self::logout();
            return false;
        }

        if (!self::$userStatusChecked) {
            try {
                $statement = Database::getConnection()->prepare(
                    "SELECT status FROM usuarios WHERE id = :id AND excluido_em IS NULL LIMIT 1"
                );
                $statement->bindValue(':id', (int) $_SESSION[self::SESSION_USER_ID], PDO::PARAM_INT);
                $statement->execute();
                self::$activeUserStatus = $statement->fetchColumn() === 'ativo';
            } catch (Throwable $e) {
                self::$activeUserStatus = false;
                LoggerHelper::error($e, ['context' => 'AuthHelper::checkUserStatus']);
            }
            self::$userStatusChecked = true;
        }

        if (!self::$activeUserStatus) {
            self::logout();
            return false;
        }

        // Atualiza timestamp da última atividade
        $_SESSION[self::SESSION_LAST_ACTIVITY] = time();
        return true;
    }

    /**
     * Retorna os dados do usuário autenticado armazenados na sessão.
     *
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id'     => (int) $_SESSION[self::SESSION_USER_ID],
            'nome'   => (string) $_SESSION[self::SESSION_USER_NAME],
            'email'  => (string) $_SESSION[self::SESSION_USER_EMAIL],
            'perfil' => (string) $_SESSION[self::SESSION_USER_ROLE],
            'avatar' => $_SESSION[self::SESSION_USER_AVATAR] ?? null,
        ];
    }

    /**
     * Retorna o ID do usuário logado ou null caso não autenticado.
     *
     * @return int|null
     */
    public static function id(): ?int
    {
        return self::check() ? (int) $_SESSION[self::SESSION_USER_ID] : null;
    }

    /**
     * Retorna o perfil do usuário logado ('admin' ou 'operador').
     *
     * @return string|null
     */
    public static function role(): ?string
    {
        return self::check() ? (string) $_SESSION[self::SESSION_USER_ROLE] : null;
    }

    /**
     * Verifica se o usuário autenticado possui o perfil de Administrador.
     *
     * @return bool
     */
    public static function isAdmin(): bool
    {
        return in_array(self::role(), ['admin', 'dev'], true);
    }

    public static function isDev(): bool
    {
        return self::role() === 'dev';
    }

    /**
     * Verifica se o usuário autenticado possui o perfil de Operador.
     *
     * @return bool
     */
    public static function isOperador(): bool
    {
        return self::role() === 'operador';
    }

    public static function requireDev(): void
    {
        self::requireRole('dev');
    }

    /**
     * Exige que o usuário esteja autenticado para prosseguir.
     * Caso não esteja, redireciona para a página de login com feedback amigável.
     *
     * @return void
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            FlashHelper::set('warning', 'Sua sessão expirou ou você precisa se autenticar para acessar esta área.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    /**
     * Exige que o usuário autenticado possua um dos papéis informados (RBAC).
     * Caso o perfil seja insuficiente, registra evento de segurança e renderiza HTTP 403.
     *
     * @param string ...$allowedRoles Perfis permitidos (ex: 'admin', 'operador')
     * @return void
     */
    public static function requireRole(string ...$allowedRoles): void
    {
        self::requireLogin();

        $currentRole = self::role();
        $hasPermission = $currentRole === 'dev' && in_array('admin', $allowedRoles, true);
        if ($currentRole === null || (!$hasPermission && !in_array($currentRole, $allowedRoles, true))) {
            LoggerHelper::security('FORBIDDEN_ACCESS_ATTEMPT', [
                'usuario_id'   => self::id(),
                'perfil_atual' => $currentRole,
                'exigidos'     => $allowedRoles,
                'url'          => $_SERVER['REQUEST_URI'] ?? '/'
            ], self::user()['email'] ?? null);

            http_response_code(403);
            $view403 = APP_ROOT . '/app/Views/errors/403.php';
            if (file_exists($view403)) {
                require $view403;
            } else {
                echo '<h1>403 — Acesso Restrito</h1><p>Você não tem autorização para esta funcionalidade.</p>';
            }
            exit;
        }
    }

    /**
     * Verifica se a sessão atual atingiu o limite de inatividade (padrão: 60 minutos = 3600 segundos).
     *
     * @return bool
     */
    public static function isExpired(): bool
    {
        $lastActivity = $_SESSION[self::SESSION_LAST_ACTIVITY] ?? null;
        if ($lastActivity === null) {
            return false;
        }

        $ttl = 3600; // 60 minutos
        $configFile = APP_ROOT . '/config/config.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            $ttl = (int) ($config['app']['session_ttl'] ?? 3600);
        }

        return (time() - (int) $lastActivity) > $ttl;
    }

    /**
     * Executa checagem global de inatividade e encerra a sessão caso o tempo seja excedido.
     *
     * @return void
     */
    public static function checkSessionTimeout(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (!empty($_SESSION[self::SESSION_USER_ID]) && self::isExpired()) {
            self::logout();
            FlashHelper::set('info', 'Sua sessão expirou por inatividade após 60 minutos. Por favor, entre novamente.');
            header('Location: ' . BASE_URL . '/login?expired=1');
            exit;
        }
    }

    /**
     * Verifica se determinado e-mail ou endereço IP atingiu o limite de tentativas de força bruta (5 falhas em 15 minutos).
     *
     * @param string $email E-mail tentado
     * @param string $ip Endereço IP do cliente
     * @return bool Retorna true se estiver bloqueado
     */
    public static function isBruteForceLocked(string $email, string $ip): bool
    {
        try {
            $db = Database::getConnection();

            // Conta tentativas falhas nos últimos 15 minutos (900 segundos)
                        $interval = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql'
                                ? "CURRENT_TIMESTAMP - INTERVAL '15 minutes'"
                                : 'NOW() - INTERVAL 15 MINUTE';
                        $sql = "SELECT COUNT(*) as total
                    FROM seguranca_logs 
                    WHERE evento = 'LOGIN_FAILED' 
                      AND (email = :email OR endereco_ip = :ip) 
                                            AND criado_em >= ({$interval})";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':ip', $ip, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch();
            $totalAttempts = (int) ($row['total'] ?? 0);

            if ($totalAttempts >= 5) {
                // Registra o bloqueio no log de segurança caso ainda não tenha sido logado recentemente
                LoggerHelper::security('IP_LOCKED_OUT', [
                    'email'            => $email,
                    'ip'               => $ip,
                    'total_tentativas' => $totalAttempts,
                    'motivo'           => 'Limite de 5 tentativas inválidas excedido nos últimos 15 minutos'
                ], $email);

                return true;
            }

            return false;
        } catch (Throwable $e) {
            LoggerHelper::error($e, ['context' => 'AuthHelper::isBruteForceLocked']);
            return false;
        }
    }

    /**
     * Registra uma tentativa falha de autenticação.
     *
     * @param string $email
     * @param string $ip
     * @param string $motivo
     * @return void
     */
    public static function recordFailedAttempt(string $email, string $ip, string $motivo = 'Credenciais incorretas'): void
    {
        LoggerHelper::security('LOGIN_FAILED', [
            'email'  => $email,
            'ip'     => $ip,
            'motivo' => $motivo
        ], $email);
    }
}
