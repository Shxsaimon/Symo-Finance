<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Controlador de Configurações Globais (SettingsController)
 *
 * Módulo exclusivo de Administradores. Permite parametrizar moeda, nome do sistema,
 * separadores numéricos, upload de logotipo da empresa e checagem de migrações.
 */
class SettingsController extends Controller
{
    private Setting $settingModel;

    public function __construct()
    {
        $this->settingModel = new Setting();
    }

    /**
     * Exibe o painel de configurações do sistema.
     *
     * @return void
     */
    public function index(): void
    {
        AuthHelper::requireLogin();

        $isDev = AuthHelper::isDev();
        $settings = $isDev ? $this->settingModel->getAll() : $this->settingModel->getUserPreferences((int) AuthHelper::id());
        $migrationsStatus = $isDev ? $this->settingModel->checkMigrationsStatus() : [];
        $githubExecution = $isDev ? GitHubActionsHelper::latestRun() : [];

        $this->render('settings/index', [
            'title'            => 'Configurações do Sistema — Symo',
            'settings'         => $settings,
            'migrationsStatus' => $migrationsStatus,
            'githubExecution'  => $githubExecution,
            'isDev'            => $isDev,
        ], 'layouts/main');
    }

    /**
     * Atualiza as preferências gerais do sistema.
     *
     * @return void
     */
    public function update(): void
    {
        AuthHelper::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/configuracoes');
        }

        if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
            FlashHelper::set('error', 'Token de segurança expirado.');
            $this->redirect('/configuracoes');
        }

        $currentUserId = (int) AuthHelper::id();
        if (!AuthHelper::isDev()) {
            $this->settingModel->updateUserPreferences([
                'simbolo_moeda' => trim((string) ($_POST['simbolo_moeda'] ?? 'R$')),
                'separador_decimal' => trim((string) ($_POST['separador_decimal'] ?? ',')),
                'separador_milhar' => trim((string) ($_POST['separador_milhar'] ?? '.')),
                'registros_por_pagina' => max(10, min(100, (int) ($_POST['registros_por_pagina'] ?? 25))),
            ], $currentUserId);
            FlashHelper::set('success', 'Preferências pessoais salvas com sucesso.');
            $this->redirect('/configuracoes');
            return;
        }

        $nomeApp = trim((string) ($_POST['nome_aplicacao'] ?? 'Symo'));
        $simboloMoeda = trim((string) ($_POST['simbolo_moeda'] ?? 'R$'));
        $separadorDecimal = trim((string) ($_POST['separador_decimal'] ?? ','));
        $separadorMilhar = trim((string) ($_POST['separador_milhar'] ?? '.'));
        $registrosPorPagina = max(10, min(100, (int) ($_POST['registros_por_pagina'] ?? 25)));

        $novasConfiguracoes = [
            'nome_aplicacao'       => !empty($nomeApp) ? $nomeApp : 'Symo',
            'simbolo_moeda'        => !empty($simboloMoeda) ? $simboloMoeda : 'R$',
            'separador_decimal'    => !empty($separadorDecimal) ? $separadorDecimal : ',',
            'separador_milhar'     => !empty($separadorMilhar) ? $separadorMilhar : '.',
            'registros_por_pagina' => $registrosPorPagina,
        ];

        $this->settingModel->updateBatch($novasConfiguracoes, $currentUserId);

        FlashHelper::set('success', 'Configurações globais salvas com sucesso.');
        $this->redirect('/configuracoes');
    }

    /**
     * Realiza o upload do logotipo da aplicação.
     *
     * @return void
     */
    public function uploadLogo(): void
    {
        AuthHelper::requireDev();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/configuracoes');
        }

        if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
            FlashHelper::set('error', 'Token de segurança expirado.');
            $this->redirect('/configuracoes');
        }

        if (empty($_FILES['logotipo']) || ($_FILES['logotipo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            FlashHelper::set('error', 'Selecione um arquivo de imagem para o logotipo.');
            $this->redirect('/configuracoes');
        }

        $logoAtual = $this->settingModel->get('logotipo_aplicacao');
        $uploadResult = UploadHelper::uploadImage($_FILES['logotipo'], 'logos', $logoAtual ? (string)$logoAtual : null);

        if (!$uploadResult['success']) {
            FlashHelper::set('error', $uploadResult['error'] ?? 'Falha no upload do logotipo.');
            $this->redirect('/configuracoes');
        }

        $currentUserId = (int) AuthHelper::id();
        $this->settingModel->set('logotipo_aplicacao', $uploadResult['filename'], 'string', 'Caminho do Logotipo da Aplicação', $currentUserId);

        FlashHelper::set('success', 'Logotipo da aplicação atualizado com sucesso.');
        $this->redirect('/configuracoes');
    }

    /**
     * Executa migrações pendentes no banco de dados.
     *
     * @return void
     */
    public function runMigrations(): void
    {
        AuthHelper::requireDev();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/configuracoes');
        }

        if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
            FlashHelper::set('error', 'Token de segurança expirado.');
            $this->redirect('/configuracoes');
        }

        $action = (string) ($_POST['database_action'] ?? 'status');
        $result = GitHubActionsHelper::dispatch($action);
        if ($result['success']) {
            FlashHelper::set('success', $result['message']);
        } else {
            FlashHelper::set('error', $result['message']);
        }

        $this->redirect('/configuracoes');
    }

    public function githubActionStatus(): void
    {
        AuthHelper::requireDev();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(GitHubActionsHelper::latestRun(), JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function githubActionLog(int|string $id): void
    {
        AuthHelper::requireDev();
        $log = GitHubActionsHelper::jobLog((int) $id);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: private, no-store');

        if ($log === null) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'O log ainda não está disponível ou não pôde ser baixado do GitHub.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            'success' => true,
            'log' => $log,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
