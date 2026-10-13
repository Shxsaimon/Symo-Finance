<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Relatórios Analíticos e Exportação Estruturada em CSV (Fase 11)
 */
$mesesNomes = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
$anoAtual = (int) date('Y');
$balancoPositivo = ((float)$resumo['saldo_periodo'] >= 0);

// Constrói a query string com os filtros atuais para o botão de exportar CSV
$queryParams = http_build_query($filters);
$exportUrl = BASE_URL . '/relatorios/exportar-csv?' . $queryParams;
?>
<!-- Cabeçalho e Ações Principais -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--symo-on-surface);">Relatórios Analíticos</h1>
        <p class="text-muted mb-0" style="font-size: 0.95rem;">
            Extratos consolidados, análises de fluxo de caixa e exportação estruturada em planilha.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2 shadow-sm" onclick="window.print()">
            <i class="bi bi-printer"></i>
            <span class="d-none d-sm-inline">Imprimir</span>
        </button>
        <a href="<?= $exportUrl ?>" class="btn btn-success d-flex align-items-center gap-2 shadow-sm fw-semibold" style="background-color: #059669; border-color: #059669;">
            <i class="bi bi-file-earmark-excel-fill fs-5"></i>
            <span>Exportar CSV</span>
        </a>
    </div>
</div>

