<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Controlador de Categorias de Receitas e Despesas (CategoryController)
 *
 * Gerencia o catálogo de categorias com segregação de permissões:
 * - Operador: consulta de categorias.
 * - Administrador: criação, alteração, inativação e exclusão lógica.
 */
class CategoryController extends Controller
{
    private Category $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new Category();
    }

    /**
     * Exibe a listagem de categorias organizadas por tipo (Receitas e Despesas).
     *
     * @return void
     */
    public function index(): void
    {
        AuthHelper::requireLogin();

        $receitas = $this->categoryModel->getByType('receita', false);
        $despesas = $this->categoryModel->getByType('despesa', false);

        $this->render('categories/index', [
            'title'    => 'Categorias de Receitas e Despesas — Symo',
            'receitas' => $receitas,
            'despesas' => $despesas,
            'isAdmin'  => AuthHelper::isAdmin(),
        ], 'layouts/main');
    }

    /**
     * Processa a criação de uma nova categoria (Apenas Administrador).
     *
     * @return void
     */
    public function store(): void
    {
        AuthHelper::requireRole('admin');

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF expirado ou inválido.');
            header('Location: ' . BASE_URL . '/categorias');
            exit;
        }

        $nome = trim((string) ($_POST['nome'] ?? ''));
        $tipo = trim((string) ($_POST['tipo'] ?? 'despesa'));
        $icone = trim((string) ($_POST['icone'] ?? 'bi-tag'));
        $cor = trim((string) ($_POST['cor'] ?? '#793CFE'));
        $status = trim((string) ($_POST['status'] ?? 'ativa'));

        if (empty($nome)) {
            FlashHelper::set('error', 'O nome da categoria é obrigatório.');
            header('Location: ' . BASE_URL . '/categorias');
            exit;
        }

        $userId = (int) AuthHelper::id();
        $this->categoryModel->createCategory([
            'nome'   => $nome,
            'tipo'   => $tipo,
            'icone'  => $icone,
            'cor'    => $cor,
            'status' => $status,
        ], $userId);

        FlashHelper::set('success', "Categoria '{$nome}' cadastrada com sucesso!");
        header('Location: ' . BASE_URL . '/categorias');
        exit;
    }

    /**
     * Processa a atualização de uma categoria existente (Apenas Administrador).
     *
     * @param int $id
     * @return void
     */
    public function update(int $id): void
    {
        AuthHelper::requireRole('admin');

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF inválido.');
            header('Location: ' . BASE_URL . '/categorias');
            exit;
        }

        $nome = trim((string) ($_POST['nome'] ?? ''));
        $tipo = trim((string) ($_POST['tipo'] ?? 'despesa'));
        $icone = trim((string) ($_POST['icone'] ?? 'bi-tag'));
        $cor = trim((string) ($_POST['cor'] ?? '#793CFE'));
        $status = trim((string) ($_POST['status'] ?? 'ativa'));

        if (empty($nome)) {
            FlashHelper::set('error', 'O nome da categoria não pode ser vazio.');
            header('Location: ' . BASE_URL . '/categorias');
            exit;
        }

        $userId = (int) AuthHelper::id();
        $updated = $this->categoryModel->updateCategory($id, [
            'nome'   => $nome,
            'tipo'   => $tipo,
            'icone'  => $icone,
            'cor'    => $cor,
            'status' => $status,
        ], $userId);

        if ($updated) {
            FlashHelper::set('success', "Categoria '{$nome}' atualizada com sucesso!");
        } else {
            FlashHelper::set('error', 'Categoria não encontrada ou não pôde ser atualizada.');
        }

        header('Location: ' . BASE_URL . '/categorias');
        exit;
    }

    /**
     * Alterna o status da categoria entre ativa/inativa (Apenas Administrador).
     *
     * @param int $id
     * @return void
     */
    public function toggle(int $id): void
    {
        AuthHelper::requireRole('admin');

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF inválido.');
            header('Location: ' . BASE_URL . '/categorias');
            exit;
        }

        $userId = (int) AuthHelper::id();
        $this->categoryModel->toggleStatus($id, $userId);

        FlashHelper::set('info', 'Status da categoria alterado com sucesso.');
        header('Location: ' . BASE_URL . '/categorias');
        exit;
    }

    /**
     * Executa a exclusão lógica da categoria se não houver vínculos (Apenas Administrador).
     *
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        AuthHelper::requireRole('admin');

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF inválido.');
            header('Location: ' . BASE_URL . '/categorias');
            exit;
        }

        $userId = (int) AuthHelper::id();
        $result = $this->categoryModel->deleteCategory($id, $userId);

        if ($result['success']) {
            FlashHelper::set('success', $result['message']);
        } else {
            FlashHelper::set('warning', $result['message']);
        }

        header('Location: ' . BASE_URL . '/categorias');
        exit;
    }
}
