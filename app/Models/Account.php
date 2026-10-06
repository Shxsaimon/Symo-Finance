<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Model de Contas Bancárias e Carteiras (Account)
 *
 * Gerencia as contas financeiras do ambiente compartilhado,
 * saldos, integridade referencial com transações e trilha de auditoria.
 */
class Account extends Model
{
    protected string $tabela = 'contas';
    protected string $chavePrimaria = 'id';

    /**
     * Retorna todas as contas ativas e não excluídas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActiveAccounts(): array
    {
        $sql = "SELECT * FROM `contas` 
                WHERE usuario_id = :usuario_id AND status = 'ativa' AND excluido_em IS NULL
                ORDER BY nome ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retorna as contas do usuário com o indicador de inclusão no Saldo Atual.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllAccounts(): array
    {
        $sql = "SELECT *,
                    CASE
                        WHEN tipo <> 'investimento' AND incluir_saldo_atual = TRUE THEN 1
                        ELSE 0
                    END AS considerar_no_saldo_atual
                FROM `contas`
                WHERE usuario_id = :usuario_id AND excluido_em IS NULL
                ORDER BY nome ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Calcula o Saldo Atual das contas ativas marcadas para inclusão.
     *
     * @return float
     */
    public function getCurrentBalance(): float
    {
        $sql = "SELECT COALESCE(SUM(saldo_atual), 0) AS saldo_atual_total
                FROM `contas` 
                WHERE usuario_id = :usuario_id
                    AND status = 'ativa'
                    AND excluido_em IS NULL
                    AND tipo <> 'investimento'
                    AND incluir_saldo_atual = TRUE";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        return (float) ($result['saldo_atual_total'] ?? 0.0);
    }

    /**
     * Calcula o saldo das contas ativas do tipo investimento.
     */
    public function getInvestmentBalance(): float
    {
        $sql = "SELECT COALESCE(SUM(saldo_atual), 0) AS saldo_investimentos
                FROM `contas`
                WHERE usuario_id = :usuario_id
                    AND status = 'ativa'
                    AND excluido_em IS NULL
                    AND tipo = 'investimento'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        return (float) ($result['saldo_investimentos'] ?? 0.0);
    }

