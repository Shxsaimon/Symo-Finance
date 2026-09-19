<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Controlador da Lixeira do Sistema (TrashController)
 *
 * Módulo exclusivo de Administradores. Permite auditar registros excluídos logicamente,
 * restaurá-los recalculando saldos financeiros ou expurgá-los definitivamente (Hard Delete).
 */
class TrashController extends Controller
{
    private Trash $trashModel;

    public function __construct()
    {
        $this->trashModel = new Trash();
    }

    /**
     * Exibe a tela de Lixeira com abas para Transações, Contas e Categorias.
     *
     * @return void
     */
    public function index(): void
    {
        AuthHelper::requireLogin();

        $activeTab = $_GET['aba'] ?? 'transacoes';
        if (!in_array($activeTab, ['transacoes', 'contas', 'categorias'], true)) {
            $activeTab = 'transacoes';
        }

        $counts = $this->trashModel->getCounts();
        $transacoes = $this->trashModel->getDeletedTransactions();
        $contas     = $this->trashModel->getDeletedAccounts();
        $categorias = $this->trashModel->getDeletedCategories();

        $this->render('trash/index', [
            'title'      => 'Lixeira do Sistema — Symo',
            'activeTab'  => $activeTab,
            'counts'     => $counts,
            'transacoes' => $transacoes,
            'contas'     => $contas,
            'categorias' => $categorias,
        ], 'layouts/main');
    }

    /**
     * Restaura um registro excluído logicamente e reprocessa saldos se necessário.
     *
     * @return void
     */
    public function restore(): void
    {
        AuthHelper::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/lixeira');
        }

        if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
            FlashHelper::set('error', 'Token de segurança expirado.');
            $this->redirect('/lixeira');
        }

        $entidade = trim((string) ($_POST['entidade'] ?? ''));
        $id = (int) ($_POST['id'] ?? 0);
        $currentUserId = (int) AuthHelper::id();

        $result = $this->trashModel->restore($entidade, $id, $currentUserId);

        if ($result['success']) {
            FlashHelper::set('success', $result['message']);
        } else {
            FlashHelper::set('error', $result['message']);
        }

        $this->redirect('/lixeira?aba=' . urlencode($entidade));
    }

    /**
     * Expurgar definitivamente um registro do banco de dados (Hard Delete).
     *
     * @return void
     */
    public function purge(): void
    {
        AuthHelper::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/lixeira');
        }

        if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
            FlashHelper::set('error', 'Token de segurança expirado.');
            $this->redirect('/lixeira');
        }

        $entidade = trim((string) ($_POST['entidade'] ?? ''));
        $id = (int) ($_POST['id'] ?? 0);
        $currentUserId = (int) AuthHelper::id();

        $result = $this->trashModel->purge($entidade, $id, $currentUserId);

        if ($result['success']) {
            FlashHelper::set('success', $result['message']);
        } else {
            FlashHelper::set('error', $result['message']);
        }

        $this->redirect('/lixeira?aba=' . urlencode($entidade));
    }
}

