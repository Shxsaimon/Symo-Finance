<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Model de Lixeira e Recuperação de Dados (Trash)
 *
 * Gerencia a recuperação e o expurgo definitivo de registros com Soft Delete
 * (Transações, Contas Bancárias e Categorias), recalculando saldos na restauração
 * e garantindo consistência financeira e trilha de auditoria.
 */
class Trash extends Model
{
    /**
     * Retorna a quantidade de itens na lixeira para cada entidade e o total.
     *
     * @return array{transacoes: int, contas: int, categorias: int, investimentos: int, movimentos_investimento: int, total: int}
     */
    public function getCounts(): array
    {
        $scope = $this->ownerClause('usuario_id');
        $sqlT = "SELECT COUNT(*) FROM `transacoes` WHERE {$scope} AND excluido_em IS NOT NULL";
        $sqlC = "SELECT COUNT(*) FROM `contas` WHERE {$scope} AND excluido_em IS NOT NULL";
        $sqlCat = "SELECT COUNT(*) FROM `categorias` WHERE {$scope} AND excluido_em IS NOT NULL";
        $sqlI = "SELECT COUNT(*) FROM `investimentos` WHERE {$scope} AND excluido_em IS NOT NULL";
        $sqlM = "SELECT COUNT(*) FROM `investimento_movimentos` m
                 INNER JOIN `investimentos` i ON i.id = m.investimento_id
                 WHERE {$this->ownerClause('m.usuario_id')}
                   AND m.excluido_em IS NOT NULL
                   AND i.excluido_em IS NULL";

        $stmtTransacoes = $this->db->prepare($sqlT);
        $this->bindOwner($stmtTransacoes);
        $stmtTransacoes->execute();
        $transacoes = (int) $stmtTransacoes->fetchColumn();

        $stmtContas = $this->db->prepare($sqlC);
        $this->bindOwner($stmtContas);
        $stmtContas->execute();
        $contas = (int) $stmtContas->fetchColumn();

        $stmtCategorias = $this->db->prepare($sqlCat);
        $this->bindOwner($stmtCategorias);
        $stmtCategorias->execute();
        $categorias = (int) $stmtCategorias->fetchColumn();

        $stmtInvestimentos = $this->db->prepare($sqlI);
        $this->bindOwner($stmtInvestimentos);
        $stmtInvestimentos->execute();
        $investimentos = (int) $stmtInvestimentos->fetchColumn();

        $stmtMovimentos = $this->db->prepare($sqlM);
        $this->bindOwner($stmtMovimentos, 'm.usuario_id');
        $stmtMovimentos->execute();
        $movimentos = (int) $stmtMovimentos->fetchColumn();

        return [
            'transacoes'              => $transacoes,
            'contas'                  => $contas,
            'categorias'              => $categorias,
            'investimentos'           => $investimentos,
            'movimentos_investimento' => $movimentos,
            'total'                   => $transacoes + $contas + $categorias + $investimentos + $movimentos,
        ];
    }

