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

    public function find(int $id): ?array
    {
        $sql = 'SELECT * FROM transacoes WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NULL LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetch();

        return $result !== false ? $result : null;
    }

    public function softDelete(int $id, int $usuarioId): bool
    {
        $registroAntigo = $this->find($id);
        if (!$registroAntigo) {
            return false;
        }

        $sql = 'UPDATE transacoes
                SET excluido_em = NOW(), excluido_por = :excluido_por, atualizado_em = NOW()
            WHERE id = :id AND usuario_id = :usuario_escopo AND excluido_em IS NULL';

        $statement = $this->db->prepare($sql);
        $statement->bindValue(':excluido_por', $usuarioId, PDO::PARAM_INT);
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->bindValue(':usuario_escopo', $usuarioId, PDO::PARAM_INT);
        $statement->execute();

        if ($statement->rowCount() === 0) {
            return false;
        }

        $this->logAudit('SOFT_DELETE', $id, $registroAntigo, [
            'excluido_em' => date('Y-m-d H:i:s'),
            'excluido_por' => $usuarioId,
        ], $usuarioId);

        return true;
    }

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
        $meioPagamentoId = (int) ($data['meio_pagamento_id'] ?? 0);
        $contaId = !empty($data['conta_id']) ? (int) $data['conta_id'] : null;
        $cartaoCreditoId = !empty($data['cartao_credito_id']) ? (int) $data['cartao_credito_id'] : null;
        $status = $data['status'] ?? ($tipo === 'receita' ? 'recebido' : 'pago');
        $dataPagamento = ($status === 'pago' || $status === 'recebido') ? ($data['data_pagamento'] ?? $dataTransacao) : null;
        $observacoes = !empty($data['observacoes']) ? trim((string) $data['observacoes']) : null;
        $faturaId = null;

        $this->validateRelatedOwnership($userId, $categoriaId, $meioPagamentoId, $contaId, $cartaoCreditoId);
        if ($tipo === 'despesa') {
            $this->validateExpensePaymentDestination($meioPagamentoId, $contaId, $cartaoCreditoId, $userId);
        }
        $contaOwnerId = $contaId !== null ? $this->accountOwnerId($contaId, $userId) : null;

        // INÍCIO DA TRANSAÇÃO ATÔMICA
        $this->db->beginTransaction();
        try {
            // Se for despesa no cartão de crédito, localiza ou gera a fatura
            if ($tipo === 'despesa' && $cartaoCreditoId !== null) {
                $invoiceModel = new CreditCardInvoice();
                $fatura = $invoiceModel->getOrCreateInvoiceForPurchase($cartaoCreditoId, $dataTransacao);
                $fatura = $invoiceModel->findForUpdate((int) $fatura['id'], $userId)
                    ?? throw new InvalidArgumentException('A fatura da compra não está disponível.');
                if ($fatura['status'] === 'paga') {
                    throw new InvalidArgumentException('Não é possível incluir uma nova compra em uma fatura já paga.');
                }
                $faturaId = (int) $fatura['id'];
                $status = 'pendente'; // Despesa no cartão fica pendente até a fatura ser paga
                $dataPagamento = null;
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
                    $this->adjustAccountBalance($contaId, $valor, (int) $contaOwnerId);
                } elseif ($tipo === 'despesa' && $status === 'pago') {
                    $this->adjustAccountBalance($contaId, -$valor, (int) $contaOwnerId);
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
        $meioPagamentoId = (int) ($data['meio_pagamento_id'] ?? 0);
        $contaId = !empty($data['conta_id']) ? (int) $data['conta_id'] : null;
        $cartaoCreditoId = !empty($data['cartao_credito_id']) ? (int) $data['cartao_credito_id'] : null;
        $observacoes = !empty($data['observacoes']) ? trim((string) $data['observacoes']) : null;

        $this->validateRelatedOwnership($userId, $categoriaId, $meioPagamentoId, $contaId, $cartaoCreditoId);
        $this->validateExpensePaymentDestination($meioPagamentoId, $contaId, $cartaoCreditoId, $userId);

        if ($totalParcelas < 2 || $totalParcelas > 72) {
            throw new InvalidArgumentException('A quantidade de parcelas deve estar entre 2 e 72.');
        }

        // Cálculo de parcelas com ajuste de dízima residual na 1ª parcela
        $valorBase = floor(($valorTotal / $totalParcelas) * 100) / 100;
        $residuo = round($valorTotal - ($valorBase * $totalParcelas), 2);

        $grupoParcelamentoId = bin2hex(random_bytes(16));
        $createdIds = [];
        $faturasAfetadas = [];

        $this->db->beginTransaction();
        try {
            $invoiceModel = new CreditCardInvoice();

            $closingDay = 0;
            $baseInvoiceRef = null;
            if ($cartaoCreditoId !== null) {
                $card = (new CreditCard())->find($cartaoCreditoId);
                $closingDay = (int) ($card['dia_fechamento'] ?? 0);
                $baseInvoiceRef = CreditCardCalendar::invoiceReferenceForPurchase($dataPrimeiraParcela, $closingDay);
            }

            for ($i = 1; $i <= $totalParcelas; $i++) {
                // Primeira parcela absorve o resíduo para fechar o valor exato
                $valorParcela = ($i === 1) ? round($valorBase + $residuo, 2) : $valorBase;
                
                $faturaId = null;
                $status = 'pendente';
                $contaParcelaId = $contaId;

                // Se for cartão, vincula à fatura do ciclo consecutivo correspondente
                if ($cartaoCreditoId !== null && $baseInvoiceRef !== null) {
                    $offset = $i - 1;
                    $targetInvoiceRef = CreditCardCalendar::nextInvoiceReference(
                        $baseInvoiceRef['mes'],
                        $baseInvoiceRef['ano'],
                        $offset
                    );
                    $targetMonth = $targetInvoiceRef['mes'];
                    $targetYear  = $targetInvoiceRef['ano'];

                    // Parcela 1 mantém a data real da compra.
                    // Parcelas futuras (2..N) caem na data de abertura da respectiva fatura (dia do fechamento anterior).
                    if ($i === 1) {
                        $dataTransacao = $dataPrimeiraParcela;
                    } else {
                        $dataTransacao = CreditCardCalendar::invoiceOpeningDate($closingDay, $targetMonth, $targetYear);
                    }

                    $fatura = $invoiceModel->getOrCreateInvoice($cartaoCreditoId, $targetMonth, $targetYear);
                    $fatura = $invoiceModel->findForUpdate((int) $fatura['id'], $userId)
                        ?? throw new InvalidArgumentException('A fatura da parcela não está disponível.');
                    if ($fatura['status'] === 'paga') {
                        throw new InvalidArgumentException('Não é possível incluir uma parcela em uma fatura já paga.');
                    }
                    $faturaId = (int) $fatura['id'];
                    $faturasAfetadas[$faturaId] = true;
                    $contaParcelaId = null;
                    $dataVencimento = !empty($fatura['data_vencimento']) ? $fatura['data_vencimento'] : $dataTransacao;
                } else {
                    $dataTransacao = CreditCardCalendar::installmentDate($dataPrimeiraParcela, $i - 1);
                    $dataVencimento = $dataTransacao;
                }

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
                $stmt->bindValue(':descricao', $descricao, PDO::PARAM_STR);
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
     * @return bool
     */
    public function updateTransaction(int $id, array $data, int $userId): bool
    {
        $old = $this->find($id);
        if (!$old) {
            return false;
        }

        // Edição financeira é limitada ao usuário proprietário do lançamento.
        if ((int) $old['usuario_id'] !== $userId) {
            throw new RuntimeException('Você não possui autorização para editar lançamentos de outros usuários.');
        }

        $isCreditCharge = !empty($old['fatura_id']);
        $tipo = $isCreditCharge ? 'despesa' : ($data['tipo'] ?? $old['tipo']);
        $descricao = isset($data['descricao']) ? trim((string) $data['descricao']) : $old['descricao'];
        $valor = isset($data['valor']) ? (float) $data['valor'] : (float) $old['valor'];
        $dataInformada = $data['data_transacao'] ?? $data['data_vencimento'] ?? $old['data_transacao'] ?? $old['data_vencimento'];

        if (!$isCreditCharge) {
            // Compras e entradas fora do cartão: data_transacao e data_vencimento são sempre iguais
            $dataTransacao = $dataInformada;
            $dataVencimento = $dataInformada;
        } else {
            // Compras no cartão de crédito: atualiza a data da compra e o vencimento acompanha a fatura do ciclo
            $dataTransacao = $dataInformada;
            $dataVencimento = $old['data_vencimento'];
        }

        $status = $isCreditCharge ? $old['status'] : ($data['status'] ?? $old['status']);
        $categoriaId = isset($data['categoria_id']) ? (int) $data['categoria_id'] : (int) $old['categoria_id'];
        $meioPagamentoId = $isCreditCharge
            ? (int) $old['meio_pagamento_id']
            : (isset($data['meio_pagamento_id']) ? (int) $data['meio_pagamento_id'] : (int) $old['meio_pagamento_id']);
        if (!$isCreditCharge && $meioPagamentoId !== (int) $old['meio_pagamento_id']) {
            $paymentMethod = (new PaymentMethod())->getForHistoricalTransaction($meioPagamentoId);
            if ($paymentMethod !== null && in_array($paymentMethod['eh_credito'], [true, 1, '1', 't', 'true'], true)) {
                throw new InvalidArgumentException('Meios de crédito só podem ser usados em despesas vinculadas a um cartão e à respectiva fatura.');
            }
        }
        $contaId = $isCreditCharge
            ? (!empty($old['conta_id']) ? (int) $old['conta_id'] : null)
            : (array_key_exists('conta_id', $data) ? (!empty($data['conta_id']) ? (int) $data['conta_id'] : null) : (!empty($old['conta_id']) ? (int) $old['conta_id'] : null));
        $observacoes = isset($data['observacoes']) ? trim((string) $data['observacoes']) : $old['observacoes'];
        $dataPagamento = ($status === 'pago' || $status === 'recebido')
            ? (!$isCreditCharge ? $dataTransacao : ($data['data_pagamento'] ?? $old['data_pagamento'] ?? date('Y-m-d')))
            : null;

        $historicalPaymentMethodId = $meioPagamentoId === (int) $old['meio_pagamento_id']
            ? $meioPagamentoId
            : null;
        $this->validateRelatedOwnership($userId, $categoriaId, $meioPagamentoId, $contaId, null, $historicalPaymentMethodId);
        $contaOwnerId = !$isCreditCharge && $contaId !== null
            ? $this->accountOwnerId($contaId, $userId)
            : null;
        $faturaId = !empty($old['fatura_id']) ? (int) $old['fatura_id'] : null;

        if ($isCreditCharge) {
            (new CreditCardInvoice())->closeMaturedInvoicesForCard(
                (int) $old['cartao_credito_id'],
                $userId
            );
        }

        $this->db->beginTransaction();
        try {
            $invoiceModel = null;
            $oldInvoice = null;
            if ($isCreditCharge) {
                $invoiceModel = new CreditCardInvoice();
                $oldInvoice = $invoiceModel->findForUpdate((int) $old['fatura_id'], $userId);
                if (!$oldInvoice) {
                    throw new InvalidArgumentException('A fatura vinculada à compra não está disponível.');
                }
                if ($oldInvoice['status'] === 'paga') {
                    $status = 'pago';
                    $dataPagamento = !empty($oldInvoice['pago_em'])
                        ? substr((string) $oldInvoice['pago_em'], 0, 10)
                        : ($old['data_pagamento'] ?? date('Y-m-d'));
                }
                if ($oldInvoice['status'] === 'paga' && abs($valor - (float) $old['valor']) >= 0.005) {
                    throw new InvalidArgumentException('Não é possível alterar o valor de uma compra vinculada a uma fatura paga.');
                }
                if ($oldInvoice['status'] === 'paga' && $dataTransacao !== $old['data_transacao']) {
                    throw new InvalidArgumentException('Não é possível alterar a data de uma compra vinculada a uma fatura paga.');
                }
            }

            if ($isCreditCharge && $oldInvoice && $oldInvoice['status'] !== 'paga') {
                if ($dataTransacao !== $old['data_transacao'] || empty($data['fatura_referencia'])) {
                    $targetInvoice = $invoiceModel->getOrCreateInvoiceForPurchase(
                        (int) $old['cartao_credito_id'],
                        $dataTransacao
                    );
                } else {
                    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $data['fatura_referencia'])) {
                        throw new InvalidArgumentException('A referência da fatura informada é inválida.');
                    }

                    [$anoRef, $mesRef] = array_map('intval', explode('-', (string) $data['fatura_referencia']));
                    $targetInvoice = $invoiceModel->getOrCreateInvoice(
                        (int) $old['cartao_credito_id'],
                        $mesRef,
                        $anoRef
                    );
                }

                $targetInvoice = $invoiceModel->findForUpdate((int) $targetInvoice['id'], $userId)
                    ?? throw new InvalidArgumentException('A fatura de destino não está disponível.');
                if ($targetInvoice['status'] === 'paga') {
                    throw new InvalidArgumentException('Não é possível mover a compra para uma fatura já paga.');
                }

                $faturaId = (int) $targetInvoice['id'];
                $dataVencimento = (string) $targetInvoice['data_vencimento'];
            } elseif ($isCreditCharge && !empty($data['fatura_referencia'])) {
                throw new InvalidArgumentException('Não é possível alterar uma compra vinculada a uma fatura paga.');
            }

            // 1. Reverte o saldo antigo da conta anterior (se estava liquidado)
            if (!$isCreditCharge && !empty($old['conta_id'])) {
                $oldContaId = (int) $old['conta_id'];
                $oldValor = (float) $old['valor'];
                if ($old['tipo'] === 'receita' && $old['status'] === 'recebido') {
                    $this->adjustAccountBalance($oldContaId, -$oldValor, (int) $old['usuario_id']);
                } elseif ($old['tipo'] === 'despesa' && $old['status'] === 'pago') {
                    $this->adjustAccountBalance($oldContaId, $oldValor, (int) $old['usuario_id']);
                }
            }

            // 2. Aplica o novo saldo na conta (se estiver liquidado)
            if (!$isCreditCharge && $contaId !== null) {
                if ($tipo === 'receita' && $status === 'recebido') {
                    $this->adjustAccountBalance($contaId, $valor, (int) $contaOwnerId);
                } elseif ($tipo === 'despesa' && $status === 'pago') {
                    $this->adjustAccountBalance($contaId, -$valor, (int) $contaOwnerId);
                }
            }

            // 3. Executa a atualização
            $sql = "UPDATE `transacoes` SET
                        tipo = :tipo, descricao = :descricao, valor = :valor,
                        data_transacao = :data_transacao,
                        data_vencimento = :data_vencimento,
                        data_pagamento = :data_pagamento, status = :status, conta_id = :conta_id,
                        categoria_id = :categoria_id, meio_pagamento_id = :meio_pagamento_id,
                        fatura_id = :fatura_id,
                        observacoes = :observacoes, atualizado_em = NOW()
                    WHERE id = :id AND usuario_id = :usuario_escopo AND excluido_em IS NULL";

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
            $stmt->bindValue(':fatura_id', $faturaId, $faturaId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':observacoes', $observacoes, $observacoes !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':usuario_escopo', $userId, PDO::PARAM_INT);
            $success = $stmt->execute();

            // 4. Se for compra parcelada e a data foi alterada, propaga para as próximas parcelas do grupo
            $faturasAfetadas = [];
            if (!empty($old['fatura_id']) && $oldInvoice && $oldInvoice['status'] !== 'paga') {
                $faturasAfetadas[(int) $old['fatura_id']] = true;
                if ($faturaId !== null) {
                    $faturasAfetadas[(int) $faturaId] = true;
                }
            }

            if (
                !empty($old['grupo_parcelamento_id'])
                && (int) ($old['total_parcelas'] ?? 1) > 1
                && $dataTransacao !== $old['data_transacao']
            ) {
                $numeroParcelaAtual = (int) ($old['numero_parcela'] ?? 1);
                $stmtNext = $this->db->prepare("SELECT * FROM `transacoes`
                    WHERE grupo_parcelamento_id = :grupo_id
                      AND usuario_id = :usuario_id
                      AND numero_parcela > :numero_parcela
                      AND excluido_em IS NULL
                    ORDER BY numero_parcela ASC");
                $stmtNext->bindValue(':grupo_id', (string) $old['grupo_parcelamento_id'], PDO::PARAM_STR);
                $stmtNext->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
                $stmtNext->bindValue(':numero_parcela', $numeroParcelaAtual, PDO::PARAM_INT);
                $stmtNext->execute();
                $proximasParcelas = $stmtNext->fetchAll();

                $cardEdit = null;
                $closingDayEdit = 0;
                $currentInvoiceRef = null;
                if (!empty($old['cartao_credito_id'])) {
                    $cardEdit = (new CreditCard())->find((int) $old['cartao_credito_id']);
                    $closingDayEdit = (int) ($cardEdit['dia_fechamento'] ?? 0);
                    $currentInvoiceRef = CreditCardCalendar::invoiceReferenceForPurchase($dataTransacao, $closingDayEdit);
                }

                foreach ($proximasParcelas as $parcela) {
                    $offset = (int) $parcela['numero_parcela'] - $numeroParcelaAtual;

                    if (!empty($parcela['fatura_id']) && !empty($parcela['cartao_credito_id']) && $currentInvoiceRef !== null) {
                        $targetInvoiceRef = CreditCardCalendar::nextInvoiceReference(
                            $currentInvoiceRef['mes'],
                            $currentInvoiceRef['ano'],
                            $offset
                        );
                        $novaDataParcela = CreditCardCalendar::invoiceOpeningDate(
                            $closingDayEdit,
                            $targetInvoiceRef['mes'],
                            $targetInvoiceRef['ano']
                        );

                        $invoiceModel = $invoiceModel ?? new CreditCardInvoice();
                        $oldParcelaInvoice = $invoiceModel->findForUpdate((int) $parcela['fatura_id'], $userId);
                        if (!$oldParcelaInvoice || $oldParcelaInvoice['status'] === 'paga') {
                            continue;
                        }

                        $novaFaturaParcela = $invoiceModel->getOrCreateInvoice(
                            (int) $parcela['cartao_credito_id'],
                            $targetInvoiceRef['mes'],
                            $targetInvoiceRef['ano']
                        );
                        $novaFaturaParcela = $invoiceModel->findForUpdate((int) $novaFaturaParcela['id'], $userId)
                            ?? throw new InvalidArgumentException('A fatura de destino de uma das parcelas não está disponível.');
                        if ($novaFaturaParcela['status'] === 'paga') {
                            throw new InvalidArgumentException('Não é possível mover uma parcela para uma fatura já paga.');
                        }

                        $novoFaturaIdParcela = (int) $novaFaturaParcela['id'];
                        $novoVencimentoParcela = (string) $novaFaturaParcela['data_vencimento'];

                        $stmtUpdateParcela = $this->db->prepare("UPDATE `transacoes` SET
                                data_transacao = :data_transacao,
                                data_vencimento = :data_vencimento,
                                fatura_id = :fatura_id,
                                atualizado_em = NOW()
                            WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NULL");
                        $stmtUpdateParcela->bindValue(':data_transacao', $novaDataParcela, PDO::PARAM_STR);
                        $stmtUpdateParcela->bindValue(':data_vencimento', $novoVencimentoParcela, PDO::PARAM_STR);
                        $stmtUpdateParcela->bindValue(':fatura_id', $novoFaturaIdParcela, PDO::PARAM_INT);
                        $stmtUpdateParcela->bindValue(':id', (int) $parcela['id'], PDO::PARAM_INT);
                        $stmtUpdateParcela->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
                        $stmtUpdateParcela->execute();

                        $faturasAfetadas[(int) $parcela['fatura_id']] = true;
                        $faturasAfetadas[$novoFaturaIdParcela] = true;
                    } else {
                        $novaDataPagamentoParcela = ($parcela['status'] === 'pago' || $parcela['status'] === 'recebido')
                            ? $novaDataParcela
                            : null;

                        $stmtUpdateParcela = $this->db->prepare("UPDATE `transacoes` SET
                                data_transacao = :data_transacao,
                                data_vencimento = :data_vencimento,
                                data_pagamento = :data_pagamento,
                                atualizado_em = NOW()
                            WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NULL");
                        $stmtUpdateParcela->bindValue(':data_transacao', $novaDataParcela, PDO::PARAM_STR);
                        $stmtUpdateParcela->bindValue(':data_vencimento', $novaDataParcela, PDO::PARAM_STR);
                        $stmtUpdateParcela->bindValue(':data_pagamento', $novaDataPagamentoParcela, $novaDataPagamentoParcela !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                        $stmtUpdateParcela->bindValue(':id', (int) $parcela['id'], PDO::PARAM_INT);
                        $stmtUpdateParcela->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
                        $stmtUpdateParcela->execute();
                    }
                }
            }

            // 5. Se tiver faturas vinculadas afetadas, recalcula o total de todas
            if ($invoiceModel !== null && !empty($faturasAfetadas)) {
                foreach (array_keys($faturasAfetadas) as $fatId) {
                    $invoiceModel->recalculateTotal((int) $fatId);
                }
            }

            $this->logAudit('UPDATE', $id, $old, [
                'tipo'           => $tipo,
                'descricao'      => $descricao,
                'valor'          => $valor,
                'data_transacao' => $dataTransacao,
                'data_vencimento'=> $dataVencimento,
                'status'         => $status,
                'conta_id'       => $contaId,
                'fatura_id'      => $faturaId,
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
     * @return array{success: bool, message: string, novo_status?: string}
     */
    public function toggleStatus(int $id, int $userId): array
    {
        $trans = $this->find($id);
        if (!$trans) {
            return ['success' => false, 'message' => 'Lançamento não encontrado.'];
        }

        if ((int) $trans['usuario_id'] !== $userId) {
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
        ], $userId);

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
     * @return array{success: bool, message: string}
     */
    public function deleteTransaction(int $id, int $userId): array
    {
        $trans = $this->find($id);
        if (!$trans) {
            return ['success' => false, 'message' => 'Lançamento não encontrado.'];
        }

        if ((int) $trans['usuario_id'] !== $userId) {
            return ['success' => false, 'message' => 'Você não possui permissão para excluir lançamentos de terceiros.'];
        }

        if (!empty($trans['fatura_id'])) {
            (new CreditCardInvoice())->closeMaturedInvoicesForCard(
                (int) $trans['cartao_credito_id'],
                $userId
            );
        }

        $this->db->beginTransaction();
        try {
            if (!empty($trans['fatura_id'])) {
                $invoice = (new CreditCardInvoice())->findForUpdate((int) $trans['fatura_id'], $userId);
                if ($invoice === null) {
                    throw new InvalidArgumentException('A fatura vinculada à compra não está disponível.');
                }
                if ($invoice['status'] === 'paga') {
                    throw new InvalidArgumentException('Não é possível excluir uma compra vinculada a uma fatura já paga.');
                }
            }

            // Reverte saldo bancário caso estivesse pago/recebido
            if (empty($trans['fatura_id']) && !empty($trans['conta_id'])) {
                $contaId = (int) $trans['conta_id'];
                $valor = (float) $trans['valor'];
                if ($trans['tipo'] === 'receita' && $trans['status'] === 'recebido') {
                    $this->adjustAccountBalance($contaId, -$valor, (int) $trans['usuario_id']);
                } elseif ($trans['tipo'] === 'despesa' && $trans['status'] === 'pago') {
                    $this->adjustAccountBalance($contaId, $valor, (int) $trans['usuario_id']);
                }
            }

            if (!$this->softDelete($id, $userId)) {
                throw new RuntimeException('O lançamento não pôde ser excluído porque foi alterado durante a operação.');
            }

            // Recalcula total da fatura se houver
            if (!empty($trans['fatura_id']) && $invoice['status'] !== 'paga') {
                $invoiceModel = new CreditCardInvoice();
                $invoiceModel->recalculateTotal((int) $trans['fatura_id']);
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Lançamento excluído com sucesso.'];
        } catch (InvalidArgumentException|DomainException $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $this->db->rollBack();
            LoggerHelper::error($e);
            return ['success' => false, 'message' => 'Não foi possível excluir o lançamento. Tente novamente.'];
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

        $params = [];
        $sql .= ' AND t.usuario_id = :usuario_id';
        $params[':usuario_id'] = AuthHelper::id();

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
                WHERE excluido_em IS NULL
                  AND MONTH(data_vencimento) = :mes
                  AND YEAR(data_vencimento) = :ano";

        $sql .= ' AND usuario_id = :usuario_id';

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
                WHERE excluido_em IS NULL";
            $sql .= ' AND usuario_id = :usuario_id';
        $sql .= " AND data_vencimento BETWEEN :data_inicio AND :data_fim
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
                WHERE t.excluido_em IS NULL";
            $sql .= ' AND t.usuario_id = :usuario_id';
        $sql .= "
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
                WHERE t.excluido_em IS NULL";
            $sql .= ' AND t.usuario_id = :usuario_id';
        $sql .= "
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
    * @param int $contaOwnerId
     * @return void
     */
    private function adjustAccountBalance(int $contaId, float $valor, int $contaOwnerId): void
    {
        $sql = "UPDATE `contas` 
                SET saldo_atual = saldo_atual + :valor, atualizado_em = NOW() 
                WHERE id = :conta_id AND usuario_id = :usuario_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':valor', $valor);
        $stmt->bindValue(':conta_id', $contaId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $contaOwnerId, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('Não foi possível ajustar o saldo da conta vinculada.');
        }
    }

    private function accountOwnerId(int $contaId, int $requestUserId): int
    {
        $sql = 'SELECT usuario_id FROM contas WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NULL';
        $statement = $this->db->prepare($sql . ' LIMIT 1');
        $statement->bindValue(':id', $contaId, PDO::PARAM_INT);
        $statement->bindValue(':usuario_id', $requestUserId, PDO::PARAM_INT);
        $statement->execute();
        $ownerId = $statement->fetchColumn();

        if ($ownerId === false) {
            throw new InvalidArgumentException('A conta selecionada não está disponível para este usuário.');
        }

        return (int) $ownerId;
    }

    private function validateExpensePaymentDestination(
        int $paymentMethodId,
        ?int $accountId,
        ?int $creditCardId,
        int $userId
    ): void {
        $paymentMethod = (new PaymentMethod())->getForHistoricalTransaction($paymentMethodId);
        if ($paymentMethod === null) {
            throw new InvalidArgumentException('O meio de pagamento selecionado não está disponível.');
        }

        $isCredit = in_array($paymentMethod['eh_credito'], [true, 1, '1', 't', 'true'], true);
        if ($isCredit) {
            if ($creditCardId === null || $accountId !== null) {
                throw new InvalidArgumentException('Meios de pagamento de crédito exigem a seleção de um cartão.');
            }

            $stmt = $this->db->prepare("SELECT 1 FROM cartoes_credito
                WHERE id = :id AND usuario_id = :usuario_id AND status = 'ativo'
                    AND excluido_em IS NULL LIMIT 1");
            $stmt->execute(['id' => $creditCardId, 'usuario_id' => $userId]);
            if ($stmt->fetchColumn() === false) {
                throw new InvalidArgumentException('O cartão selecionado está inativo ou indisponível para novas compras.');
            }

            return;
        }

        if ($accountId === null || $creditCardId !== null) {
            throw new InvalidArgumentException('Este meio de pagamento exige uma conta bancária e não aceita cartão de crédito.');
        }

        $stmt = $this->db->prepare("SELECT 1 FROM contas
            WHERE id = :id AND usuario_id = :usuario_id AND status = 'ativa'
                AND excluido_em IS NULL LIMIT 1");
        $stmt->execute(['id' => $accountId, 'usuario_id' => $userId]);
        if ($stmt->fetchColumn() === false) {
            throw new InvalidArgumentException('A conta selecionada está inativa ou indisponível para novas despesas.');
        }
    }

    private function validateRelatedOwnership(
        int $userId,
        int $categoriaId,
        int $meioPagamentoId,
        ?int $contaId,
        ?int $cartaoCreditoId,
        ?int $historicalPaymentMethodId = null
    ): void
    {
        $checks = [
            ['categorias', $categoriaId, 'Categoria'],
        ];

        if ($contaId !== null) {
            $checks[] = ['contas', $contaId, 'Conta'];
        }
        if ($cartaoCreditoId !== null) {
            $checks[] = ['cartoes_credito', $cartaoCreditoId, 'Cartão de crédito'];
        }

        foreach ($checks as [$table, $id, $label]) {
            $sql = "SELECT 1 FROM `{$table}` WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NULL";
            $params = ['id' => $id, 'usuario_id' => $userId];
            $stmt = $this->db->prepare($sql . ' LIMIT 1');
            $stmt->execute($params);
            if ($stmt->fetchColumn() === false) {
                throw new InvalidArgumentException("{$label} inválido ou não pertence ao usuário autenticado.");
            }
        }

        $paymentMethod = new PaymentMethod();
        if ($historicalPaymentMethodId === $meioPagamentoId) {
            if ($paymentMethod->getForHistoricalTransaction($meioPagamentoId) === null) {
                throw new InvalidArgumentException('O meio de pagamento histórico não está disponível.');
            }
            return;
        }

        if (!$paymentMethod->isAvailableForUser($meioPagamentoId, $userId)) {
            throw new InvalidArgumentException('O meio de pagamento selecionado não está disponível para novos lançamentos.');
        }
    }
}
