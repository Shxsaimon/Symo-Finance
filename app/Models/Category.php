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
                WHERE tipo = :tipo AND excluido_em IS NULL";

        if ($apenasAtivas) {
            $sql .= " AND status = 'ativa'";
        }

        $sql .= " ORDER BY nome ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
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
                WHERE status = 'ativa' AND excluido_em IS NULL 
                ORDER BY tipo ASC, nome ASC";
        $stmt = $this->db->query($sql);
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
        $sql = "INSERT INTO `categorias` (nome, tipo, icone, cor, status, criado_em, atualizado_em)
                VALUES (:nome, :tipo, :icone, :cor, :status, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
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
                WHERE id = :id AND excluido_em IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
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

        $sql = "UPDATE `categorias` SET status = :status, atualizado_em = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':status', $novoStatus, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
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
        $sql = "SELECT COUNT(*) as total FROM `transacoes` WHERE categoria_id = :id AND excluido_em IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
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
