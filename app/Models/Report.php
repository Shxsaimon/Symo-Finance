<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Model de Relatórios Analíticos (Report)
 *
 * Executa consultas consolidadas, filtros combinados e agregação de dados
 * para relatórios analíticos e exportação estruturada em CSV.
 */
class Report extends Model
{
    /**
     * Retorna a listagem detalhada de transações conforme os filtros especificados.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function getDetailedReport(array $filters): array
    {
        $sql = "SELECT t.*, 
                       c.nome as conta_nome, c.cor as conta_cor,
                       cat.nome as categoria_nome, cat.icone as categoria_icone, cat.cor as categoria_cor,
                       mp.nome as meio_pagamento_nome,
                       cc.nome as cartao_nome, cc.cor as cartao_cor,
                       fc.data_vencimento as fatura_vencimento,
                       u.nome as usuario_nome
                FROM `transacoes` t
                LEFT JOIN `contas` c ON t.conta_id = c.id
                LEFT JOIN `categorias` cat ON t.categoria_id = cat.id
                LEFT JOIN `meios_pagamento` mp ON t.meio_pagamento_id = mp.id
                LEFT JOIN `cartoes_credito` cc ON t.cartao_credito_id = cc.id
                LEFT JOIN `faturas_cartao` fc ON t.fatura_id = fc.id
                LEFT JOIN `usuarios` u ON t.usuario_id = u.id
                WHERE t.usuario_id = :usuario_id AND t.excluido_em IS NULL";

        $params = [':usuario_id' => AuthHelper::id()];
        $this->applyFilters($sql, $params, $filters);

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
     * Calcula os totais e indicadores consolidados para o relatório analítico.
     *
     * @param array<string, mixed> $filters
     * @return array{total_receitas: float, receitas_recebidas: float, receitas_pendentes: float, total_despesas: float, despesas_pagas: float, despesas_pendentes: float, saldo_periodo: float, total_registros: int, ticket_medio: float}
     */
    public function getSummary(array $filters): array
    {
        $sql = "SELECT 
                    COUNT(t.id) as total_registros,
                    SUM(CASE WHEN t.tipo = 'receita' THEN t.valor ELSE 0 END) as total_receitas,
                    SUM(CASE WHEN t.tipo = 'receita' AND t.status = 'recebido' THEN t.valor ELSE 0 END) as receitas_recebidas,
                    SUM(CASE WHEN t.tipo = 'receita' AND t.status = 'pendente' THEN t.valor ELSE 0 END) as receitas_pendentes,
                    SUM(CASE WHEN t.tipo = 'despesa' THEN t.valor ELSE 0 END) as total_despesas,
                    SUM(CASE WHEN t.tipo = 'despesa' AND t.status = 'pago' THEN t.valor ELSE 0 END) as despesas_pagas,
                    SUM(CASE WHEN t.tipo = 'despesa' AND t.status = 'pendente' THEN t.valor ELSE 0 END) as despesas_pendentes
                FROM `transacoes` t
                LEFT JOIN `contas` c ON t.conta_id = c.id
                LEFT JOIN `categorias` cat ON t.categoria_id = cat.id
                LEFT JOIN `meios_pagamento` mp ON t.meio_pagamento_id = mp.id
                LEFT JOIN `cartoes_credito` cc ON t.cartao_credito_id = cc.id
                LEFT JOIN `faturas_cartao` fc ON t.fatura_id = fc.id
                WHERE t.usuario_id = :usuario_id AND t.excluido_em IS NULL";

        $params = [':usuario_id' => AuthHelper::id()];
        $this->applyFilters($sql, $params, $filters);

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            if (is_int($v)) {
                $stmt->bindValue($k, $v, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
        }
        $stmt->execute();
        $res = $stmt->fetch();

        $totalRegistros = (int) ($res['total_registros'] ?? 0);
        $totalReceitas = (float) ($res['total_receitas'] ?? 0.0);
        $receitasRecebidas = (float) ($res['receitas_recebidas'] ?? 0.0);
        $receitasPendentes = (float) ($res['receitas_pendentes'] ?? 0.0);

        $totalDespesas = (float) ($res['total_despesas'] ?? 0.0);
        $despesasPagas = (float) ($res['despesas_pagas'] ?? 0.0);
        $despesasPendentes = (float) ($res['despesas_pendentes'] ?? 0.0);

        $saldoPeriodo = $totalReceitas - $totalDespesas;
        $ticketMedio = ($totalRegistros > 0) ? round(($totalReceitas + $totalDespesas) / $totalRegistros, 2) : 0.0;

        return [
            'total_registros'    => $totalRegistros,
            'total_receitas'     => $totalReceitas,
            'receitas_recebidas' => $receitasRecebidas,
            'receitas_pendentes' => $receitasPendentes,
            'total_despesas'     => $totalDespesas,
            'despesas_pagas'     => $despesasPagas,
            'despesas_pendentes' => $despesasPendentes,
            'saldo_periodo'      => $saldoPeriodo,
            'ticket_medio'       => $ticketMedio,
        ];
    }

    /**
     * Retorna os dados agrupados por categoria para o relatório sintético.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function getGroupedByCategory(array $filters): array
    {
        $sql = "SELECT cat.id, cat.nome, cat.tipo, cat.cor, cat.icone,
                       COUNT(t.id) as total_operacoes,
                       SUM(t.valor) as total_valor
                FROM `transacoes` t
                JOIN `categorias` cat ON t.categoria_id = cat.id
                LEFT JOIN `contas` c ON t.conta_id = c.id
                LEFT JOIN `meios_pagamento` mp ON t.meio_pagamento_id = mp.id
                LEFT JOIN `cartoes_credito` cc ON t.cartao_credito_id = cc.id
                LEFT JOIN `faturas_cartao` fc ON t.fatura_id = fc.id
                WHERE t.usuario_id = :usuario_id AND t.excluido_em IS NULL";

        $params = [':usuario_id' => AuthHelper::id()];
        $this->applyFilters($sql, $params, $filters);

        $sql .= " GROUP BY cat.id, cat.nome, cat.tipo, cat.cor, cat.icone
                  ORDER BY total_valor DESC";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            if (is_int($v)) {
                $stmt->bindValue($k, $v, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
        }
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
     * Aplica as cláusulas WHERE correspondentes aos filtros selecionados.
     *
     * @param string $sql
     * @param array<string, mixed> $params
     * @param array<string, mixed> $filters
     * @return void
     */
    private function applyFilters(string &$sql, array &$params, array $filters): void
    {
        // 1. Filtro de Período
        $tipoPeriodo = $filters['tipo_periodo'] ?? 'mes';
        if ($tipoPeriodo === 'custom' && !empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $sql .= " AND t.data_transacao BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = (string) $filters['data_inicio'];
            $params[':data_fim'] = (string) $filters['data_fim'];
        } else {
            if (!empty($filters['mes'])) {
                $sql .= " AND MONTH(t.data_transacao) = :mes";
                $params[':mes'] = (int) $filters['mes'];
            }
            if (!empty($filters['ano'])) {
                $sql .= " AND YEAR(t.data_transacao) = :ano";
                $params[':ano'] = (int) $filters['ano'];
            }
        }

        // 2. Tipo de Movimentação (receita / despesa)
        if (!empty($filters['tipo']) && in_array($filters['tipo'], ['receita', 'despesa'], true)) {
            $sql .= " AND t.tipo = :tipo";
            $params[':tipo'] = (string) $filters['tipo'];
        }

        // 3. Status (pendente, pago, recebido)
        if (!empty($filters['status']) && in_array($filters['status'], ['pendente', 'pago', 'recebido'], true)) {
            $sql .= " AND t.status = :status";
            $params[':status'] = (string) $filters['status'];
        }

        // 4. Categoria
        if (!empty($filters['categoria_id'])) {
            $sql .= " AND t.categoria_id = :categoria_id";
            $params[':categoria_id'] = (int) $filters['categoria_id'];
        }

        // 5. Conta Bancária
        if (!empty($filters['conta_id'])) {
            $sql .= " AND t.conta_id = :conta_id";
            $params[':conta_id'] = (int) $filters['conta_id'];
        }

        // 6. Cartão de Crédito
        if (!empty($filters['cartao_credito_id'])) {
            $sql .= " AND t.cartao_credito_id = :cartao_id";
            $params[':cartao_id'] = (int) $filters['cartao_credito_id'];
        }

        // 7. Meio de Pagamento
        if (!empty($filters['meio_pagamento_id'])) {
            $sql .= " AND t.meio_pagamento_id = :meio_id";
            $params[':meio_id'] = (int) $filters['meio_pagamento_id'];
        }

        // 8. Busca Textual na Descrição ou Observações
        if (!empty($filters['busca'])) {
            $sql .= " AND (t.descricao LIKE :busca_desc OR t.observacoes LIKE :busca_obs)";
            $buscaTerm = '%' . trim((string) $filters['busca']) . '%';
            $params[':busca_desc'] = $buscaTerm;
            $params[':busca_obs'] = $buscaTerm;
        }
    }
}
