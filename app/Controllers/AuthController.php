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
    private AuthToken $tokenModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->tokenModel = new AuthToken();
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

        // 7. Verificação de confirmação de e-mail (envia código novo automaticamente)
        if (!User::isEmailVerified($user)) {
            $_SESSION['pending_verify_user_id'] = (int) $user['id'];
            $_SESSION['pending_verify_email'] = (string) $user['email'];
            $_SESSION['pending_verify_nome'] = (string) $user['nome'];

            // Gera e envia um novo código imediatamente para garantir que o usuário tenha um código ativo
            $code = $this->tokenModel->createVerificationCode((int) $user['id']);
            $mailResult = MailHelper::sendVerificationCode((string) $user['email'], (string) $user['nome'], $code);

            if ($mailResult['success']) {
                FlashHelper::set('info', 'Seu endereço de e-mail ainda não foi confirmado. Enviamos um novo código de 6 dígitos para sua caixa de entrada.');
            } else {
                FlashHelper::set('info', 'Seu endereço de e-mail ainda não foi confirmado. Digite o código de 6 dígitos ou solicite o reenvio.');
            }

            $this->redirect('/verificar-email');
            return;
        }

        // 8. Autenticação bem-sucedida
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
     * Exibe a tela de confirmação de e-mail com código numérico de 6 dígitos.
     *
     * @return void
     */
    public function showVerifyEmail(): void
    {
        $loggedUser = AuthHelper::user();
        if ($loggedUser && !empty($loggedUser['email_verificado_em'])) {
            $this->redirect('/dashboard');
            return;
        }

        $pendingUserId = $_SESSION['pending_verify_user_id'] ?? ($loggedUser['id'] ?? null);
        if (!$pendingUserId) {
            FlashHelper::set('info', 'Nenhuma validação de e-mail pendente. Faça login para continuar.');
            $this->redirect('/login');
            return;
        }

        $email = (string) ($_SESSION['pending_verify_email'] ?? ($loggedUser['email'] ?? ''));

        $this->render('auth/verify-email', [
            'title' => 'Verificação de E-mail — Symo',
            'email' => $email,
        ], 'layouts/auth');
    }

    /**
     * Valida o código numérico de 6 dígitos digitado pelo usuário.
     *
     * @return void
     */
    public function verifyEmail(): void
    {
        if (!$this->validateCsrf()) {
            LoggerHelper::security('CSRF_INVALID', ['action' => 'verifyEmail']);
            FlashHelper::set('error', 'Token de segurança expirado ou inválido. Envie o formulário novamente.');
            $this->redirect('/verificar-email');
            return;
        }

        $loggedUser = AuthHelper::user();
        $pendingUserId = $_SESSION['pending_verify_user_id'] ?? ($loggedUser['id'] ?? null);
        if (!$pendingUserId) {
            FlashHelper::set('error', 'Sessão de verificação expirada. Faça login para tentar novamente.');
            $this->redirect('/login');
            return;
        }

        $post = $this->getPost();
        $code = trim((string) ($post['codigo'] ?? ''));

        if ($code === '' || strlen($code) !== 6 || !ctype_digit($code)) {
            FlashHelper::set('error', 'Por favor, digite os 6 números do código recebido por e-mail.');
            $this->redirect('/verificar-email');
            return;
        }

        $result = $this->tokenModel->verifyCode((int) $pendingUserId, $code);
        if (!$result['success']) {
            FlashHelper::set('error', $result['message']);
            $this->redirect('/verificar-email');
            return;
        }

        // Marca o e-mail como verificado no banco
        $this->userModel->markEmailAsVerified((int) $pendingUserId);

        // Popula dados padrão (categorias e preferências) apenas agora que o e-mail foi validado
        try {
            $db = Database::getConnection();
            DefaultDataHelper::popularDadosPadrao($db, (int) $pendingUserId);
        } catch (Throwable $e) {
            LoggerHelper::error($e, ['context' => 'AuthController::verifyEmail - popularDadosPadrao', 'usuario_id' => $pendingUserId]);
        }

        $user = $this->userModel->find((int) $pendingUserId);
        if (!$user) {
            FlashHelper::set('error', 'Usuário não localizado. Tente fazer login.');
            $this->redirect('/login');
            return;
        }

        unset(
            $_SESSION['pending_verify_user_id'],
            $_SESSION['pending_verify_email'],
            $_SESSION['pending_verify_nome']
        );

        AuthHelper::login($user);
        $this->userModel->touch((int) $user['id']);

        FlashHelper::set('success', 'E-mail confirmado com sucesso! Bem-vindo(a) ao Symo.');
        $this->redirect('/dashboard');
    }

    /**
     * Reenvia o código de verificação de 6 dígitos respeitando cooldown de segurança.
     *
     * @return void
     */
    public function resendVerificationCode(): void
    {
        if (!$this->validateCsrf()) {
            LoggerHelper::security('CSRF_INVALID', ['action' => 'resendVerificationCode']);
            FlashHelper::set('error', 'Token de segurança expirado. Tente novamente.');
            $this->redirect('/verificar-email');
            return;
        }

        $loggedUser = AuthHelper::user();
        $pendingUserId = $_SESSION['pending_verify_user_id'] ?? ($loggedUser['id'] ?? null);
        if (!$pendingUserId) {
            FlashHelper::set('error', 'Sessão expirada. Acesse com seu login para solicitar verificação.');
            $this->redirect('/login');
            return;
        }

        if (!$this->tokenModel->canResendCode((int) $pendingUserId, 60)) {
            FlashHelper::set('error', 'Aguarde 60 segundos antes de solicitar o reenvio de um novo código.');
            $this->redirect('/verificar-email');
            return;
        }

        $email = (string) ($_SESSION['pending_verify_email'] ?? ($loggedUser['email'] ?? ''));
        $nome  = (string) ($_SESSION['pending_verify_nome'] ?? ($loggedUser['nome'] ?? 'Usuário'));

        $code = $this->tokenModel->createVerificationCode((int) $pendingUserId);
        $mailResult = MailHelper::sendVerificationCode($email, $nome, $code);

        if (!$mailResult['success']) {
            FlashHelper::set('error', 'Não foi possível reenviar o e-mail: ' . htmlspecialchars($mailResult['message'], ENT_QUOTES, 'UTF-8'));
        } else {
            FlashHelper::set('success', 'Novo código de 6 dígitos enviado para seu e-mail!');
        }

        $this->redirect('/verificar-email');
    }

    /**
     * Exibe a tela com formulário de solicitação de recuperação de senha.
     *
     * @return void
     */
    public function showForgotPassword(): void
    {
        if (AuthHelper::check()) {
            $this->redirect('/dashboard');
            return;
        }

        $this->render('auth/forgot-password', [
            'title' => 'Recuperação de Senha — Symo'
        ], 'layouts/auth');
    }

    /**
     * Processa a solicitação de redefinição de senha e dispara e-mail com link seguro.
     *
     * @return void
     */
    public function sendPasswordReset(): void
    {
        if (!$this->validateCsrf()) {
            LoggerHelper::security('CSRF_INVALID', ['action' => 'sendPasswordReset']);
            FlashHelper::set('error', 'Token de segurança expirado ou inválido. Tente novamente.');
            $this->redirect('/esqueci-senha');
            return;
        }

        $post = $this->getPost();
        $email = trim(strtolower((string) ($post['email'] ?? '')));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            FlashHelper::set('error', 'Por favor, informe um endereço de e-mail válido.');
            $this->redirect('/esqueci-senha');
            return;
        }

        $user = $this->userModel->findByEmail($email);
        if ($user && User::isActive($user)) {
            $rawToken = $this->tokenModel->createPasswordResetToken((int) $user['id']);
            $resetUrl = rtrim(BASE_URL, '/') . '/redefinir-senha?token=' . urlencode($rawToken);
            $mailResult = MailHelper::sendPasswordReset($user['email'], (string) $user['nome'], $resetUrl);
            if (!$mailResult['success']) {
                LoggerHelper::error(
                    new RuntimeException('Falha no envio de e-mail de recuperação: ' . $mailResult['message']),
                    ['context' => 'AuthController::sendPasswordReset', 'email' => $email]
                );
            }
        }

        // Resposta genérica para evitar enumeração de contas
        FlashHelper::set('success', 'Se o e-mail informado estiver cadastrado, você receberá em instantes as instruções e o link seguro para criar uma nova senha.');
        $this->redirect('/esqueci-senha');
    }

    /**
     * Exibe o formulário de definição da nova senha após validação do token recebido.
     *
     * @return void
     */
    public function showResetPassword(): void
    {
        if (AuthHelper::check()) {
            $this->redirect('/dashboard');
            return;
        }

        $query = $this->getQuery();
        $token = trim((string) ($query['token'] ?? ''));

        $tokenData = $this->tokenModel->verifyPasswordResetToken($token);

        $this->render('auth/reset-password', [
            'title'   => 'Criar Nova Senha — Symo',
            'token'   => $token,
            'isValid' => $tokenData !== null,
        ], 'layouts/auth');
    }

    /**
     * Atualiza a senha de acesso utilizando o token validado.
     *
     * @return void
     */
    public function resetPasswordWithToken(): void
    {
        if (!$this->validateCsrf()) {
            LoggerHelper::security('CSRF_INVALID', ['action' => 'resetPasswordWithToken']);
            FlashHelper::set('error', 'Token de segurança expirado. Tente novamente.');
            $this->redirect('/esqueci-senha');
            return;
        }

        $post = $this->getPost();
        $token = trim((string) ($post['token'] ?? ''));
        $senha = (string) ($post['senha'] ?? '');
        $senhaConfirmacao = (string) ($post['senha_confirmacao'] ?? '');

        $tokenData = $this->tokenModel->verifyPasswordResetToken($token);
        if (!$tokenData) {
            FlashHelper::set('error', 'Este link de redefinição expirou ou é inválido. Por favor, solicite um novo link.');
            $this->redirect('/esqueci-senha');
            return;
        }

        if (mb_strlen($senha) < 8 || !preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $senha)) {
            FlashHelper::set('error', 'A nova senha deve conter no mínimo 8 caracteres, incluindo letras e números.');
            $this->redirect('/redefinir-senha?token=' . urlencode($token));
            return;
        }

        if ($senha !== $senhaConfirmacao) {
            FlashHelper::set('error', 'A confirmação de senha não coincide com a nova senha digitada.');
            $this->redirect('/redefinir-senha?token=' . urlencode($token));
            return;
        }

        $userId = (int) $tokenData['usuario_id'];
        $updated = $this->userModel->updatePassword($userId, $senha);

        if (!$updated) {
            FlashHelper::set('error', 'Não foi possível atualizar a senha. Tente novamente em instantes.');
            $this->redirect('/redefinir-senha?token=' . urlencode($token));
            return;
        }

        // Marca o token como consumido
        $this->tokenModel->consumePasswordResetToken((int) $tokenData['id']);

        // Garante que o e-mail passa a constar como verificado
        $this->userModel->markEmailAsVerified($userId);

        // Garante que dados padrão existam caso a conta ainda não estivesse populada
        try {
            $db = Database::getConnection();
            DefaultDataHelper::popularDadosPadrao($db, $userId);
        } catch (Throwable $e) {
            LoggerHelper::error($e, ['context' => 'AuthController::resetPasswordWithToken - popularDadosPadrao', 'usuario_id' => $userId]);
        }

        LoggerHelper::security('PASSWORD_RESET_SUCCESS', ['usuario_id' => $userId]);

        FlashHelper::set('success', 'Senha redefinida com sucesso! Você já pode entrar com sua nova senha.');
        $this->redirect('/login');
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

        // Gera código numérico de 6 dígitos para validação de e-mail
        $code = $this->tokenModel->createVerificationCode((int) $user['id']);
        $mailResult = MailHelper::sendVerificationCode($email, $nome, $code);

        $_SESSION['pending_verify_user_id'] = (int) $user['id'];
        $_SESSION['pending_verify_email'] = $email;
        $_SESSION['pending_verify_nome'] = $nome;

        if (!$mailResult['success']) {
            LoggerHelper::error(
                new RuntimeException('Falha no envio de e-mail de verificação: ' . $mailResult['message']),
                ['context' => 'AuthController::register', 'email' => $email]
            );
            FlashHelper::set('info', 'Conta criada! Não foi possível enviar o e-mail no momento (' . htmlspecialchars($mailResult['message'], ENT_QUOTES, 'UTF-8') . '). Você pode solicitar o reenvio.');
        } else {
            FlashHelper::set('success', 'Conta criada! Enviamos um código de 6 dígitos para o seu e-mail.');
        }

        $this->redirect('/verificar-email');
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

            // Garante que se a conta local ainda não havia sido verificada, os dados padrão sejam populados
            try {
                $db = Database::getConnection();
                DefaultDataHelper::popularDadosPadrao($db, (int) $userByEmail['id']);
            } catch (Throwable $e) {
                LoggerHelper::error($e, ['context' => 'AuthController::handleGoogleCallback - popularDadosPadrao', 'usuario_id' => $userByEmail['id']]);
            }

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
