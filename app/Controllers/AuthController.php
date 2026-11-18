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

    /**
     * Redireciona o usuário para o fluxo de consentimento do Google OAuth 2.0.
     *
     * @return void
     */
    public function redirectToGoogle(): void
    {
        if (AuthHelper::check()) {
            $this->redirect('/dashboard');
            return;
        }

        if (!GoogleAuthHelper::isConfigured()) {
            FlashHelper::set('error', 'O login com o Google ainda não foi configurado neste ambiente.');
            $this->redirect('/login');
            return;
        }

        $state = bin2hex(random_bytes(32));
        $_SESSION['google_oauth_state'] = $state;

        $authUrl = GoogleAuthHelper::getAuthUrl($state);
        header('Location: ' . $authUrl);
        exit;
    }

    /**
     * Processa o retorno (callback) do Google OAuth 2.0 após a autorização do usuário.
     *
     * @return void
     */
    public function handleGoogleCallback(): void
    {
        if (AuthHelper::check()) {
            $this->redirect('/dashboard');
            return;
        }

        $query = $this->getQuery();

        // 1. Tratamento de cancelamento ou recusa pelo usuário no Google
        if (!empty($query['error'])) {
            $errorMsg = ($query['error'] === 'access_denied')
                ? 'Acesso cancelado no Google.'
                : 'Falha na autenticação com o Google: ' . htmlspecialchars((string) $query['error'], ENT_QUOTES, 'UTF-8');
            FlashHelper::set('info', $errorMsg);
            $this->redirect('/login');
            return;
        }

        // 2. Validação do state contra a sessão (proteção anti-CSRF no OAuth)
        $savedState = $_SESSION['google_oauth_state'] ?? null;
        unset($_SESSION['google_oauth_state']);

        $returnedState = (string) ($query['state'] ?? '');
        if (!$savedState || !hash_equals((string) $savedState, $returnedState)) {
            LoggerHelper::security('CSRF_INVALID', ['action' => 'google_oauth_callback']);
            FlashHelper::set('error', 'Falha na validação de segurança da requisição Google. Tente novamente.');
            $this->redirect('/login');
            return;
        }

        // 3. Validação da presença do authorization code
        $code = trim((string) ($query['code'] ?? ''));
        if ($code === '') {
            FlashHelper::set('error', 'Código de autorização não recebido do Google.');
            $this->redirect('/login');
            return;
        }

        // 4. Troca do código de autorização por access token
        $tokenData = GoogleAuthHelper::exchangeCode($code);
        $accessToken = (string) ($tokenData['access_token'] ?? '');
        if ($accessToken === '') {
            FlashHelper::set('error', 'Não foi possível autenticar junto ao Google. Tente novamente.');
            $this->redirect('/login');
            return;
        }

        // 5. Consulta aos dados de perfil do usuário no Google
        $userInfo = GoogleAuthHelper::getUserInfo($accessToken);
        if (!$userInfo || empty($userInfo['sub']) || empty($userInfo['email'])) {
            FlashHelper::set('error', 'Não foi possível recuperar os dados de perfil da sua conta Google.');
            $this->redirect('/login');
            return;
        }

        if (empty($userInfo['email_verified'])) {
            FlashHelper::set('error', 'Apenas contas Google com e-mail verificado podem ser utilizadas para acesso.');
            $this->redirect('/login');
            return;
        }

        $googleId = (string) $userInfo['sub'];
        $email    = trim(strtolower((string) $userInfo['email']));

        // 6. Resolução da conta do usuário em 3 cenários
        // Cenário A: Usuário já possui vínculo ativo pelo google_id
        $user = $this->userModel->findByGoogleId($googleId);
        if ($user) {
            if (!User::isActive($user)) {
                LoggerHelper::security('LOGIN_INACTIVE_USER', ['usuario_id' => $user['id']], $email);
                FlashHelper::set('error', 'Esta conta de acesso foi inativada. Entre em contato com o Administrador do sistema.');
                $this->redirect('/login');
                return;
            }

            AuthHelper::login($user);
            $this->userModel->touch((int) $user['id']);
            LoggerHelper::security('LOGIN_GOOGLE_SUCCESS', ['usuario_id' => $user['id']], $user['email']);
            $this->redirect('/dashboard');
            return;
        }

        // Cenário B: Conta local já existente com o mesmo e-mail verificado (comprovação de posse e vínculo seguro)
        $userByEmail = $this->userModel->findByEmail($email);
        if ($userByEmail) {
            if (!User::isActive($userByEmail)) {
                LoggerHelper::security('LOGIN_INACTIVE_USER', ['usuario_id' => $userByEmail['id']], $email);
                FlashHelper::set('error', 'Esta conta de acesso foi inativada. Entre em contato com o Administrador do sistema.');
                $this->redirect('/login');
                return;
            }

            $this->userModel->linkGoogleAccount((int) $userByEmail['id'], $googleId);
            $userByEmail['google_id'] = $googleId;

            AuthHelper::login($userByEmail);
            $this->userModel->touch((int) $userByEmail['id']);
            LoggerHelper::security('LINK_GOOGLE_SUCCESS', ['usuario_id' => $userByEmail['id']], $email);

            FlashHelper::set('success', 'Sua conta Google foi vinculada com sucesso! Bem-vindo(a) ao Symo.');
            $this->redirect('/dashboard');
            return;
        }

        // Cenário C: Novo usuário (cadastro automático com dados financeiros padrão)
        $result = $this->userModel->registerFromGoogle($userInfo);
        if (!$result['success']) {
            FlashHelper::set('error', (string) $result['message']);
            $this->redirect('/login');
            return;
        }

        $newUser = $result['user'] ?? null;
        if (!$newUser) {
            FlashHelper::set('success', 'Conta criada com sucesso via Google! Acesse com sua conta Google.');
            $this->redirect('/login');
            return;
        }

        AuthHelper::login($newUser);
        $this->userModel->touch((int) $newUser['id']);
        LoggerHelper::security('REGISTER_GOOGLE_SUCCESS', ['usuario_id' => $newUser['id']], $email);

        FlashHelper::set('success', 'Conta criada com sucesso com sua conta Google! Bem-vindo(a) ao Symo.');
        $this->redirect('/dashboard');
    }
}