<!-- 1. Cards de Resumo Analítico do Filtro -->
<div class="row g-3 mb-4">
    <!-- Card 1: Receitas do Filtro -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="symo-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="label-caps">Receitas do Período</span>
                <div class="icon-box" style="background-color: #d1fae5; color: #047857; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-arrow-down-left fs-5"></i>
                </div>
            </div>
            <div class="kpi-value fs-3 fw-bold mb-1 text-success tabular-nums">
                <?= FormatHelper::currency((float)$resumo['total_receitas']) ?>
            </div>
            <div class="d-flex justify-content-between text-muted" style="font-size: 0.8rem;">
                <span class="text-success"><i class="bi bi-check2"></i> Realizadas: <?= FormatHelper::currency((float)$resumo['receitas_recebidas']) ?></span>
                <?php if ((float)$resumo['receitas_pendentes'] > 0): ?>
                    <span class="text-secondary"><i class="bi bi-clock"></i> Pend: <?= FormatHelper::currency((float)$resumo['receitas_pendentes']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Card 2: Despesas do Filtro -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="symo-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="label-caps">Despesas do Período</span>
                <div class="icon-box" style="background-color: #ffdad6; color: #ba1a1a; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-arrow-up-right fs-5"></i>
                </div>
            </div>
            <div class="kpi-value fs-3 fw-bold mb-1 text-danger tabular-nums">
                <?= FormatHelper::currency((float)$resumo['total_despesas']) ?>
            </div>
            <div class="d-flex justify-content-between text-muted" style="font-size: 0.8rem;">
                <span class="text-danger"><i class="bi bi-check2"></i> Pagas: <?= FormatHelper::currency((float)$resumo['despesas_pagas']) ?></span>
                <?php if ((float)$resumo['despesas_pendentes'] > 0): ?>
                    <span class="text-secondary"><i class="bi bi-clock"></i> Pend: <?= FormatHelper::currency((float)$resumo['despesas_pendentes']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Card 3: Balanço Líquido -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="symo-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="label-caps">Balanço Líquido</span>
                <div class="icon-box" style="background-color: <?= $balancoPositivo ? '#e0f2fe' : '#fee2e2' ?>; color: <?= $balancoPositivo ? '#0284c7' : '#dc2626' ?>; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-pie-chart fs-5"></i>
                </div>
            </div>
            <div class="kpi-value fs-3 fw-bold mb-1 tabular-nums <?= $balancoPositivo ? 'text-success' : 'text-danger' ?>">
                <?= FormatHelper::currency((float)$resumo['saldo_periodo']) ?>
            </div>
            <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.8rem;">
                <i class="bi bi-activity"></i>
                <span><?= $balancoPositivo ? 'Superávit no período' : 'Déficit no período' ?></span>
            </div>
        </div>
    </div>

    <!-- Card 4: Registros e Ticket Médio -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="symo-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="label-caps">Volume de Operações</span>
                <div class="icon-box" style="background-color: var(--symo-primary-fixed); color: var(--symo-on-primary-fixed); width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-calculator fs-5"></i>
                </div>
            </div>
            <div class="kpi-value fs-3 fw-bold mb-1 tabular-nums" style="color: var(--symo-on-surface);">
                <?= $resumo['total_registros'] ?> <span class="fs-6 text-muted fw-normal">lançamentos</span>
            </div>
            <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.8rem;">
                <i class="bi bi-tag"></i>
                <span>Ticket médio: <strong><?= FormatHelper::currency((float)$resumo['ticket_medio']) ?></strong></span>
            </div>
        </div>
    </div>
</div>

<!-- 2. Painel de Filtros Avançados -->
<div class="symo-card mb-4 p-3 bg-light border">
    <form method="GET" action="<?= BASE_URL ?>/relatorios" id="formFiltrosRelatorio">
        <div class="row g-3">
            <!-- Tipo de Período -->
            <div class="col-12 col-md-3">
                <label for="tipo_periodo" class="form-label small fw-semibold mb-1">Tipo de Período</label>
                <select class="form-select form-select-sm" id="tipo_periodo" name="tipo_periodo" onchange="alternarCamposPeriodo(this.value)">
                    <option value="mes" <?= $filters['tipo_periodo'] === 'mes' ? 'selected' : '' ?>>Mês e Ano</option>
                    <option value="custom" <?= $filters['tipo_periodo'] === 'custom' ? 'selected' : '' ?>>Intervalo Personalizado</option>
                </select>
            </div>

            <!-- Campos Mês e Ano -->
            <div class="col-6 col-md-2 campos-mes-ano" style="<?= $filters['tipo_periodo'] === 'custom' ? 'display: none;' : '' ?>">
                <label for="rep_mes" class="form-label small fw-semibold mb-1">Mês</label>
                <select class="form-select form-select-sm" id="rep_mes" name="mes">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= (int)$filters['mes'] === $m ? 'selected' : '' ?>>
                            <?= $mesesNomes[$m] ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-6 col-md-2 campos-mes-ano" style="<?= $filters['tipo_periodo'] === 'custom' ? 'display: none;' : '' ?>">
                <label for="rep_ano" class="form-label small fw-semibold mb-1">Ano</label>
                <select class="form-select form-select-sm" id="rep_ano" name="ano">
                    <?php for ($a = $anoAtual - 3; $a <= $anoAtual + 2; $a++): ?>
                        <option value="<?= $a ?>" <?= (int)$filters['ano'] === $a ? 'selected' : '' ?>>
                            <?= $a ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Campos Data Inicial e Data Final -->
            <div class="col-6 col-md-2 campos-custom-data" style="<?= $filters['tipo_periodo'] !== 'custom' ? 'display: none;' : '' ?>">
                <label for="data_inicio" class="form-label small fw-semibold mb-1">Data Inicial</label>
                <input type="date" class="form-control form-select-sm" id="data_inicio" name="data_inicio" value="<?= htmlspecialchars((string)$filters['data_inicio'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-6 col-md-2 campos-custom-data" style="<?= $filters['tipo_periodo'] !== 'custom' ? 'display: none;' : '' ?>">
                <label for="data_fim" class="form-label small fw-semibold mb-1">Data Final</label>
                <input type="date" class="form-control form-select-sm" id="data_fim" name="data_fim" value="<?= htmlspecialchars((string)$filters['data_fim'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <!-- Tipo de Movimentação -->
            <div class="col-6 col-md-2">
                <label for="rep_tipo" class="form-label small fw-semibold mb-1">Tipo</label>
                <select class="form-select form-select-sm" id="rep_tipo" name="tipo">
                    <option value="">Todas (Receitas & Despesas)</option>
                    <option value="receita" <?= $filters['tipo'] === 'receita' ? 'selected' : '' ?>>Apenas Receitas</option>
                    <option value="despesa" <?= $filters['tipo'] === 'despesa' ? 'selected' : '' ?>>Apenas Despesas</option>
                </select>
            </div>

            <!-- Status -->
            <div class="col-6 col-md-3">
                <label for="rep_status" class="form-label small fw-semibold mb-1">Status</label>
                <select class="form-select form-select-sm" id="rep_status" name="status">
                    <option value="">Todos os status</option>
                    <option value="pago" <?= $filters['status'] === 'pago' ? 'selected' : '' ?>>Pago / Liquidado</option>
                    <option value="recebido" <?= $filters['status'] === 'recebido' ? 'selected' : '' ?>>Recebido</option>
                    <option value="pendente" <?= $filters['status'] === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                </select>
            </div>

            <!-- Categoria -->
            <div class="col-6 col-md-3">
                <label for="rep_categoria" class="form-label small fw-semibold mb-1">Categoria</label>
                <select class="form-select form-select-sm" id="rep_categoria" name="categoria_id">
                    <option value="">Todas as categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (int)$filters['categoria_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= ucfirst($cat['tipo']) ?>: <?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Conta Bancária -->
            <div class="col-6 col-md-3">
                <label for="rep_conta" class="form-label small fw-semibold mb-1">Conta Bancária</label>
                <select class="form-select form-select-sm" id="rep_conta" name="conta_id">
                    <option value="">Todas as contas</option>
                    <?php foreach ($contas as $acc): ?>
                        <option value="<?= $acc['id'] ?>" <?= (int)$filters['conta_id'] === (int)$acc['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($acc['nome'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Cartão de Crédito -->
            <div class="col-6 col-md-3">
                <label for="rep_cartao" class="form-label small fw-semibold mb-1">Cartão de Crédito</label>
                <select class="form-select form-select-sm" id="rep_cartao" name="cartao_credito_id">
                    <option value="">Todos os cartões</option>
                    <?php foreach ($cartoes as $card): ?>
                        <option value="<?= $card['id'] ?>" <?= (int)$filters['cartao_credito_id'] === (int)$card['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($card['nome'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Visão / Agrupamento -->
            <div class="col-6 col-md-3">
                <label for="rep_agrupamento" class="form-label small fw-semibold mb-1">Visão do Relatório</label>
                <select class="form-select form-select-sm fw-semibold" id="rep_agrupamento" name="agrupamento">
                    <option value="analitico" <?= $filters['agrupamento'] === 'analitico' ? 'selected' : '' ?>>Extrato Analítico (Detalhado)</option>
                    <option value="categoria" <?= $filters['agrupamento'] === 'categoria' ? 'selected' : '' ?>>Sintético por Categoria</option>
                </select>
            </div>

            <!-- Busca Textual e Botões -->
            <div class="col-12 col-md-9">
                <label for="rep_busca" class="form-label small fw-semibold mb-1">Busca por Descrição ou Observações</label>
                <input type="text" class="form-control form-control-sm" id="rep_busca" name="busca" placeholder="Filtrar por palavras-chave..." value="<?= htmlspecialchars((string)($filters['busca'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-12 col-md-3 d-flex gap-2 align-items-end">
                <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                    <i class="bi bi-funnel me-1"></i> Filtrar
                </button>
                <a href="<?= BASE_URL ?>/relatorios" class="btn btn-sm btn-outline-secondary" title="Limpar Filtros">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </div>
    </form>
</div>

<!-- 3. Tabelas de Resultados Conforme o Agrupamento Selecionado -->

<?php if ($filters['agrupamento'] === 'categoria'): ?>
    <!-- VISÃO SINTÉTICA POR CATEGORIA -->
    <div class="symo-card p-0 overflow-hidden mb-4">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
            <h5 class="mb-0 fw-bold fs-6" style="color: var(--symo-on-surface);">
                <i class="bi bi-pie-chart-fill me-2" style="color: var(--symo-primary-brand);"></i>Resumo por Categoria
            </h5>
            <span class="badge text-bg-secondary rounded-pill"><?= count($dadosAgrupados) ?> categorias</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-finance">
                <thead class="table-light">
                    <tr class="label-caps">
                        <th scope="col" style="padding-left: 1.5rem;">Categoria</th>
                        <th scope="col">Tipo</th>
                        <th scope="col" class="text-center">Lançamentos</th>
                        <th scope="col" style="width: 30%;">Participação (%)</th>
                        <th scope="col" class="text-end" style="padding-right: 1.5rem;">Total (R$)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dadosAgrupados)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                Nenhum lançamento encontrado para os filtros selecionados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dadosAgrupados as $item): ?>
                            <?php
                                $isReceita = ($item['tipo'] === 'receita');
                                $catCor = !empty($item['cor']) ? htmlspecialchars($item['cor'], ENT_QUOTES, 'UTF-8') : '#6F02FF';
                                $catIcone = !empty($item['icone']) ? htmlspecialchars($item['icone'], ENT_QUOTES, 'UTF-8') : 'bi-tag';
                            ?>
                            <tr>
                                <td style="padding-left: 1.5rem;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width: 28px; height: 28px; border-radius: 6px; background-color: <?= $catCor ?>20; color: <?= $catCor ?>; display: flex; align-items: center; justify-content: center;">
                                            <i class="bi <?= $catIcone ?>"></i>
                                        </div>
                                        <span class="fw-bold" style="color: var(--symo-on-surface);"><?= htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $isReceita ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?>">
                                        <?= ucfirst((string)$item['tipo']) ?>
                                    </span>
                                </td>
                                <td class="text-center tabular-nums fw-semibold">
                                    <?= $item['total_operacoes'] ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar <?= $isReceita ? 'bg-success' : 'bg-danger' ?>" 
                                                 role="progressbar" 
                                                 style="width: <?= $item['percentual'] ?>%;" 
                                                 aria-valuenow="<?= $item['percentual'] ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                        <span class="tabular-nums small fw-bold text-muted" style="min-width: 45px; text-align: right;"><?= $item['percentual'] ?>%</span>
                                    </div>
                                </td>
                                <td class="text-end tabular-nums fw-bold fs-6 <?= $isReceita ? 'text-success' : 'text-danger' ?>" style="padding-right: 1.5rem;">
                                    <?= $isReceita ? '+' : '-' ?> <?= FormatHelper::currency((float)$item['total_valor']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>
    <!-- VISÃO ANALÍTICA (EXTRATO COMPLETO DETALHADO) -->
    <div class="symo-card p-0 overflow-hidden mb-4">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
            <h5 class="mb-0 fw-bold fs-6" style="color: var(--symo-on-surface);">
                <i class="bi bi-receipt-cutoff me-2 text-muted"></i>Extrato Analítico Detalhado
            </h5>
            <span class="badge text-bg-secondary rounded-pill"><?= count($dadosAnaliticos) ?> registros</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-finance">
                <thead class="table-light">
                    <tr class="label-caps">
                        <th scope="col" style="padding-left: 1.5rem;">Data</th>
                        <th scope="col">Categoria</th>
                        <th scope="col">Descrição</th>
                        <th scope="col">Origem / Destino</th>
                        <th scope="col">Meio / Vencimento</th>
                        <th scope="col" class="text-center">Status</th>
                        <th scope="col" class="text-end" style="padding-right: 1.5rem;">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dadosAnaliticos)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                Nenhum lançamento encontrado para os filtros selecionados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dadosAnaliticos as $t): ?>
                            <?php
                                $isReceita = ($t['tipo'] === 'receita');
                                $catCor = !empty($t['categoria_cor']) ? htmlspecialchars($t['categoria_cor'], ENT_QUOTES, 'UTF-8') : '#793CFE';
                                $catIcone = !empty($t['categoria_icone']) ? htmlspecialchars($t['categoria_icone'], ENT_QUOTES, 'UTF-8') : 'bi-tag';
                            ?>
                            <tr>
                                <!-- Data da Compra -->
                                <td style="padding-left: 1.5rem;" class="tabular-nums text-muted small">
                                    <span class="fw-semibold text-dark"><?= FormatHelper::date($t['data_transacao']) ?></span>
                                </td>

                                <!-- Categoria -->
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width: 26px; height: 26px; border-radius: 6px; background-color: <?= $catCor ?>20; color: <?= $catCor ?>; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;">
                                            <i class="bi <?= $catIcone ?>"></i>
                                        </div>
                                        <span class="small fw-semibold" style="color: var(--symo-on-surface);">
                                            <?= htmlspecialchars($t['categoria_nome'] ?? 'Geral', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                </td>

                                <!-- Descrição -->
                                <td>
                                    <div class="fw-bold" style="color: var(--symo-on-surface);">
                                        <?= htmlspecialchars(FormatHelper::transactionDescription($t), ENT_QUOTES, 'UTF-8') ?>
                                        <?php if (!empty($t['total_parcelas']) && (int)$t['total_parcelas'] > 1): ?>
                                            <span class="badge text-bg-light border text-muted ms-1" style="font-size: 0.75rem;">
                                                <?= $t['numero_parcela'] ?>/<?= $t['total_parcelas'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted" style="font-size: 0.75rem;">
                                        Por: <?= htmlspecialchars($t['usuario_nome'] ?? 'Usuário', ENT_QUOTES, 'UTF-8') ?>
                                        <?php if (!empty($t['observacoes'])): ?>
                                            • <em><?= htmlspecialchars($t['observacoes'], ENT_QUOTES, 'UTF-8') ?></em>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Origem / Destino -->
                                <td>
                                    <?php if (!empty($t['conta_nome'])): ?>
                                        <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1">
                                            <span style="width: 8px; height: 8px; border-radius: 50%; background-color: <?= htmlspecialchars($t['conta_cor'] ?? '#6F02FF', ENT_QUOTES, 'UTF-8') ?>;"></span>
                                            <?= htmlspecialchars($t['conta_nome'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php elseif (!empty($t['cartao_nome'])): ?>
                                        <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1">
                                            <span style="width: 8px; height: 8px; border-radius: 50%; background-color: <?= htmlspecialchars($t['cartao_cor'] ?? '#3BD7D7', ENT_QUOTES, 'UTF-8') ?>;"></span>
                                            <?= htmlspecialchars($t['cartao_nome'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Meio de Pagamento e Vencimento -->
                                <td class="small text-muted">
                                    <div><?= htmlspecialchars($t['meio_pagamento_nome'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php if (!empty($t['data_vencimento']) && $t['data_vencimento'] !== $t['data_transacao']): ?>
                                        <div class="tabular-nums" style="font-size: 0.72rem;">Venc: <?= FormatHelper::date($t['data_vencimento']) ?></div>
                                    <?php endif; ?>
                                </td>

                                <!-- Status -->
                                <td class="text-center">
                                    <?php if ($t['status'] === 'pago' || $t['status'] === 'recebido'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-check2"></i> <?= ucfirst((string)$t['status']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                            <i class="bi bi-clock"></i> Pendente
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Valor -->
                                <td class="text-end tabular-nums fw-bold fs-6 <?= $isReceita ? 'text-success' : 'text-danger' ?>" style="padding-right: 1.5rem;">
                                    <?= $isReceita ? '+' : '-' ?> <?= FormatHelper::currency((float)$t['valor']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <!-- Rodapé com Totalizadores -->
                <?php if (!empty($dadosAnaliticos)): ?>
                    <tfoot class="table-light border-top border-2">
                        <tr class="fw-bold">
                            <td colspan="4" style="padding-left: 1.5rem;">Totalizador do Período Filtrado:</td>
                            <td colspan="2" class="text-end text-muted small">
                                Receitas: <span class="text-success"><?= FormatHelper::currency((float)$resumo['total_receitas']) ?></span> | 
                                Despesas: <span class="text-danger"><?= FormatHelper::currency((float)$resumo['total_despesas']) ?></span>
                            </td>
                            <td class="text-end tabular-nums fs-6 <?= $balancoPositivo ? 'text-success' : 'text-danger' ?>" style="padding-right: 1.5rem;">
                                <?= FormatHelper::currency((float)$resumo['saldo_periodo']) ?>
                            </td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
function alternarCamposPeriodo(tipo) {
    const mesAno = document.querySelectorAll('.campos-mes-ano');
    const custom = document.querySelectorAll('.campos-custom-data');

    if (tipo === 'custom') {
        mesAno.forEach(el => el.style.display = 'none');
        custom.forEach(el => el.style.display = 'block');
    } else {
        mesAno.forEach(el => el.style.display = 'block');
        custom.forEach(el => el.style.display = 'none');
    }
}
</script>
