<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Controlador de Gestão de Usuários (UserController)
 *
 * Módulo exclusivo do perfil Administrador (RBAC). Permite criação, edição,
 * alternância de status (inativação imediata), redefinição de senha e exclusão lógica.
 */
class UserController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Exibe a listagem de usuários do sistema.
     *
     * @return void
     */
    public function index(): void
    {
        AuthHelper::requireDev();

        $users = $this->userModel->getAllUsers();

        $this->render('users/index', [
            'title'       => 'Gestão de Usuários — Symo',
            'users'       => $users,
            'currentUser' => AuthHelper::user(),
        ], 'layouts/main');
    }

    /**
     * Cadastra um novo usuário no sistema.
     *
     * @return void
     */
    public function store(): void
    {
        AuthHelper::requireDev();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/usuarios');
        }

        if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
            FlashHelper::set('error', 'Token de segurança expirado. Tente novamente.');
            $this->redirect('/usuarios');
        }

        $avatarFilename = null;
        if (!empty($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = UploadHelper::uploadImage($_FILES['avatar'], 'avatars');
            if (!$uploadResult['success']) {
                FlashHelper::set('error', $uploadResult['error'] ?? 'Falha no upload do avatar.');
                $this->redirect('/usuarios');
            }
            $avatarFilename = $uploadResult['filename'] ?? null;
        }

        $data = [
            'nome'   => $_POST['nome'] ?? '',
            'email'  => $_POST['email'] ?? '',
            'senha'  => $_POST['senha'] ?? '',
            'perfil' => $_POST['perfil'] ?? 'operador',
            'status' => $_POST['status'] ?? 'ativo',
            'avatar' => $avatarFilename,
        ];

        $currentUserId = (int) AuthHelper::id();
        $result = $this->userModel->createUser($data, $currentUserId);

        if ($result['success']) {
            FlashHelper::set('success', $result['message']);
        } else {
            // Se falhou e foi feito upload, limpa a imagem
            if ($avatarFilename) {
                UploadHelper::deleteFile('avatars', $avatarFilename);
            }
            FlashHelper::set('error', $result['message']);
        }

        $this->redirect('/usuarios');
    }

    /**
     * Atualiza os dados de um usuário existente.
     *
     * @param int|string $id
     * @return void
     */
    public function update(int|string $id): void
    {
        AuthHelper::requireDev();
        $userId = (int) $id;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/usuarios');
        }

        if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
            FlashHelper::set('error', 'Token de segurança expirado. Tente novamente.');
            $this->redirect('/usuarios');
        }

        $existingUser = $this->userModel->find($userId);
        if (!$existingUser) {
            FlashHelper::set('error', 'Usuário não encontrado.');
            $this->redirect('/usuarios');
        }

        $avatarFilename = $existingUser['avatar'];
        if (!empty($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = UploadHelper::uploadImage($_FILES['avatar'], 'avatars', $existingUser['avatar']);
            if (!$uploadResult['success']) {
                FlashHelper::set('error', $uploadResult['error'] ?? 'Falha no upload do avatar.');
                $this->redirect('/usuarios');
            }
            $avatarFilename = $uploadResult['filename'] ?? null;
        }

        $data = [
            'nome'   => $_POST['nome'] ?? $existingUser['nome'],
            'email'  => $_POST['email'] ?? $existingUser['email'],
            'perfil' => $_POST['perfil'] ?? $existingUser['perfil'],
            'status' => $_POST['status'] ?? $existingUser['status'],
            'avatar' => $avatarFilename,
        ];

        $currentUserId = (int) AuthHelper::id();
        $result = $this->userModel->updateUser($userId, $data, $currentUserId);

        if ($result['success']) {
            FlashHelper::set('success', $result['message']);
        } else {
            FlashHelper::set('error', $result['message']);
        }

        $this->redirect('/usuarios');
    }

    /**
     * Alterna o status do usuário entre ativo e inativo.
     *
     * @param int|string $id
     * @return void
     */
    public function toggleStatus(int|string $id): void
    {
        AuthHelper::requireDev();
        $userId = (int) $id;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/usuarios');
        }

        if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
            FlashHelper::set('error', 'Token de segurança expirado.');
            $this->redirect('/usuarios');
        }

        $currentUserId = (int) AuthHelper::id();
        $result = $this->userModel->toggleStatus($userId, $currentUserId);

        if ($result['success']) {
            FlashHelper::set('success', $result['message']);
        } else {
            FlashHelper::set('error', $result['message']);
        }

        $this->redirect('/usuarios');
    }

    /**
     * Redefine a senha de um usuário.
     *
     * @param int|string $id
     * @return void
     */
    public function resetPassword(int|string $id): void
    {
        AuthHelper::requireDev();
        $userId = (int) $id;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/usuarios');
        }

        if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
            FlashHelper::set('error', 'Token de segurança expirado.');
            $this->redirect('/usuarios');
        }

        $novaSenha = (string) ($_POST['nova_senha'] ?? '');
        $currentUserId = (int) AuthHelper::id();
        $result = $this->userModel->resetPassword($userId, $novaSenha, $currentUserId);

        if ($result['success']) {
            FlashHelper::set('success', $result['message']);
        } else {
            FlashHelper::set('error', $result['message']);
        }

        $this->redirect('/usuarios');
    }

    /**
     * Exclui logicamente um usuário do sistema.
     *
     * @param int|string $id
     * @return void
     */
    public function delete(int|string $id): void
    {
        AuthHelper::requireDev();
        $userId = (int) $id;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/usuarios');
        }

        if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
            FlashHelper::set('error', 'Token de segurança expirado.');
            $this->redirect('/usuarios');
        }

        $currentUserId = (int) AuthHelper::id();
        $result = $this->userModel->deleteUser($userId, $currentUserId);

        if ($result['success']) {
            FlashHelper::set('success', $result['message']);
        } else {
            FlashHelper::set('error', $result['message']);
        }

        $this->redirect('/usuarios');
    }
}