    /**
     * Conta as contas ativas que entram no Saldo Atual.
     */
    public function getCurrentBalanceAccountsCount(): int
    {
        $sql = "SELECT COUNT(*)
                FROM `contas`
                WHERE usuario_id = :usuario_id
                    AND status = 'ativa'
                    AND excluido_em IS NULL
                    AND tipo <> 'investimento'
                    AND incluir_saldo_atual = TRUE";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Cadastra uma nova conta bancária ou carteira, inicializando o saldo atual igual ao saldo inicial.
     *
     * @param array<string, mixed> $data
     * @param int $userId ID do usuário autor da ação
     * @return int ID da nova conta criada
     */
    public function createAccount(array $data, int $userId): int
    {
        $saldoInicial = (float) ($data['saldo_inicial'] ?? 0.0);
        $tipo = (string) ($data['tipo'] ?? 'corrente');
        $incluirSaldoAtual = $tipo !== 'investimento' && (bool) ($data['incluir_saldo_atual'] ?? true);

        $sql = "INSERT INTO `contas` (usuario_id, nome, instituicao, tipo, saldo_inicial, saldo_atual, incluir_saldo_atual, cor, status, criado_em, atualizado_em)
            VALUES (:usuario_id, :nome, :instituicao, :tipo, :saldo_inicial, :saldo_atual, :incluir_saldo_atual, :cor, :status, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':nome', trim((string) $data['nome']), PDO::PARAM_STR);
        $stmt->bindValue(':instituicao', !empty($data['instituicao']) ? trim((string) $data['instituicao']) : null, !empty($data['instituicao']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
        $stmt->bindValue(':saldo_inicial', $saldoInicial);
        $stmt->bindValue(':saldo_atual', $saldoInicial); // Saldo atual inicia com o mesmo valor do saldo inicial
        $stmt->bindValue(':incluir_saldo_atual', $incluirSaldoAtual, PDO::PARAM_BOOL);
        $stmt->bindValue(':cor', !empty($data['cor']) ? trim((string) $data['cor']) : '#6F02FF', PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'] ?? 'ativa', PDO::PARAM_STR);
        $stmt->execute();

        $novoId = (int) $this->db->lastInsertId();

        $this->logAudit('INSERT', $novoId, null, [
            'nome'          => $data['nome'],
            'instituicao'   => $data['instituicao'] ?? null,
            'tipo'          => $tipo,
            'saldo_inicial' => $saldoInicial,
            'saldo_atual'   => $saldoInicial,
            'incluir_saldo_atual' => $incluirSaldoAtual,
            'status'        => $data['status'] ?? 'ativa'
        ], $userId);

        return $novoId;
    }

    /**
     * Atualiza os dados cadastrais de uma conta existente.
     * Não altera o saldo_atual diretamente para preservar a integridade contábil.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @param int $userId
     * @return bool
     */
    public function updateAccount(int $id, array $data, int $userId): bool
    {
        $contaAntiga = $this->find($id);
        if (!$contaAntiga) {
            return false;
        }

        $tipo = (string) ($data['tipo'] ?? $contaAntiga['tipo']);
        $incluirSaldoAtual = $tipo !== 'investimento'
            && (bool) ($data['incluir_saldo_atual'] ?? $contaAntiga['incluir_saldo_atual']);

        $sql = "UPDATE `contas` 
                SET nome = :nome, 
                    instituicao = :instituicao, 
                    tipo = :tipo, 
                    incluir_saldo_atual = :incluir_saldo_atual,
                    cor = :cor, 
                    status = :status, 
                    atualizado_em = NOW() 
                WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':nome', trim((string) $data['nome']), PDO::PARAM_STR);
        $stmt->bindValue(':instituicao', !empty($data['instituicao']) ? trim((string) $data['instituicao']) : null, !empty($data['instituicao']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
        $stmt->bindValue(':incluir_saldo_atual', $incluirSaldoAtual, PDO::PARAM_BOOL);
        $stmt->bindValue(':cor', !empty($data['cor']) ? trim((string) $data['cor']) : $contaAntiga['cor'], PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'] ?? $contaAntiga['status'], PDO::PARAM_STR);
        $success = $stmt->execute();

        if ($success) {
            $contaNova = $this->find($id);
            $this->logAudit('UPDATE', $id, $contaAntiga, $contaNova, $userId);
        }

        return $success;
    }

    /**
     * Alterna o status de uma conta entre 'ativa' e 'inativa'.
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function toggleStatus(int $id, int $userId): bool
    {
        $conta = $this->find($id);
        if (!$conta) {
            return false;
        }

        $novoStatus = ($conta['status'] === 'ativa') ? 'inativa' : 'ativa';

        $sql = "UPDATE `contas` SET status = :status, atualizado_em = NOW() WHERE id = :id AND usuario_id = :usuario_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':status', $novoStatus, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $success = $stmt->execute();

        if ($success) {
            $this->logAudit('UPDATE_STATUS', $id, ['status' => $conta['status']], ['status' => $novoStatus], $userId);
        }

        return $success;
    }

    /**
     * Verifica se a conta possui qualquer transação ou transferência vinculada.
     *
     * @param int $id
     * @return bool
     */
    public function hasTransactions(int $id): bool
    {
        // 1. Checa na tabela transacoes
        $sqlTrans = "SELECT COUNT(*) as total FROM `transacoes` WHERE conta_id = :id AND usuario_id = :usuario_id AND excluido_em IS NULL";
        $stmt = $this->db->prepare($sqlTrans);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        $resTrans = $stmt->fetch();
        if ((int) ($resTrans['total'] ?? 0) > 0) {
            return true;
        }

        // 2. Checa na tabela transferencias (origem ou destino)
        $sqlTransf = "SELECT COUNT(*) as total FROM `transferencias` 
                  WHERE (conta_origem_id = :id_origem OR conta_destino_id = :id_destino) AND usuario_id = :usuario_id AND excluido_em IS NULL";
        $stmtTransf = $this->db->prepare($sqlTransf);
        $stmtTransf->bindValue(':id_origem', $id, PDO::PARAM_INT);
        $stmtTransf->bindValue(':id_destino', $id, PDO::PARAM_INT);
        $stmtTransf->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmtTransf->execute();
        $resTransf = $stmtTransf->fetch();
        return ((int) ($resTransf['total'] ?? 0)) > 0;
    }

    /**
     * Realiza a exclusão lógica segura da conta caso ela não possua movimentações vinculadas.
     * Se possuir transações, a exclusão é rejeitada para manter a consistência financeira.
     *
     * @param int $id
     * @param int $userId
     * @return array{success: bool, message: string}
     */
    public function deleteAccount(int $id, int $userId): array
    {
        $conta = $this->find($id);
        if (!$conta) {
            return ['success' => false, 'message' => 'Conta bancária não encontrada.'];
        }

        if ($this->hasTransactions($id)) {
            return [
                'success' => false,
                'message' => 'Esta conta possui lançamentos ou transferências vinculadas e não pode ser excluída. Recomendamos inativá-la.'
            ];
        }

        $deleted = $this->softDelete($id, $userId);
        if ($deleted) {
            $this->logAudit('SOFT_DELETE', $id, $conta, ['excluido_em' => date('Y-m-d H:i:s'), 'excluido_por' => $userId], $userId);
            return ['success' => true, 'message' => 'Conta bancária excluída com sucesso.'];
        }

        return ['success' => false, 'message' => 'Não foi possível excluir a conta.'];
    }
}
