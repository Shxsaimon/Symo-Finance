<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Controlador de Transferências entre Contas (TransferController)
 *
 * Gerencia a movimentação interna de valores entre contas bancárias com atomicidade
 * e isolamento RBAC para exclusão/estorno.
 */
class TransferController extends Controller
{
    private Transfer $transferModel;
    private Account $accountModel;

    public function __construct()
    {
        $this->transferModel = new Transfer();
        $this->accountModel = new Account();
    }

    /**
     * Exibe a listagem filtrada de transferências e formulário modal.
     *
     * @return void
     */
    public function index(): void
    {
        AuthHelper::requireLogin();

        $mesAtual = (int) date('n');
        $anoAtual = (int) date('Y');

        $filters = [
            'mes'      => !empty($_GET['mes']) ? (int) $_GET['mes'] : $mesAtual,
            'ano'      => !empty($_GET['ano']) ? (int) $_GET['ano'] : $anoAtual,
            'conta_id' => !empty($_GET['conta_id']) ? (int) $_GET['conta_id'] : null,
            'busca'    => !empty($_GET['busca']) ? trim((string) $_GET['busca']) : null,
        ];

        $transferencias = $this->transferModel->getFiltered($filters);
        $resumo = $this->transferModel->getMonthlySummary($filters['mes'], $filters['ano'], $filters['conta_id']);
        $contas = $this->accountModel->getActiveAccounts();

        $this->render('transfers/index', [
            'title'          => 'Transferências entre Contas — Symo',
            'transferencias' => $transferencias,
            'resumo'         => $resumo,
            'filters'        => $filters,
            'contas'         => $contas,
            'currentUserId'  => (int) AuthHelper::id(),
            'isAdmin'        => AuthHelper::isAdmin(),
        ], 'layouts/main');
    }

    /**
     * Processa o registro de uma nova transferência com débito e crédito atômicos.
     *
     * @return void
     */
    public function store(): void
    {
        AuthHelper::requireLogin();

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF expirado ou inválido.');
            header('Location: ' . BASE_URL . '/transferencias');
            exit;
        }

        $contaOrigemId = (int) ($_POST['conta_origem_id'] ?? 0);
        $contaDestinoId = (int) ($_POST['conta_destino_id'] ?? 0);
        $valorRaw = trim((string) ($_POST['valor'] ?? '0,00'));
        $dataTransferencia = !empty($_POST['data_transferencia']) ? trim((string) $_POST['data_transferencia']) : date('Y-m-d');
        $observacoes = !empty($_POST['observacoes']) ? trim((string) $_POST['observacoes']) : null;

        if ($contaOrigemId <= 0 || $contaDestinoId <= 0) {
            FlashHelper::set('error', 'Selecione a conta de origem e a conta de destino.');
            header('Location: ' . BASE_URL . '/transferencias');
            exit;
        }

        if ($contaOrigemId === $contaDestinoId) {
            FlashHelper::set('error', 'A conta de origem e a conta de destino não podem ser iguais.');
            header('Location: ' . BASE_URL . '/transferencias');
            exit;
        }

        $valor = FormatHelper::parseCurrency($valorRaw);
        if ($valor <= 0) {
            FlashHelper::set('error', 'O valor da transferência deve ser maior que zero.');
            header('Location: ' . BASE_URL . '/transferencias');
            exit;
        }

        $userId = (int) AuthHelper::id();

        try {
            $this->transferModel->createTransfer([
                'conta_origem_id'    => $contaOrigemId,
                'conta_destino_id'   => $contaDestinoId,
                'valor'              => $valor,
                'data_transferencia' => $dataTransferencia,
                'observacoes'        => $observacoes,
            ], $userId);

            FlashHelper::set('success', 'Transferência de ' . FormatHelper::currency($valor) . ' realizada com sucesso!');
        } catch (Throwable $e) {
            FlashHelper::set('error', 'Erro ao realizar transferência: ' . $e->getMessage());
        }

        $dt = new DateTimeImmutable($dataTransferencia);
        $mes = $dt->format('n');
        $ano = $dt->format('Y');

        header('Location: ' . BASE_URL . "/transferencias?mes={$mes}&ano={$ano}");
        exit;
    }

    /**
     * Processa a exclusão lógica e estorno dos saldos da transferência.
     *
     * @param int|string $id
     * @return void
     */
    public function delete(int|string $id): void
    {
        AuthHelper::requireLogin();
        $id = (int) $id;

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF inválido.');
            header('Location: ' . BASE_URL . '/transferencias');
            exit;
        }

        $userId = (int) AuthHelper::id();
        $result = $this->transferModel->deleteTransfer($id, $userId);

        if ($result['success']) {
            FlashHelper::set('success', $result['message']);
        } else {
            FlashHelper::set('error', $result['message']);
        }

        $redirect = $_POST['redirect_to'] ?? BASE_URL . '/transferencias';
        header('Location: ' . $redirect);
        exit;
    }
}

