<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Controlador de Transações Financeiras (TransactionController)
 *
 * Gerencia o ciclo completo de receitas, despesas à vista e parceladas,
 * filtros avançados e controle de permissões por perfil (RBAC).
 */
class TransactionController extends Controller
{
    private Transaction $transactionModel;
    private Account $accountModel;
    private Category $categoryModel;
    private PaymentMethod $paymentMethodModel;
    private CreditCard $creditCardModel;

    public function __construct()
    {
        $this->transactionModel = new Transaction();
        $this->accountModel = new Account();
        $this->categoryModel = new Category();
        $this->paymentMethodModel = new PaymentMethod();
        $this->creditCardModel = new CreditCard();
    }

    /**
     * Exibe a listagem filtrada de transações com resumo financeiro e modais de lançamento.
     *
     * @return void
     */
    public function index(): void
    {
        AuthHelper::requireLogin();

        $mesAtual = (int) date('n');
        $anoAtual = (int) date('Y');

        $filters = [
            'mes'               => !empty($_GET['mes']) ? (int) $_GET['mes'] : $mesAtual,
            'ano'               => !empty($_GET['ano']) ? (int) $_GET['ano'] : $anoAtual,
            'tipo'              => !empty($_GET['tipo']) ? trim((string) $_GET['tipo']) : null,
            'status'            => !empty($_GET['status']) ? trim((string) $_GET['status']) : null,
            'conta_id'          => !empty($_GET['conta_id']) ? (int) $_GET['conta_id'] : null,
            'categoria_id'      => !empty($_GET['categoria_id']) ? (int) $_GET['categoria_id'] : null,
            'cartao_credito_id' => !empty($_GET['cartao_credito_id']) ? (int) $_GET['cartao_credito_id'] : null,
            'busca'             => !empty($_GET['busca']) ? trim((string) $_GET['busca']) : null,
        ];

        $transacoes = $this->transactionModel->getFiltered($filters);
        $resumo = $this->transactionModel->getMonthlySummary($filters['mes'], $filters['ano'], $filters['conta_id']);

        $contas = $this->accountModel->getActiveAccounts();
        $categoriasReceita = $this->categoryModel->getByType('receita');
        $categoriasDespesa = $this->categoryModel->getByType('despesa');
        $meiosPagamento = $this->paymentMethodModel->getActive();
        $cartoes = $this->creditCardModel->getAllWithSummary();

        $this->render('transactions/index', [
            'title'             => 'Lançamentos Financeiros — Symo',
            'transacoes'        => $transacoes,
            'resumo'            => $resumo,
            'filters'           => $filters,
            'contas'            => $contas,
            'categoriasReceita' => $categoriasReceita,
            'categoriasDespesa' => $categoriasDespesa,
            'meiosPagamento'    => $meiosPagamento,
            'cartoes'           => $cartoes,
            'currentUserId'     => (int) AuthHelper::id(),
            'isAdmin'           => AuthHelper::isAdmin(),
        ], 'layouts/main');
    }

    /**
     * Processa a criação de um novo lançamento (receita, despesa à vista ou parcelada).
     *
     * @return void
     */
    public function store(): void
    {
        AuthHelper::requireLogin();

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF expirado ou inválido.');
            header('Location: ' . BASE_URL . '/transacoes');
            exit;
        }

        $tipo = trim((string) ($_POST['tipo'] ?? 'despesa'));
        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        $valorRaw = trim((string) ($_POST['valor'] ?? '0,00'));
        $dataTransacao = !empty($_POST['data_transacao']) ? trim((string) $_POST['data_transacao']) : date('Y-m-d');
        $dataVencimento = !empty($_POST['data_vencimento']) ? trim((string) $_POST['data_vencimento']) : $dataTransacao;
        $categoriaId = (int) ($_POST['categoria_id'] ?? 1);
        $meioPagamentoId = (int) ($_POST['meio_pagamento_id'] ?? 1);
        $destinoTipo = trim((string) ($_POST['destino_tipo'] ?? 'conta')); // 'conta' ou 'cartao'
        $contaId = ($destinoTipo === 'conta' && !empty($_POST['conta_id'])) ? (int) $_POST['conta_id'] : null;
        $cartaoCreditoId = ($destinoTipo === 'cartao' && !empty($_POST['cartao_credito_id'])) ? (int) $_POST['cartao_credito_id'] : null;
        $status = trim((string) ($_POST['status'] ?? ($tipo === 'receita' ? 'recebido' : 'pago')));
        $observacoes = !empty($_POST['observacoes']) ? trim((string) $_POST['observacoes']) : null;
        $parcelado = trim((string) ($_POST['parcelado'] ?? '0'));
        $totalParcelas = (int) ($_POST['total_parcelas'] ?? 1);