    /**
     * Retorna a listagem de transações excluídas com dados contextuais.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDeletedTransactions(): array
    {
        $sql = "SELECT t.*, 
                       c.nome as conta_nome, c.cor as conta_cor,
                       cat.nome as categoria_nome, cat.icone as categoria_icone, cat.cor as categoria_cor,
                       cc.nome as cartao_nome,
                       u.nome as usuario_nome,
                       ue.nome as excluido_por_nome
                FROM `transacoes` t
                LEFT JOIN `contas` c ON t.conta_id = c.id
                LEFT JOIN `categorias` cat ON t.categoria_id = cat.id
                LEFT JOIN `cartoes_credito` cc ON t.cartao_credito_id = cc.id
                LEFT JOIN `usuarios` u ON t.usuario_id = u.id
                LEFT JOIN `usuarios` ue ON t.excluido_por = ue.id
                WHERE {$this->ownerClause('t.usuario_id')} AND t.excluido_em IS NOT NULL
                ORDER BY t.excluido_em DESC";

        $stmt = $this->db->prepare($sql);
        $this->bindOwner($stmt, 't.usuario_id');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retorna a listagem de contas bancárias excluídas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDeletedAccounts(): array
    {
        $sql = "SELECT c.*, ue.nome as excluido_por_nome
                FROM `contas` c
                LEFT JOIN `usuarios` ue ON c.excluido_por = ue.id
                WHERE {$this->ownerClause('c.usuario_id')} AND c.excluido_em IS NOT NULL
                ORDER BY c.excluido_em DESC";

        $stmt = $this->db->prepare($sql);
        $this->bindOwner($stmt, 'c.usuario_id');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retorna a listagem de categorias excluídas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDeletedCategories(): array
    {
        $sql = "SELECT cat.*, ue.nome as excluido_por_nome
                FROM `categorias` cat
                LEFT JOIN `usuarios` ue ON cat.excluido_por = ue.id
                WHERE {$this->ownerClause('cat.usuario_id')} AND cat.excluido_em IS NOT NULL
                ORDER BY cat.excluido_em DESC";

        $stmt = $this->db->prepare($sql);
        $this->bindOwner($stmt, 'cat.usuario_id');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retorna a listagem de posições de investimento excluídas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDeletedInvestments(): array
    {
        $sql = "SELECT i.*, c.nome as conta_nome, ue.nome as excluido_por_nome,
                       (SELECT COUNT(*) FROM `investimento_movimentos` m WHERE m.investimento_id = i.id) as total_movimentos
                FROM `investimentos` i
                LEFT JOIN `contas` c ON i.conta_id = c.id
                LEFT JOIN `usuarios` ue ON i.excluido_por = ue.id
                WHERE {$this->ownerClause('i.usuario_id')} AND i.excluido_em IS NOT NULL
                ORDER BY i.excluido_em DESC";

        $stmt = $this->db->prepare($sql);
        $this->bindOwner($stmt, 'i.usuario_id');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retorna movimentações  de investimentos excluídas cujo investimento pai está ativo.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDeletedInvestmentMovements(): array
    {
        $sql = "SELECT m.*, i.nome as investimento_nome, c.nome as conta_nome, ue.nome as excluido_por_nome
                FROM `investimento_movimentos` m
                INNER JOIN `investimentos` i ON m.investimento_id = i.id
                LEFT JOIN `contas` c ON m.conta_id = c.id
                LEFT JOIN `usuarios` ue ON m.excluido_por = ue.id
                WHERE {$this->ownerClause('m.usuario_id')}
                  AND m.excluido_em IS NOT NULL
                  AND i.excluido_em IS NULL
                ORDER BY m.excluido_em DESC";

        $stmt = $this->db->prepare($sql);
        $this->bindOwner($stmt, 'm.usuario_id');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Restaura um item da lixeira com reprocessamento de saldo quando aplicável.
     *
     * @param string $entity 'transacao', 'conta', 'categoria', 'investimento' ou 'movimento_investimento'
     * @param int $id
     * @param int $currentUserId
     * @return array{success: bool, message: string}
     */
    public function restore(string $entity, int $id, int $currentUserId): array
    {
        $tables = [
            'transacao'              => 'transacoes',
            'transacoes'             => 'transacoes',
            'conta'                  => 'contas',
            'contas'                 => 'contas',
            'categoria'              => 'categorias',
            'categorias'             => 'categorias',
            'investimento'           => 'investimentos',
            'investimentos'          => 'investimentos',
            'investimento_movimento' => 'investimento_movimentos',
            'movimento_investimento' => 'investimento_movimentos',
            'investimento_movimentos'=> 'investimento_movimentos',
        ];

        if (!isset($tables[$entity])) {
            return ['success' => false, 'message' => 'Tipo de entidade inválido para restauração.'];
        }

        $this->tabela = $tables[$entity];

        return match ($entity) {
            'transacao', 'transacoes'                                           => $this->restoreTransaction($id, $currentUserId),
            'conta', 'contas'                                                   => $this->restoreAccount($id, $currentUserId),
            'categoria', 'categorias'                                           => $this->restoreCategory($id, $currentUserId),
            'investimento', 'investimentos'                                     => $this->restoreInvestment($id, $currentUserId),
            'investimento_movimento', 'movimento_investimento', 'investimento_movimentos' => $this->restoreInvestmentMovement($id, $currentUserId),
        };
    }

