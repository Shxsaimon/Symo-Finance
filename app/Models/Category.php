<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Model de Categorias de Receitas e Despesas (Category)
 *
 * Gerencia a árvore de categorias financeiras, ícones visuais, cores,
 * validação de uso em transações e auditoria.
 */
class Category extends Model
{
    protected string $tabela = 'categorias';
    protected string $chavePrimaria = 'id';

    /**
     * Retorna categorias filtradas pelo tipo ('receita' ou 'despesa').
     *
     * @param string $tipo 'receita' ou 'despesa'
     * @param bool $apenasAtivas Se true, retorna apenas categorias com status 'ativa'
     * @return array<int, array<string, mixed>>
     */
    public function getByType(string $tipo, bool $apenasAtivas = true): array
    {
        $sql = "SELECT * FROM `categorias` 
            WHERE usuario_id = :usuario_id AND tipo = :tipo AND excluido_em IS NULL";

        if ($apenasAtivas) {
            $sql .= " AND status = 'ativa'";
        }

        $sql .= " ORDER BY nome ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retorna as categorias ativas agrupadas por usuario_id (proprietário) para alimentar
     * dinamicamente os modais de lançamento ao selecionar contas próprias ou compartilhadas.
     *
     * @param array<int, int> $ownerIds
     * @return array<int, array{receita: array<int, array<string, mixed>>, despesa: array<int, array<string, mixed>>}>
     */
    public function getActiveCategoriesByOwners(array $ownerIds): array
    {
        $ownerIds = array_values(array_unique(array_filter(array_map('intval', $ownerIds), fn ($id) => $id > 0)));
        if (empty($ownerIds)) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach ($ownerIds as $idx => $uid) {
            $ph = ":uid_{$idx}";
            $placeholders[] = $ph;
            $params[$ph] = $uid;
        }
        $inList = implode(', ', $placeholders);

        $sql = "SELECT id, usuario_id, nome, tipo, icone, cor
                FROM `categorias`
                WHERE usuario_id IN ({$inList})
                  AND status = 'ativa'
                  AND excluido_em IS NULL
                ORDER BY nome ASC";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $grouped = [];
        foreach ($ownerIds as $uid) {
            $grouped[$uid] = ['receita' => [], 'despesa' => []];
        }
        foreach ($rows as $row) {
            $uid = (int) $row['usuario_id'];
            $tipo = $row['tipo'] === 'receita' ? 'receita' : 'despesa';
            $grouped[$uid][$tipo][] = $row;
        }
        return $grouped;
    }

    /**
     * Retorna as categorias para os filtros de Lançamentos e Relatórios:
     * categorias ativas do próprio usuário + categorias utilizadas em lançamentos visíveis de contas compartilhadas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFilterCategoriesForUser(): array
    {
        $userId = (int) AuthHelper::id();

        $sql = "SELECT DISTINCT cat.*,
                       CASE WHEN cat.usuario_id = :uid_flag THEN 1 ELSE 0 END AS eh_propria,
                       u.nome AS proprietario_nome
                FROM `categorias` cat
                INNER JOIN `usuarios` u ON u.id = cat.usuario_id
                WHERE (
                    (cat.usuario_id = :uid_own AND cat.status = 'ativa' AND cat.excluido_em IS NULL)
                    OR EXISTS (
                        SELECT 1
                        FROM `transacoes` t
                        INNER JOIN `compartilhamentos` cp
                            ON cp.recurso_tipo = 'conta'
                           AND cp.recurso_id = t.conta_id
                           AND cp.participante_id = :uid_part
                           AND cp.status = 'ativo'
                        WHERE t.categoria_id = cat.id
                          AND t.excluido_em IS NULL
                          AND EXISTS (
                              SELECT 1
                              FROM `compartilhamento_periodos` p
                              WHERE p.recurso_tipo = 'conta'
                                AND p.recurso_id = t.conta_id
                                AND p.participante_id = :uid_period
                                AND t.criado_em >= p.aceito_em
                                AND (p.encerrado_em IS NULL OR t.criado_em <= p.encerrado_em)
                          )
                    )
                )
                ORDER BY cat.tipo ASC, eh_propria DESC, cat.nome ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid_flag', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_own', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_part', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_period', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retorna todas as categorias ativas e não excluídas, agrupadas logicamente por tipo e ordenadas por nome.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActiveCategories(): array
    {
        $sql = "SELECT * FROM `categorias` 
                WHERE usuario_id = :usuario_id AND status = 'ativa' AND excluido_em IS NULL
                ORDER BY tipo ASC, nome ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Cadastra uma nova categoria.
     *
     * @param array<string, mixed> $data
     * @param int $userId
     * @return int ID da nova categoria
     */
    public function createCategory(array $data, int $userId): int
    {
        $sql = "INSERT INTO `categorias` (usuario_id, nome, tipo, icone, cor, status, criado_em, atualizado_em)
            VALUES (:usuario_id, :nome, :tipo, :icone, :cor, :status, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':nome', trim((string) $data['nome']), PDO::PARAM_STR);
        $stmt->bindValue(':tipo', $data['tipo'] === 'receita' ? 'receita' : 'despesa', PDO::PARAM_STR);
        $stmt->bindValue(':icone', !empty($data['icone']) ? trim((string) $data['icone']) : 'bi-tag', PDO::PARAM_STR);
        $stmt->bindValue(':cor', !empty($data['cor']) ? trim((string) $data['cor']) : '#793CFE', PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'] ?? 'ativa', PDO::PARAM_STR);
        $stmt->execute();

        $novoId = (int) $this->db->lastInsertId();

        $this->logAudit('INSERT', $novoId, null, [
            'nome'   => $data['nome'],
            'tipo'   => $data['tipo'],
            'icone'  => $data['icone'] ?? 'bi-tag',
            'cor'    => $data['cor'] ?? '#793CFE',
            'status' => $data['status'] ?? 'ativa'
        ], $userId);

        return $novoId;
    }

    /**
     * Atualiza os dados de uma categoria existente.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @param int $userId
     * @return bool
     */
    public function updateCategory(int $id, array $data, int $userId): bool
    {
        $antiga = $this->find($id);
        if (!$antiga) {
            return false;
        }

        $sql = "UPDATE `categorias` 
                SET nome = :nome, 
                    tipo = :tipo, 
                    icone = :icone, 
                    cor = :cor, 
                    status = :status, 
                    atualizado_em = NOW() 
                WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':nome', trim((string) $data['nome']), PDO::PARAM_STR);
        $stmt->bindValue(':tipo', $data['tipo'] === 'receita' ? 'receita' : 'despesa', PDO::PARAM_STR);
        $stmt->bindValue(':icone', !empty($data['icone']) ? trim((string) $data['icone']) : $antiga['icone'], PDO::PARAM_STR);
        $stmt->bindValue(':cor', !empty($data['cor']) ? trim((string) $data['cor']) : $antiga['cor'], PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'] ?? $antiga['status'], PDO::PARAM_STR);
        $success = $stmt->execute();

        if ($success) {
            $nova = $this->find($id);
            $this->logAudit('UPDATE', $id, $antiga, $nova, $userId);
        }

        return $success;
    }

    /**
     * Alterna o status da categoria entre 'ativa' e 'inativa'.
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function toggleStatus(int $id, int $userId): bool
    {
        $cat = $this->find($id);
        if (!$cat) {
            return false;
        }

        $novoStatus = ($cat['status'] === 'ativa') ? 'inativa' : 'ativa';

        $sql = "UPDATE `categorias` SET status = :status, atualizado_em = NOW() WHERE id = :id AND usuario_id = :usuario_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':status', $novoStatus, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $success = $stmt->execute();

        if ($success) {
            $this->logAudit('UPDATE_STATUS', $id, ['status' => $cat['status']], ['status' => $novoStatus], $userId);
        }

        return $success;
    }

    /**
     * Verifica se a categoria possui qualquer movimentação vinculada.
     *
     * @param int $id
     * @return bool
     */
    public function hasTransactions(int $id): bool
    {
        $sql = "SELECT COUNT(*) as total FROM `transacoes` WHERE categoria_id = :id AND usuario_id = :usuario_id AND excluido_em IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch();
        return ((int) ($res['total'] ?? 0)) > 0;
    }

    /**
     * Realiza a exclusão lógica da categoria se não estiver vinculada a transações.
     *
     * @param int $id
     * @param int $userId
     * @return array{success: bool, message: string}
     */
    public function deleteCategory(int $id, int $userId): array
    {
        $cat = $this->find($id);
        if (!$cat) {
            return ['success' => false, 'message' => 'Categoria não encontrada.'];
        }

        if ($this->hasTransactions($id)) {
            return [
                'success' => false,
                'message' => 'Esta categoria possui transações vinculadas e não pode ser excluída. Sugerimos inativá-la.'
            ];
        }

        $deleted = $this->softDelete($id, $userId);
        if ($deleted) {
            $this->logAudit('SOFT_DELETE', $id, $cat, ['excluido_em' => date('Y-m-d H:i:s'), 'excluido_por' => $userId], $userId);
            return ['success' => true, 'message' => 'Categoria excluída com sucesso.'];
        }

        return ['success' => false, 'message' => 'Não foi possível excluir a categoria.'];
    }
}
