<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Model de Meios de Pagamento (PaymentMethod)
 *
 * Gerencia as formas de liquidação (Pix, Dinheiro, Cartão de Débito, Boleto, etc.),
 * checagem de transações associadas e auditoria.
 */
class PaymentMethod extends Model
{
    protected string $tabela = 'meios_pagamento';
    protected string $chavePrimaria = 'id';

    /**
     * Retorna todos os meios de pagamento ativos e não excluídos.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActive(): array
    {
        $sql = "SELECT * FROM `meios_pagamento` 
            WHERE usuario_id = :usuario_id AND status = 'ativo' AND excluido_em IS NULL
                ORDER BY nome ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Alias semântico para getActive().
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActivePaymentMethods(): array
    {
        return $this->getActive();
    }

    /**
     * Cadastra um novo meio de pagamento.
     *
     * @param array<string, mixed> $data
     * @param int $userId
     * @return int ID do novo meio de pagamento
     */
    public function createPaymentMethod(array $data, int $userId): int
    {
        $ehCredito = !empty($data['eh_credito']) ? 1 : 0;

        $sql = "INSERT INTO `meios_pagamento` (usuario_id, nome, eh_credito, status, criado_em, atualizado_em)
            VALUES (:usuario_id, :nome, :eh_credito, :status, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':nome', trim((string) $data['nome']), PDO::PARAM_STR);
        $stmt->bindValue(':eh_credito', $ehCredito, PDO::PARAM_INT);
        $stmt->bindValue(':status', $data['status'] ?? 'ativo', PDO::PARAM_STR);
        $stmt->execute();

        $novoId = (int) $this->db->lastInsertId();

        $this->logAudit('INSERT', $novoId, null, [
            'nome'       => $data['nome'],
            'eh_credito' => $ehCredito,
            'status'     => $data['status'] ?? 'ativo'
        ], $userId);

        return $novoId;
    }

    /**
     * Atualiza um meio de pagamento existente.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @param int $userId
     * @return bool
     */
    public function updatePaymentMethod(int $id, array $data, int $userId): bool
    {
        $antigo = $this->find($id);
        if (!$antigo) {
            return false;
        }

        $ehCredito = isset($data['eh_credito']) ? (!empty($data['eh_credito']) ? 1 : 0) : (int) $antigo['eh_credito'];

        $sql = "UPDATE `meios_pagamento` 
                SET nome = :nome, 
                    eh_credito = :eh_credito, 
                    status = :status, 
                    atualizado_em = NOW() 
                WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':nome', trim((string) $data['nome']), PDO::PARAM_STR);
        $stmt->bindValue(':eh_credito', $ehCredito, PDO::PARAM_INT);
        $stmt->bindValue(':status', $data['status'] ?? $antigo['status'], PDO::PARAM_STR);
        $success = $stmt->execute();

        if ($success) {
            $novo = $this->find($id);
            $this->logAudit('UPDATE', $id, $antigo, $novo, $userId);
        }

        return $success;
    }

    /**
     * Alterna o status entre 'ativo' e 'inativo'.
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function toggleStatus(int $id, int $userId): bool
    {
        $item = $this->find($id);
        if (!$item) {
            return false;
        }

        $novoStatus = ($item['status'] === 'ativo') ? 'inativo' : 'ativo';

        $sql = "UPDATE `meios_pagamento` SET status = :status, atualizado_em = NOW() WHERE id = :id AND usuario_id = :usuario_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':status', $novoStatus, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $success = $stmt->execute();

        if ($success) {
            $this->logAudit('UPDATE_STATUS', $id, ['status' => $item['status']], ['status' => $novoStatus], $userId);
        }

        return $success;
    }

    /**
     * Verifica se o meio de pagamento está associado a lançamentos.
     *
     * @param int $id
     * @return bool
     */
    public function hasTransactions(int $id): bool
    {
        $sql = "SELECT COUNT(*) as total FROM `transacoes` WHERE meio_pagamento_id = :id AND usuario_id = :usuario_id AND excluido_em IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch();
        return ((int) ($res['total'] ?? 0)) > 0;
    }

    /**
     * Realiza a exclusão lógica segura se não estiver vinculado a movimentações.
     *
     * @param int $id
     * @param int $userId
     * @return array{success: bool, message: string}
     */
    public function deletePaymentMethod(int $id, int $userId): array
    {
        $item = $this->find($id);
        if (!$item) {
            return ['success' => false, 'message' => 'Meio de pagamento não encontrado.'];
        }

        if ($this->hasTransactions($id)) {
            return [
                'success' => false,
                'message' => 'Este meio de pagamento está associado a transações e não pode ser excluído. Sugerimos inativá-lo.'
            ];
        }

        $deleted = $this->softDelete($id, $userId);
        if ($deleted) {
            $this->logAudit('SOFT_DELETE', $id, $item, ['excluido_em' => date('Y-m-d H:i:s'), 'excluido_por' => $userId], $userId);
            return ['success' => true, 'message' => 'Meio de pagamento excluído com sucesso.'];
        }

        return ['success' => false, 'message' => 'Não foi possível excluir o meio de pagamento.'];
    }
}