    /**
     * Exclui definitivamente um item da lixeira (Hard Delete).
     *
     * @param string $entity 'transacao', 'conta', 'categoria', 'investimento' ou 'movimento_investimento'
     * @param int $id
     * @param int $currentUserId
     * @return array{success: bool, message: string}
     */
    public function purge(string $entity, int $id, int $currentUserId): array
    {
        return match ($entity) {
            'transacao', 'transacoes'                                           => $this->purgeRecord('transacoes', $id, $currentUserId),
            'conta', 'contas'                                                   => $this->purgeRecord('contas', $id, $currentUserId),
            'categoria', 'categorias'                                           => $this->purgeRecord('categorias', $id, $currentUserId),
            'investimento', 'investimentos'                                     => $this->purgeInvestment($id, $currentUserId),
            'investimento_movimento', 'movimento_investimento', 'investimento_movimentos' => $this->purgeRecord('investimento_movimentos', $id, $currentUserId),
            default                                                             => ['success' => false, 'message' => 'Tipo de entidade inválido para expurgo.'],
        };
    }

    /**
     * Restaura uma transação excluída e reaplica os impactos no saldo bancário.
     *
     * @param int $id
     * @param int $currentUserId
     * @return array{success: bool, message: string}
     */
    private function restoreTransaction(int $id, int $currentUserId): array
    {
        $sql = "SELECT * FROM `transacoes` WHERE id = :id AND {$this->ownerClause('usuario_id')} AND excluido_em IS NOT NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindOwner($stmt);
        $stmt->execute();
        $transacao = $stmt->fetch();

        if (!$transacao) {
            return ['success' => false, 'message' => 'Transação não encontrada na lixeira.'];
        }

        if (!empty($transacao['fatura_id'])) {
            (new CreditCardInvoice())->closeMaturedInvoicesForCard(
                (int) $transacao['cartao_credito_id'],
                $currentUserId
            );
        }

        $this->beginTransaction();

        try {
            $invoice = null;
            if (!empty($transacao['fatura_id'])) {
                $invoice = (new CreditCardInvoice())->findForUpdate(
                    (int) $transacao['fatura_id'],
                    $currentUserId
                );
                if ($invoice === null) {
                    throw new InvalidArgumentException('A fatura vinculada à compra não está disponível.');
                }
                if ($invoice['status'] === 'paga') {
                    if (!empty($invoice['conta_pagamento_id'])) {
                        throw new InvalidArgumentException('Não é possível restaurar uma compra vinculada a uma fatura já paga.');
                    }
                    // Fatura foi quitada automaticamente por estar sem lançamentos: reabre ou fecha conforme a data de fechamento
                    $novoStatus = ($invoice['data_fechamento'] <= date('Y-m-d')) ? 'fechada' : 'aberta';
                    $stmtReabrir = $this->db->prepare("UPDATE faturas_cartao SET status = :status, pago_em = NULL, atualizado_em = NOW() WHERE id = :id");
                    $stmtReabrir->execute(['status' => $novoStatus, 'id' => $invoice['id']]);
                }
            }

            // 1. Limpa as flags de exclusão
            $sqlUp = "UPDATE `transacoes` SET excluido_em = NULL, excluido_por = NULL, atualizado_em = NOW() WHERE id = :id AND {$this->ownerClause('usuario_id')}";
            $stmtUp = $this->db->prepare($sqlUp);
            $stmtUp->bindValue(':id', $id, PDO::PARAM_INT);
            $this->bindOwner($stmtUp);
            $stmtUp->execute();

            // 2. Reaplica saldo na conta bancária caso esteja liquidada
            $status = (string) $transacao['status'];
            $contaId = !empty($transacao['conta_id']) ? (int) $transacao['conta_id'] : null;
            $valor = (float) $transacao['valor'];

            if (empty($transacao['fatura_id']) && $contaId !== null && in_array($status, ['pago', 'recebido'], true)) {
                $ajuste = ($transacao['tipo'] === 'receita') ? $valor : -$valor;
                $sqlSaldo = "UPDATE `contas` SET saldo_atual = saldo_atual + :ajuste, atualizado_em = NOW() WHERE id = :conta_id AND usuario_id = :usuario_id";
                $stmtSaldo = $this->db->prepare($sqlSaldo);
                $stmtSaldo->bindValue(':ajuste', $ajuste, PDO::PARAM_STR);
                $stmtSaldo->bindValue(':conta_id', $contaId, PDO::PARAM_INT);
                $stmtSaldo->bindValue(':usuario_id', (int) $transacao['usuario_id'], PDO::PARAM_INT);
                $stmtSaldo->execute();
                if ($stmtSaldo->rowCount() !== 1) {
                    throw new RuntimeException('A conta vinculada não pertence ao usuário da transação.');
                }
            }

            if ($invoice !== null) {
                (new CreditCardInvoice())->recalculateTotal((int) $invoice['id']);
            }

            // 3. Log de Auditoria
            $this->logAudit('RESTORE', $id, ['excluido_em' => $transacao['excluido_em']], ['excluido_em' => null], $currentUserId);

            $this->commit();
            return ['success' => true, 'message' => 'Transação restaurada com sucesso e saldo reprocessado.'];
        } catch (InvalidArgumentException|DomainException $e) {
            $this->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $this->rollBack();
            LoggerHelper::error($e, ['context' => 'Trash::restoreTransaction', 'id' => $id]);
            return ['success' => false, 'message' => 'Não foi possível restaurar a transação. Tente novamente.'];
        }
    }

