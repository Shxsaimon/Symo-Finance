<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Controlador de Meios de Pagamento (PaymentMethodController)
 *
 * Gerencia as formas de liquidação financeiras com isolamento RBAC:
 * - Operador: consulta de meios de pagamento disponíveis.
 * - Administrador: cadastro, alteração, inativação e exclusão lógica.
 */
class PaymentMethodController extends Controller
{
    private PaymentMethod $paymentMethodModel;

    public function __construct()
    {
        $this->paymentMethodModel = new PaymentMethod();
    }

    /**
     * Exibe a listagem de meios de pagamento cadastrados.
     *
     * @return void
     */
    public function index(): void
    {
        AuthHelper::requireLogin();

        $meios = $this->paymentMethodModel->all('nome ASC');

        $this->render('payment-methods/index', [
            'title'   => 'Meios de Pagamento — Symo',
            'meios'   => $meios,
            'isAdmin' => AuthHelper::isAdmin(),
        ], 'layouts/main');
    }

    /**
     * Processa a inclusão de um novo meio de pagamento (Apenas Administrador).
     *
     * @return void
     */
    public function store(): void
    {
        AuthHelper::requireRole('admin');

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF expirado ou inválido.');
            header('Location: ' . BASE_URL . '/meios-pagamento');
            exit;
        }

        $nome = trim((string) ($_POST['nome'] ?? ''));
        $ehCredito = !empty($_POST['eh_credito']) ? 1 : 0;
        $status = trim((string) ($_POST['status'] ?? 'ativo'));

        if (empty($nome)) {
            FlashHelper::set('error', 'O nome do meio de pagamento é obrigatório.');
            header('Location: ' . BASE_URL . '/meios-pagamento');
            exit;
        }

        $userId = (int) AuthHelper::id();
        $this->paymentMethodModel->createPaymentMethod([
            'nome'       => $nome,
            'eh_credito' => $ehCredito,
            'status'     => $status,
        ], $userId);

        FlashHelper::set('success', "Meio de pagamento '{$nome}' cadastrado com sucesso!");
        header('Location: ' . BASE_URL . '/meios-pagamento');
        exit;
    }

    /**
     * Processa a atualização de um meio de pagamento (Apenas Administrador).
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
            header('Location: ' . BASE_URL . '/meios-pagamento');
            exit;
        }

        $nome = trim((string) ($_POST['nome'] ?? ''));
        $ehCredito = !empty($_POST['eh_credito']) ? 1 : 0;
        $status = trim((string) ($_POST['status'] ?? 'ativo'));

        if (empty($nome)) {
            FlashHelper::set('error', 'O nome não pode ser vazio.');
            header('Location: ' . BASE_URL . '/meios-pagamento');
            exit;
        }

        $userId = (int) AuthHelper::id();
        $updated = $this->paymentMethodModel->updatePaymentMethod($id, [
            'nome'       => $nome,
            'eh_credito' => $ehCredito,
            'status'     => $status,
        ], $userId);

        if ($updated) {
            FlashHelper::set('success', "Meio de pagamento '{$nome}' atualizado com sucesso!");
        } else {
            FlashHelper::set('error', 'Meio de pagamento não encontrado ou não pôde ser atualizado.');
        }

        header('Location: ' . BASE_URL . '/meios-pagamento');
        exit;
    }

    /**
     * Alterna o status entre ativo/inativo (Apenas Administrador).
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
            header('Location: ' . BASE_URL . '/meios-pagamento');
            exit;
        }

        $userId = (int) AuthHelper::id();
        $this->paymentMethodModel->toggleStatus($id, $userId);

        FlashHelper::set('info', 'Status do meio de pagamento alterado com sucesso.');
        header('Location: ' . BASE_URL . '/meios-pagamento');
        exit;
    }

    /**
     * Executa a exclusão lógica se não houver lançamentos associados (Apenas Administrador).
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
            header('Location: ' . BASE_URL . '/meios-pagamento');
            exit;
        }

        $userId = (int) AuthHelper::id();
        $result = $this->paymentMethodModel->deletePaymentMethod($id, $userId);

        if ($result['success']) {
            FlashHelper::set('success', $result['message']);
        } else {
            FlashHelper::set('warning', $result['message']);
        }

        header('Location: ' . BASE_URL . '/meios-pagamento');
        exit;
    }
}
