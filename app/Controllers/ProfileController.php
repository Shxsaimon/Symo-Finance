<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Controlador de Perfil Pessoal (ProfileController)
 *
 * Permite a qualquer usuário autenticado (admin ou operador)
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
            'email'  => $user['email'], // Preserva email
            'perfil' => $user['perfil'], // Preserva perfil
            'status' => $user['status'], // Preserva status
            'avatar' => $avatarFilename,
        ];

        $res = $this->userModel->updateUser($userId, $dadosUpdate, $userId);

        // Se informou alteração de senha
        $novaSenha = (string) ($_POST['nova_senha'] ?? '');
        $confirmacaoSenha = (string) ($_POST['confirmacao_senha'] ?? '');

        if (!empty($novaSenha)) {
            if ($novaSenha !== $confirmacaoSenha) {
                FlashHelper::set('error', 'A confirmação de senha não confere com a nova senha digitada.');
                $this->redirect('/perfil');
            }

            if (mb_strlen($novaSenha) < 6) {
                FlashHelper::set('error', 'A nova senha deve conter no mínimo 6 caracteres.');
                $this->redirect('/perfil');
            }

            $this->userModel->updatePassword($userId, $novaSenha);
        }

        if ($res['success']) {
            // Atualiza dados na sessão ativa
            $userAtualizado = $this->userModel->find($userId);
            if ($userAtualizado) {
                AuthHelper::login($userAtualizado);
            }
            FlashHelper::set('success', 'Perfil atualizado com sucesso.');
        } else {
            FlashHelper::set('error', $res['message']);
        }

        $this->redirect('/perfil');
    }
}

