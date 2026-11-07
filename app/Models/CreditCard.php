<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Model de Cartão de Crédito (CreditCard)
 *
 * Gerencia os cartões de crédito, limites totais/disponíveis e vínculos com contas bancárias.
 */
class CreditCard extends Model
{
    protected string $tabela = 'cartoes_credito';
    protected string $chavePrimaria = 'id';

    /**
     * Cria um novo cartão de crédito com auditoria.
     *
     * @param array<string, mixed> $data
     * @param int $userId
     * @return int
     */
    public function createCard(array $data, int $userId): int
    {
        $nome = trim((string) ($data['nome'] ?? ''));
        $limiteCredito = (float) ($data['limite_credito'] ?? 0.0);
        $diaFechamento = max(1, min(31, (int) ($data['dia_fechamento'] ?? 1)));
        $diaVencimento = max(1, min(31, (int) ($data['dia_vencimento'] ?? 10)));
        $contaPadraoId = !empty($data['conta_padrao_id']) ? (int) $data['conta_padrao_id'] : null;
        $cor = !empty($data['cor']) ? trim((string) $data['cor']) : '#3BD7D7';
        $status = $data['status'] ?? 'ativo';

        if ($contaPadraoId !== null && !$this->accountBelongsToUser($contaPadraoId, $userId)) {
            throw new InvalidArgumentException('A conta padrão selecionada não pertence ao usuário autenticado.');
        }

        $sql = "INSERT INTO `cartoes_credito` (usuario_id, nome, limite_credito, dia_fechamento, dia_vencimento, conta_padrao_id, cor, status, criado_em, atualizado_em)
            VALUES (:usuario_id, :nome, :limite_credito, :dia_fechamento, :dia_vencimento, :conta_padrao_id, :cor, :status, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
        $stmt->bindValue(':limite_credito', $limiteCredito);
        $stmt->bindValue(':dia_fechamento', $diaFechamento, PDO::PARAM_INT);
        $stmt->bindValue(':dia_vencimento', $diaVencimento, PDO::PARAM_INT);
        $stmt->bindValue(':conta_padrao_id', $contaPadraoId, $contaPadraoId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':cor', $cor, PDO::PARAM_STR);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->execute();

        $novoId = (int) $this->db->lastInsertId();

        $this->logAudit('INSERT', $novoId, null, [
            'nome'            => $nome,
            'limite_credito'  => $limiteCredito,
            'dia_fechamento'  => $diaFechamento,
            'dia_vencimento'  => $diaVencimento,
            'conta_padrao_id' => $contaPadraoId,
            'cor'             => $cor,
            'status'          => $status,
        ], $userId);

        return $novoId;
    }

    /**
     * Atualiza dados de um cartão de crédito com auditoria.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @param int $userId
     * @return bool
     */
    public function updateCard(int $id, array $data, int $userId): bool
    {
        $oldData = $this->find($id);
        if (!$oldData) {
            return false;
        }

        $nome = isset($data['nome']) ? trim((string) $data['nome']) : $oldData['nome'];
        $limiteCredito = isset($data['limite_credito']) ? (float) $data['limite_credito'] : (float) $oldData['limite_credito'];
        $diaFechamento = isset($data['dia_fechamento']) ? max(1, min(31, (int) $data['dia_fechamento'])) : (int) $oldData['dia_fechamento'];
        $diaVencimento = isset($data['dia_vencimento']) ? max(1, min(31, (int) $data['dia_vencimento'])) : (int) $oldData['dia_vencimento'];
        $contaPadraoId = array_key_exists('conta_padrao_id', $data) ? (!empty($data['conta_padrao_id']) ? (int) $data['conta_padrao_id'] : null) : (!empty($oldData['conta_padrao_id']) ? (int) $oldData['conta_padrao_id'] : null);
        $cor = isset($data['cor']) ? trim((string) $data['cor']) : $oldData['cor'];
        $status = isset($data['status']) ? (string) $data['status'] : $oldData['status'];

        if ($contaPadraoId !== null && !$this->accountBelongsToUser($contaPadraoId, $userId)) {
            return false;
        }

        $sql = "UPDATE `cartoes_credito`
                SET nome = :nome, limite_credito = :limite_credito, dia_fechamento = :dia_fechamento,
                    dia_vencimento = :dia_vencimento, conta_padrao_id = :conta_padrao_id, cor = :cor,
                    status = :status, atualizado_em = NOW()
                WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
        $stmt->bindValue(':limite_credito', $limiteCredito);
        $stmt->bindValue(':dia_fechamento', $diaFechamento, PDO::PARAM_INT);
        $stmt->bindValue(':dia_vencimento', $diaVencimento, PDO::PARAM_INT);
        $stmt->bindValue(':conta_padrao_id', $contaPadraoId, $contaPadraoId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':cor', $cor, PDO::PARAM_STR);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $success = $stmt->execute();

        if ($success) {
            $this->logAudit('UPDATE', $id, $oldData, [
                'nome'            => $nome,
                'limite_credito'  => $limiteCredito,
                'dia_fechamento'  => $diaFechamento,
                'dia_vencimento'  => $diaVencimento,
                'conta_padrao_id' => $contaPadraoId,
                'cor'             => $cor,
                'status'          => $status,
            ], $userId);
        }

        return $success;
    }

    /**
     * Alterna o status do cartão entre ativo/inativo.
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function toggleStatus(int $id, int $userId): bool
    {
        $card = $this->find($id);
        if (!$card) {
            return false;
        }

        $newStatus = ($card['status'] === 'ativo') ? 'inativo' : 'ativo';
        return $this->updateCard($id, ['status' => $newStatus], $userId);
    }

    /**
     * Retorna todos os cartões não excluídos com informações agregadas de limites e conta padrão.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllWithSummary(): array
    {
        $sql = "SELECT c.*, cp.nome as conta_padrao_nome
                FROM `cartoes_credito` c
                LEFT JOIN `contas` cp ON c.conta_padrao_id = cp.id
            WHERE c.usuario_id = :usuario_id AND c.excluido_em IS NULL
                ORDER BY c.status ASC, c.nome ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        $cards = $stmt->fetchAll();

        foreach ($cards as &$card) {
            $limits = $this->calculateLimits((int) $card['id'], (float) $card['limite_credito']);
            $card['limite_usado'] = $limits['limite_usado'];
            $card['limite_disponivel'] = $limits['limite_disponivel'];
            $card['porcentagem_usada'] = $limits['porcentagem_usada'];
            $card['fatura_atual'] = $this->getCurrentOpenInvoice((int) $card['id'], $card);
        }

        return $cards;
    }

    /**
     * Retorna os detalhes completos de um cartão pelo ID.
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function getCardWithDetails(int $id): ?array
    {
        $sql = "SELECT c.*, cp.nome as conta_padrao_nome
                FROM `cartoes_credito` c
                LEFT JOIN `contas` cp ON c.conta_padrao_id = cp.id
                WHERE c.id = :id AND c.usuario_id = :usuario_id AND c.excluido_em IS NULL
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        $card = $stmt->fetch();

        if (!$card) {
            return null;
        }

        $limits = $this->calculateLimits((int) $card['id'], (float) $card['limite_credito']);
        $card['limite_usado'] = $limits['limite_usado'];
        $card['limite_disponivel'] = $limits['limite_disponivel'];
        $card['porcentagem_usada'] = $limits['porcentagem_usada'];
        $card['fatura_atual'] = $this->getCurrentOpenInvoice((int) $card['id'], $card);

        return $card;
    }

    /**
     * Calcula o limite utilizado (soma de faturas abertas e fechadas não pagas) e o limite disponível.
     *
     * @param int $cardId
     * @param float $limiteTotal
     * @return array{limite_total: float, limite_usado: float, limite_disponivel: float, porcentagem_usada: float}
     */
    public function calculateLimits(int $cardId, float $limiteTotal): array
    {
        $sql = "SELECT COALESCE(SUM(valor_total), 0) as total_usado
                FROM `faturas_cartao`
                WHERE cartao_credito_id = :card_id AND status != 'paga'";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':card_id', $cardId, PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch();

        $limiteUsado = (float) ($res['total_usado'] ?? 0.0);
        $limiteDisponivel = max(0.0, $limiteTotal - $limiteUsado);
        $porcentagemUsada = ($limiteTotal > 0) ? min(100.0, round(($limiteUsado / $limiteTotal) * 100, 1)) : 0.0;

        return [
            'limite_total'      => $limiteTotal,
            'limite_usado'      => $limiteUsado,
            'limite_disponivel' => $limiteDisponivel,
            'porcentagem_usada' => $porcentagemUsada,
        ];
    }

    private function accountBelongsToUser(int $accountId, int $userId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM `contas` WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NULL LIMIT 1');
        $stmt->execute(['id' => $accountId, 'usuario_id' => $userId]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Retorna a fatura do ciclo atual do cartão de crédito (ou a fatura pendente mais relevante até o ciclo atual).
     * Nunca retorna faturas de ciclos futuros posteriores ao ciclo corrente.
     *
     * @param int $cardId
     * @param array<string, mixed>|null $card
     * @return array<string, mixed>|null
     */
    public function getCurrentOpenInvoice(int $cardId, ?array $card = null): ?array
    {
        $userId = (int) AuthHelper::id();

        if ($card === null) {
            $card = $this->find($cardId);
        }

        if (!$card || ((int) ($card['usuario_id'] ?? 0) !== $userId && !AuthHelper::isAdmin() && !AuthHelper::isDev())) {
            if (!$card || (int) ($card['usuario_id'] ?? 0) !== $userId) {
                return null;
            }
        }

        $diaFechamento = (int) ($card['dia_fechamento'] ?? 0);
        if ($diaFechamento <= 0) {
            return null;
        }

        $invoiceModel = new CreditCardInvoice();
        $invoiceModel->closeMaturedInvoicesForCard($cardId, $userId);

        $hoje = date('Y-m-d');
        $currentRef = CreditCardCalendar::invoiceReferenceForPurchase($hoje, $diaFechamento);
        $currentMonth = $currentRef['mes'];
        $currentYear  = $currentRef['ano'];

        // 1. PRIORIDADE MÁXIMA: Verifica se há alguma fatura fechada pendente de pagamento (até o ciclo atual)
        $sqlPending = "SELECT f.*, cp.nome as conta_pagamento_nome
                       FROM `faturas_cartao` f
                       LEFT JOIN `contas` cp ON f.conta_pagamento_id = cp.id
                       INNER JOIN `cartoes_credito` c ON c.id = f.cartao_credito_id
                       WHERE f.cartao_credito_id = :card_id
                         AND c.usuario_id = :usuario_id
                         AND f.status = 'fechada'
                         AND f.valor_total > 0
                         AND (f.ano_referencia < :ano OR (f.ano_referencia = :ano AND f.mes_referencia <= :mes))
                       ORDER BY f.ano_referencia ASC, f.mes_referencia ASC
                       LIMIT 1";

        $stmtPending = $this->db->prepare($sqlPending);
        $stmtPending->bindValue(':card_id', $cardId, PDO::PARAM_INT);
        $stmtPending->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmtPending->bindValue(':mes', $currentMonth, PDO::PARAM_INT);
        $stmtPending->bindValue(':ano', $currentYear, PDO::PARAM_INT);
        $stmtPending->execute();
        $pendingInvoice = $stmtPending->fetch();

        if ($pendingInvoice) {
            return $pendingInvoice;
        }

        // 2. Se não houver fatura fechada pendente, obtém a fatura do ciclo aberto atual
        $sqlCurrent = "SELECT f.*, cp.nome as conta_pagamento_nome
                       FROM `faturas_cartao` f
                       LEFT JOIN `contas` cp ON f.conta_pagamento_id = cp.id
                       INNER JOIN `cartoes_credito` c ON c.id = f.cartao_credito_id
                       WHERE f.cartao_credito_id = :card_id
                         AND c.usuario_id = :usuario_id
                         AND f.mes_referencia = :mes
                         AND f.ano_referencia = :ano
                       LIMIT 1";

        $stmt = $this->db->prepare($sqlCurrent);
        $stmt->bindValue(':card_id', $cardId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':mes', $currentMonth, PDO::PARAM_INT);
        $stmt->bindValue(':ano', $currentYear, PDO::PARAM_INT);
        $stmt->execute();
        $currentInvoice = $stmt->fetch();

        if ($currentInvoice) {
            return $currentInvoice;
        }

        // 3. Fallback: cria a fatura do ciclo atual sob demanda
        return $invoiceModel->getOrCreateInvoice($cardId, $currentMonth, $currentYear);
    }

    /**
     * Verifica se o cartão possui transações ou faturas pendentes vinculadas.
     *
     * @param int $cardId
     * @return bool
     */
    public function hasTransactionsOrInvoices(int $cardId): bool
    {
        // 1. Verifica transações vinculadas
        $sqlTrans = "SELECT COUNT(*) as total FROM `transacoes`
                     WHERE cartao_credito_id = :card_id AND excluido_em IS NULL";
        $stmtTrans = $this->db->prepare($sqlTrans);
        $stmtTrans->bindValue(':card_id', $cardId, PDO::PARAM_INT);
        $stmtTrans->execute();
        $resTrans = $stmtTrans->fetch();
        if (((int) ($resTrans['total'] ?? 0)) > 0) {
            return true;
        }

        // 2. Verifica faturas não pagas
        $sqlFat = "SELECT COUNT(*) as total FROM `faturas_cartao`
                   WHERE cartao_credito_id = :card_id AND status != 'paga'";
        $stmtFat = $this->db->prepare($sqlFat);
        $stmtFat->bindValue(':card_id', $cardId, PDO::PARAM_INT);
        $stmtFat->execute();
        $resFat = $stmtFat->fetch();

        return ((int) ($resFat['total'] ?? 0)) > 0;
    }

    /**
     * Exclui logicamente o cartão de crédito se não houver vínculos impeditivos.
     *
     * @param int $id
     * @param int $userId
     * @return array{success: bool, message: string}
     */
    public function deleteCard(int $id, int $userId): array
    {
        $card = $this->find($id);
        if (!$card) {
            return ['success' => false, 'message' => 'Cartão de crédito não encontrado.'];
        }

        if ($this->hasTransactionsOrInvoices($id)) {
            return [
                'success' => false,
                'message' => "Não é possível excluir o cartão '{$card['nome']}' pois existem lançamentos ou faturas vinculadas. Recomendamos inativar o cartão.",
            ];
        }

        $this->softDelete($id, $userId);

        return ['success' => true, 'message' => "Cartão de crédito '{$card['nome']}' excluído com sucesso."];
    }
}
