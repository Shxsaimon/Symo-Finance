<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Controlador de Compartilhamento e Notificações de Convites (ShareController)
 *
 * Gerencia o envio de convites para contas bancárias e investimentos,
 * aceite/recusa pelo Modal de Notificações, alteração de permissões,
 * revogação pelo proprietário, saída voluntária pelo participante e
 * preferência individual de exibição no Dashboard.
 */
class ShareController extends Controller
{
    private Share $shareModel;

    public function __construct()
    {
        $this->shareModel = new Share();
    }

    /**
     * Envia um convite de compartilhamento para um e-mail cadastrado no Symo.
     */
    public function invite(): void
    {
        $this->guardPost('/contas');

        $recursoTipo = trim((string) ($_POST['recurso_tipo'] ?? 'conta'));
        $recursoId = (int) ($_POST['recurso_id'] ?? 0);
        $email = trim((string) ($_POST['email'] ?? ''));
        $permissao = trim((string) ($_POST['permissao'] ?? 'visualizar'));
        $redirect = $this->resolveRedirect($recursoTipo, $recursoId);

        $result = $this->shareModel->invite(
            $recursoTipo,
            $recursoId,
            $email,
            $permissao,
            (int) AuthHelper::id()
        );

        FlashHelper::set($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect($redirect);
    }

    /**
     * Aceita um convite pendente a partir do Modal de Notificações.
     */
    public function accept(int|string $id): void
    {
        $this->guardPost('/dashboard');
        $shareId = (int) $id;

        $result = $this->shareModel->acceptInvite($shareId, (int) AuthHelper::id());
        FlashHelper::set($result['success'] ? 'success' : 'error', $result['message']);

        $redirect = trim((string) ($_POST['redirect_to'] ?? '/dashboard'));
        $this->redirect($redirect, '/dashboard');
    }

    /**
     * Recusa um convite pendente a partir do Modal de Notificações.
     */
    public function decline(int|string $id): void
    {
        $this->guardPost('/dashboard');
        $shareId = (int) $id;

        $result = $this->shareModel->declineInvite($shareId, (int) AuthHelper::id());
        FlashHelper::set($result['success'] ? 'info' : 'error', $result['message']);

        $redirect = trim((string) ($_POST['redirect_to'] ?? '/dashboard'));
        $this->redirect($redirect, '/dashboard');
    }

    /**
     * Atualiza o nível de permissão de um participante (proprietário).
     */
    public function updatePermission(int|string $id): void
    {
        $this->guardPost('/contas');
        $shareId = (int) $id;
        $permissao = trim((string) ($_POST['permissao'] ?? 'visualizar'));
        $recursoTipo = trim((string) ($_POST['recurso_tipo'] ?? 'conta'));
        $recursoId = (int) ($_POST['recurso_id'] ?? 0);

        $result = $this->shareModel->updatePermission($shareId, $permissao, (int) AuthHelper::id());
        FlashHelper::set($result['success'] ? 'success' : 'error', $result['message']);

        $this->redirect($this->resolveRedirect($recursoTipo, $recursoId));
    }

    /**
     * Revoga o acesso de um participante (proprietário).
     */
    public function revoke(int|string $id): void
    {
        $this->guardPost('/contas');
        $shareId = (int) $id;
        $recursoTipo = trim((string) ($_POST['recurso_tipo'] ?? 'conta'));
        $recursoId = (int) ($_POST['recurso_id'] ?? 0);

        $result = $this->shareModel->revoke($shareId, (int) AuthHelper::id());
        FlashHelper::set($result['success'] ? 'info' : 'error', $result['message']);

        $this->redirect($this->resolveRedirect($recursoTipo, $recursoId));
    }

    /**
     * Sai voluntariamente de um compartilhamento (participante).
     */
    public function leave(int|string $id): void
    {
        $this->guardPost('/contas');
        $shareId = (int) $id;
        $recursoTipo = trim((string) ($_POST['recurso_tipo'] ?? 'conta'));

        $result = $this->shareModel->leave($shareId, (int) AuthHelper::id());
        FlashHelper::set($result['success'] ? 'info' : 'error', $result['message']);

        $this->redirect($recursoTipo === 'investimento' ? '/investimentos' : '/contas');
    }

    /**
     * Alterna a preferência individual do participante de exibir/ocultar o item no Dashboard.
     */
    public function updateDashboardVisibility(int|string $id): void
    {
        $this->guardPost('/contas');
        $shareId = (int) $id;
        $exibir = isset($_POST['exibir_dashboard']) && $_POST['exibir_dashboard'] === '1';
        $recursoTipo = trim((string) ($_POST['recurso_tipo'] ?? 'conta'));
        $recursoId = (int) ($_POST['recurso_id'] ?? 0);

        $result = $this->shareModel->updateParticipantDashboardVisibility(
            $shareId,
            $exibir,
            (int) AuthHelper::id()
        );
        FlashHelper::set($result['success'] ? 'success' : 'error', $result['message']);

        $this->redirect($this->resolveRedirect($recursoTipo, $recursoId));
    }

    private function resolveRedirect(string $recursoTipo, int $recursoId): string
    {
        $custom = trim((string) ($_POST['redirect_to'] ?? ''));
        if ($custom !== '') {
            return $custom;
        }
        if ($recursoTipo === 'investimento' && $recursoId > 0) {
            return '/investimentos/' . $recursoId;
        }
        return $recursoTipo === 'investimento' ? '/investimentos' : '/contas';
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