    /**
     * Restaura uma conta bancária excluída.
     *
     * @param int $id
     * @param int $currentUserId
     * @return array{success: bool, message: string}
     */
    private function restoreAccount(int $id, int $currentUserId): array
    {
        $sql = "SELECT * FROM `contas` WHERE id = :id AND {$this->ownerClause('usuario_id')} AND excluido_em IS NOT NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindOwner($stmt);
        $stmt->execute();
        $conta = $stmt->fetch();

        if (!$conta) {
            return ['success' => false, 'message' => 'Conta bancária não encontrada na lixeira.'];
        }

        $sqlUp = "UPDATE `contas` SET excluido_em = NULL, excluido_por = NULL, atualizado_em = NOW() WHERE id = :id AND {$this->ownerClause('usuario_id')}";
        $stmtUp = $this->db->prepare($sqlUp);
        $stmtUp->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindOwner($stmtUp);
        $stmtUp->execute();

        $this->logAudit('RESTORE', $id, ['excluido_em' => $conta['excluido_em']], ['excluido_em' => null], $currentUserId);

        return ['success' => true, 'message' => 'Conta bancária restaurada com sucesso.'];
    }

    /**
     * Restaura uma categoria excluída.
     *
     * @param int $id
     * @param int $currentUserId
     * @return array{success: bool, message: string}
     */
    private function restoreCategory(int $id, int $currentUserId): array
    {
        $sql = "SELECT * FROM `categorias` WHERE id = :id AND {$this->ownerClause('usuario_id')} AND excluido_em IS NOT NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindOwner($stmt);
        $stmt->execute();
        $categoria = $stmt->fetch();

        if (!$categoria) {
            return ['success' => false, 'message' => 'Categoria não encontrada na lixeira.'];
        }

        $sqlUp = "UPDATE `categorias` SET excluido_em = NULL, excluido_por = NULL, atualizado_em = NOW() WHERE id = :id AND {$this->ownerClause('usuario_id')}";
        $stmtUp = $this->db->prepare($sqlUp);
        $stmtUp->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindOwner($stmtUp);
        $stmtUp->execute();

        $this->logAudit('RESTORE', $id, ['excluido_em' => $categoria['excluido_em']], ['excluido_em' => null], $currentUserId);

        return ['success' => true, 'message' => 'Categoria restaurada com sucesso.'];
    }

