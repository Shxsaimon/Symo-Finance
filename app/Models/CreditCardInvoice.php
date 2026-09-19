<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Model de Faturas de Cartão de Crédito (CreditCardInvoice)
 *
 * Gerencia o ciclo de faturas mensais (aberta, fechada, paga) e liquidação atômica com débito em conta.
 */
class CreditCardInvoice extends Model
{
    protected string $tabela = 'faturas_cartao';
    protected string $chavePrimaria = 'id';

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT f.* FROM faturas_cartao f
            INNER JOIN cartoes_credito c ON c.id = f.cartao_credito_id
            WHERE f.id = :id AND c.usuario_id = :usuario_id LIMIT 1");
        $stmt->execute(['id' => $id, 'usuario_id' => AuthHelper::id()]);
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Obtém ou cria a fatura de um cartão para o mês e ano de referência especificados.
     *
     * @param int $cardId
     * @param int $mesRef (1 a 12)
     * @param int $anoRef (Ex: 2026)
     * @return array<string, mixed>
     */
    public function getOrCreateInvoice(int $cardId, int $mesRef, int $anoRef): array
    {
        $sql = "SELECT * FROM `faturas_cartao`
                                WHERE cartao_credito_id = :card_id
                                    AND EXISTS (SELECT 1 FROM cartoes_credito WHERE id = cartao_credito_id AND usuario_id = :usuario_id)
                                    AND mes_referencia = :mes
                  AND ano_referencia = :ano
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':card_id', $cardId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->bindValue(':mes', $mesRef, PDO::PARAM_INT);
        $stmt->bindValue(':ano', $anoRef, PDO::PARAM_INT);
        $stmt->execute();
        $invoice = $stmt->fetch();

        if ($invoice) {
            return $invoice;
        }

        // Se não existir, busca dados do cartão para calcular as datas
        $cardModel = new CreditCard();
        $card = $cardModel->find($cardId);
        if (!$card) {
            throw new InvalidArgumentException("Cartão de crédito ID {$cardId} não encontrado.");
        }

        $diaFechamento = (int) $card['dia_fechamento'];
        $diaVencimento = (int) $card['dia_vencimento'];

        $dates = $this->calculateInvoiceDates($diaFechamento, $diaVencimento, $mesRef, $anoRef);

        $sqlInsert = "INSERT INTO `faturas_cartao` 
                      (cartao_credito_id, mes_referencia, ano_referencia, data_fechamento, data_vencimento, valor_total, status, conta_pagamento_id, criado_em, atualizado_em)
                      VALUES (:card_id, :mes, :ano, :fechamento, :vencimento, 0.00, 'aberta', :conta_pagamento_id, NOW(), NOW())";

        $stmtInsert = $this->db->prepare($sqlInsert);
        $stmtInsert->bindValue(':card_id', $cardId, PDO::PARAM_INT);
        $stmtInsert->bindValue(':mes', $mesRef, PDO::PARAM_INT);
        $stmtInsert->bindValue(':ano', $anoRef, PDO::PARAM_INT);
        $stmtInsert->bindValue(':fechamento', $dates['data_fechamento']);
        $stmtInsert->bindValue(':vencimento', $dates['data_vencimento']);
        $contaPadraoId = !empty($card['conta_padrao_id']) ? (int) $card['conta_padrao_id'] : null;
        $stmtInsert->bindValue(':conta_pagamento_id', $contaPadraoId, $contaPadraoId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmtInsert->execute();

        $newId = (int) $this->db->lastInsertId();

        return $this->find($newId) ?? [];
    }

    /**
     * Calcula as datas de fechamento e vencimento de uma fatura de forma segura para qualquer mês/ano.
     *
     * @param int $diaFechamento
     * @param int $diaVencimento
     * @param int $mesRef
     * @param int $anoRef
     * @return array{data_fechamento: string, data_vencimento: string}
     */
    public function calculateInvoiceDates(int $diaFechamento, int $diaVencimento, int $mesRef, int $anoRef): array
    {
        // 1. Data de Fechamento (no mês de referência)
        $ultimoDiaMesRef = (int) (new DateTimeImmutable("{$anoRef}-{$mesRef}-01"))->format('t');
        $diaFechReal = min($diaFechamento, $ultimoDiaMesRef);
        $dataFechamento = sprintf('%04d-%02d-%02d', $anoRef, $mesRef, $diaFechReal);

        // 2. Data de Vencimento
        // Se dia do vencimento for menor ou igual ao fechamento, o vencimento ocorre no mês seguinte
        if ($diaVencimento <= $diaFechamento) {
            $mesVenc = $mesRef + 1;
            $anoVenc = $anoRef;
            if ($mesVenc > 12) {
                $mesVenc = 1;
                $anoVenc++;
            }
        } else {
            $mesVenc = $mesRef;
            $anoVenc = $anoRef;
        }

        $ultimoDiaMesVenc = (int) (new DateTimeImmutable("{$anoVenc}-{$mesVenc}-01"))->format('t');
        $diaVencReal = min($diaVencimento, $ultimoDiaMesVenc);
        $dataVencimento = sprintf('%04d-%02d-%02d', $anoVenc, $mesVenc, $diaVencReal);

        return [
            'data_fechamento' => $dataFechamento,
            'data_vencimento' => $dataVencimento,
        ];
    }

    /**
     * Retorna todas as faturas de um cartão com totais calculados e dados de quitação.
     *
     * @param int $cardId
     * @return array<int, array<string, mixed>>
     */
    public function getInvoicesByCard(int $cardId): array
    {
        $sql = "SELECT f.*, cp.nome as conta_pagamento_nome
                FROM `faturas_cartao` f
                LEFT JOIN `contas` cp ON f.conta_pagamento_id = cp.id
                                WHERE f.cartao_credito_id = :card_id
                                    AND EXISTS (SELECT 1 FROM cartoes_credito WHERE id = f.cartao_credito_id AND usuario_id = :usuario_id)
                ORDER BY f.ano_referencia DESC, f.mes_referencia DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':card_id', $cardId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retorna os detalhes de uma fatura com os lançamentos associados.
     *
     * @param int $invoiceId
     * @return array<string, mixed>|null
     */
    public function getInvoiceDetails(int $invoiceId): ?array
    {
        $sql = "SELECT f.*, c.nome as cartao_nome, c.cor as cartao_cor, c.limite_credito as cartao_limite,
                       cp.nome as conta_pagamento_nome
                FROM `faturas_cartao` f
                JOIN `cartoes_credito` c ON f.cartao_credito_id = c.id
                LEFT JOIN `contas` cp ON f.conta_pagamento_id = cp.id
                WHERE f.id = :id AND c.usuario_id = :usuario_id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $invoiceId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        $invoice = $stmt->fetch();

        if (!$invoice) {
            return null;
        }

        // Busca transações vinculadas a esta fatura
        $sqlTrans = "SELECT t.*, cat.nome as categoria_nome, cat.icone as categoria_icone, cat.cor as categoria_cor,
                            u.nome as usuario_nome
                     FROM `transacoes` t
                     LEFT JOIN `categorias` cat ON t.categoria_id = cat.id
                     LEFT JOIN `usuarios` u ON t.usuario_id = u.id
                     WHERE t.fatura_id = :fatura_id AND t.excluido_em IS NULL
                     ORDER BY t.data_transacao DESC, t.id DESC";

        $stmtTrans = $this->db->prepare($sqlTrans);
        $stmtTrans->bindValue(':fatura_id', $invoiceId, PDO::PARAM_INT);
        $stmtTrans->execute();
        $invoice['transacoes'] = $stmtTrans->fetchAll();

        return $invoice;
    }

    /**
     * Recalcula o valor total da fatura com base nas transações ativas associadas.
     *
     * @param int $invoiceId
     * @return float
     */
    public function recalculateTotal(int $invoiceId): float
    {
        $sql = "SELECT COALESCE(SUM(valor), 0) as total
                FROM `transacoes`
                WHERE fatura_id = :fatura_id AND excluido_em IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':fatura_id', $invoiceId, PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch();

        $total = (float) ($res['total'] ?? 0.0);

        $sqlUpdate = "UPDATE `faturas_cartao` SET valor_total = :total, atualizado_em = NOW() WHERE id = :id";
        $stmtUp = $this->db->prepare($sqlUpdate);
        $stmtUp->bindValue(':total', $total);
        $stmtUp->bindValue(':id', $invoiceId, PDO::PARAM_INT);
        $stmtUp->execute();

        return $total;
    }

    /**
     * Fecha manualmente uma fatura aberta.
     *
     * @param int $invoiceId
     * @param int $userId
     * @return bool
     */
    public function closeInvoice(int $invoiceId, int $userId): bool
    {
        $invoice = $this->find($invoiceId);
        if (!$invoice || $invoice['status'] !== 'aberta') {
            return false;
        }

        // Recalcula total antes de fechar
        $this->recalculateTotal($invoiceId);

        $sql = "UPDATE `faturas_cartao` SET status = 'fechada', atualizado_em = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $invoiceId, PDO::PARAM_INT);
        $success = $stmt->execute();

        if ($success) {
            $this->logAudit('UPDATE', $invoiceId, $invoice, ['status' => 'fechada'], $userId);
        }

        return $success;
    }

    /**
     * Reabre uma fatura fechada para inserção/alteração de despesas.
     *
     * @param int $invoiceId
     * @param int $userId
     * @return bool
     */
    public function reopenInvoice(int $invoiceId, int $userId): bool
    {
        $invoice = $this->find($invoiceId);
        if (!$invoice || $invoice['status'] !== 'fechada') {
            return false;
        }

        $sql = "UPDATE `faturas_cartao` SET status = 'aberta', atualizado_em = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $invoiceId, PDO::PARAM_INT);
        $success = $stmt->execute();

        if ($success) {
            $this->logAudit('UPDATE', $invoiceId, $invoice, ['status' => 'aberta'], $userId);
        }

        return $success;
    }

    /**
     * Efetua o pagamento consolidado de uma fatura sob transação atômica.
     *
     * @param int $invoiceId
     * @param int $contaPagamentoId
     * @param string|null $dataPagamento
     * @param int $userId
     * @return array{success: bool, message: string, novo_saldo?: float}
     */
    public function payInvoice(int $invoiceId, int $contaPagamentoId, ?string $dataPagamento, int $userId): array
    {
        $invoice = $this->find($invoiceId);
        if (!$invoice) {
            return ['success' => false, 'message' => 'Fatura não encontrada.'];
        }

        if ($invoice['status'] === 'paga') {
            return ['success' => false, 'message' => 'Esta fatura já se encontra liquidada e paga.'];
        }

        $valorFatura = $this->recalculateTotal($invoiceId);
        $dataPagtoReal = !empty($dataPagamento) ? $dataPagamento : date('Y-m-d H:i:s');

        // Busca dados da conta bancária de débito
        $accountModel = new Account();
        $conta = $accountModel->find($contaPagamentoId);
        if (!$conta) {
            return ['success' => false, 'message' => 'Conta bancária para pagamento não encontrada.'];
        }

        if ($conta['status'] !== 'ativa') {
            return ['success' => false, 'message' => 'A conta bancária selecionada está inativa.'];
        }

        // INÍCIO DA TRANSAÇÃO ATÔMICA
        $this->db->beginTransaction();
        try {
            // 1. Debita o valor total da fatura no saldo da conta corrente
            $sqlConta = "UPDATE `contas` 
                         SET saldo_atual = saldo_atual - :valor, atualizado_em = NOW() 
                         WHERE id = :conta_id";
            $stmtConta = $this->db->prepare($sqlConta);
            $stmtConta->bindValue(':valor', $valorFatura);
            $stmtConta->bindValue(':conta_id', $contaPagamentoId, PDO::PARAM_INT);
            $stmtConta->execute();

            // 2. Atualiza a fatura para 'paga'
            $sqlFatura = "UPDATE `faturas_cartao`
                          SET status = 'paga', pago_em = :pago_em, conta_pagamento_id = :conta_id, atualizado_em = NOW()
                          WHERE id = :fatura_id";
            $stmtFatura = $this->db->prepare($sqlFatura);
            $stmtFatura->bindValue(':pago_em', $dataPagtoReal);
            $stmtFatura->bindValue(':conta_id', $contaPagamentoId, PDO::PARAM_INT);
            $stmtFatura->bindValue(':fatura_id', $invoiceId, PDO::PARAM_INT);
            $stmtFatura->execute();

            // 3. Atualiza as transações vinculadas para status 'pago' e data de pagamento
            $sqlTrans = "UPDATE `transacoes`
                         SET status = 'pago', data_pagamento = :data_pagto, conta_id = :conta_id, atualizado_em = NOW()
                         WHERE fatura_id = :fatura_id AND excluido_em IS NULL";
            $stmtTrans = $this->db->prepare($sqlTrans);
            $stmtTrans->bindValue(':data_pagto', substr($dataPagtoReal, 0, 10));
            $stmtTrans->bindValue(':conta_id', $contaPagamentoId, PDO::PARAM_INT);
            $stmtTrans->bindValue(':fatura_id', $invoiceId, PDO::PARAM_INT);
            $stmtTrans->execute();

            // 4. Registra auditoria
            $this->logAudit('UPDATE', $invoiceId, $invoice, [
                'status'             => 'paga',
                'pago_em'            => $dataPagtoReal,
                'conta_pagamento_id' => $contaPagamentoId,
                'valor_pago'         => $valorFatura,
            ], $userId);

            $this->db->commit();

            // Busca saldo atualizado
            $contaAtualizada = $accountModel->find($contaPagamentoId);
            $novoSaldo = (float) ($contaAtualizada['saldo_atual'] ?? 0.0);

            return [
                'success'    => true,
                'message'    => 'Fatura paga com sucesso! O valor foi debitado da conta bancária e o limite do cartão foi restabelecido.',
                'novo_saldo' => $novoSaldo,
            ];
        } catch (Throwable $e) {
            $this->db->rollBack();
            LoggerHelper::error($e);
            return [
                'success' => false,
                'message' => 'Ocorreu uma falha ao processar a liquidação da fatura: ' . $e->getMessage(),
            ];
        }
    }
}
