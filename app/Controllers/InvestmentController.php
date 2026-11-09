<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Controlador de Investimentos (InvestmentController)
 *
 * Posições e movimentos são sempre limitados ao usuário autenticado.
 */
class InvestmentController extends Controller
{
    private Investment $investmentModel;

    public function __construct()
    {
        $this->investmentModel = new Investment();
    }

    public function index(): void
    {
        AuthHelper::requireLogin();

        $mes = isset($_GET['mes']) ? max(1, min(12, (int) $_GET['mes'])) : (int) date('n');
        $ano = isset($_GET['ano']) ? max(2000, min(2100, (int) $_GET['ano'])) : (int) date('Y');

        $filters = [
            'status'      => in_array($_GET['status'] ?? 'ativo', ['ativo', 'encerrado', 'todos'], true) ? ($_GET['status'] ?? 'ativo') : 'ativo',
            'conta_id'    => !empty($_GET['conta_id']) ? (int) $_GET['conta_id'] : null,
            'classe'      => (string) ($_GET['classe'] ?? ''),
            'custodiante' => trim((string) ($_GET['custodiante'] ?? '')),
            'finalidade'  => trim((string) ($_GET['finalidade'] ?? '')),
        ];

        $this->render('investments/index', [
            'title'          => 'Investimentos — Symo',
            'investimentos'  => $this->investmentModel->getFiltered($filters),
            'filters'        => $filters,
            'mes'            => $mes,
            'ano'            => $ano,
            'totalInvestido' => $this->investmentModel->getTotalInvested(),
            'rendimentoMes'  => $this->investmentModel->getMonthlyYield($mes, $ano),
            'contas'         => $this->investmentModel->getEligibleAccounts(),
            'todasContas'    => (new Account())->getAllAccounts(),
        ], 'layouts/main');
    }

    public function show(int|string $id): void
    {
        AuthHelper::requireLogin();
        $id = (int) $id;

        $investimento = $this->investmentModel->find($id);
        if (!$investimento) {
            FlashHelper::set('error', 'Investimento não encontrado.');
            $this->redirect('/investimentos');
        }

        $mes = isset($_GET['mes']) ? max(1, min(12, (int) $_GET['mes'])) : (int) date('n');
        $ano = isset($_GET['ano']) ? max(2000, min(2100, (int) $_GET['ano'])) : (int) date('Y');

        $this->render('investments/show', [
            'title'         => 'Investimento — Symo',
            'investimento'  => $investimento,
            'movimentos'    => $this->investmentModel->getMovements($id),
            'rendimentoMes' => $this->investmentModel->getPositionYield($id, $mes, $ano),
            'mes'           => $mes,
            'ano'           => $ano,
            'contas'        => $this->investmentModel->getEligibleAccounts(),
        ], 'layouts/main');
    }

    public function store(): void
    {
        $this->guardPost('/investimentos');

        try {
            $this->investmentModel->createPosition([
                'nome'               => $_POST['nome'] ?? '',
                'conta_id'           => $_POST['conta_id'] ?? 0,
                'custodiante'        => $_POST['custodiante'] ?? '',
                'classe'             => $_POST['classe'] ?? 'nao_informado',
                'finalidade'         => $_POST['finalidade'] ?? '',
                'saldo_inicial'      => FormatHelper::parseCurrency($_POST['saldo_inicial'] ?? '0'),
                'data_saldo_inicial' => $_POST['data_saldo_inicial'] ?? '',
            ], (int) AuthHelper::id());
            FlashHelper::set('success', 'Investimento cadastrado com sucesso.');
        } catch (InvalidArgumentException $e) {
            FlashHelper::set('error', $e->getMessage());
        } catch (Throwable $e) {
            LoggerHelper::error($e);
            FlashHelper::set('error', 'Não foi possível cadastrar o investimento.');
        }

        $this->redirect('/investimentos');
    }

    public function update(int|string $id): void
    {
        $id = (int) $id;
        $this->guardPost('/investimentos/' . $id);

        try {
            $this->investmentModel->updatePosition($id, [
                'nome'        => $_POST['nome'] ?? '',
                'conta_id'    => $_POST['conta_id'] ?? 0,
                'custodiante' => $_POST['custodiante'] ?? '',
                'classe'      => $_POST['classe'] ?? 'nao_informado',
                'finalidade'  => $_POST['finalidade'] ?? '',
            ], (int) AuthHelper::id());
            FlashHelper::set('success', 'Investimento atualizado com sucesso.');
        } catch (InvalidArgumentException $e) {
            FlashHelper::set('error', $e->getMessage());
        } catch (Throwable $e) {
            LoggerHelper::error($e);
            FlashHelper::set('error', 'Não foi possível atualizar o investimento.');
        }

        $this->redirect('/investimentos/' . $id);
    }

