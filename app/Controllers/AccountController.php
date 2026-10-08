<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Controlador de Contas Bancárias (AccountController)
 *
 * Gerencia a exibição e operações de contas e carteiras com regras RBAC:
 * - Operador: visualiza contas e saldos.
 * - Administrador: cria, edita, inativa e exclui logicamente.
 */
class AccountController extends Controller
{
    private Account $accountModel;

    public function __construct()
    {
        $this->accountModel = new Account();
    }

    /**
     * Exibe a listagem de contas bancárias e carteiras financeiras.
     *
     * @return void
     */
    public function index(): void
    {
        AuthHelper::requireLogin();

        $contas = $this->accountModel->getAllAccounts();
        $saldoAtual = $this->accountModel->getCurrentBalance();
        $saldoInvestimentos = $this->accountModel->getInvestmentBalance() + (new Investment())->getTotalInvested();

        $this->render('accounts/index', [
            'title'              => 'Contas Bancárias e Carteiras — Symo',
            'contas'             => $contas,
            'saldoAtual'         => $saldoAtual,
            'saldoInvestimentos' => $saldoInvestimentos,
            'isAdmin'            => AuthHelper::isAdmin(),
        ], 'layouts/main');
    }

    /**
     * Processa a criação de uma nova conta bancária (Apenas Administrador).
     *
     * @return void
     */
    public function store(): void
    {
        AuthHelper::requireRole('admin');

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF expirado ou inválido.');
            header('Location: ' . BASE_URL . '/contas');
            exit;
        }

        $nome = trim((string) ($_POST['nome'] ?? ''));
        $instituicao = trim((string) ($_POST['instituicao'] ?? ''));
        $tipo = trim((string) ($_POST['tipo'] ?? 'corrente'));
        $saldoInicialRaw = trim((string) ($_POST['saldo_inicial'] ?? '0,00'));
        $incluirSaldoAtual = isset($_POST['incluir_saldo_atual']) && $_POST['incluir_saldo_atual'] === '1';
        $cor = trim((string) ($_POST['cor'] ?? '#6F02FF'));
        $status = trim((string) ($_POST['status'] ?? 'ativa'));

        if (empty($nome)) {
            FlashHelper::set('error', 'O nome da conta é de preenchimento obrigatório.');
            header('Location: ' . BASE_URL . '/contas');
            exit;
        }

        // Limpa formatação brasileira de moeda (ex: R$ 1.500,50 -> 1500.50)
        $saldoInicial = FormatHelper::parseCurrency($saldoInicialRaw);

        $userId = (int) AuthHelper::id();
        $this->accountModel->createAccount([
            'nome'          => $nome,
            'instituicao'   => $instituicao,
            'tipo'          => $tipo,
            'saldo_inicial' => $saldoInicial,
            'incluir_saldo_atual' => $incluirSaldoAtual,
            'cor'           => $cor,
            'status'        => $status,
        ], $userId);

        FlashHelper::set('success', "Conta '{$nome}' cadastrada com sucesso!");
        header('Location: ' . BASE_URL . '/contas');
        exit;
    }

    /**
     * Processa a edição de dados cadastrais de uma conta (Apenas Administrador).
     *
     * @param int $id
     * @return void
     */
    public function update(int|string $id): void
    {
        AuthHelper::requireRole('admin');
        $id = (int) $id;

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF inválido.');
            header('Location: ' . BASE_URL . '/contas');
            exit;
        }

        $nome = trim((string) ($_POST['nome'] ?? ''));
        $instituicao = trim((string) ($_POST['instituicao'] ?? ''));
        $tipo = trim((string) ($_POST['tipo'] ?? 'corrente'));
        $incluirSaldoAtual = isset($_POST['incluir_saldo_atual']) && $_POST['incluir_saldo_atual'] === '1';
        $cor = trim((string) ($_POST['cor'] ?? '#6F02FF'));
        $status = trim((string) ($_POST['status'] ?? 'ativa'));

        if (empty($nome)) {
            FlashHelper::set('error', 'O nome da conta não pode ficar vazio.');
            header('Location: ' . BASE_URL . '/contas');
            exit;
        }

        $userId = (int) AuthHelper::id();
        $updated = $this->accountModel->updateAccount($id, [
            'nome'        => $nome,
            'instituicao' => $instituicao,
            'tipo'        => $tipo,
            'incluir_saldo_atual' => $incluirSaldoAtual,
            'cor'         => $cor,
            'status'      => $status,
        ], $userId);

        if ($updated) {
            FlashHelper::set('success', "Conta '{$nome}' atualizada com sucesso!");
        } else {
            FlashHelper::set('error', 'Conta bancária não encontrada ou não pôde ser atualizada.');
        }

        header('Location: ' . BASE_URL . '/contas');
        exit;
    }

    /**
     * Alterna o status da conta entre ativa/inativa (Apenas Administrador).
     *
     * @param int|string $id
     * @return void
     */
    public function toggle(int|string $id): void
    {
        AuthHelper::requireRole('admin');
        $id = (int) $id;

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF inválido.');
            header('Location: ' . BASE_URL . '/contas');
            exit;
        }

        $userId = (int) AuthHelper::id();
        $this->accountModel->toggleStatus($id, $userId);

        FlashHelper::set('info', 'Status da conta alterado com sucesso.');
        header('Location: ' . BASE_URL . '/contas');
        exit;
    }

    /**
     * Executa a exclusão lógica da conta (Apenas Administrador).
     *
     * @param int|string $id
     * @return void
     */
    public function delete(int|string $id): void
    {
        AuthHelper::requireRole('admin');
        $id = (int) $id;

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF inválido.');
            header('Location: ' . BASE_URL . '/contas');
            exit;
        }

        $userId = (int) AuthHelper::id();
        $result = $this->accountModel->deleteAccount($id, $userId);

        if ($result['success']) {
            FlashHelper::set('success', $result['message']);
        } else {
            FlashHelper::set('warning', $result['message']);
        }

        header('Location: ' . BASE_URL . '/contas');
        exit;
    }
}
