<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Controlador do Dashboard Financeiro (DashboardController)
 *
 * Consolida e apresenta os KPIs da aplicação, evolução financeira,
 * distribuição por categorias, limites de cartões e contas a vencer.
 */
class DashboardController extends Controller
{
    private Account $accountModel;
    private Transaction $transactionModel;
    private CreditCard $creditCardModel;

    public function __construct()
    {
        $this->accountModel = new Account();
        $this->transactionModel = new Transaction();
        $this->creditCardModel = new CreditCard();
    }

    /**
     * Exibe o painel panorâmico principal.
     *
     * @return void
     */
    public function index(): void
    {
        AuthHelper::requireLogin();

        $mesAtual = (int) date('n');
        $anoAtual = (int) date('Y');

        $mes = !empty($_GET['mes']) ? max(1, min(12, (int) $_GET['mes'])) : $mesAtual;
        $ano = !empty($_GET['ano']) ? (int) $_GET['ano'] : $anoAtual;

        // 1. Saldo Atual das contas ativas marcadas para inclusão
        $saldoAtual = $this->accountModel->getCurrentBalance();

        // 2. Resumo financeiro do mês (receitas, despesas, balanço líquido)
        $resumoMes = $this->transactionModel->getMonthlySummary($mes, $ano);

        // 3. Evolução financeira dos últimos 6 meses para o gráfico de barras/linhas
        $evolucaoMensal = $this->transactionModel->getMonthlyEvolution($mes, $ano, 6);

        // 4. Distribuição de despesas por categoria para o gráfico Donut
        $despesasCategoria = $this->transactionModel->getExpensesByCategory($mes, $ano);

        // 5. Compromissos pendentes e a vencer nos próximos 7 dias
        $proximosVencimentos = $this->transactionModel->getUpcomingPending(7, 6);

        // 6. Visão consolidada de cartões de crédito e limites
        $cartoes = $this->creditCardModel->getAllWithSummary();

        // 7. Lista de contas bancárias ativas
        $contas = $this->accountModel->getActiveAccounts();
        $contasNoSaldoAtual = $this->accountModel->getCurrentBalanceAccountsCount();

        $this->render('dashboard/index', [
            'title'               => 'Dashboard Financeiro — Symo',
            'user'                => AuthHelper::user(),
            'mes'                 => $mes,
            'ano'                 => $ano,
            'saldoAtual'          => $saldoAtual,
            'resumoMes'           => $resumoMes,
            'evolucaoMensal'      => $evolucaoMensal,
            'despesasCategoria'   => $despesasCategoria,
            'proximosVencimentos' => $proximosVencimentos,
            'cartoes'             => $cartoes,
            'contas'              => $contas,
            'contasNoSaldoAtual'  => $contasNoSaldoAtual,
            'totalInvestido'      => (new Investment())->getTotalInvested(),
        ], 'layouts/main');
    }
}
