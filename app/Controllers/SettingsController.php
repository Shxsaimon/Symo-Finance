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
        AuthHelper::requireRole('admin');

        $settings = $this->settingModel->getAll();
        $migrationsStatus = $this->settingModel->checkMigrationsStatus();

        $this->render('settings/index', [
            'title'            => 'Configurações do Sistema — Symo',
            'settings'         => $settings,
            'migrationsStatus' => $migrationsStatus,
        ], 'layouts/main');
    }

    /**
     * Atualiza as preferências gerais do sistema.
     *
     * @return void
     */
    public function update(): void
    {
        AuthHelper::requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/configuracoes');
        }

        if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
            FlashHelper::set('error', 'Token de segurança expirado.');
            $this->redirect('/configuracoes');
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

        $currentUserId = (int) AuthHelper::id();
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
        AuthHelper::requireRole('admin');

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
        AuthHelper::requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/configuracoes');
        }

        if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
            FlashHelper::set('error', 'Token de segurança expirado.');
            $this->redirect('/configuracoes');
        }

        try {
            $pdo = Database::getConnection();
            $migrationsPath = ROOT_PATH . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
            $files = glob($migrationsPath . DIRECTORY_SEPARATOR . '*.php');
            if ($files === false) {
                $files = [];
            }

            // Garante tabela de migrações
            $pdo->exec("CREATE TABLE IF NOT EXISTS `migracoes` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `migracao` VARCHAR(255) NOT NULL UNIQUE,
                `lote` INT UNSIGNED NOT NULL,
                `executado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $stmt = $pdo->query("SELECT migracao FROM `migracoes`");
            $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $stmtLote = $pdo->query("SELECT MAX(lote) as max_lote FROM `migracoes`");
            $resLote = $stmtLote->fetch();
            $novoLote = ((int) ($resLote['max_lote'] ?? 0)) + 1;

            $executadasCount = 0;
            sort($files);

            foreach ($files as $file) {
                $versao = pathinfo($file, PATHINFO_FILENAME);
                if (!in_array($versao, $executed, true)) {
                    $migrationObj = require $file;
                    if (is_object($migrationObj) && method_exists($migrationObj, 'up')) {
                        $migrationObj->up($pdo);
                        $ins = $pdo->prepare("INSERT INTO `migracoes` (migracao, lote, executado_em) VALUES (:migracao, :lote, NOW())");
                        $ins->execute(['migracao' => $versao, 'lote' => $novoLote]);
                        $executadasCount++;
                    }
                }
            }

            if ($executadasCount > 0) {
                FlashHelper::set('success', "{$executadasCount} migração(ões) executada(s) com sucesso.");
            } else {
                FlashHelper::set('info', 'O banco de dados já está totalmente atualizado.');
            }
        } catch (Throwable $e) {
            LoggerHelper::error($e, ['context' => 'SettingsController::runMigrations']);
            FlashHelper::set('error', 'Erro ao executar migrações: ' . $e->getMessage());
        }

        $this->redirect('/configuracoes');
    }
}
