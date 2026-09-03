<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Controlador de Cartões de Crédito e Faturas (CreditCardController)
 *
 * Gerencia os cartões, limites de crédito, ciclos de fatura e quitações com isolamento RBAC.
 */
class CreditCardController extends Controller
{
    private CreditCard $cardModel;
    private CreditCardInvoice $invoiceModel;
    private Account $accountModel;

    public function __construct()
    {
        $this->cardModel = new CreditCard();
        $this->invoiceModel = new CreditCardInvoice();
        $this->accountModel = new Account();
    }

    /**
     * Exibe a listagem visual dos cartões de crédito com indicadores de limite e faturas.
     *
     * @return void
     */
    public function index(): void
    {
        AuthHelper::requireLogin();

        $cartoes = $this->cardModel->getAllWithSummary();
        $contas = $this->accountModel->getActiveAccounts();

        $this->render('credit-cards/index', [
            'title'   => 'Cartões de Crédito — Symo',
            'cartoes' => $cartoes,
            'contas'  => $contas,
            'isAdmin' => AuthHelper::isAdmin(),
        ], 'layouts/main');
    }

    /**
     * Exibe os detalhes de um cartão de crédito específico e seu histórico de faturas.
     *
     * @param int|string $id
     * @return void
     */
    public function show(int|string $id): void
    {
        AuthHelper::requireLogin();
        $id = (int) $id;

        $cartao = $this->cardModel->getCardWithDetails($id);
        if (!$cartao) {
            FlashHelper::set('error', 'Cartão de crédito não encontrado.');
            header('Location: ' . BASE_URL . '/cartoes');
            exit;
        }

        $faturas = $this->invoiceModel->getInvoicesByCard($id);
        $contas = $this->accountModel->getActiveAccounts();

        $this->render('credit-cards/show', [
            'title'   => "Cartão {$cartao['nome']} — Symo",
            'cartao'  => $cartao,
            'faturas' => $faturas,
            'contas'  => $contas,
            'isAdmin' => AuthHelper::isAdmin(),
        ], 'layouts/main');
    }

    /**
     * Exibe os detalhes de uma fatura e seus lançamentos discriminados.
     *
     * @param int|string $id
     * @return void
     */
    public function invoice(int|string $id): void
    {
        AuthHelper::requireLogin();
        $id = (int) $id;

        $fatura = $this->invoiceModel->getInvoiceDetails($id);
        if (!$fatura) {
            FlashHelper::set('error', 'Fatura não encontrada.');
            header('Location: ' . BASE_URL . '/cartoes');
            exit;
        }

        $contas = $this->accountModel->getActiveAccounts();

        $this->render('credit-cards/invoice', [
            'title'   => "Fatura " . sprintf('%02d/%04d', $fatura['mes_referencia'], $fatura['ano_referencia']) . " — {$fatura['cartao_nome']}",
            'fatura'  => $fatura,
            'contas'  => $contas,
            'isAdmin' => AuthHelper::isAdmin(),
        ], 'layouts/main');
    }

    /**
     * Processa o cadastro de um novo cartão de crédito (Apenas Administrador).
     *
     * @return void
     */
    public function store(): void
    {
        AuthHelper::requireRole('admin');

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF expirado ou inválido.');
            header('Location: ' . BASE_URL . '/cartoes');
            exit;
        }

        $nome = trim((string) ($_POST['nome'] ?? ''));
        $limiteRaw = trim((string) ($_POST['limite_credito'] ?? '0,00'));
        $diaFechamento = (int) ($_POST['dia_fechamento'] ?? 1);
        $diaVencimento = (int) ($_POST['dia_vencimento'] ?? 10);
        $contaPadraoId = !empty($_POST['conta_padrao_id']) ? (int) $_POST['conta_padrao_id'] : null;
        $cor = trim((string) ($_POST['cor'] ?? '#3BD7D7'));
        $status = trim((string) ($_POST['status'] ?? 'ativo'));

        if (empty($nome)) {
            FlashHelper::set('error', 'O nome do cartão é obrigatório.');
            header('Location: ' . BASE_URL . '/cartoes');
            exit;
        }

        $limiteCredito = FormatHelper::parseCurrency($limiteRaw);
        $userId = (int) AuthHelper::id();

        $cardId = $this->cardModel->createCard([
            'nome'            => $nome,
            'limite_credito'  => $limiteCredito,
            'dia_fechamento'  => $diaFechamento,
            'dia_vencimento'  => $diaVencimento,
            'conta_padrao_id' => $contaPadraoId,
            'cor'             => $cor,
            'status'          => $status,
        ], $userId);

        // Gera automaticamente a fatura aberta do mês corrente
        $mesAtual = (int) date('n');
        $anoAtual = (int) date('Y');
        $this->invoiceModel->getOrCreateInvoice($cardId, $mesAtual, $anoAtual);

