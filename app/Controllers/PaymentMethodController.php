<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Controlador de Meios de Pagamento (PaymentMethodController)
 *
 * Apresenta a disponibilidade pessoal e restringe a manutenção global ao dev.
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

        $meios = $this->paymentMethodModel->getForUser((int) AuthHelper::id());

        $this->render('payment-methods/index', [
            'title'   => 'Meios de Pagamento — Symo',
            'meios'   => $meios,
            'isDev'   => AuthHelper::isDev(),
        ], 'layouts/main');
    }

    /**
    * Processa a inclusão de uma opção global (Apenas dev).
     *
     * @return void
     */
    public function store(): void
    {
        AuthHelper::requireDev();

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF expirado ou inválido.');
            header('Location: ' . BASE_URL . '/meios-pagamento');
            exit;
        }

        $nome = trim((string) ($_POST['nome'] ?? ''));
        $ehCredito = !empty($_POST['eh_credito']) ? 1 : 0;

        if ($nome === '' || mb_strlen($nome) > 80) {
            FlashHelper::set('error', 'Informe um nome de até 80 caracteres.');
            header('Location: ' . BASE_URL . '/meios-pagamento');
            exit;
        }

        $userId = (int) AuthHelper::id();
        try {
            $this->paymentMethodModel->createPaymentMethod([
                'nome'       => $nome,
                'eh_credito' => $ehCredito,
            ], $userId);
        } catch (InvalidArgumentException $exception) {
            FlashHelper::set('warning', $exception->getMessage());
            header('Location: ' . BASE_URL . '/meios-pagamento');
            exit;
        }

        FlashHelper::set('success', "Meio de pagamento '{$nome}' cadastrado com sucesso!");
        header('Location: ' . BASE_URL . '/meios-pagamento');
        exit;
    }

    /**
    * Processa a atualização de uma opção global (Apenas dev).
     *
     * @param int $id
     * @return void
     */
    public function update(int|string $id): void
    {
        AuthHelper::requireDev();
        $id = (int) $id;

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfHelper::validateToken($token)) {
            FlashHelper::set('error', 'Token de validação CSRF inválido.');
            header('Location: ' . BASE_URL . '/meios-pagamento');
            exit;
        }

        $nome = trim((string) ($_POST['nome'] ?? ''));
        $ehCredito = !empty($_POST['eh_credito']) ? 1 : 0;

        if ($nome === '' || mb_strlen($nome) > 80) {
            FlashHelper::set('error', 'Informe um nome de até 80 caracteres.');
            header('Location: ' . BASE_URL . '/meios-pagamento');
            exit;
        }

        $userId = (int) AuthHelper::id();
        try {
            $updated = $this->paymentMethodModel->updatePaymentMethod($id, [
                'nome'       => $nome,
                'eh_credito' => $ehCredito,
            ], $userId);
        } catch (InvalidArgumentException $exception) {
            FlashHelper::set('warning', $exception->getMessage());
            header('Location: ' . BASE_URL . '/meios-pagamento');
            exit;
        }

        if ($updated) {
            FlashHelper::set('success', "Meio de pagamento '{$nome}' atualizado com sucesso!");
        } else {
            FlashHelper::set('error', 'Meio de pagamento não encontrado ou não pôde ser atualizado.');
        }

        header('Location: ' . BASE_URL . '/meios-pagamento');
        exit;
    }

    /**
    * Alterna a disponibilidade individual do usuário autenticado.
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
            header('Location: ' . BASE_URL . '/meios-pagamento');
            exit;
        }

        $userId = (int) AuthHelper::id();
        if ($this->paymentMethodModel->toggleStatus($id, $userId)) {
            FlashHelper::set('info', 'Disponibilidade alterada apenas para sua conta.');
        } else {
            FlashHelper::set('warning', 'Esta opção foi arquivada globalmente e não pode ser ativada.');
        }
        header('Location: ' . BASE_URL . '/meios-pagamento');
        exit;
    }

    /**
    * Arquiva globalmente uma opção sem remover vínculos históricos (Apenas dev).
     *
     * @param int|string $id
     * @return void
     */
    public function delete(int|string $id): void
    {
        AuthHelper::requireDev();
        $id = (int) $id;

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
