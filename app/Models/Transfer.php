<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Model de Transferências entre Contas (Transfer)
 *
 * Gerencia a movimentação interna de saldos entre contas bancárias e carteiras
 * sob transação atômica (débito na origem + crédito no destino) sem distorcer receitas/despesas.
 */
class Transfer extends Model
{
    protected string $tabela = 'transferencias';
    protected string $chavePrimaria = 'id';

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM transferencias WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NULL LIMIT 1'
        );
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * Cria uma transferência interna entre duas contas bancárias distintas com transação atômica.
     *
     * @param array<string, mixed> $data
     * @param int $userId ID do usuário autor
     * @return int ID da transferência criada
     */
    public function createTransfer(array $data, int $userId): int
    {
        $contaOrigemId = (int) ($data['conta_origem_id'] ?? 0);
        $contaDestinoId = (int) ($data['conta_destino_id'] ?? 0);
        $valor = (float) ($data['valor'] ?? 0.0);
        $dataTransferencia = !empty($data['data_transferencia']) ? trim((string) $data['data_transferencia']) : date('Y-m-d');
        $observacoes = !empty($data['observacoes']) ? trim((string) $data['observacoes']) : null;

        if ($contaOrigemId <= 0 || $contaDestinoId <= 0) {
            throw new InvalidArgumentException('Selecione contas de origem e destino válidas.');
        }

        if ($contaOrigemId === $contaDestinoId) {
            throw new InvalidArgumentException('A conta de origem e a conta de destino devem ser diferentes.');
        }

        if ($valor <= 0) {
            throw new InvalidArgumentException('O valor da transferência deve ser maior que zero.');
        }

        $accountModel = new Account();
        $contaOrigem = $accountModel->find($contaOrigemId);
        $contaDestino = $accountModel->find($contaDestinoId);

        if (!$contaOrigem || $contaOrigem['status'] !== 'ativa') {
            throw new InvalidArgumentException('A conta de origem não foi encontrada ou está inativa.');
        }

        if (!$contaDestino || $contaDestino['status'] !== 'ativa') {
            throw new InvalidArgumentException('A conta de destino não foi encontrada ou está inativa.');
        }

        // Validação de Saldo Suficiente na Conta de Origem
        $saldoOrigem = (float) ($contaOrigem['saldo_atual'] ?? 0.0);
        if ($saldoOrigem < $valor) {
            throw new InvalidArgumentException(
                "Saldo insuficiente na conta de origem '{$contaOrigem['nome']}'. Saldo disponível: " . FormatHelper::currency($saldoOrigem) . ", valor solicitado: " . FormatHelper::currency($valor) . "."
            );
        }

        // INÍCIO DA TRANSAÇÃO ATÔMICA
        $this->db->beginTransaction();
        try {
            // 1. Debita o valor na conta de origem
            $sqlDebito = "UPDATE `contas` 
                          SET saldo_atual = saldo_atual - :valor, atualizado_em = NOW() 
                                                    WHERE id = :conta_id AND usuario_id = :usuario_id
                                                        AND status = 'ativa' AND excluido_em IS NULL AND saldo_atual >= :saldo_minimo";
            $stmtDeb = $this->db->prepare($sqlDebito);
            $stmtDeb->bindValue(':valor', $valor);
            $stmtDeb->bindValue(':conta_id', $contaOrigemId, PDO::PARAM_INT);
            $stmtDeb->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmtDeb->bindValue(':saldo_minimo', $valor);
            $stmtDeb->execute();
            if ($stmtDeb->rowCount() !== 1) {
                throw new InvalidArgumentException('O saldo da conta de origem foi alterado ou é insuficiente para esta transferência.');
            }

            // 2. Credita o valor na conta de destino
            $sqlCredito = "UPDATE `contas` 
                           SET saldo_atual = saldo_atual + :valor, atualizado_em = NOW() 
                                                     WHERE id = :conta_id AND usuario_id = :usuario_id
                                                         AND status = 'ativa' AND excluido_em IS NULL";
            $stmtCred = $this->db->prepare($sqlCredito);
            $stmtCred->bindValue(':valor', $valor);
            $stmtCred->bindValue(':conta_id', $contaDestinoId, PDO::PARAM_INT);
            $stmtCred->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmtCred->execute();
            if ($stmtCred->rowCount() !== 1) {
                throw new InvalidArgumentException('A conta de destino foi alterada durante a transferência.');
            }

            // 3. Registra a transferência na tabela transferencias
            $sqlTransf = "INSERT INTO `transferencias` (
                            usuario_id, conta_origem_id, conta_destino_id, valor,
                            data_transferencia, observacoes, criado_em, atualizado_em
                          ) VALUES (
                            :usuario_id, :origem_id, :destino_id, :valor,
                            :data_transf, :observacoes, NOW(), NOW()
                          )";
            $stmtTransf = $this->db->prepare($sqlTransf);
            $stmtTransf->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmtTransf->bindValue(':origem_id', $contaOrigemId, PDO::PARAM_INT);
            $stmtTransf->bindValue(':destino_id', $contaDestinoId, PDO::PARAM_INT);
            $stmtTransf->bindValue(':valor', $valor);
            $stmtTransf->bindValue(':data_transf', $dataTransferencia, PDO::PARAM_STR);
            $stmtTransf->bindValue(':observacoes', $observacoes, $observacoes !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtTransf->execute();

            $transferenciaId = (int) $this->db->lastInsertId();

            // 4. Registra auditoria
            $this->logAudit('INSERT', $transferenciaId, null, [
                'conta_origem_id'    => $contaOrigemId,
                'conta_destino_id'   => $contaDestinoId,
                'valor'              => $valor,
                'data_transferencia' => $dataTransferencia,
                'observacoes'        => $observacoes,
            ], $userId);

            $this->db->commit();

            return $transferenciaId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            LoggerHelper::error($e);
            throw $e;
        }
    }

    /**
     * Exclui logicamente uma transferência (soft delete) e estorna os saldos atomicamente.
     *
     * @param int $id ID da transferência
     * @param int $userId ID do usuário solicitante
     * @return array{success: bool, message: string}
     */
    public function deleteTransfer(int $id, int $userId): array
    {
        $transferencia = $this->find($id);
        if (!$transferencia) {
            return ['success' => false, 'message' => 'Transferência não encontrada.'];
        }

        // Estorno financeiro é limitado ao usuário proprietário da transferência.
        if ((int) $transferencia['usuario_id'] !== $userId) {
            return ['success' => false, 'message' => 'Você não possui permissão para excluir transferências de outros usuários.'];
        }

        $contaOrigemId = (int) $transferencia['conta_origem_id'];
        $contaDestinoId = (int) $transferencia['conta_destino_id'];
        $valor = (float) $transferencia['valor'];

        $this->db->beginTransaction();
        try {
            // 1. Estorna a conta de origem (devolve o saldo)
            $sqlEstornoOrigem = "UPDATE `contas` 
                                 SET saldo_atual = saldo_atual + :valor, atualizado_em = NOW() 
                                 WHERE id = :conta_id AND usuario_id = :usuario_id";
            $stmtOrigem = $this->db->prepare($sqlEstornoOrigem);
            $stmtOrigem->bindValue(':valor', $valor);
            $stmtOrigem->bindValue(':conta_id', $contaOrigemId, PDO::PARAM_INT);
            $stmtOrigem->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmtOrigem->execute();
            if ($stmtOrigem->rowCount() !== 1) {
                throw new RuntimeException('Não foi possível reverter o saldo da conta de origem.');
            }

            // 2. Estorna a conta de destino (remove o saldo creditado)
            $sqlEstornoDestino = "UPDATE `contas` 
                                  SET saldo_atual = saldo_atual - :valor, atualizado_em = NOW() 
                                  WHERE id = :conta_id AND usuario_id = :usuario_id";
            $stmtDestino = $this->db->prepare($sqlEstornoDestino);
            $stmtDestino->bindValue(':valor', $valor);
            $stmtDestino->bindValue(':conta_id', $contaDestinoId, PDO::PARAM_INT);
            $stmtDestino->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmtDestino->execute();
            if ($stmtDestino->rowCount() !== 1) {
                throw new RuntimeException('Não foi possível reverter o saldo da conta de destino.');
            }

            // 3. Executa o Soft Delete
            if (!$this->softDelete($id, $userId)) {
                throw new RuntimeException('A transferência já foi alterada ou excluída.');
            }

            $this->db->commit();

            return ['success' => true, 'message' => 'Transferência estornada e excluída com sucesso! Os saldos das contas foram restabelecidos.'];
        } catch (Throwable $e) {
            $this->db->rollBack();
            LoggerHelper::error($e);
            return ['success' => false, 'message' => 'Erro ao estornar transferência: ' . $e->getMessage()];
        }
    }

    /**
     * Retorna a listagem filtrada de transferências com nomes de contas e usuários.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function getFiltered(array $filters): array
    {
        $sql = "SELECT t.*, 
                       co.nome as conta_origem_nome, co.cor as conta_origem_cor,
                       cd.nome as conta_destino_nome, cd.cor as conta_destino_cor,
                       u.nome as usuario_nome
                FROM `transferencias` t
                JOIN `contas` co ON t.conta_origem_id = co.id
                JOIN `contas` cd ON t.conta_destino_id = cd.id
                JOIN `usuarios` u ON t.usuario_id = u.id
                WHERE t.usuario_id = :usuario_id AND t.excluido_em IS NULL";

        $params = [];

        if (!empty($filters['mes'])) {
            $sql .= " AND MONTH(t.data_transferencia) = :mes";
            $params[':mes'] = (int) $filters['mes'];
        }

        if (!empty($filters['ano'])) {
            $sql .= " AND YEAR(t.data_transferencia) = :ano";
            $params[':ano'] = (int) $filters['ano'];
        }

        if (!empty($filters['conta_id'])) {
            $sql .= " AND (t.conta_origem_id = :conta_origem_filtro OR t.conta_destino_id = :conta_destino_filtro)";
            $params[':conta_origem_filtro'] = (int) $filters['conta_id'];
            $params[':conta_destino_filtro'] = (int) $filters['conta_id'];
        }

        if (!empty($filters['busca'])) {
            $sql .= " AND t.observacoes LIKE :busca";
            $params[':busca'] = '%' . trim((string) $filters['busca']) . '%';
        }

        $sql .= " ORDER BY t.data_transferencia DESC, t.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        foreach ($params as $k => $v) {
            if (is_int($v)) {
                $stmt->bindValue($k, $v, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Retorna o resumo consolidado de transferências no período.
     *
     * @param int $mes
     * @param int $ano
     * @param int|null $contaId
     * @return array{volume_total: float, total_transferencias: int}
     */
    public function getMonthlySummary(int $mes, int $ano, ?int $contaId = null): array
    {
        $sql = "SELECT COALESCE(SUM(valor), 0) as volume_total, COUNT(*) as total_transferencias
                FROM `transferencias`
                WHERE excluido_em IS NULL
                  AND MONTH(data_transferencia) = :mes
                  AND YEAR(data_transferencia) = :ano";

        $sql .= ' AND usuario_id = :usuario_id';

        if ($contaId !== null) {
            $sql .= " AND (conta_origem_id = :conta_origem_filtro OR conta_destino_id = :conta_destino_filtro)";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':mes', $mes, PDO::PARAM_INT);
        $stmt->bindValue(':ano', $ano, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        if ($contaId !== null) {
            $stmt->bindValue(':conta_origem_filtro', $contaId, PDO::PARAM_INT);
            $stmt->bindValue(':conta_destino_filtro', $contaId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $res = $stmt->fetch();

        return [
            'volume_total'         => (float) ($res['volume_total'] ?? 0.0),
            'total_transferencias' => (int) ($res['total_transferencias'] ?? 0),
        ];
    }
}