        FlashHelper::set('success', "Cartão '{$nome}' cadastrado com sucesso!");
        header('Location: ' . BASE_URL . '/cartoes');
        exit;
    }

    /**
     * Processa a atualização dos dados cadastrais do cartão (Apenas Administrador).
     *
     * @param int|string $id
     * @return void
     */
    public function update(int|string $id): void
    {
        AuthHelper::requireRole('admin');
        $id = (int) $id;

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF inválido.');
            header('Location: ' . BASE_URL . '/cartoes');
            exit;
        }

        $nome = trim((string) ($_POST['nome'] ?? ''));
        $limiteRaw = trim((string) ($_POST['limite_credito'] ?? '0,00'));
        $diaFechamento = (int) ($_POST['dia_fechamento'] ?? 1);
        $diaVencimento = (int) ($_POST['dia_vencimento'] ?? 10);
        $contaPadraoId = !empty($_POST['conta_padrao_id']) ? (int) $_POST['conta_padrao_id'] : null;
        $cor = trim((string) ($_POST['cor'] ?? '#3BD7D7'));
        $status = trim((string) ($_POST['status'] ?? 'ativo'));

        if (empty($nome)) {
            FlashHelper::set('error', 'O nome do cartão não pode ficar vazio.');
            header('Location: ' . BASE_URL . '/cartoes');
            exit;
        }

        $limiteCredito = FormatHelper::parseCurrency($limiteRaw);
        $userId = (int) AuthHelper::id();

        $updated = $this->cardModel->updateCard($id, [
            'nome'            => $nome,
            'limite_credito'  => $limiteCredito,
            'dia_fechamento'  => $diaFechamento,
            'dia_vencimento'  => $diaVencimento,
            'conta_padrao_id' => $contaPadraoId,
            'cor'             => $cor,
            'status'          => $status,
        ], $userId);

        if ($updated) {
            FlashHelper::set('success', "Cartão '{$nome}' atualizado com sucesso!");
        } else {
            FlashHelper::set('error', 'Cartão não encontrado ou não pôde ser atualizado.');
        }

        header('Location: ' . BASE_URL . '/cartoes');
        exit;
    }

    /**
     * Alterna o status do cartão entre ativo/inativo (Apenas Administrador).
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
            header('Location: ' . BASE_URL . '/cartoes');
            exit;
        }

        $userId = (int) AuthHelper::id();
        $this->cardModel->toggleStatus($id, $userId);

        FlashHelper::set('info', 'Status do cartão alterado com sucesso.');
        header('Location: ' . BASE_URL . '/cartoes');
        exit;
    }

    /**
     * Executa a exclusão lógica do cartão se não houver faturas/despesas pendentes (Apenas Administrador).
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
            header('Location: ' . BASE_URL . '/cartoes');
            exit;
        }

        $userId = (int) AuthHelper::id();
        $result = $this->cardModel->deleteCard($id, $userId);

        if ($result['success']) {
            FlashHelper::set('success', $result['message']);
        } else {
            FlashHelper::set('warning', $result['message']);
        }

        header('Location: ' . BASE_URL . '/cartoes');
        exit;
    }

    /**
     * Fecha manualmente uma fatura aberta.
     *
     * @param int|string $id ID da fatura
     * @return void
     */
    public function closeInvoice(int|string $id): void
    {
        AuthHelper::requireLogin();
        $id = (int) $id;

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF inválido.');
            header('Location: ' . BASE_URL . '/cartoes');
            exit;
        }

        $userId = (int) AuthHelper::id();
        $success = $this->invoiceModel->closeInvoice($id, $userId);

        if ($success) {
            FlashHelper::set('success', 'Fatura fechada com sucesso para conferência e pagamento.');
        } else {
            FlashHelper::set('error', 'Apenas faturas em estado "aberta" podem ser fechadas.');
        }

        $redirect = $_POST['redirect_to'] ?? BASE_URL . '/cartoes';
        header('Location: ' . $redirect);
        exit;
    }

    /**
     * Reabre uma fatura fechada (Apenas Administrador).
     *
     * @param int|string $id ID da fatura
     * @return void
     */
    public function reopenInvoice(int|string $id): void
    {
        AuthHelper::requireRole('admin');
        $id = (int) $id;

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF inválido.');
            header('Location: ' . BASE_URL . '/cartoes');
            exit;
        }

        $userId = (int) AuthHelper::id();
        $success = $this->invoiceModel->reopenInvoice($id, $userId);

        if ($success) {
            FlashHelper::set('info', 'Fatura reaberta com sucesso.');
        } else {
            FlashHelper::set('error', 'Não foi possível reabrir a fatura.');
        }

        $redirect = $_POST['redirect_to'] ?? BASE_URL . '/cartoes';
        header('Location: ' . $redirect);
        exit;
    }

    /**
     * Processa a liquidação/pagamento de uma fatura com débito em conta corrente.
     *
     * @param int|string $id ID da fatura
     * @return void
     */
    public function payInvoice(int|string $id): void
    {
        AuthHelper::requireLogin();
        $id = (int) $id;

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF inválido.');
            header('Location: ' . BASE_URL . '/cartoes');
            exit;
        }

        $contaPagamentoId = (int) ($_POST['conta_pagamento_id'] ?? 0);
        $dataPagamento = !empty($_POST['data_pagamento']) ? $_POST['data_pagamento'] . ' ' . date('H:i:s') : null;

        if ($contaPagamentoId <= 0) {
            FlashHelper::set('error', 'Selecione uma conta bancária válida para efetuar o débito.');
            header('Location: ' . BASE_URL . '/cartoes');
            exit;
        }

        $userId = (int) AuthHelper::id();
        $result = $this->invoiceModel->payInvoice($id, $contaPagamentoId, $dataPagamento, $userId);

        if ($result['success']) {
            FlashHelper::set('success', $result['message']);
        } else {
            FlashHelper::set('error', $result['message']);
        }

        $redirect = $_POST['redirect_to'] ?? BASE_URL . '/cartoes';
        header('Location: ' . $redirect);
        exit;
    }
}