    public function reactivate(int|string $id): void
    {
        $id = (int) $id;
        $this->guardPost('/investimentos/' . $id);

        try {
            $this->investmentModel->reactivate($id, (int) AuthHelper::id());
            FlashHelper::set('success', 'Investimento reativado.');
        } catch (InvalidArgumentException $e) {
            FlashHelper::set('error', $e->getMessage());
        }

        $this->redirect('/investimentos/' . $id);
    }

    public function deactivate(int|string $id): void
    {
        $id = (int) $id;
        $this->guardPost('/investimentos/' . $id);

        try {
            $this->investmentModel->deactivate($id, (int) AuthHelper::id());
            FlashHelper::set('success', 'Investimento desativado.');
        } catch (InvalidArgumentException $e) {
            FlashHelper::set('error', $e->getMessage());
        }

        $this->redirect('/investimentos/' . $id);
    }

    public function delete(int|string $id): void
    {
        $id = (int) $id;
        $this->guardPost('/investimentos/' . $id);

        try {
            $result = $this->investmentModel->deletePosition($id, (int) AuthHelper::id());
            FlashHelper::set('success', $result['message']);
            $this->redirect('/investimentos');
        } catch (InvalidArgumentException $e) {
            FlashHelper::set('error', $e->getMessage());
            $this->redirect('/investimentos/' . $id);
        } catch (Throwable $e) {
            LoggerHelper::error($e);
            FlashHelper::set('error', 'Não foi possível excluir o investimento.');
            $this->redirect('/investimentos/' . $id);
        }
    }

    public function storeMovement(int|string $id): void
    {
        $id = (int) $id;
        $this->guardPost('/investimentos/' . $id);

        try {
            $this->investmentModel->addMovement($id, [
                'tipo'           => $_POST['tipo'] ?? '',
                'valor'          => FormatHelper::parseCurrency($_POST['valor'] ?? '0'),
                'novo_total'     => FormatHelper::parseCurrency($_POST['novo_total'] ?? '0'),
                'data_movimento' => $_POST['data_movimento'] ?? '',
                'observacoes'    => $_POST['observacoes'] ?? '',
            ], (int) AuthHelper::id());
            FlashHelper::set('success', 'Movimento registrado com sucesso.');
        } catch (InvalidArgumentException $e) {
            FlashHelper::set('error', $e->getMessage());
        } catch (Throwable $e) {
            LoggerHelper::error($e);
            FlashHelper::set('error', 'Não foi possível registrar o movimento.');
        }

        $this->redirect('/investimentos/' . $id);
    }

    public function updateMovement(int|string $id): void
    {
        $id = (int) $id;
        $this->guardPost('/investimentos');
        $investimentoId = null;

        try {
            $investimentoId = $this->investmentModel->updateMovement($id, [
                'valor'          => FormatHelper::parseCurrency($_POST['valor'] ?? '0'),
                'data_movimento' => $_POST['data_movimento'] ?? '',
                'observacoes'    => $_POST['observacoes'] ?? '',
            ], (int) AuthHelper::id());
            FlashHelper::set('success', 'Movimento atualizado com sucesso.');
        } catch (InvalidArgumentException $e) {
            FlashHelper::set('error', $e->getMessage());
        } catch (Throwable $e) {
            LoggerHelper::error($e);
            FlashHelper::set('error', 'Não foi possível atualizar o movimento.');
        }

        $this->redirect($investimentoId !== null ? '/investimentos/' . $investimentoId : '/investimentos');
    }

    public function deleteMovement(int|string $id): void
    {
        $id = (int) $id;
        $this->guardPost('/investimentos');
        $investimentoId = null;

        try {
            $investimentoId = $this->investmentModel->deleteMovement($id, (int) AuthHelper::id());
            FlashHelper::set('success', 'Movimento excluído e saldos recalculados.');
        } catch (InvalidArgumentException $e) {
            FlashHelper::set('error', $e->getMessage());
        } catch (Throwable $e) {
            LoggerHelper::error($e);
            FlashHelper::set('error', 'Não foi possível excluir o movimento.');
        }

        $this->redirect($investimentoId !== null ? '/investimentos/' . $investimentoId : '/investimentos');
    }

    private function guardPost(string $fallback): void
    {
        AuthHelper::requireLogin();

        if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
            FlashHelper::set('error', 'Token de validação CSRF expirado ou inválido.');
            $this->redirect($fallback);
        }
    }
}
