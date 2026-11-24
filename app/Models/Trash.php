<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Model de Lixeira e Recuperação de Dados (Trash)
 *
 * Gerencia a recuperação e o expurgo definitivo de registros com Soft Delete
 * (Transações, Contas Bancárias, Categorias, Investimentos e Movimentos de Investimento),
 * recalculando saldos na restauração e respeitando o compartilhamento ativo, períodos de
 * participação e níveis de permissão ('visualizar', 'proprios', 'total').
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
        $userId = (int) AuthHelper::id();

        $sqlT = "SELECT COUNT(*)
                 FROM `transacoes` t
                 LEFT JOIN `contas` c ON t.conta_id = c.id
                 LEFT JOIN `compartilhamentos` cp
                     ON cp.recurso_tipo = 'conta'
                    AND cp.recurso_id = t.conta_id
                    AND cp.participante_id = :uid_join
                    AND cp.status = 'ativo'
                 WHERE t.excluido_em IS NOT NULL
                   AND (
                       (t.conta_id IS NULL AND t.usuario_id = :uid_card)
                       OR c.usuario_id = :uid_owner
                       OR (
                           cp.id IS NOT NULL
                           AND EXISTS (
                               SELECT 1 FROM `compartilhamento_periodos` p
                               WHERE p.recurso_tipo = 'conta'
                                 AND p.recurso_id = t.conta_id
                                 AND p.participante_id = :uid_period
                                 AND t.criado_em >= p.aceito_em
                                 AND (p.encerrado_em IS NULL OR t.criado_em <= p.encerrado_em)
                           )
                       )
                   )";

        $sqlC = "SELECT COUNT(*) FROM `contas` WHERE usuario_id = :usuario_id AND excluido_em IS NOT NULL";
        $sqlCat = "SELECT COUNT(*) FROM `categorias` WHERE usuario_id = :usuario_id AND excluido_em IS NOT NULL";
        $sqlI = "SELECT COUNT(*) FROM `investimentos` WHERE usuario_id = :usuario_id AND excluido_em IS NOT NULL";

        $sqlM = "SELECT COUNT(*)
                 FROM `investimento_movimentos` m
                 INNER JOIN `investimentos` i ON i.id = m.investimento_id
                 LEFT JOIN `compartilhamentos` cp
                     ON cp.recurso_tipo = 'investimento'
                    AND cp.recurso_id = i.id
                    AND cp.participante_id = :uid_join
                    AND cp.status = 'ativo'
                 WHERE m.excluido_em IS NOT NULL
                   AND i.excluido_em IS NULL
                   AND (
                       i.usuario_id = :uid_owner
                       OR (
                           cp.id IS NOT NULL
                           AND EXISTS (
                               SELECT 1 FROM `compartilhamento_periodos` p
                               WHERE p.recurso_tipo = 'investimento'
                                 AND p.recurso_id = i.id
                                 AND p.participante_id = :uid_period
                                 AND m.criado_em >= p.aceito_em
                                 AND (p.encerrado_em IS NULL OR m.criado_em <= p.encerrado_em)
                           )
                       )
                   )";

        $stmtTransacoes = $this->db->prepare($sqlT);
        $stmtTransacoes->bindValue(':uid_join', $userId, PDO::PARAM_INT);
        $stmtTransacoes->bindValue(':uid_card', $userId, PDO::PARAM_INT);
        $stmtTransacoes->bindValue(':uid_owner', $userId, PDO::PARAM_INT);
        $stmtTransacoes->bindValue(':uid_period', $userId, PDO::PARAM_INT);
        $stmtTransacoes->execute();
        $transacoes = (int) $stmtTransacoes->fetchColumn();

        $stmtContas = $this->db->prepare($sqlC);
        $stmtContas->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmtContas->execute();
        $contas = (int) $stmtContas->fetchColumn();

        $stmtCategorias = $this->db->prepare($sqlCat);
        $stmtCategorias->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmtCategorias->execute();
        $categorias = (int) $stmtCategorias->fetchColumn();

        $stmtInvestimentos = $this->db->prepare($sqlI);
        $stmtInvestimentos->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmtInvestimentos->execute();
        $investimentos = (int) $stmtInvestimentos->fetchColumn();

        $stmtMovimentos = $this->db->prepare($sqlM);
        $stmtMovimentos->bindValue(':uid_join', $userId, PDO::PARAM_INT);
        $stmtMovimentos->bindValue(':uid_owner', $userId, PDO::PARAM_INT);
        $stmtMovimentos->bindValue(':uid_period', $userId, PDO::PARAM_INT);
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
     * Retorna a listagem de transações excluídas com dados contextuais e flag de permissão pode_operar.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDeletedTransactions(): array
    {
        $userId = (int) AuthHelper::id();

        $sql = "SELECT t.*, 
                       c.nome as conta_nome, c.cor as conta_cor, c.usuario_id as conta_proprietario_id,
                       cat.nome as categoria_nome, cat.icone as categoria_icone, cat.cor as categoria_cor,
                       cc.nome as cartao_nome,
                       u.nome as usuario_nome,
                       ue.nome as excluido_por_nome,
                       cp.permissao as compartilhamento_permissao
                FROM `transacoes` t
                LEFT JOIN `contas` c ON t.conta_id = c.id
                LEFT JOIN `categorias` cat ON t.categoria_id = cat.id
                LEFT JOIN `cartoes_credito` cc ON t.cartao_credito_id = cc.id
                LEFT JOIN `usuarios` u ON t.usuario_id = u.id
                LEFT JOIN `usuarios` ue ON t.excluido_por = ue.id
                LEFT JOIN `compartilhamentos` cp
                    ON cp.recurso_tipo = 'conta'
                   AND cp.recurso_id = t.conta_id
                   AND cp.participante_id = :uid_join
                   AND cp.status = 'ativo'
                WHERE t.excluido_em IS NOT NULL
                  AND (
                      (t.conta_id IS NULL AND t.usuario_id = :uid_card)
                      OR c.usuario_id = :uid_owner
                      OR (
                          cp.id IS NOT NULL
                          AND EXISTS (
                              SELECT 1 FROM `compartilhamento_periodos` p
                              WHERE p.recurso_tipo = 'conta'
                                AND p.recurso_id = t.conta_id
                                AND p.participante_id = :uid_period
                                AND t.criado_em >= p.aceito_em
                                AND (p.encerrado_em IS NULL OR t.criado_em <= p.encerrado_em)
                          )
                      )
                  )
                ORDER BY t.excluido_em DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid_join', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_card', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_owner', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_period', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            if (empty($row['conta_id'])) {
                $row['pode_operar'] = ((int) $row['usuario_id'] === $userId);
            } elseif ((int) ($row['conta_proprietario_id'] ?? 0) === $userId) {
                $row['pode_operar'] = true;
            } else {
                $perm = (string) ($row['compartilhamento_permissao'] ?? 'visualizar');
                $row['pode_operar'] = ($perm === 'total')
                    || ($perm === 'proprios' && (int) $row['usuario_id'] === $userId);
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * Retorna a listagem de contas bancárias excluídas (apenas proprietário).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDeletedAccounts(): array
    {
        $sql = "SELECT c.*, ue.nome as excluido_por_nome
                FROM `contas` c
                LEFT JOIN `usuarios` ue ON c.excluido_por = ue.id
                WHERE c.usuario_id = :usuario_id AND c.excluido_em IS NOT NULL
                ORDER BY c.excluido_em DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', (int) AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retorna a listagem de categorias excluídas (apenas proprietário).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDeletedCategories(): array
    {
        $sql = "SELECT cat.*, ue.nome as excluido_por_nome
                FROM `categorias` cat
                LEFT JOIN `usuarios` ue ON cat.excluido_por = ue.id
                WHERE cat.usuario_id = :usuario_id AND cat.excluido_em IS NOT NULL
                ORDER BY cat.excluido_em DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', (int) AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retorna a listagem de posições de investimento excluídas (apenas proprietário).
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
                WHERE i.usuario_id = :usuario_id AND i.excluido_em IS NOT NULL
                ORDER BY i.excluido_em DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', (int) AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retorna movimentações de investimentos excluídas cujo investimento pai está ativo
     * (próprios ou de investimentos compartilhados ativos nos períodos válidos).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDeletedInvestmentMovements(): array
    {
        $userId = (int) AuthHelper::id();

        $sql = "SELECT m.*,
                       i.nome as investimento_nome,
                       i.usuario_id as investimento_proprietario_id,
                       i.conta_id as investimento_conta_id,
                       c.nome as conta_nome,
                       u.nome as usuario_nome,
                       ue.nome as excluido_por_nome,
                       cp.permissao as compartilhamento_permissao
                FROM `investimento_movimentos` m
                INNER JOIN `investimentos` i ON m.investimento_id = i.id
                LEFT JOIN `contas` c ON m.conta_id = c.id
                LEFT JOIN `usuarios` u ON m.usuario_id = u.id
                LEFT JOIN `usuarios` ue ON m.excluido_por = ue.id
                LEFT JOIN `compartilhamentos` cp
                    ON cp.recurso_tipo = 'investimento'
                   AND cp.recurso_id = i.id
                   AND cp.participante_id = :uid_join
                   AND cp.status = 'ativo'
                WHERE m.excluido_em IS NOT NULL
                  AND i.excluido_em IS NULL
                  AND (
                      i.usuario_id = :uid_owner
                      OR (
                          cp.id IS NOT NULL
                          AND EXISTS (
                              SELECT 1 FROM `compartilhamento_periodos` p
                              WHERE p.recurso_tipo = 'investimento'
                                AND p.recurso_id = i.id
                                AND p.participante_id = :uid_period
                                AND m.criado_em >= p.aceito_em
                                AND (p.encerrado_em IS NULL OR m.criado_em <= p.encerrado_em)
                          )
                      )
                  )
                ORDER BY m.excluido_em DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid_join', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_owner', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_period', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $invModel = new Investment();
        $accountAccessCache = [];

        foreach ($rows as &$row) {
            if ((int) $row['investimento_proprietario_id'] === $userId) {
                $row['pode_operar'] = true;
            } else {
                $contaVinculadaId = (int) $row['investimento_conta_id'];
                if (!array_key_exists($contaVinculadaId, $accountAccessCache)) {
                    $accountAccessCache[$contaVinculadaId] = $invModel->hasLaunchAccessToLinkedAccount($contaVinculadaId, $userId);
                }
                $perm = (string) ($row['compartilhamento_permissao'] ?? 'visualizar');
                $temPermMov = ($perm === 'total') || ($perm === 'proprios' && (int) $row['usuario_id'] === $userId);
                $row['pode_operar'] = $temPermMov && $accountAccessCache[$contaVinculadaId];
            }
        }
        unset($row);

        return $rows;
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
            'transacao'               => 'transacoes',
            'transacoes'              => 'transacoes',
            'conta'                   => 'contas',
            'contas'                  => 'contas',
            'categoria'               => 'categorias',
            'categorias'              => 'categorias',
            'investimento'            => 'investimentos',
            'investimentos'           => 'investimentos',
            'investimento_movimento'  => 'investimento_movimentos',
            'movimento_investimento'  => 'investimento_movimentos',
            'investimento_movimentos' => 'investimento_movimentos',
        ];

        if (!isset($tables[$entity])) {
            return ['success' => false, 'message' => 'Tipo de entidade inválido para restauração.'];
        }

        $this->tabela = $tables[$entity];

        return match ($entity) {
            'transacao', 'transacoes'                                                     => $this->restoreTransaction($id, $currentUserId),
            'conta', 'contas'                                                             => $this->restoreAccount($id, $currentUserId),
            'categoria', 'categorias'                                                     => $this->restoreCategory($id, $currentUserId),
            'investimento', 'investimentos'                                               => $this->restoreInvestment($id, $currentUserId),
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
            'transacao', 'transacoes'                                                     => $this->purgeTransaction($id, $currentUserId),
            'conta', 'contas'                                                             => $this->purgeOwnedRecord('contas', $id, $currentUserId),
            'categoria', 'categorias'                                                     => $this->purgeOwnedRecord('categorias', $id, $currentUserId),
            'investimento', 'investimentos'                                               => $this->purgeInvestment($id, $currentUserId),
            'investimento_movimento', 'movimento_investimento', 'investimento_movimentos' => $this->purgeInvestmentMovement($id, $currentUserId),
            default                                                                       => ['success' => false, 'message' => 'Tipo de entidade inválido para expurgo.'],
        };
    }

    /**
     * Verifica se o usuário tem autorização para restaurar ou expurgar uma transação na lixeira.
     *
     * @param array<string, mixed> $transacao
     */
    private function canOperateDeletedTransaction(array $transacao, int $currentUserId): bool
    {
        if (empty($transacao['conta_id'])) {
            return (int) $transacao['usuario_id'] === $currentUserId;
        }

        $contaId = (int) $transacao['conta_id'];
        $stmtC = $this->db->prepare('SELECT usuario_id FROM contas WHERE id = :id LIMIT 1');
        $stmtC->execute(['id' => $contaId]);
        $contaOwnerId = (int) $stmtC->fetchColumn();

        if ($contaOwnerId === $currentUserId) {
            return true;
        }

        $shareModel = new Share();
        $share = $shareModel->getActiveShare('conta', $contaId, $currentUserId);
        if (!$share) {
            return false;
        }

        if (!$shareModel->isTimestampWithinParticipantPeriods('conta', $contaId, $currentUserId, (string) $transacao['criado_em'])) {
            return false;
        }

        if ($share['permissao'] === 'total') {
            return true;
        }
        if ($share['permissao'] === 'proprios' && (int) $transacao['usuario_id'] === $currentUserId) {
            return true;
        }

        return false;
    }

    /**
     * Verifica se o usuário tem autorização para restaurar ou expurgar um movimento de investimento na lixeira.
     *
     * @param array<string, mixed> $mov
     * @param array<string, mixed> $inv
     */
    private function canOperateDeletedInvestmentMovement(array $mov, array $inv, int $currentUserId): bool
    {
        if ((int) $inv['usuario_id'] === $currentUserId) {
            return true;
        }

        $invId = (int) $inv['id'];
        $shareModel = new Share();
        $share = $shareModel->getActiveShare('investimento', $invId, $currentUserId);
        if (!$share) {
            return false;
        }

        if (!$shareModel->isTimestampWithinParticipantPeriods('investimento', $invId, $currentUserId, (string) $mov['criado_em'])) {
            return false;
        }

        if (!(new Investment())->hasLaunchAccessToLinkedAccount((int) $inv['conta_id'], $currentUserId)) {
            return false;
        }

        if ($share['permissao'] === 'total') {
            return true;
        }
        if ($share['permissao'] === 'proprios' && (int) $mov['usuario_id'] === $currentUserId) {
            return true;
        }

        return false;
    }

    /**
     * Restaura uma transação excluída e reaplica os impactos no saldo bancário.
     */
    private function restoreTransaction(int $id, int $currentUserId): array
    {
        $sql = "SELECT * FROM `transacoes` WHERE id = :id AND excluido_em IS NOT NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $transacao = $stmt->fetch();

        if (!$transacao || !$this->canOperateDeletedTransaction($transacao, $currentUserId)) {
            return ['success' => false, 'message' => 'Transação não encontrada na lixeira ou você não possui permissão para restaurá-la.'];
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
                    $novoStatus = ($invoice['data_fechamento'] <= date('Y-m-d')) ? 'fechada' : 'aberta';
                    $stmtReabrir = $this->db->prepare("UPDATE faturas_cartao SET status = :status, pago_em = NULL, atualizado_em = NOW() WHERE id = :id");
                    $stmtReabrir->execute(['status' => $novoStatus, 'id' => $invoice['id']]);
                }
            }

            $sqlUp = "UPDATE `transacoes` SET excluido_em = NULL, excluido_por = NULL, atualizado_em = NOW() WHERE id = :id";
            $stmtUp = $this->db->prepare($sqlUp);
            $stmtUp->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtUp->execute();

            $status = (string) $transacao['status'];
            $contaId = !empty($transacao['conta_id']) ? (int) $transacao['conta_id'] : null;
            $valor = (float) $transacao['valor'];

            if (empty($transacao['fatura_id']) && $contaId !== null && in_array($status, ['pago', 'recebido'], true)) {
                $ajuste = ($transacao['tipo'] === 'receita') ? $valor : -$valor;
                $sqlSaldo = "UPDATE `contas` SET saldo_atual = saldo_atual + :ajuste, atualizado_em = NOW() WHERE id = :conta_id";
                $stmtSaldo = $this->db->prepare($sqlSaldo);
                $stmtSaldo->bindValue(':ajuste', $ajuste, PDO::PARAM_STR);
                $stmtSaldo->bindValue(':conta_id', $contaId, PDO::PARAM_INT);
                $stmtSaldo->execute();
                if ($stmtSaldo->rowCount() !== 1) {
                    throw new RuntimeException('A conta vinculada à transação não está disponível.');
                }
            }

            if ($invoice !== null) {
                (new CreditCardInvoice())->recalculateTotal((int) $invoice['id']);
            }

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
     * Expurga definitivamente uma transação da lixeira respeitando permissões da conta compartilhada.
     */
    private function purgeTransaction(int $id, int $currentUserId): array
    {
        $stmtFind = $this->db->prepare("SELECT * FROM `transacoes` WHERE id = :id AND excluido_em IS NOT NULL LIMIT 1");
        $stmtFind->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtFind->execute();
        $antigo = $stmtFind->fetch();

        if (!$antigo || !$this->canOperateDeletedTransaction($antigo, $currentUserId)) {
            return ['success' => false, 'message' => 'Transação não encontrada ou você não possui permissão para expurgá-la.'];
        }

        $stmtDel = $this->db->prepare("DELETE FROM `transacoes` WHERE id = :id AND excluido_em IS NOT NULL");
        $stmtDel->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtDel->execute();

        $this->tabela = 'transacoes';
        $this->logAudit('PURGE', $id, $antigo, null, $currentUserId);

        return ['success' => true, 'message' => 'Transação expurgada definitivamente do sistema.'];
    }

    /**
     * Restaura uma conta bancária excluída (exclusivo do proprietário).
     */
    private function restoreAccount(int $id, int $currentUserId): array
    {
        $sql = "SELECT * FROM `contas` WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NOT NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $currentUserId, PDO::PARAM_INT);
        $stmt->execute();
        $conta = $stmt->fetch();

        if (!$conta) {
            return ['success' => false, 'message' => 'Conta bancária não encontrada na lixeira.'];
        }

        $sqlUp = "UPDATE `contas` SET excluido_em = NULL, excluido_por = NULL, atualizado_em = NOW() WHERE id = :id AND usuario_id = :usuario_id";
        $stmtUp = $this->db->prepare($sqlUp);
        $stmtUp->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtUp->bindValue(':usuario_id', $currentUserId, PDO::PARAM_INT);
        $stmtUp->execute();

        $this->logAudit('RESTORE', $id, ['excluido_em' => $conta['excluido_em']], ['excluido_em' => null], $currentUserId);

        return ['success' => true, 'message' => 'Conta bancária restaurada com sucesso.'];
    }

    /**
     * Restaura uma categoria excluída (exclusivo do proprietário).
     */
    private function restoreCategory(int $id, int $currentUserId): array
    {
        $sql = "SELECT * FROM `categorias` WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NOT NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $currentUserId, PDO::PARAM_INT);
        $stmt->execute();
        $categoria = $stmt->fetch();

        if (!$categoria) {
            return ['success' => false, 'message' => 'Categoria não encontrada na lixeira.'];
        }

        $sqlUp = "UPDATE `categorias` SET excluido_em = NULL, excluido_por = NULL, atualizado_em = NOW() WHERE id = :id AND usuario_id = :usuario_id";
        $stmtUp = $this->db->prepare($sqlUp);
        $stmtUp->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtUp->bindValue(':usuario_id', $currentUserId, PDO::PARAM_INT);
        $stmtUp->execute();

        $this->logAudit('RESTORE', $id, ['excluido_em' => $categoria['excluido_em']], ['excluido_em' => null], $currentUserId);

        return ['success' => true, 'message' => 'Categoria restaurada com sucesso.'];
    }

    /**
     * Restaura uma posição de investimento e reativa suas movimentações excluídas junto com ela (exclusivo do proprietário).
     */
    private function restoreInvestment(int $id, int $currentUserId): array
    {
        $this->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM `investimentos` WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NOT NULL FOR UPDATE"
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':usuario_id', $currentUserId, PDO::PARAM_INT);
            $stmt->execute();
            $inv = $stmt->fetch();
            if (!$inv) {
                throw new InvalidArgumentException('Investimento não encontrado ou não está na lixeira.');
            }

            $contaId = (int) $inv['conta_id'];
            $stmtConta = $this->db->prepare(
                "SELECT * FROM `contas` WHERE id = :conta_id AND usuario_id = :usuario_id AND excluido_em IS NULL"
            );
            $stmtConta->bindValue(':conta_id', $contaId, PDO::PARAM_INT);
            $stmtConta->bindValue(':usuario_id', $currentUserId, PDO::PARAM_INT);
            $stmtConta->execute();
            if (!$stmtConta->fetch()) {
                throw new InvalidArgumentException('A conta bancária vinculada ao investimento está excluída ou indisponível. Restaure-a antes.');
            }

            $excluidoEmInvestimento = $inv['excluido_em'];

            $stmtUp = $this->db->prepare(
                "UPDATE `investimentos` SET excluido_em = NULL, excluido_por = NULL, atualizado_em = NOW() WHERE id = :id AND usuario_id = :usuario_id"
            );
            $stmtUp->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtUp->bindValue(':usuario_id', $currentUserId, PDO::PARAM_INT);
            $stmtUp->execute();

            $stmtUpMov = $this->db->prepare(
                "UPDATE `investimento_movimentos` SET excluido_em = NULL, excluido_por = NULL, atualizado_em = NOW()
                 WHERE investimento_id = :id AND excluido_em = :excluido_em"
            );
            $stmtUpMov->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtUpMov->bindValue(':excluido_em', $excluidoEmInvestimento, PDO::PARAM_STR);
            $stmtUpMov->execute();

            (new Investment())->recalculate($id);

            $this->logAudit('RESTORE', $id, ['excluido_em' => $inv['excluido_em']], ['excluido_em' => null], $currentUserId);

            $this->commit();
            return ['success' => true, 'message' => "Investimento '{$inv['nome']}' e suas movimentações ativas foram restaurados com sucesso."];
        } catch (Throwable $e) {
            $this->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Expurga definitivamente um investimento e todos os seus movimentos em cascata (exclusivo do proprietário).
     */
    private function purgeInvestment(int $id, int $currentUserId): array
    {
        $this->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM `investimentos` WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NOT NULL FOR UPDATE"
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':usuario_id', $currentUserId, PDO::PARAM_INT);
            $stmt->execute();
            $inv = $stmt->fetch();
            if (!$inv) {
                throw new InvalidArgumentException('Investimento não encontrado ou não está na lixeira.');
            }

            $stmtDelMov = $this->db->prepare(
                "DELETE FROM `investimento_movimentos` WHERE investimento_id = :id"
            );
            $stmtDelMov->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtDelMov->execute();

            $stmtDel = $this->db->prepare(
                "DELETE FROM `investimentos` WHERE id = :id AND usuario_id = :usuario_id"
            );
            $stmtDel->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtDel->bindValue(':usuario_id', $currentUserId, PDO::PARAM_INT);
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
     */
    private function restoreInvestmentMovement(int $id, int $currentUserId): array
    {
        $this->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM `investimento_movimentos`
                 WHERE id = :id AND excluido_em IS NOT NULL
                 FOR UPDATE"
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $mov = $stmt->fetch();
            if (!$mov) {
                throw new InvalidArgumentException('Movimento não encontrado ou não está na lixeira.');
            }

            $stmtInv = $this->db->prepare(
                "SELECT * FROM `investimentos`
                 WHERE id = :id
                 FOR UPDATE"
            );
            $stmtInv->bindValue(':id', (int) $mov['investimento_id'], PDO::PARAM_INT);
            $stmtInv->execute();
            $inv = $stmtInv->fetch();
            if (!$inv) {
                throw new InvalidArgumentException('O investimento vinculado a este movimento não foi encontrado.');
            }

            if (!$this->canOperateDeletedInvestmentMovement($mov, $inv, $currentUserId)) {
                throw new InvalidArgumentException('Você não possui permissão para restaurar esta movimentação de investimento.');
            }

            if (!empty($inv['excluido_em'])) {
                throw new InvalidArgumentException('O investimento deste movimento está na lixeira. Restaure o investimento primeiro.');
            }

            $valor = (float) $mov['valor'];
            $tipo  = (string) $mov['tipo'];

            if ($tipo === 'aporte') {
                $contaId = (int) $mov['conta_id'];
                $stmtConta = $this->db->prepare(
                    "SELECT * FROM `contas` WHERE id = :id FOR UPDATE"
                );
                $stmtConta->bindValue(':id', $contaId, PDO::PARAM_INT);
                $stmtConta->execute();
                $conta = $stmtConta->fetch();

                if (!$conta || !empty($conta['excluido_em']) || $conta['status'] !== 'ativa') {
                    throw new InvalidArgumentException('A conta bancária deste aporte está inativa ou excluída.');
                }
                if ((float) $conta['saldo_atual'] < $valor) {
                    throw new InvalidArgumentException('Saldo insuficiente na conta vinculada para restaurar este aporte.');
                }
                $sqlConta = "UPDATE `contas` SET saldo_atual = saldo_atual - :valor, atualizado_em = NOW() WHERE id = :conta_id";
                $stmtC = $this->db->prepare($sqlConta);
                $stmtC->bindValue(':valor', $valor);
                $stmtC->bindValue(':conta_id', $contaId, PDO::PARAM_INT);
                $stmtC->execute();
            } elseif ($tipo === 'resgate') {
                $contaId = (int) $mov['conta_id'];
                $stmtConta = $this->db->prepare(
                    "SELECT * FROM `contas` WHERE id = :id FOR UPDATE"
                );
                $stmtConta->bindValue(':id', $contaId, PDO::PARAM_INT);
                $stmtConta->execute();
                $conta = $stmtConta->fetch();

                if (!$conta || !empty($conta['excluido_em']) || $conta['status'] !== 'ativa') {
                    throw new InvalidArgumentException('A conta bancária deste resgate está inativa ou excluída.');
                }
                if ((float) $inv['saldo_atual'] < $valor) {
                    throw new InvalidArgumentException('Saldo insuficiente no investimento para restaurar este resgate.');
                }
                $sqlConta = "UPDATE `contas` SET saldo_atual = saldo_atual + :valor, atualizado_em = NOW() WHERE id = :conta_id";
                $stmtC = $this->db->prepare($sqlConta);
                $stmtC->bindValue(':valor', $valor);
                $stmtC->bindValue(':conta_id', $contaId, PDO::PARAM_INT);
                $stmtC->execute();
            }

            $sqlUp = "UPDATE `investimento_movimentos` SET excluido_em = NULL, excluido_por = NULL, atualizado_em = NOW() WHERE id = :id";
            $stmtUp = $this->db->prepare($sqlUp);
            $stmtUp->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtUp->execute();

            (new Investment())->recalculate((int) $mov['investimento_id']);

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
     * Expurga definitivamente uma movimentação de investimento da lixeira respeitando permissões.
     */
    private function purgeInvestmentMovement(int $id, int $currentUserId): array
    {
        $stmtFind = $this->db->prepare("SELECT * FROM `investimento_movimentos` WHERE id = :id AND excluido_em IS NOT NULL LIMIT 1");
        $stmtFind->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtFind->execute();
        $mov = $stmtFind->fetch();

        if (!$mov) {
            return ['success' => false, 'message' => 'Movimentação não encontrada para expurgo definitivo.'];
        }

        $stmtInv = $this->db->prepare("SELECT * FROM `investimentos` WHERE id = :id LIMIT 1");
        $stmtInv->bindValue(':id', (int) $mov['investimento_id'], PDO::PARAM_INT);
        $stmtInv->execute();
        $inv = $stmtInv->fetch();

        if (!$inv || !$this->canOperateDeletedInvestmentMovement($mov, $inv, $currentUserId)) {
            return ['success' => false, 'message' => 'Você não possui permissão para expurgar esta movimentação de investimento.'];
        }

        $stmtDel = $this->db->prepare("DELETE FROM `investimento_movimentos` WHERE id = :id AND excluido_em IS NOT NULL");
        $stmtDel->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtDel->execute();

        $this->tabela = 'investimento_movimentos';
        $this->logAudit('PURGE', $id, $mov, null, $currentUserId);

        return ['success' => true, 'message' => 'Movimentação de investimento expurgada definitivamente do sistema.'];
    }

    /**
     * Executa exclusão física definitiva de registros exclusivos do proprietário (contas e categorias).
     */
    private function purgeOwnedRecord(string $tableName, int $id, int $currentUserId): array
    {
        $allowed = ['contas', 'categorias'];
        if (!in_array($tableName, $allowed, true)) {
            return ['success' => false, 'message' => 'Tabela inválida para expurgo.'];
        }

        $sqlFind = "SELECT * FROM `{$tableName}` WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NOT NULL LIMIT 1";
        $stmtFind = $this->db->prepare($sqlFind);
        $stmtFind->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtFind->bindValue(':usuario_id', $currentUserId, PDO::PARAM_INT);
        $stmtFind->execute();
        $antigo = $stmtFind->fetch();

        if (!$antigo) {
            return ['success' => false, 'message' => 'Registro não encontrado para expurgo definitivo.'];
        }

        $sqlDel = "DELETE FROM `{$tableName}` WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NOT NULL";
        $stmtDel = $this->db->prepare($sqlDel);
        $stmtDel->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtDel->bindValue(':usuario_id', $currentUserId, PDO::PARAM_INT);
        $stmtDel->execute();

        $this->tabela = $tableName;
        $this->logAudit('PURGE', $id, $antigo, null, $currentUserId);

        return ['success' => true, 'message' => 'Registro expurgado definitivamente do sistema.'];
    }
}
