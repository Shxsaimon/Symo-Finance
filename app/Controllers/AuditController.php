<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Controlador da Trilha de Auditoria (AuditController)
 *
 * Módulo exclusivo de Administradores. Exibe logs detalhados de operações,
 * filtros avançados e comparativo estruturado de JSON Diff.
 */
class AuditController extends Controller
{
    private AuditLog $auditLogModel;
    private User $userModel;

    public function __construct()
    {
        $this->auditLogModel = new AuditLog();
        $this->userModel = new User();
    }

    /**
     * Exibe a listagem de registros da trilha de auditoria com paginação e filtros.
     *
     * @return void
     */
    public function index(): void
    {
        AuthHelper::requireDev();

        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $porPagina = 30;
        $offset = ($pagina - 1) * $porPagina;

        $filters = [
            'tabela'      => !empty($_GET['tabela']) ? trim((string) $_GET['tabela']) : null,
            'acao'        => !empty($_GET['acao']) ? trim((string) $_GET['acao']) : null,
            'usuario_id'  => !empty($_GET['usuario_id']) ? (int) $_GET['usuario_id'] : null,
            'data_inicio' => !empty($_GET['data_inicio']) ? trim((string) $_GET['data_inicio']) : null,
            'data_fim'    => !empty($_GET['data_fim']) ? trim((string) $_GET['data_fim']) : null,
            'busca'       => !empty($_GET['busca']) ? trim((string) $_GET['busca']) : null,
        ];

        $logs = $this->auditLogModel->getFiltered($filters, $porPagina, $offset);
        $totalRegistros = $this->auditLogModel->countFiltered($filters);
        $totalPaginas = (int) ceil($totalRegistros / $porPagina);

        $tabelas = $this->auditLogModel->getAuditedTables();
        $usuarios = $this->userModel->getAllUsers();
        $metricas = $this->auditLogModel->getSummaryMetrics();

        $this->render('audit/index', [
            'title'          => 'Trilha de Auditoria — Symo',
            'logs'           => $logs,
            'filters'        => $filters,
            'tabelas'        => $tabelas,
            'usuarios'       => $usuarios,
            'metricas'       => $metricas,
            'paginaAtual'    => $pagina,
            'totalPaginas'   => $totalPaginas,
            'totalRegistros' => $totalRegistros,
        ], 'layouts/main');
    }

    /**
     * Retorna os detalhes e o JSON Diff de um registro de auditoria via resposta JSON.
     *
     * @param int|string $id
     * @return void
     */
    public function detail(int|string $id): void
    {
        AuthHelper::requireDev();
        $logId = (int) $id;

        $registro = $this->auditLogModel->getDetailsById($logId);
        if (!$registro) {
            $this->json(['success' => false, 'message' => 'Registro de auditoria não encontrado.'], 404);
            return;
        }

        $this->json([
            'success' => true,
            'data'    => $registro,
        ]);
    }
}

