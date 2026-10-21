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
     * Busca uma fatura do usuário e bloqueia sua linha até o fim da transação.
     *
     * @return array<string, mixed>|null
     */
    public function findForUpdate(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT f.* FROM faturas_cartao f
            INNER JOIN cartoes_credito c ON c.id = f.cartao_credito_id
            WHERE f.id = :id AND c.usuario_id = :usuario_id
            LIMIT 1 FOR UPDATE");
        $stmt->execute(['id' => $id, 'usuario_id' => $userId]);
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
        if ($mesRef < 1 || $mesRef > 12 || $anoRef < 1) {
            throw new InvalidArgumentException('Mês ou ano de referência da fatura inválido.');
        }

        $this->closeMaturedInvoicesForCard($cardId, (int) AuthHelper::id());
        $sql = "SELECT f.* FROM `faturas_cartao` f
                WHERE f.cartao_credito_id = :card_id
                    AND EXISTS (SELECT 1 FROM cartoes_credito c WHERE c.id = f.cartao_credito_id AND c.usuario_id = :usuario_id)
                    AND f.mes_referencia = :mes
                    AND f.ano_referencia = :ano
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
        $this->closeMaturedInvoicesForCard($cardId, (int) AuthHelper::id());

        return $this->find($newId) ?? [];
    }

    /**
     * Resolve o ciclo de fatura a partir da data efetiva da compra e do fechamento do cartão.
     *
     * @return array<string, mixed>
     */
    public function getOrCreateInvoiceForPurchase(int $cardId, string $purchaseDate): array
    {
        $card = (new CreditCard())->find($cardId);
        if (!$card) {
            throw new InvalidArgumentException('Cartão de crédito não encontrado.');
        }

        $reference = CreditCardCalendar::invoiceReferenceForPurchase(
            $purchaseDate,
            (int) $card['dia_fechamento']
        );

        return $this->getOrCreateInvoice($cardId, $reference['mes'], $reference['ano']);
    }

    /**
     * Fecha faturas abertas cujo dia de fechamento já passou. Chamado sob demanda no runtime serverless.
     */
    public function closeMaturedInvoicesForCard(int $cardId, int $userId): void
    {
        $stmt = $this->db->prepare("SELECT f.* FROM faturas_cartao f
            INNER JOIN cartoes_credito c ON c.id = f.cartao_credito_id
            WHERE f.cartao_credito_id = :card_id AND c.usuario_id = :usuario_id
                AND f.status = 'aberta' AND f.data_fechamento <= :hoje");
        $stmt->execute([
            'card_id' => $cardId,
            'usuario_id' => $userId,
            'hoje' => date('Y-m-d'),
        ]);
        $maturedInvoices = $stmt->fetchAll();
        if ($maturedInvoices === []) {
            return;
        }

        $update = $this->db->prepare("UPDATE faturas_cartao
            SET status = 'fechada', atualizado_em = NOW()
            WHERE id = :id AND status = 'aberta'
                AND EXISTS (SELECT 1 FROM cartoes_credito c
                    WHERE c.id = faturas_cartao.cartao_credito_id AND c.usuario_id = :usuario_id)");

        foreach ($maturedInvoices as $invoice) {
            $update->execute(['id' => $invoice['id'], 'usuario_id' => $userId]);
            if ($update->rowCount() === 1) {
                $this->recalculateTotal((int) $invoice['id']);
                $this->logAudit('UPDATE', (int) $invoice['id'], $invoice, ['status' => 'fechada'], $userId);
            }
        }
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
        return CreditCardCalendar::invoiceDates(
            $diaFechamento,
            $diaVencimento,
            $mesRef,
            $anoRef
        );
    }

    /**
     * Retorna todas as faturas de um cartão com totais calculados e dados de quitação.
     *
     * @param int $cardId
     * @return array<int, array<string, mixed>>
     */
    public function getInvoicesByCard(int $cardId): array
    {
        $this->closeMaturedInvoicesForCard($cardId, (int) AuthHelper::id());
        $sql = "SELECT f.*, cp.nome as conta_pagamento_nome
                FROM `faturas_cartao` f
                LEFT JOIN `contas` cp ON f.conta_pagamento_id = cp.id AND cp.usuario_id = :conta_usuario_id
                WHERE f.cartao_credito_id = :card_id
                    AND EXISTS (SELECT 1 FROM cartoes_credito WHERE id = f.cartao_credito_id AND usuario_id = :usuario_id)
                ORDER BY f.ano_referencia DESC, f.mes_referencia DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':card_id', $cardId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->bindValue(':conta_usuario_id', AuthHelper::id(), PDO::PARAM_INT);
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
        $invoiceBeforeSync = $this->find($invoiceId);
        if ($invoiceBeforeSync !== null) {
            $this->closeMaturedInvoicesForCard(
                (int) $invoiceBeforeSync['cartao_credito_id'],
                (int) AuthHelper::id()
            );
        }

        $sql = "SELECT f.*, c.nome as cartao_nome, c.cor as cartao_cor, c.limite_credito as cartao_limite,
                       cp.nome as conta_pagamento_nome
                FROM `faturas_cartao` f
                JOIN `cartoes_credito` c ON f.cartao_credito_id = c.id
                LEFT JOIN `contas` cp ON f.conta_pagamento_id = cp.id AND cp.usuario_id = :conta_usuario_id
                WHERE f.id = :id AND c.usuario_id = :usuario_id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $invoiceId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->bindValue(':conta_usuario_id', AuthHelper::id(), PDO::PARAM_INT);
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
                     WHERE t.fatura_id = :fatura_id AND t.usuario_id = :usuario_id AND t.excluido_em IS NULL
                     ORDER BY t.data_transacao DESC, t.id DESC";

        $stmtTrans = $this->db->prepare($sqlTrans);
        $stmtTrans->bindValue(':fatura_id', $invoiceId, PDO::PARAM_INT);
        $stmtTrans->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
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
        $userId = (int) AuthHelper::id();
        $sql = "SELECT COALESCE(SUM(t.valor), 0) as total
                FROM `transacoes` t
                WHERE t.fatura_id = :fatura_id AND t.usuario_id = :usuario_id AND t.excluido_em IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':fatura_id', $invoiceId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch();

        $total = (float) ($res['total'] ?? 0.0);

        $sqlUpdate = "UPDATE `faturas_cartao` SET valor_total = :total, atualizado_em = NOW()
                      WHERE id = :id
                        AND EXISTS (SELECT 1 FROM cartoes_credito c
                            WHERE c.id = faturas_cartao.cartao_credito_id AND c.usuario_id = :usuario_id)";
        $stmtUp = $this->db->prepare($sqlUpdate);
        $stmtUp->bindValue(':total', $total);
        $stmtUp->bindValue(':id', $invoiceId, PDO::PARAM_INT);
        $stmtUp->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmtUp->execute();

        return $total;
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
        $this->closeMaturedInvoicesForCard(
            (int) (($this->find($invoiceId)['cartao_credito_id'] ?? 0)),
            $userId
        );

        $accountModel = new Account();
        $dataPagtoReal = !empty($dataPagamento) ? $dataPagamento : date('Y-m-d H:i:s');
        $this->db->beginTransaction();
        try {
            $stmtInvoice = $this->db->prepare("SELECT f.* FROM faturas_cartao f
                INNER JOIN cartoes_credito c ON c.id = f.cartao_credito_id
                WHERE f.id = :id AND c.usuario_id = :usuario_id
                LIMIT 1 FOR UPDATE");
            $stmtInvoice->execute(['id' => $invoiceId, 'usuario_id' => $userId]);
            $invoice = $stmtInvoice->fetch();
            if (!$invoice) {
                throw new InvalidArgumentException('Fatura não encontrada.');
            }
            if ($invoice['status'] !== 'fechada') {
                throw new InvalidArgumentException(
                    $invoice['status'] === 'paga'
                        ? 'Esta fatura já se encontra liquidada e paga.'
                        : 'A fatura ainda não foi fechada e não pode ser paga.'
                );
            }

            $valorFatura = $this->recalculateTotal($invoiceId);
            if ($valorFatura <= 0) {
                throw new InvalidArgumentException('Não é possível pagar uma fatura sem saldo devido.');
            }

            $conta = $accountModel->find($contaPagamentoId);
            if (!$conta || $conta['status'] !== 'ativa') {
                throw new InvalidArgumentException('A conta bancária selecionada está inativa ou indisponível.');
            }

            $sqlConta = "UPDATE `contas`
                         SET saldo_atual = saldo_atual - :valor, atualizado_em = NOW()
                         WHERE id = :conta_id AND usuario_id = :usuario_id
                            AND status = 'ativa' AND saldo_atual >= :saldo_minimo";
            $stmtConta = $this->db->prepare($sqlConta);
            $stmtConta->bindValue(':valor', $valorFatura);
            $stmtConta->bindValue(':conta_id', $contaPagamentoId, PDO::PARAM_INT);
            $stmtConta->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmtConta->bindValue(':saldo_minimo', $valorFatura);
            $stmtConta->execute();
            if ($stmtConta->rowCount() !== 1) {
                throw new InvalidArgumentException('Saldo insuficiente na conta selecionada para pagar a fatura.');
            }

            $sqlFatura = "UPDATE `faturas_cartao`
                          SET status = 'paga', pago_em = :pago_em, conta_pagamento_id = :conta_id, atualizado_em = NOW()
                          WHERE id = :fatura_id AND status = 'fechada'";
            $stmtFatura = $this->db->prepare($sqlFatura);
            $stmtFatura->bindValue(':pago_em', $dataPagtoReal);
            $stmtFatura->bindValue(':conta_id', $contaPagamentoId, PDO::PARAM_INT);
            $stmtFatura->bindValue(':fatura_id', $invoiceId, PDO::PARAM_INT);
            $stmtFatura->execute();
            if ($stmtFatura->rowCount() !== 1) {
                throw new RuntimeException('A fatura não pôde ser marcada como paga.');
            }

            $sqlTrans = "UPDATE `transacoes`
                         SET status = 'pago', data_pagamento = :data_pagto, atualizado_em = NOW()
                         WHERE fatura_id = :fatura_id AND usuario_id = :usuario_id AND excluido_em IS NULL";
            $stmtTrans = $this->db->prepare($sqlTrans);
            $stmtTrans->bindValue(':data_pagto', substr($dataPagtoReal, 0, 10));
            $stmtTrans->bindValue(':fatura_id', $invoiceId, PDO::PARAM_INT);
            $stmtTrans->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmtTrans->execute();

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
                'success' => true,
                'message' => 'Fatura paga com sucesso! O valor foi debitado da conta bancária e o limite do cartão foi restabelecido.',
                'novo_saldo' => $novoSaldo,
            ];
        } catch (InvalidArgumentException|DomainException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            LoggerHelper::error($e);
            return [
                'success' => false,
                'message' => 'Não foi possível processar o pagamento da fatura. Tente novamente.',
            ];
        }
    }
}
