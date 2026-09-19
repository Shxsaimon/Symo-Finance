<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Model de Transações Financeiras (Transaction)
 *
 * Gerencia o fluxo financeiro de receitas e despesas à vista e parceladas,
 * controle atômico de saldos bancários, vínculo com faturas de cartão e auditoria.
 */
class Transaction extends Model
{
    protected string $tabela = 'transacoes';
    protected string $chavePrimaria = 'id';

    /**
     * Cria uma transação avulsa (receita ou despesa à vista).
     *
     * @param array<string, mixed> $data
     * @param int $userId
     * @return int
     */
    public function createSingleTransaction(array $data, int $userId): int
    {
        $tipo = $data['tipo'] === 'receita' ? 'receita' : 'despesa';
        $descricao = trim((string) ($data['descricao'] ?? ''));
        $valor = (float) ($data['valor'] ?? 0.0);
        $dataTransacao = !empty($data['data_transacao']) ? $data['data_transacao'] : date('Y-m-d');
        $dataVencimento = !empty($data['data_vencimento']) ? $data['data_vencimento'] : $dataTransacao;
        $categoriaId = (int) ($data['categoria_id'] ?? 1);
        $meioPagamentoId = (int) ($data['meio_pagamento_id'] ?? 1);
        $contaId = !empty($data['conta_id']) ? (int) $data['conta_id'] : null;
        $cartaoCreditoId = !empty($data['cartao_credito_id']) ? (int) $data['cartao_credito_id'] : null;
        $status = $data['status'] ?? ($tipo === 'receita' ? 'recebido' : 'pago');
        $dataPagamento = ($status === 'pago' || $status === 'recebido') ? ($data['data_pagamento'] ?? $dataTransacao) : null;
        $observacoes = !empty($data['observacoes']) ? trim((string) $data['observacoes']) : null;
        $faturaId = null;

        $this->validateRelatedOwnership($userId, $categoriaId, $meioPagamentoId, $contaId, $cartaoCreditoId);

        // INÍCIO DA TRANSAÇÃO ATÔMICA
        $this->db->beginTransaction();
        try {
            // Se for despesa no cartão de crédito, localiza ou gera a fatura
            if ($tipo === 'despesa' && $cartaoCreditoId !== null) {
                $dt = new DateTimeImmutable($dataTransacao);
                $mesRef = (int) $dt->format('n');
                $anoRef = (int) $dt->format('Y');

                $invoiceModel = new CreditCardInvoice();
                $fatura = $invoiceModel->getOrCreateInvoice($cartaoCreditoId, $mesRef, $anoRef);
                $faturaId = (int) $fatura['id'];
                $status = 'pendente'; // Despesa no cartão fica pendente até a fatura ser paga
                $contaId = null; // Não debita conta no momento da compra
                if (!empty($fatura['data_vencimento'])) {
                    $dataVencimento = $fatura['data_vencimento'];
                }
            }

            // Inserção da transação
            $sql = "INSERT INTO `transacoes` (
                        usuario_id, tipo, descricao, valor, data_transacao, data_vencimento,
                        data_pagamento, status, conta_id, categoria_id, meio_pagamento_id,
                        cartao_credito_id, fatura_id, numero_parcela, total_parcelas,
                        observacoes, criado_em, atualizado_em
                    ) VALUES (
                        :usuario_id, :tipo, :descricao, :valor, :data_transacao, :data_vencimento,
                        :data_pagamento, :status, :conta_id, :categoria_id, :meio_pagamento_id,
                        :cartao_credito_id, :fatura_id, 1, 1,
                        :observacoes, NOW(), NOW()
                    )";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->bindValue(':descricao', $descricao, PDO::PARAM_STR);
            $stmt->bindValue(':valor', $valor);
            $stmt->bindValue(':data_transacao', $dataTransacao, PDO::PARAM_STR);
            $stmt->bindValue(':data_vencimento', $dataVencimento, PDO::PARAM_STR);
            $stmt->bindValue(':data_pagamento', $dataPagamento, $dataPagamento !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':conta_id', $contaId, $contaId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':categoria_id', $categoriaId, PDO::PARAM_INT);
            $stmt->bindValue(':meio_pagamento_id', $meioPagamentoId, PDO::PARAM_INT);
            $stmt->bindValue(':cartao_credito_id', $cartaoCreditoId, $cartaoCreditoId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':fatura_id', $faturaId, $faturaId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':observacoes', $observacoes, $observacoes !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->execute();

            $transacaoId = (int) $this->db->lastInsertId();

            // Atualização de saldo em conta bancária (se afetar conta imediatamente)
            if ($contaId !== null) {
                if ($tipo === 'receita' && $status === 'recebido') {
                    $this->adjustAccountBalance($contaId, $valor);
                } elseif ($tipo === 'despesa' && $status === 'pago') {
                    $this->adjustAccountBalance($contaId, -$valor);
                }
            }

            // Recalcula total da fatura se houver cartão
            if ($faturaId !== null) {
                $invoiceModel = new CreditCardInvoice();
                $invoiceModel->recalculateTotal($faturaId);
            }

            $this->logAudit('INSERT', $transacaoId, null, [
                'tipo'           => $tipo,
                'descricao'      => $descricao,
                'valor'          => $valor,
                'status'         => $status,
                'conta_id'       => $contaId,
                'categoria_id'   => $categoriaId,
                'data_transacao' => $dataTransacao,
            ], $userId);

            $this->db->commit();

            return $transacaoId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            LoggerHelper::error($e);
            throw $e;
        }
    }