    /**
     * Restaura uma posição de investimento e reativa suas movimentações que foram excluídas junto com ela.
     *
     * @param int $id
     * @param int $currentUserId
     * @return array{success: bool, message: string}
     */
    private function restoreInvestment(int $id, int $currentUserId): array
    {
        $this->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM `investimentos` WHERE id = :id AND {$this->ownerClause('usuario_id')} AND excluido_em IS NOT NULL FOR UPDATE"
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $this->bindOwner($stmt);
            $stmt->execute();
            $inv = $stmt->fetch();
            if (!$inv) {
                throw new InvalidArgumentException('Investimento não encontrado ou não está na lixeira.');
            }

            // Verifica se a conta vinculada ainda existe e está ativa
            $contaId = (int) $inv['conta_id'];
            $stmtConta = $this->db->prepare(
                "SELECT * FROM `contas` WHERE id = :conta_id AND {$this->ownerClause('usuario_id')} AND excluido_em IS NULL"
            );
            $stmtConta->bindValue(':conta_id', $contaId, PDO::PARAM_INT);
            $this->bindOwner($stmtConta);
            $stmtConta->execute();
            if (!$stmtConta->fetch()) {
                throw new InvalidArgumentException('A conta bancária vinculada ao investimento está excluída ou indisponível. Restaure-a antes.');
            }

            $excluidoEmInvestimento = $inv['excluido_em'];

            // Restaura o investimento
            $stmtUp = $this->db->prepare(
                "UPDATE `investimentos` SET excluido_em = NULL, excluido_por = NULL, atualizado_em = NOW() WHERE id = :id AND {$this->ownerClause('usuario_id')}"
            );
            $stmtUp->bindValue(':id', $id, PDO::PARAM_INT);
            $this->bindOwner($stmtUp);
            $stmtUp->execute();

            // Restaura em cascata os movimentos que foram excluídos junto com o investimento (mesmo excluido_em)
            $stmtUpMov = $this->db->prepare(
                "UPDATE `investimento_movimentos` SET excluido_em = NULL, excluido_por = NULL, atualizado_em = NOW()
                 WHERE investimento_id = :id AND {$this->ownerClause('usuario_id')} AND excluido_em = :excluido_em"
            );
            $stmtUpMov->bindValue(':id', $id, PDO::PARAM_INT);
            $this->bindOwner($stmtUpMov);
            $stmtUpMov->bindValue(':excluido_em', $excluidoEmInvestimento, PDO::PARAM_STR);
            $stmtUpMov->execute();

            // Recalcula o saldo do investimento
            (new Investment())->recalculate($id, $currentUserId);

            $this->logAudit('RESTORE', $id, ['excluido_em' => $inv['excluido_em']], ['excluido_em' => null], $currentUserId);

            $this->commit();
            return ['success' => true, 'message' => "Investimento '{$inv['nome']}' e suas movimentações ativas foram restaurados com sucesso."];
        } catch (Throwable $e) {
            $this->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Expurgar definitivamente um investimento e todos os seus movimentos em cascata.
     *
     * @param int $id
     * @param int $currentUserId
     * @return array{success: bool, message: string}
     */
    private function purgeInvestment(int $id, int $currentUserId): array
    {
        $this->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM `investimentos` WHERE id = :id AND {$this->ownerClause('usuario_id')} AND excluido_em IS NOT NULL FOR UPDATE"
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $this->bindOwner($stmt);
            $stmt->execute();
            $inv = $stmt->fetch();
            if (!$inv) {
                throw new InvalidArgumentException('Investimento não encontrado ou não está na lixeira.');
            }

            // Exclui todos os movimentos vinculados (em cascata)
            $stmtDelMov = $this->db->prepare(
                "DELETE FROM `investimento_movimentos` WHERE investimento_id = :id AND {$this->ownerClause('usuario_id')}"
            );
            $stmtDelMov->bindValue(':id', $id, PDO::PARAM_INT);
            $this->bindOwner($stmtDelMov);
            $stmtDelMov->execute();

            // Exclui a posição de investimento
            $stmtDel = $this->db->prepare(
                "DELETE FROM `investimentos` WHERE id = :id AND {$this->ownerClause('usuario_id')}"
            );
            $stmtDel->bindValue(':id', $id, PDO::PARAM_INT);
            $this->bindOwner($stmtDel);
            $stmtDel->execute();

            $this->tabela = 'investimentos';
            $this->logAudit('PURGE', $id, $inv, null, $currentUserId);

            $this->commit();
            return ['success' => true, 'message' => "Investimento '{$inv['nome']}' e todas as suas movimentações foram expurgados definitivamente."];
        } catch (Throwable $e) {
            $this->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Restaura uma movimentação de investimento e reaplica o impacto financeiro.
     *
     * @param int $id
     * @param int $currentUserId
     * @return array{success: bool, message: string}
     */
    private function restoreInvestmentMovement(int $id, int $currentUserId): array
    {
        $this->beginTransaction();
        try {
            // 1. Busca e bloqueia o movimento excluído
            $stmt = $this->db->prepare(
                "SELECT * FROM `investimento_movimentos`
                 WHERE id = :id AND {$this->ownerClause('usuario_id')} AND excluido_em IS NOT NULL
                 FOR UPDATE"
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $this->bindOwner($stmt);
            $stmt->execute();
            $mov = $stmt->fetch();
            if (!$mov) {
                throw new InvalidArgumentException('Movimento não encontrado ou não está na lixeira.');
            }

            // 2. Busca e bloqueia a posição de investimento
            $stmtInv = $this->db->prepare(
                "SELECT * FROM `investimentos`
                 WHERE id = :id AND {$this->ownerClause('usuario_id')}
                 FOR UPDATE"
            );
            $stmtInv->bindValue(':id', (int) $mov['investimento_id'], PDO::PARAM_INT);
            $this->bindOwner($stmtInv);
            $stmtInv->execute();
            $inv = $stmtInv->fetch();
            if (!$inv) {
                throw new InvalidArgumentException('O investimento vinculado a este movimento não foi encontrado.');
            }

            if (!empty($inv['excluido_em'])) {
                throw new InvalidArgumentException('O investimento deste movimento está na lixeira. Restaure o investimento primeiro.');
            }

            $valor = (float) $mov['valor'];
            $tipo  = (string) $mov['tipo'];

            // 3. Validação atômica e reaplicação dos impactos financeiros na conta bancária (se houver)
            if ($tipo === 'aporte') {
                $contaId = (int) $mov['conta_id'];
                $stmtConta = $this->db->prepare(
                    "SELECT * FROM `contas` WHERE id = :id AND {$this->ownerClause('usuario_id')} FOR UPDATE"
                );
                $stmtConta->bindValue(':id', $contaId, PDO::PARAM_INT);
                $this->bindOwner($stmtConta);
                $stmtConta->execute();
                $conta = $stmtConta->fetch();

                if (!$conta || !empty($conta['excluido_em']) || $conta['status'] !== 'ativa') {
                    throw new InvalidArgumentException('A conta bancária deste aporte está inativa ou excluída.');
                }
                if ((float) $conta['saldo_atual'] < $valor) {
                    throw new InvalidArgumentException('Saldo insuficiente na conta vinculada para restaurar este aporte.');
                }
                // Debita da conta bancária
                $sqlConta = "UPDATE `contas` SET saldo_atual = saldo_atual - :valor, atualizado_em = NOW() WHERE id = :conta_id AND usuario_id = :usuario_id";
                $stmtC = $this->db->prepare($sqlConta);
                $stmtC->bindValue(':valor', $valor);
                $stmtC->bindValue(':conta_id', $contaId, PDO::PARAM_INT);
                $stmtC->bindValue(':usuario_id', $currentUserId, PDO::PARAM_INT);
                $stmtC->execute();
            } elseif ($tipo === 'resgate') {
                $contaId = (int) $mov['conta_id'];
                $stmtConta = $this->db->prepare(
                    "SELECT * FROM `contas` WHERE id = :id AND {$this->ownerClause('usuario_id')} FOR UPDATE"
                );
                $stmtConta->bindValue(':id', $contaId, PDO::PARAM_INT);
                $this->bindOwner($stmtConta);
                $stmtConta->execute();
                $conta = $stmtConta->fetch();

                if (!$conta || !empty($conta['excluido_em']) || $conta['status'] !== 'ativa') {
                    throw new InvalidArgumentException('A conta bancária deste resgate está inativa ou excluída.');
                }
                if ((float) $inv['saldo_atual'] < $valor) {
                    throw new InvalidArgumentException('Saldo insuficiente no investimento para restaurar este resgate.');
                }
                // Credita na conta bancária
                $sqlConta = "UPDATE `contas` SET saldo_atual = saldo_atual + :valor, atualizado_em = NOW() WHERE id = :conta_id AND usuario_id = :usuario_id";
                $stmtC = $this->db->prepare($sqlConta);
                $stmtC->bindValue(':valor', $valor);
                $stmtC->bindValue(':conta_id', $contaId, PDO::PARAM_INT);
                $stmtC->bindValue(':usuario_id', $currentUserId, PDO::PARAM_INT);
                $stmtC->execute();
            }

            // 4. Limpa as flags de exclusão do movimento
            $sqlUp = "UPDATE `investimento_movimentos` SET excluido_em = NULL, excluido_por = NULL, atualizado_em = NOW() WHERE id = :id AND {$this->ownerClause('usuario_id')}";
            $stmtUp = $this->db->prepare($sqlUp);
            $stmtUp->bindValue(':id', $id, PDO::PARAM_INT);
            $this->bindOwner($stmtUp);
            $stmtUp->execute();

            // 5. Recalcula o saldo da posição de investimento
            (new Investment())->recalculate((int) $mov['investimento_id'], $currentUserId);

            $this->tabela = 'investimento_movimentos';
            $this->logAudit('RESTORE', $id, ['excluido_em' => $mov['excluido_em']], ['excluido_em' => null], $currentUserId);

            $this->commit();
            return ['success' => true, 'message' => "Movimentação de " . ucfirst($tipo) . " no investimento '{$inv['nome']}' restaurada com sucesso."];
        } catch (Throwable $e) {
            $this->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Executa exclusão física definitiva no banco de dados.
     *
     * @param string $tableName
     * @param int $id
     * @param int $currentUserId
     * @return array{success: bool, message: string}
     */
    private function purgeRecord(string $tableName, int $id, int $currentUserId): array
    {
        $allowed = ['transacoes', 'contas', 'categorias', 'investimentos', 'investimento_movimentos'];
        if (!in_array($tableName, $allowed, true)) {
            return ['success' => false, 'message' => 'Tabela inválida para expurgo.'];
        }

        $sqlFind = "SELECT * FROM `{$tableName}` WHERE id = :id AND {$this->ownerClause('usuario_id')} AND excluido_em IS NOT NULL LIMIT 1";
        $stmtFind = $this->db->prepare($sqlFind);
        $stmtFind->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindOwner($stmtFind);
        $stmtFind->execute();
        $antigo = $stmtFind->fetch();

        if (!$antigo) {
            return ['success' => false, 'message' => 'Registro não encontrado para expurgo definitivo.'];
        }

        $sqlDel = "DELETE FROM `{$tableName}` WHERE id = :id AND {$this->ownerClause('usuario_id')} AND excluido_em IS NOT NULL";
        $stmtDel = $this->db->prepare($sqlDel);
        $stmtDel->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindOwner($stmtDel);
        $stmtDel->execute();

        $this->tabela = $tableName;
        $this->logAudit('PURGE', $id, $antigo, null, $currentUserId);

        return ['success' => true, 'message' => 'Registro expurgado definitivamente do sistema.'];
    }

    private function ownerClause(string $column): string
    {
        return "{$column} = :usuario_id";
    }

    private function bindOwner(PDOStatement $stmt, string $column = 'usuario_id'): void
    {
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
    }
}