        if (empty($descricao)) {
            FlashHelper::set('error', 'A descrição do lançamento é obrigatória.');
            header('Location: ' . BASE_URL . '/transacoes');
            exit;
        }

        $valor = FormatHelper::parseCurrency($valorRaw);
        if ($valor <= 0) {
            FlashHelper::set('error', 'O valor do lançamento deve ser maior que zero.');
            header('Location: ' . BASE_URL . '/transacoes');
            exit;
        }

        $userId = (int) AuthHelper::id();

        try {
            if ($tipo === 'despesa' && $parcelado === '1' && $totalParcelas > 1) {
                // Compra parcelada em N vezes
                $this->transactionModel->createInstallmentTransactions([
                    'descricao'         => $descricao,
                    'valor'             => $valor,
                    'total_parcelas'    => $totalParcelas,
                    'data_transacao'    => $dataTransacao,
                    'categoria_id'      => $categoriaId,
                    'meio_pagamento_id' => $meioPagamentoId,
                    'conta_id'          => $contaId,
                    'cartao_credito_id' => $cartaoCreditoId,
                    'observacoes'       => $observacoes,
                ], $userId);

                FlashHelper::set('success', "Despesa parcelada em {$totalParcelas}x lançada com sucesso!");
            } else {
                // Lançamento avulso à vista
                $this->transactionModel->createSingleTransaction([
                    'tipo'              => $tipo,
                    'descricao'         => $descricao,
                    'valor'             => $valor,
                    'data_transacao'    => $dataTransacao,
                    'data_vencimento'   => $dataVencimento,
                    'categoria_id'      => $categoriaId,
                    'meio_pagamento_id' => $meioPagamentoId,
                    'conta_id'          => $contaId,
                    'cartao_credito_id' => $cartaoCreditoId,
                    'status'            => $status,
                    'observacoes'       => $observacoes,
                ], $userId);

                $nomeTipo = ($tipo === 'receita') ? 'Receita' : 'Despesa';
                FlashHelper::set('success', "{$nomeTipo} '{$descricao}' lançada com sucesso!");
            }
        } catch (Throwable $e) {
            FlashHelper::set('error', 'Erro ao salvar lançamento: ' . $e->getMessage());
        }

        $dt = new DateTimeImmutable($dataVencimento);
        $mes = $dt->format('n');
        $ano = $dt->format('Y');