    /**
     * Cria uma compra parcelada gerando N lançamentos individuais com tratamento de resíduo de centavos.
     *
     * @param array<string, mixed> $data
     * @param int $userId
     * @return array<int, int> Lista de IDs das transações geradas
     */
    public function createInstallmentTransactions(array $data, int $userId): array
    {
        $descricao = trim((string) ($data['descricao'] ?? ''));
        $valorTotal = (float) ($data['valor'] ?? 0.0);
        $totalParcelas = max(2, (int) ($data['total_parcelas'] ?? 2));
        $dataPrimeiraParcela = !empty($data['data_transacao']) ? $data['data_transacao'] : date('Y-m-d');
        $categoriaId = (int) ($data['categoria_id'] ?? 1);
        $meioPagamentoId = (int) ($data['meio_pagamento_id'] ?? 1);
        $contaId = !empty($data['conta_id']) ? (int) $data['conta_id'] : null;
        $cartaoCreditoId = !empty($data['cartao_credito_id']) ? (int) $data['cartao_credito_id'] : null;
        $observacoes = !empty($data['observacoes']) ? trim((string) $data['observacoes']) : null;

        $this->validateRelatedOwnership($userId, $categoriaId, $meioPagamentoId, $contaId, $cartaoCreditoId);

        // Cálculo de parcelas com ajuste de dízima residual na 1ª parcela
        $valorBase = floor(($valorTotal / $totalParcelas) * 100) / 100;
        $residuo = round($valorTotal - ($valorBase * $totalParcelas), 2);

        $grupoParcelamentoId = bin2hex(random_bytes(16));
        $createdIds = [];
        $faturasAfetadas = [];

        $this->db->beginTransaction();
        try {
            $dtBase = new DateTimeImmutable($dataPrimeiraParcela);
            $invoiceModel = new CreditCardInvoice();

            for ($i = 1; $i <= $totalParcelas; $i++) {
                // Primeira parcela absorve o resíduo para fechar o valor exato
                $valorParcela = ($i === 1) ? round($valorBase + $residuo, 2) : $valorBase;
                
                // Data da parcela atual (mês a mês)
                $dtParcela = $dtBase->modify('+' . ($i - 1) . ' month');
                $dataTransacao = $dtParcela->format('Y-m-d');
                $dataVencimento = $dataTransacao;
                $faturaId = null;
                $status = 'pendente';
                $contaParcelaId = $contaId;

                // Se for cartão, vincula à fatura do mês correspondente
                if ($cartaoCreditoId !== null) {
                    $mesRef = (int) $dtParcela->format('n');
                    $anoRef = (int) $dtParcela->format('Y');
                    $fatura = $invoiceModel->getOrCreateInvoice($cartaoCreditoId, $mesRef, $anoRef);
                    $faturaId = (int) $fatura['id'];
                    $faturasAfetadas[$faturaId] = true;
                    $contaParcelaId = null;
                    if (!empty($fatura['data_vencimento'])) {
                        $dataVencimento = $fatura['data_vencimento'];
                    }
                }

                $descricaoParcela = "{$descricao} [{$i}/{$totalParcelas}]";

                $sql = "INSERT INTO `transacoes` (
                            usuario_id, tipo, descricao, valor, data_transacao, data_vencimento,
                            status, conta_id, categoria_id, meio_pagamento_id, cartao_credito_id,
                            fatura_id, grupo_parcelamento_id, numero_parcela, total_parcelas,
                            observacoes, criado_em, atualizado_em
                        ) VALUES (
                            :usuario_id, 'despesa', :descricao, :valor, :data_transacao, :data_vencimento,
                            :status, :conta_id, :categoria_id, :meio_pagamento_id, :cartao_credito_id,
                            :fatura_id, :grupo_parcelamento_id, :numero_parcela, :total_parcelas,
                            :observacoes, NOW(), NOW()
                        )";

                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
                $stmt->bindValue(':descricao', $descricaoParcela, PDO::PARAM_STR);
                $stmt->bindValue(':valor', $valorParcela);
                $stmt->bindValue(':data_transacao', $dataTransacao, PDO::PARAM_STR);
                $stmt->bindValue(':data_vencimento', $dataVencimento, PDO::PARAM_STR);
                $stmt->bindValue(':status', $status, PDO::PARAM_STR);
                $stmt->bindValue(':conta_id', $contaParcelaId, $contaParcelaId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $stmt->bindValue(':categoria_id', $categoriaId, PDO::PARAM_INT);
                $stmt->bindValue(':meio_pagamento_id', $meioPagamentoId, PDO::PARAM_INT);
                $stmt->bindValue(':cartao_credito_id', $cartaoCreditoId, $cartaoCreditoId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $stmt->bindValue(':fatura_id', $faturaId, $faturaId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $stmt->bindValue(':grupo_parcelamento_id', $grupoParcelamentoId, PDO::PARAM_STR);
                $stmt->bindValue(':numero_parcela', $i, PDO::PARAM_INT);
                $stmt->bindValue(':total_parcelas', $totalParcelas, PDO::PARAM_INT);
                $stmt->bindValue(':observacoes', $observacoes, $observacoes !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmt->execute();

                $createdIds[] = (int) $this->db->lastInsertId();
            }

            // Recalcula totais das faturas afetadas
            foreach (array_keys($faturasAfetadas) as $fatId) {
                $invoiceModel->recalculateTotal($fatId);
            }

            $this->logAudit('INSERT', $createdIds[0], null, [
                'tipo'                  => 'despesa',
                'descricao'             => "{$descricao} (Parcelado {$totalParcelas}x)",
                'valor_total'           => $valorTotal,
                'grupo_parcelamento_id' => $grupoParcelamentoId,
                'total_parcelas'        => $totalParcelas,
            ], $userId);

            $this->db->commit();

            return $createdIds;
        } catch (Throwable $e) {
            $this->db->rollBack();
            LoggerHelper::error($e);
            throw $e;
        }
    }

    /**
     * Atualiza uma transação existente com verificação RBAC e ajuste atômico de saldos.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @param int $userId
     * @param bool $isAdmin
     * @return bool
     */
    public function updateTransaction(int $id, array $data, int $userId, bool $isAdmin): bool
    {
        $old = $this->find($id);
        if (!$old) {
            return false;
        }

        // Restrição RBAC: operador só edita lançamentos criados por ele mesmo
        if (!$isAdmin && (int) $old['usuario_id'] !== $userId) {
            throw new RuntimeException('Você não possui autorização para editar lançamentos de outros usuários.');
        }

        $tipo = $data['tipo'] ?? $old['tipo'];
        $descricao = isset($data['descricao']) ? trim((string) $data['descricao']) : $old['descricao'];
        $valor = isset($data['valor']) ? (float) $data['valor'] : (float) $old['valor'];
        $dataTransacao = $data['data_transacao'] ?? $old['data_transacao'];
        $dataVencimento = $data['data_vencimento'] ?? $old['data_vencimento'];
        $status = $data['status'] ?? $old['status'];
        $categoriaId = isset($data['categoria_id']) ? (int) $data['categoria_id'] : (int) $old['categoria_id'];
        $meioPagamentoId = isset($data['meio_pagamento_id']) ? (int) $data['meio_pagamento_id'] : (int) $old['meio_pagamento_id'];
        $contaId = array_key_exists('conta_id', $data) ? (!empty($data['conta_id']) ? (int) $data['conta_id'] : null) : (!empty($old['conta_id']) ? (int) $old['conta_id'] : null);
        $observacoes = isset($data['observacoes']) ? trim((string) $data['observacoes']) : $old['observacoes'];
        $dataPagamento = ($status === 'pago' || $status === 'recebido') ? ($data['data_pagamento'] ?? $old['data_pagamento'] ?? date('Y-m-d')) : null;

        $this->validateRelatedOwnership($userId, $categoriaId, $meioPagamentoId, $contaId, null);

        $this->db->beginTransaction();
        try {
            // 1. Reverte o saldo antigo da conta anterior (se estava liquidado)
            if (!empty($old['conta_id'])) {
                $oldContaId = (int) $old['conta_id'];
                $oldValor = (float) $old['valor'];
                if ($old['tipo'] === 'receita' && $old['status'] === 'recebido') {
                    $this->adjustAccountBalance($oldContaId, -$oldValor);
                } elseif ($old['tipo'] === 'despesa' && $old['status'] === 'pago') {
                    $this->adjustAccountBalance($oldContaId, $oldValor);
                }
            }

            // 2. Aplica o novo saldo na conta (se estiver liquidado)
            if ($contaId !== null) {
                if ($tipo === 'receita' && $status === 'recebido') {
                    $this->adjustAccountBalance($contaId, $valor);
                } elseif ($tipo === 'despesa' && $status === 'pago') {
                    $this->adjustAccountBalance($contaId, -$valor);
                }
            }

            // 3. Executa a atualização
            $sql = "UPDATE `transacoes` SET
                        tipo = :tipo, descricao = :descricao, valor = :valor,
                        data_transacao = :data_transacao, data_vencimento = :data_vencimento,
                        data_pagamento = :data_pagamento, status = :status, conta_id = :conta_id,
                        categoria_id = :categoria_id, meio_pagamento_id = :meio_pagamento_id,
                        observacoes = :observacoes, atualizado_em = NOW()
                    WHERE id = :id AND excluido_em IS NULL";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->bindValue(':descricao', $descricao, PDO::PARAM_STR);
            $stmt->bindValue(':valor', $valor);
            $stmt->bindValue(':data_transacao', $dataTransacao, PDO::PARAM_STR);
            $stmt->bindValue(':data_vencimento', $dataVencimento, PDO::PARAM_STR);
            $stmt->bindValue(':data_pagamento', $dataPagamento, $dataPagamento !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':conta_id', $contaId, $contaId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':categoria_id', $categoriaId, PDO::PARAM_INT);
            $stmt->bindValue(':meio_pagamento_id', $meioPagamentoId, PDO::PARAM_INT);
            $stmt->bindValue(':observacoes', $observacoes, $observacoes !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $success = $stmt->execute();

            // 4. Se tiver fatura vinculada, recalcula o total
            if (!empty($old['fatura_id'])) {
                $invoiceModel = new CreditCardInvoice();
                $invoiceModel->recalculateTotal((int) $old['fatura_id']);
            }

            $this->logAudit('UPDATE', $id, $old, [
                'tipo'      => $tipo,
                'descricao' => $descricao,
                'valor'     => $valor,
                'status'    => $status,
                'conta_id'  => $contaId,
            ], $userId);

            $this->db->commit();
            return $success;
        } catch (Throwable $e) {
            $this->db->rollBack();
            LoggerHelper::error($e);
            throw $e;
        }
    }

    /**
     * Alterna o status de uma transação (de pendente para pago/recebido e vice-versa).
     *
     * @param int $id
     * @param int $userId
     * @param bool $isAdmin
     * @return array{success: bool, message: string, novo_status?: string}
     */
    public function toggleStatus(int $id, int $userId, bool $isAdmin): array
    {
        $trans = $this->find($id);
        if (!$trans) {
            return ['success' => false, 'message' => 'Lançamento não encontrado.'];
        }

        if (!$isAdmin && (int) $trans['usuario_id'] !== $userId) {
            return ['success' => false, 'message' => 'Você não possui permissão para alterar lançamentos de terceiros.'];
        }

        // Não permite alternar individualmente se for despesa de cartão de crédito vinculado a fatura
        if (!empty($trans['fatura_id'])) {
            return ['success' => false, 'message' => 'Lançamentos de cartão de crédito são liquidados através do pagamento da fatura.'];
        }

        $novoStatus = 'pendente';
        if ($trans['status'] === 'pendente') {
            $novoStatus = ($trans['tipo'] === 'receita') ? 'recebido' : 'pago';
        }

        $dataPagto = ($novoStatus !== 'pendente') ? date('Y-m-d') : null;

        $this->updateTransaction($id, [
            'status'         => $novoStatus,
            'data_pagamento' => $dataPagto,
        ], $userId, $isAdmin);

        return [
            'success'     => true,
            'message'     => "Status alterado para '{$novoStatus}' com sucesso.",
            'novo_status' => $novoStatus,
        ];
    }

    /**
     * Exclui logicamente uma transação (soft delete) com reversão de saldos e controle RBAC.
     *
     * @param int $id
     * @param int $userId
     * @param bool $isAdmin
     * @return array{success: bool, message: string}
     */
    public function deleteTransaction(int $id, int $userId, bool $isAdmin): array
    {
        $trans = $this->find($id);
        if (!$trans) {
            return ['success' => false, 'message' => 'Lançamento não encontrado.'];
        }

        if (!$isAdmin && (int) $trans['usuario_id'] !== $userId) {
            return ['success' => false, 'message' => 'Você não possui permissão para excluir lançamentos de terceiros.'];
        }

        $this->db->beginTransaction();
        try {
            // Reverte saldo bancário caso estivesse pago/recebido
            if (!empty($trans['conta_id'])) {
                $contaId = (int) $trans['conta_id'];
                $valor = (float) $trans['valor'];
                if ($trans['tipo'] === 'receita' && $trans['status'] === 'recebido') {
                    $this->adjustAccountBalance($contaId, -$valor);
                } elseif ($trans['tipo'] === 'despesa' && $trans['status'] === 'pago') {
                    $this->adjustAccountBalance($contaId, $valor);
                }
            }

            $this->softDelete($id, $userId);

            // Recalcula total da fatura se houver
            if (!empty($trans['fatura_id'])) {
                $invoiceModel = new CreditCardInvoice();
                $invoiceModel->recalculateTotal((int) $trans['fatura_id']);
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Lançamento excluído com sucesso.'];
        } catch (Throwable $e) {
            $this->db->rollBack();
            LoggerHelper::error($e);
            return ['success' => false, 'message' => 'Erro ao excluir lançamento: ' . $e->getMessage()];
        }
    }

    /**
     * Retorna a listagem de transações filtradas com relacionamentos.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function getFiltered(array $filters): array
    {
        $sql = "SELECT t.*, 
                       c.nome as conta_nome, c.cor as conta_cor,
                       cat.nome as categoria_nome, cat.icone as categoria_icone, cat.cor as categoria_cor,
                       mp.nome as meio_pagamento_nome,
                       cc.nome as cartao_nome, cc.cor as cartao_cor,
                       u.nome as usuario_nome
                FROM `transacoes` t
                LEFT JOIN `contas` c ON t.conta_id = c.id
                LEFT JOIN `categorias` cat ON t.categoria_id = cat.id
                LEFT JOIN `meios_pagamento` mp ON t.meio_pagamento_id = mp.id
                LEFT JOIN `cartoes_credito` cc ON t.cartao_credito_id = cc.id
                LEFT JOIN `usuarios` u ON t.usuario_id = u.id
                WHERE t.excluido_em IS NULL";

            $sql .= " AND t.usuario_id = :usuario_id";
            $params[':usuario_id'] = AuthHelper::id();

        $params = [];

        if (!empty($filters['mes'])) {
            $sql .= " AND MONTH(t.data_vencimento) = :mes";
            $params[':mes'] = (int) $filters['mes'];
        }

        if (!empty($filters['ano'])) {
            $sql .= " AND YEAR(t.data_vencimento) = :ano";
            $params[':ano'] = (int) $filters['ano'];
        }

        if (!empty($filters['tipo']) && in_array($filters['tipo'], ['receita', 'despesa'], true)) {
            $sql .= " AND t.tipo = :tipo";
            $params[':tipo'] = $filters['tipo'];
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['pendente', 'pago', 'recebido'], true)) {
            $sql .= " AND t.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['conta_id'])) {
            $sql .= " AND t.conta_id = :conta_id";
            $params[':conta_id'] = (int) $filters['conta_id'];
        }

        if (!empty($filters['categoria_id'])) {
            $sql .= " AND t.categoria_id = :categoria_id";
            $params[':categoria_id'] = (int) $filters['categoria_id'];
        }

        if (!empty($filters['cartao_credito_id'])) {
            $sql .= " AND t.cartao_credito_id = :cartao_id";
            $params[':cartao_id'] = (int) $filters['cartao_credito_id'];
        }

        if (!empty($filters['busca'])) {
            $sql .= " AND t.descricao LIKE :busca";
            $params[':busca'] = '%' . trim((string) $filters['busca']) . '%';
        }

        $sql .= " ORDER BY t.data_transacao DESC, t.data_vencimento DESC, t.id DESC";

        $stmt = $this->db->prepare($sql);
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
     * Calcula o resumo financeiro consolidado de receitas e despesas para o período.
     *
     * @param int $mes
     * @param int $ano
     * @param int|null $contaId
     * @return array{total_receitas: float, receitas_recebidas: float, receitas_pendentes: float, total_despesas: float, despesas_pagas: float, despesas_pendentes: float, saldo_periodo: float}
     */
    public function getMonthlySummary(int $mes, int $ano, ?int $contaId = null): array
    {
        $sql = "SELECT 
                    SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) as total_receitas,
                    SUM(CASE WHEN tipo = 'receita' AND status = 'recebido' THEN valor ELSE 0 END) as receitas_recebidas,
                    SUM(CASE WHEN tipo = 'receita' AND status = 'pendente' THEN valor ELSE 0 END) as receitas_pendentes,
                    SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) as total_despesas,
                    SUM(CASE WHEN tipo = 'despesa' AND status = 'pago' THEN valor ELSE 0 END) as despesas_pagas,
                    SUM(CASE WHEN tipo = 'despesa' AND status = 'pendente' THEN valor ELSE 0 END) as despesas_pendentes
                FROM `transacoes`
                WHERE usuario_id = :usuario_id AND excluido_em IS NULL
                  AND MONTH(data_vencimento) = :mes
                  AND YEAR(data_vencimento) = :ano";

        if ($contaId !== null) {
            $sql .= " AND conta_id = :conta_id";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':mes', $mes, PDO::PARAM_INT);
        $stmt->bindValue(':ano', $ano, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        if ($contaId !== null) {
            $stmt->bindValue(':conta_id', $contaId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $res = $stmt->fetch();

        $totalReceitas = (float) ($res['total_receitas'] ?? 0.0);
        $receitasRecebidas = (float) ($res['receitas_recebidas'] ?? 0.0);
        $receitasPendentes = (float) ($res['receitas_pendentes'] ?? 0.0);

        $totalDespesas = (float) ($res['total_despesas'] ?? 0.0);
        $despesasPagas = (float) ($res['despesas_pagas'] ?? 0.0);
        $despesasPendentes = (float) ($res['despesas_pendentes'] ?? 0.0);

        $saldoPeriodo = $totalReceitas - $totalDespesas;

        return [
            'total_receitas'     => $totalReceitas,
            'receitas_recebidas' => $receitasRecebidas,
            'receitas_pendentes' => $receitasPendentes,
            'total_despesas'     => $totalDespesas,
            'despesas_pagas'     => $despesasPagas,
            'despesas_pendentes' => $despesasPendentes,
            'saldo_periodo'      => $saldoPeriodo,
        ];
    }

    /**
     * Retorna a evolução financeira dos últimos N meses para alimentar os gráficos do Dashboard.
     *
     * @param int $mesFinal
     * @param int $anoFinal
     * @param int $quantidadeMeses
     * @return array<int, array<string, mixed>>
     */
    public function getMonthlyEvolution(int $mesFinal, int $anoFinal, int $quantidadeMeses = 6): array
    {
        $mesesNomesAbrev = [
            1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr',
            5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'
        ];

        // Gera a lista cronológica de meses
        $meses = [];
        $dataCursor = new DateTimeImmutable(sprintf('%04d-%02d-01', $anoFinal, $mesFinal));
        for ($i = $quantidadeMeses - 1; $i >= 0; $i--) {
            $dt = $dataCursor->modify("-{$i} month");
            $m = (int) $dt->format('n');
            $a = (int) $dt->format('Y');
            $chave = sprintf('%04d-%02d', $a, $m);
            $meses[$chave] = [
                'mes'                => $m,
                'ano'                => $a,
                'label'              => $mesesNomesAbrev[$m] . '/' . $dt->format('y'),
                'total_receitas'     => 0.0,
                'receitas_recebidas' => 0.0,
                'total_despesas'     => 0.0,
                'despesas_pagas'     => 0.0,
                'balanco_liquido'    => 0.0,
            ];
        }

        $primeiroMes = reset($meses);
        $ultimoMes = end($meses);
        $dataInicio = sprintf('%04d-%02d-01', $primeiroMes['ano'], $primeiroMes['mes']);
        $dataFim = (new DateTimeImmutable(sprintf('%04d-%02d-01', $ultimoMes['ano'], $ultimoMes['mes'])))->format('Y-m-t');

        $sql = "SELECT 
                    YEAR(data_vencimento) as ano,
                    MONTH(data_vencimento) as mes,
                    SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) as total_receitas,
                    SUM(CASE WHEN tipo = 'receita' AND status = 'recebido' THEN valor ELSE 0 END) as receitas_recebidas,
                    SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) as total_despesas,
                    SUM(CASE WHEN tipo = 'despesa' AND status = 'pago' THEN valor ELSE 0 END) as despesas_pagas
                FROM `transacoes`
                WHERE usuario_id = :usuario_id AND excluido_em IS NULL
                  AND data_vencimento BETWEEN :data_inicio AND :data_fim
                GROUP BY YEAR(data_vencimento), MONTH(data_vencimento)
                ORDER BY ano ASC, mes ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':data_inicio', $dataInicio, PDO::PARAM_STR);
        $stmt->bindValue(':data_fim', $dataFim, PDO::PARAM_STR);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        $registros = $stmt->fetchAll();

        foreach ($registros as $reg) {
            $chave = sprintf('%04d-%02d', (int)$reg['ano'], (int)$reg['mes']);
            if (isset($meses[$chave])) {
                $rec = (float) ($reg['total_receitas'] ?? 0.0);
                $desp = (float) ($reg['total_despesas'] ?? 0.0);
                $meses[$chave]['total_receitas'] = $rec;
                $meses[$chave]['receitas_recebidas'] = (float) ($reg['receitas_recebidas'] ?? 0.0);
                $meses[$chave]['total_despesas'] = $desp;
                $meses[$chave]['despesas_pagas'] = (float) ($reg['despesas_pagas'] ?? 0.0);
                $meses[$chave]['balanco_liquido'] = $rec - $desp;
            }
        }

        return array_values($meses);
    }

    /**
     * Retorna a distribuição de despesas por categoria no mês para alimentar o gráfico de rosca (Donut).
     *
     * @param int $mes
     * @param int $ano
     * @return array<int, array<string, mixed>>
     */
    public function getExpensesByCategory(int $mes, int $ano): array
    {
        $sql = "SELECT c.id, c.nome, c.cor, c.icone,
                       SUM(t.valor) as total_valor,
                       COUNT(t.id) as total_lancamentos
                FROM `transacoes` t
                JOIN `categorias` c ON t.categoria_id = c.id
                WHERE t.excluido_em IS NULL
                                    AND t.usuario_id = :usuario_id
                  AND t.tipo = 'despesa'
                  AND MONTH(t.data_vencimento) = :mes
                  AND YEAR(t.data_vencimento) = :ano
                GROUP BY c.id, c.nome, c.cor, c.icone
                ORDER BY total_valor DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':mes', $mes, PDO::PARAM_INT);
        $stmt->bindValue(':ano', $ano, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        $categorias = $stmt->fetchAll();

        $totalGeral = array_reduce($categorias, fn($acc, $item) => $acc + (float)$item['total_valor'], 0.0);

        foreach ($categorias as &$cat) {
            $val = (float) $cat['total_valor'];
            $cat['total_valor'] = $val;
            $cat['percentual'] = ($totalGeral > 0) ? round(($val / $totalGeral) * 100, 1) : 0.0;
        }

        return $categorias;
    }

    /**
     * Retorna os próximos compromissos financeiros pendentes ou vencidos.
     * Considera a data de vencimento da fatura do cartão quando for despesa em cartão de crédito.
     *
     * @param int $dias Intervalo em dias a partir de hoje
     * @param int $limite Quantidade máxima de registros retornados
     * @return array<int, array<string, mixed>>
     */
    public function getUpcomingPending(int $dias = 7, int $limite = 6): array
    {
        $sql = "SELECT t.*, 
                       c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone,
                       co.nome as conta_nome, co.cor as conta_cor,
                       cc.nome as cartao_nome, cc.cor as cartao_cor,
                       COALESCE(fc.data_vencimento, t.data_vencimento) as data_vencimento_efetiva,
                       (COALESCE(fc.data_vencimento, t.data_vencimento) - CURRENT_DATE) as dias_para_vencer
                FROM `transacoes` t
                LEFT JOIN `categorias` c ON t.categoria_id = c.id
                LEFT JOIN `contas` co ON t.conta_id = co.id
                LEFT JOIN `cartoes_credito` cc ON t.cartao_credito_id = cc.id
                LEFT JOIN `faturas_cartao` fc ON t.fatura_id = fc.id
                WHERE t.excluido_em IS NULL
                                    AND t.usuario_id = :usuario_id
                  AND t.status = 'pendente'
                  AND COALESCE(fc.data_vencimento, t.data_vencimento) <= (CURRENT_DATE + (:dias * INTERVAL '1 day'))
                ORDER BY data_vencimento_efetiva ASC, t.id ASC
                LIMIT :limite";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':dias', $dias, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Ajusta atomicamente o saldo_atual de uma conta bancária.
     *
     * @param int $contaId
     * @param float $valor (positivo para crédito, negativo para débito)
     * @return void
     */
    private function adjustAccountBalance(int $contaId, float $valor): void
    {
        $sql = "UPDATE `contas` 
                SET saldo_atual = saldo_atual + :valor, atualizado_em = NOW() 
                WHERE id = :conta_id AND usuario_id = :usuario_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':valor', $valor);
        $stmt->bindValue(':conta_id', $contaId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
    }

    private function validateRelatedOwnership(int $userId, int $categoriaId, int $meioPagamentoId, ?int $contaId, ?int $cartaoCreditoId): void
    {
        $checks = [
            ['categorias', $categoriaId, 'Categoria'],
            ['meios_pagamento', $meioPagamentoId, 'Meio de pagamento'],
        ];

        if ($contaId !== null) {
            $checks[] = ['contas', $contaId, 'Conta'];
        }
        if ($cartaoCreditoId !== null) {
            $checks[] = ['cartoes_credito', $cartaoCreditoId, 'Cartão de crédito'];
        }

        foreach ($checks as [$table, $id, $label]) {
            $stmt = $this->db->prepare("SELECT 1 FROM `{$table}` WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NULL LIMIT 1");
            $stmt->execute(['id' => $id, 'usuario_id' => $userId]);
            if ($stmt->fetchColumn() === false) {
                throw new InvalidArgumentException("{$label} inválido ou não pertence ao usuário autenticado.");
            }
        }
    }
}

