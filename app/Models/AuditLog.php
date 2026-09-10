<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Model de Auditoria e Rastreabilidade (AuditLog)
 *
 * Consulta a trilha de auditoria (auditoria_logs), consolida eventos de modificação,
 * inserção, soft delete, restauração e expurgo, com suporte a filtros e busca por JSON Diff.
 */
class AuditLog extends Model
{
    protected string $tabela = 'auditoria_logs';
    protected string $chavePrimaria = 'id';

    /**
     * Retorna a listagem de registros de auditoria com paginação e filtros combinados.
     *
     * @param array<string, mixed> $filters
     * @param int $limit
     * @param int $offset
     * @return array<int, array<string, mixed>>
     */
    public function getFiltered(array $filters, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT a.*, u.nome as usuario_nome, u.email as usuario_email, u.perfil as usuario_perfil
                FROM `{$this->tabela}` a
                LEFT JOIN `usuarios` u ON a.usuario_id = u.id
                WHERE 1=1";

        $params = [];
        $this->applyFilters($sql, $params, $filters);

        $sql .= " ORDER BY a.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            if (is_int($v)) {
                $stmt->bindValue($k, $v, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Conta a quantidade total de registros correspondentes aos filtros para paginação.
     *
     * @param array<string, mixed> $filters
     * @return int
     */
    public function countFiltered(array $filters): int
    {
        $sql = "SELECT COUNT(*) as total
                FROM `{$this->tabela}` a
                LEFT JOIN `usuarios` u ON a.usuario_id = u.id
                WHERE 1=1";

        $params = [];
        $this->applyFilters($sql, $params, $filters);

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            if (is_int($v)) {
                $stmt->bindValue($k, $v, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
        }
        $stmt->execute();
        $res = $stmt->fetch();
        return (int) ($res['total'] ?? 0);
    }

    /**
     * Retorna os detalhes completos de um registro de auditoria pelo ID.
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function getDetailsById(int $id): ?array
    {
        $sql = "SELECT a.*, u.nome as usuario_nome, u.email as usuario_email, u.perfil as usuario_perfil, u.avatar as usuario_avatar
                FROM `{$this->tabela}` a
                LEFT JOIN `usuarios` u ON a.usuario_id = u.id
                WHERE a.id = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $res = $stmt->fetch();
        if (!$res) {
            return null;
        }

        $res['valores_antigos_parsed'] = !empty($res['valores_antigos']) ? json_decode($res['valores_antigos'], true) : null;
        $res['valores_novos_parsed']   = !empty($res['valores_novos']) ? json_decode($res['valores_novos'], true) : null;

        return $res;
    }

    /**
     * Retorna a lista de tabelas distintas registradas nos logs de auditoria.
     *
     * @return array<int, string>
     */
    public function getAuditedTables(): array
    {
        $sql = "SELECT DISTINCT tabela FROM `{$this->tabela}` ORDER BY tabela ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return is_array($rows) ? $rows : [];
    }

    /**
     * Retorna indicadores e métricas consolidadas de auditoria.
     *
     * @return array{total_eventos: int, eventos_hoje: int, total_insert: int, total_update: int, total_delete: int, total_restore: int, total_purge: int}
     */
    public function getSummaryMetrics(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_eventos,
                    SUM(CASE WHEN DATE(criado_em) = CURRENT_DATE() THEN 1 ELSE 0 END) as eventos_hoje,
                    SUM(CASE WHEN acao = 'INSERT' THEN 1 ELSE 0 END) as total_insert,
                    SUM(CASE WHEN acao = 'UPDATE' THEN 1 ELSE 0 END) as total_update,
                    SUM(CASE WHEN acao = 'SOFT_DELETE' THEN 1 ELSE 0 END) as total_delete,
                    SUM(CASE WHEN acao = 'RESTORE' THEN 1 ELSE 0 END) as total_restore,
                    SUM(CASE WHEN acao = 'PURGE' THEN 1 ELSE 0 END) as total_purge
                FROM `{$this->tabela}`";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $res = $stmt->fetch();

        return [
            'total_eventos' => (int) ($res['total_eventos'] ?? 0),
            'eventos_hoje'  => (int) ($res['eventos_hoje'] ?? 0),
            'total_insert'  => (int) ($res['total_insert'] ?? 0),
            'total_update'  => (int) ($res['total_update'] ?? 0),
            'total_delete'  => (int) ($res['total_delete'] ?? 0),
            'total_restore' => (int) ($res['total_restore'] ?? 0),
            'total_purge'   => (int) ($res['total_purge'] ?? 0),
        ];
    }

    /**
     * Aplica os filtros de consulta na string SQL e array de parâmetros.
     *
     * @param string $sql
     * @param array<string, mixed> $params
     * @param array<string, mixed> $filters
     * @return void
     */
    private function applyFilters(string &$sql, array &$params, array $filters): void
    {
        if (!empty($filters['tabela'])) {
            $sql .= " AND a.tabela = :tabela";
            $params[':tabela'] = (string) $filters['tabela'];
        }

        if (!empty($filters['acao']) && in_array($filters['acao'], ['INSERT', 'UPDATE', 'SOFT_DELETE', 'RESTORE', 'PURGE'], true)) {
            $sql .= " AND a.acao = :acao";
            $params[':acao'] = (string) $filters['acao'];
        }

        if (!empty($filters['usuario_id'])) {
            $sql .= " AND a.usuario_id = :usuario_id";
            $params[':usuario_id'] = (int) $filters['usuario_id'];
        }

        if (!empty($filters['data_inicio'])) {
            $sql .= " AND DATE(a.criado_em) >= :data_inicio";
            $params[':data_inicio'] = (string) $filters['data_inicio'];
        }

        if (!empty($filters['data_fim'])) {
            $sql .= " AND DATE(a.criado_em) <= :data_fim";
            $params[':data_fim'] = (string) $filters['data_fim'];
        }

        if (!empty($filters['busca'])) {
            $termo = '%' . trim((string) $filters['busca']) . '%';
            $sql .= " AND (a.tabela LIKE :busca_tab OR a.endereco_ip LIKE :busca_ip OR a.valores_antigos LIKE :busca_old OR a.valores_novos LIKE :busca_new)";
            $params[':busca_tab'] = $termo;
            $params[':busca_ip']  = $termo;
            $params[':busca_old'] = $termo;
            $params[':busca_new'] = $termo;
        }
    }
}

