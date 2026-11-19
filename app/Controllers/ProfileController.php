<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Controlador de Perfil Pessoal (ProfileController)
 *
 * Permite a qualquer usuário autenticado
 * atualizar seus dados cadastrais, alterar senha e enviar foto de avatar.
 */
class ProfileController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Exibe a tela de perfil do próprio usuário autenticado.
     *
     * @return void
     */
    public function index(): void
    {
        AuthHelper::requireLogin();

        $userId = (int) AuthHelper::id();
        $user = $this->userModel->find($userId);

        $this->render('users/profile', [
            'title' => 'Meu Perfil — Symo',
            'user'  => $user,
        ], 'layouts/main');
    }

    /**
     * Dispara um novo código de verificação para o e-mail do usuário logado e redireciona para a tela de verificação.
     *
     * @return void
     */
    public function sendVerification(): void
    {
        AuthHelper::requireLogin();

        $userId = (int) AuthHelper::id();
        $user = $this->userModel->find($userId);

        if (!$user) {
            $this->redirect('/login');
            return;
        }

        if (!empty($user['email_verificado_em'])) {
            FlashHelper::set('info', 'Seu endereço de e-mail já está confirmado.');
            $this->redirect('/perfil');
            return;
        }

        $tokenModel = new AuthToken();
        $code = $tokenModel->createVerificationCode($userId);
        $mailResult = MailHelper::sendVerificationCode((string) $user['email'], (string) $user['nome'], $code);

        $_SESSION['pending_verify_user_id'] = $userId;
        $_SESSION['pending_verify_email'] = (string) $user['email'];
        $_SESSION['pending_verify_nome'] = (string) $user['nome'];

        if ($mailResult['success']) {
            FlashHelper::set('success', 'Enviamos um novo código de 6 dígitos para o seu e-mail.');
        } else {
            FlashHelper::set('info', 'Código gerado. Caso não receba, utilize a opção de reenviar na tela de confirmação.');
        }

        $this->redirect('/verificar-email');
    }

    /**
     * Atualiza os dados de perfil do usuário autenticado.
     *
     * @return void
     */
    public function update(): void
    {
        AuthHelper::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/perfil');
        }

        if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
            FlashHelper::set('error', 'Token de segurança expirado.');
            $this->redirect('/perfil');
        }

        $userId = (int) AuthHelper::id();
        $user = $this->userModel->find($userId);
        if (!$user) {
            FlashHelper::set('error', 'Usuário não encontrado.');
            $this->redirect('/perfil');
        }

        $nome = trim((string) ($_POST['nome'] ?? $user['nome']));
        $email = trim((string) ($_POST['email'] ?? $user['email']));
        $senhaAtual = (string) ($_POST['senha_atual'] ?? '');
        $novaSenha = (string) ($_POST['nova_senha'] ?? '');
        $confirmacaoSenha = (string) ($_POST['confirmacao_senha'] ?? '');
        $alterandoSenha = $senhaAtual !== '' || $novaSenha !== '' || $confirmacaoSenha !== '';

        if ($alterandoSenha) {
            if ($senhaAtual === '' || $novaSenha === '' || $confirmacaoSenha === '') {
                FlashHelper::set('error', 'Preencha a senha atual, a nova senha e a confirmação para alterar sua senha.');
                $this->redirect('/perfil');
            }

            if (mb_strlen($novaSenha) < 8 || !preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $novaSenha)) {
                FlashHelper::set('error', 'A nova senha deve conter no mínimo 8 caracteres, incluindo letras e números.');
                $this->redirect('/perfil');
            }

            if ($novaSenha !== $confirmacaoSenha) {
                FlashHelper::set('error', 'A confirmação de senha não confere com a nova senha digitada.');
                $this->redirect('/perfil');
            }

            if (empty($user['senha']) || !password_verify($senhaAtual, (string) $user['senha'])) {
                FlashHelper::set('error', 'A senha atual informada está incorreta.');
                $this->redirect('/perfil');
            }
        }

        $validation = $this->userModel->validateOwnProfile($userId, [
            'nome' => $nome,
            'email' => $email,
        ]);
        if (!$validation['success']) {
            FlashHelper::set('error', $validation['message']);
            $this->redirect('/perfil');
        }

        $avatarFilename = $user['avatar'];

        if (!empty($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = UploadHelper::uploadImage($_FILES['avatar'], 'avatars', $user['avatar']);
            if (!$uploadResult['success']) {
                FlashHelper::set('error', $uploadResult['error'] ?? 'Falha no upload do avatar.');
                $this->redirect('/perfil');
            }
            $avatarFilename = $uploadResult['filename'] ?? null;
        }

        $dadosUpdate = [
            'nome'   => $nome,
            'email'  => $email,
            'avatar' => $avatarFilename,
        ];

        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $res = $this->userModel->updateOwnProfile($userId, $dadosUpdate);
            if (!$res['success']) {
                $db->rollBack();
                FlashHelper::set('error', $res['message']);
                $this->redirect('/perfil');
            }

            if ($alterandoSenha && !$this->userModel->updatePassword($userId, $novaSenha)) {
                throw new RuntimeException('Não foi possível atualizar a senha do usuário.');
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        $userAtualizado = $this->userModel->find($userId);
        if (!$userAtualizado) {
            throw new RuntimeException('O perfil foi salvo, mas não foi possível recarregar os dados do usuário.');
        }

        AuthHelper::refreshUserData($userAtualizado);
        FlashHelper::set('success', 'Perfil atualizado com sucesso.');
        $this->redirect('/perfil');
    }
}