        header('Location: ' . BASE_URL . "/transacoes?mes={$mes}&ano={$ano}");
        exit;
    }

    /**
     * Retorna os dados de um lançamento em formato JSON para exibição no modal de edição.
     *
     * @param int|string $id
     * @return void
     */
    public function getJson(int|string $id): void
    {
        AuthHelper::requireLogin();
        $id = (int) $id;

        $transacao = $this->transactionModel->find($id);
        if (!$transacao) {
            http_response_code(404);
            echo json_encode(['error' => 'Lançamento não encontrado.']);
            exit;
        }

        $userId = (int) AuthHelper::id();
        $isAdmin = AuthHelper::isAdmin();

        // Operador não pode editar lançamentos de terceiros
        if (!$isAdmin && (int) $transacao['usuario_id'] !== $userId) {
            http_response_code(403);
            echo json_encode(['error' => 'Sem permissão para acessar este registro.']);
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'id'                => $transacao['id'],
            'tipo'              => $transacao['tipo'],
            'descricao'         => $transacao['descricao'],
            'valor'             => FormatHelper::currency((float) $transacao['valor']),
            'data_transacao'    => $transacao['data_transacao'],
            'data_vencimento'   => $transacao['data_vencimento'],
            'status'            => $transacao['status'],
            'conta_id'          => $transacao['conta_id'],
            'categoria_id'      => $transacao['categoria_id'],
            'meio_pagamento_id' => $transacao['meio_pagamento_id'],
            'cartao_credito_id' => $transacao['cartao_credito_id'],
            'fatura_id'         => $transacao['fatura_id'],
            'observacoes'       => $transacao['observacoes'] ?? '',
        ]);
        exit;
    }

    /**
     * Atualiza os dados de um lançamento.
     *
     * @param int|string $id
     * @return void
     */
    public function update(int|string $id): void
    {
        AuthHelper::requireLogin();
        $id = (int) $id;

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF inválido.');
            header('Location: ' . BASE_URL . '/transacoes');
            exit;
        }

        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        $valorRaw = trim((string) ($_POST['valor'] ?? '0,00'));
        $dataTransacao = !empty($_POST['data_transacao']) ? trim((string) $_POST['data_transacao']) : date('Y-m-d');
        $dataVencimento = !empty($_POST['data_vencimento']) ? trim((string) $_POST['data_vencimento']) : $dataTransacao;
        $categoriaId = (int) ($_POST['categoria_id'] ?? 1);
        $meioPagamentoId = (int) ($_POST['meio_pagamento_id'] ?? 1);
        $contaId = !empty($_POST['conta_id']) ? (int) $_POST['conta_id'] : null;
        $status = trim((string) ($_POST['status'] ?? 'pendente'));
        $observacoes = !empty($_POST['observacoes']) ? trim((string) $_POST['observacoes']) : null;

        if (empty($descricao)) {
            FlashHelper::set('error', 'A descrição do lançamento não pode ficar vazia.');
            header('Location: ' . BASE_URL . '/transacoes');
            exit;
        }

        $valor = FormatHelper::parseCurrency($valorRaw);
        $userId = (int) AuthHelper::id();
        $isAdmin = AuthHelper::isAdmin();

        try {
            $updated = $this->transactionModel->updateTransaction($id, [
                'descricao'         => $descricao,
                'valor'             => $valor,
                'data_transacao'    => $dataTransacao,
                'data_vencimento'   => $dataVencimento,
                'categoria_id'      => $categoriaId,
                'meio_pagamento_id' => $meioPagamentoId,
                'conta_id'          => $contaId,
                'status'            => $status,
                'observacoes'       => $observacoes,
            ], $userId, $isAdmin);

            if ($updated) {
                FlashHelper::set('success', "Lançamento '{$descricao}' atualizado com sucesso!");
            } else {
                FlashHelper::set('error', 'Não foi possível atualizar o lançamento.');
            }
        } catch (Throwable $e) {
            FlashHelper::set('error', $e->getMessage());
        }

        $dt = new DateTimeImmutable($dataVencimento);
        $mes = $dt->format('n');
        $ano = $dt->format('Y');

        header('Location: ' . BASE_URL . "/transacoes?mes={$mes}&ano={$ano}");
        exit;
    }

    /**
     * Alterna rapidamente o status de um lançamento (pendente <-> pago/recebido).
     *
     * @param int|string $id
     * @return void
     */
    public function toggle(int|string $id): void
    {
        AuthHelper::requireLogin();
        $id = (int) $id;

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF inválido.');
            header('Location: ' . BASE_URL . '/transacoes');
            exit;
        }

        $userId = (int) AuthHelper::id();
        $isAdmin = AuthHelper::isAdmin();

        $result = $this->transactionModel->toggleStatus($id, $userId, $isAdmin);

        if ($result['success']) {
            FlashHelper::set('success', $result['message']);
        } else {
            FlashHelper::set('error', $result['message']);
        }

        $redirect = $_POST['redirect_to'] ?? BASE_URL . '/transacoes';
        header('Location: ' . $redirect);
        exit;
    }

    /**
     * Executa a exclusão lógica de um lançamento com controle RBAC.
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
            header('Location: ' . BASE_URL . '/transacoes');
            exit;
        }

        $userId = (int) AuthHelper::id();
        $isAdmin = AuthHelper::isAdmin();

        $result = $this->transactionModel->deleteTransaction($id, $userId, $isAdmin);

        if ($result['success']) {
            FlashHelper::set('success', $result['message']);
        } else {
            FlashHelper::set('error', $result['message']);
        }

        $redirect = $_POST['redirect_to'] ?? BASE_URL . '/transacoes';
        header('Location: ' . $redirect);
        exit;
    }
}
