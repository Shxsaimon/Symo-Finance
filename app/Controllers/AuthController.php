<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Controlador de Autenticação (AuthController)
 *
 * Gerencia os fluxos de exibição de login, autenticação com proteção anti-força bruta,
 * logout seguro e orientações de recuperação de senha.
 */
class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Exibe a tela de login ou redireciona para o dashboard se o usuário já estiver autenticado.
     *
     * @return void
     */
    public function showLogin(): void
    {
        if (AuthHelper::check()) {
            $this->redirect('/dashboard');
            return;
        }

        $query = $this->getQuery();
        if (isset($query['expired']) && $query['expired'] === '1' && !FlashHelper::has('info')) {
            FlashHelper::set('info', 'Sua sessão expirou por inatividade após 60 minutos. Por favor, acesse novamente.');
        }

        if (isset($query['logout']) && $query['logout'] === '1' && !FlashHelper::has('success')) {
            FlashHelper::set('success', 'Sessão encerrada com sucesso.');
        }

        $this->render('auth/login', [
            'title'    => 'Entrar no Symo — Gestão Financeira',
            'oldEmail' => $_SESSION['old_email'] ?? '',
        ], 'layouts/auth');

        unset($_SESSION['old_email']);
    }

    /**
     * Processa a submissão do formulário de autenticação.
     *
     * @return void
     */
    public function authenticate(): void
    {
        // 1. Validação do Token anti-CSRF
        if (!$this->validateCsrf()) {
            LoggerHelper::security('CSRF_INVALID', ['action' => 'authenticate']);
            FlashHelper::set('error', 'Token de segurança expirado ou inválido. Por favor, envie o formulário novamente.');
            $this->redirect('/login');
            return;
        }

        $post = $this->getPost();
        $email = trim((string) ($post['email'] ?? ''));
        $senha = (string) ($post['senha'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $_SESSION['old_email'] = $email;

        // 2. Validação de preenchimento
        if (empty($email) || empty($senha)) {
            FlashHelper::set('error', 'Por favor, preencha o e-mail e a senha.');
            $this->redirect('/login');
            return;
        }

        // 3. Verificação de Proteção contra Força Bruta (5 falhas em 15 minutos)
        if (AuthHelper::isBruteForceLocked($email, $ip)) {
            FlashHelper::set('error', 'Muitas tentativas incorretas. Por segurança, o acesso para este e-mail/IP foi bloqueado temporariamente por 15 minutos.');
            $this->redirect('/login');
            return;
        }

        // 4. Busca do usuário pelo e-mail
        $user = $this->userModel->findByEmail($email);

        // 5. Verificação da existência e correspondência da senha
        if (!$user || !$this->userModel->verifyPassword($senha, (string) $user['senha'])) {
            AuthHelper::recordFailedAttempt($email, $ip, 'Credenciais incorretas');
            FlashHelper::set('error', 'E-mail ou senha incorretos.');
            $this->redirect('/login');
            return;
        }

        // 6. Verificação do status do usuário
        if (!User::isActive($user)) {
            LoggerHelper::security('LOGIN_INACTIVE_USER', ['usuario_id' => $user['id']], $email);
            FlashHelper::set('error', 'Esta conta de acesso foi inativada. Entre em contato com o Administrador do sistema.');
            $this->redirect('/login');
            return;
        }

        // 7. Autenticação bem-sucedida
        unset($_SESSION['old_email']);
        AuthHelper::login($user);
        $this->userModel->touch((int) $user['id']);

        //FlashHelper::set('success', 'Bem-vindo(a) ao Symo, ' . htmlspecialchars($user['nome'], ENT_QUOTES, 'UTF-8') . '!');
        $this->redirect('/dashboard');
    }

    /**
     * Encerra a sessão ativa do usuário e redireciona para a tela de login.
     *
     * @return void
     */
    public function logout(): void
    {
        if (!$this->validateCsrf()) {
            LoggerHelper::security('CSRF_INVALID', ['action' => 'logout']);
            http_response_code(403);
            $view403 = APP_ROOT . '/app/Views/errors/403.php';
            if (file_exists($view403)) {
                require $view403;
            } else {
                echo '<h1>403 — Acesso Restrito</h1><p>Token de segurança inválido.</p>';
            }
            exit;
        }

        AuthHelper::logout();
        FlashHelper::set('success', 'Você saiu do sistema com segurança.');
        $this->redirect('/login?logout=1');
    }

    /**
     * Exibe a tela com instruções de recuperação de senha.
     *
     * @return void
     */
    public function showForgotPassword(): void
    {
        $this->render('auth/forgot-password', [
            'title' => 'Recuperação de Senha — Symo'
        ], 'layouts/auth');
    }

    /**
     * Exibe a tela de autocadastro ou redireciona para o dashboard se já autenticado.
     *
     * @return void
     */
    public function showRegister(): void
    {
        if (AuthHelper::check()) {
            $this->redirect('/dashboard');
            return;
        }

        $this->render('auth/register', [
            'title'   => 'Criar Conta — Symo',
            'oldNome' => $_SESSION['old_register_nome'] ?? '',
            'oldEmail' => $_SESSION['old_register_email'] ?? '',
        ], 'layouts/auth');

        unset($_SESSION['old_register_nome'], $_SESSION['old_register_email']);
    }

    /**
     * Processa o autocadastro de uma nova conta.
     *
     * @return void
     */
    public function register(): void
    {
        if (!$this->validateCsrf()) {
            LoggerHelper::security('CSRF_INVALID', ['action' => 'register']);
            FlashHelper::set('error', 'Token de segurança expirado ou inválido. Por favor, envie o formulário novamente.');
            $this->redirect('/registrar');
            return;
        }

        $post = $this->getPost();
        $nome = trim((string) ($post['nome'] ?? ''));
        $email = trim((string) ($post['email'] ?? ''));
        $senha = (string) ($post['senha'] ?? '');
        $senhaConfirmacao = (string) ($post['senha_confirmacao'] ?? '');

        $_SESSION['old_register_nome'] = $nome;
        $_SESSION['old_register_email'] = $email;

        if (empty($nome) || empty($email) || empty($senha)) {
            FlashHelper::set('error', 'Preencha nome, e-mail e senha para criar sua conta.');
            $this->redirect('/registrar');
            return;
        }

        if ($senha !== $senhaConfirmacao) {
            FlashHelper::set('error', 'A confirmação de senha não coincide com a senha informada.');
            $this->redirect('/registrar');
            return;
        }

        $result = $this->userModel->registerSelf([
            'nome'  => $nome,
            'email' => $email,
            'senha' => $senha,
        ]);

        if (!$result['success']) {
            FlashHelper::set('error', (string) $result['message']);
            $this->redirect('/registrar');
            return;
        }

        $user = $result['user'] ?? null;
        if (!$user) {
            LoggerHelper::error(new RuntimeException('Cadastro concluído mas usuário não retornado para login automático.'), ['context' => 'AuthController::register', 'email' => $email]);
            FlashHelper::set('success', 'Conta criada com sucesso! Faça login para continuar.');
            $this->redirect('/login');
            return;
        }

        unset($_SESSION['old_register_nome'], $_SESSION['old_register_email']);

        AuthHelper::login($user);

        FlashHelper::set('success', 'Conta criada com sucesso! Bem-vindo(a) ao Symo.');
        $this->redirect('/dashboard');
    }
}
