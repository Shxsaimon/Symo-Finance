<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Lançamentos Financeiros (Receitas, Despesas e Parcelamento)
 */
$mesesNomes = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
$anoAtual = (int) date('Y');
?>
<!-- Cabeçalho e Ações Principais -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--symo-on-surface);">Lançamentos</h1>
        <p class="text-muted mb-0" style="font-size: 0.95rem;">
            Gerencie todas as receitas, despesas à vista e compras parceladas do orçamento.
        </p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-success fw-semibold d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovaReceita">
            <i class="bi bi-arrow-down-circle"></i>
            <span>Nova Receita</span>
        </button>
        <button type="button" class="btn-symo-primary d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovaDespesa">
            <i class="bi bi-arrow-up-circle"></i>
            <span>Nova Despesa</span>
        </button>
    </div>
</div>

<!-- Cards Resumo Financeiro do Período -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="symo-card">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="label-caps text-success"><i class="bi bi-arrow-down-left me-1"></i> Receitas do Período</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                    <?= FormatHelper::currency((float) $resumo['receitas_recebidas']) ?> recebido
                </span>
            </div>
            <div class="kpi-value fs-2 fw-bold tabular-nums text-success">
                <?= FormatHelper::currency((float) $resumo['total_receitas']) ?>
            </div>
            <div class="text-muted small">
                Pendente: <strong class="tabular-nums"><?= FormatHelper::currency((float) $resumo['receitas_pendentes']) ?></strong>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="symo-card">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="label-caps text-danger"><i class="bi bi-arrow-up-right me-1"></i> Despesas do Período</span>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                    <?= FormatHelper::currency((float) $resumo['despesas_pagas']) ?> pago
                </span>
            </div>
            <div class="kpi-value fs-2 fw-bold tabular-nums text-danger">
                <?= FormatHelper::currency((float) $resumo['total_despesas']) ?>
            </div>
            <div class="text-muted small">
                Pendente: <strong class="tabular-nums"><?= FormatHelper::currency((float) $resumo['despesas_pendentes']) ?></strong>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="symo-card">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="label-caps" style="color: var(--symo-primary-brand);"><i class="bi bi-wallet2 me-1"></i> Balanço do Período</span>
            </div>
            <div class="kpi-value fs-2 fw-bold tabular-nums <?= $resumo['saldo_periodo'] >= 0 ? 'text-primary' : 'text-danger' ?>">
                <?= FormatHelper::currency((float) $resumo['saldo_periodo']) ?>
            </div>
            <div class="text-muted small">
                Resultado líquido de competência
            </div>
        </div>
    </div>
</div>

<!-- Filtros Avançados -->
<div class="symo-card mb-4 p-3 bg-light border">
    <form method="GET" action="<?= BASE_URL ?>/transacoes" class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label for="filter_mes" class="form-label small fw-semibold mb-1">Mês</label>
            <select class="form-select form-select-sm" id="filter_mes" name="mes">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= (int)$filters['mes'] === $m ? 'selected' : '' ?>>
                        <?= $mesesNomes[$m] ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label for="filter_ano" class="form-label small fw-semibold mb-1">Ano</label>
            <select class="form-select form-select-sm" id="filter_ano" name="ano">
                <?php for ($a = $anoAtual - 2; $a <= $anoAtual + 2; $a++): ?>
                    <option value="<?= $a ?>" <?= (int)$filters['ano'] === $a ? 'selected' : '' ?>>
                        <?= $a ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label for="filter_tipo" class="form-label small fw-semibold mb-1">Tipo</label>
            <select class="form-select form-select-sm" id="filter_tipo" name="tipo">
                <option value="">Todos os tipos</option>
                <option value="receita" <?= $filters['tipo'] === 'receita' ? 'selected' : '' ?>>Receitas</option>
                <option value="despesa" <?= $filters['tipo'] === 'despesa' ? 'selected' : '' ?>>Despesas</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label for="filter_status" class="form-label small fw-semibold mb-1">Status</label>
            <select class="form-select form-select-sm" id="filter_status" name="status">
                <option value="">Todos os status</option>
                <option value="pendente" <?= $filters['status'] === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                <option value="pago" <?= $filters['status'] === 'pago' ? 'selected' : '' ?>>Pago</option>
                <option value="recebido" <?= $filters['status'] === 'recebido' ? 'selected' : '' ?>>Recebido</option>
            </select>
        </div>
        <div class="col-12 col-md-2">
            <label for="filter_conta" class="form-label small fw-semibold mb-1">Conta Bancária</label>
            <select class="form-select form-select-sm" id="filter_conta" name="conta_id">
                <option value="">Todas as contas</option>
                <?php foreach ($contas as $acc): ?>
                    <option value="<?= $acc['id'] ?>" <?= (int)$filters['conta_id'] === (int)$acc['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($acc['nome'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                <i class="bi bi-funnel me-1"></i> Filtrar
            </button>
            <a href="<?= BASE_URL ?>/transacoes" class="btn btn-sm btn-outline-secondary" title="Limpar Filtros">
                <i class="bi bi-x-lg"></i>
            </a>
        </div>
    </form>
</div>

<!-- Tabela de Lançamentos -->
<div class="symo-card p-0 overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
        <h5 class="mb-0 fw-bold fs-6" style="color: var(--symo-on-surface);">
            <i class="bi bi-receipt-cutoff me-2 text-muted"></i>Extrato de Lançamentos
        </h5>
        <span class="badge text-bg-secondary rounded-pill"><?= count($transacoes) ?> lançamentos</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-finance">
            <thead class="table-light">
                <tr class="label-caps">
                    <th scope="col" style="padding-left: 1.5rem;">Data</th>
                    <th scope="col">Categoria</th>
                    <th scope="col">Descrição</th>
                    <th scope="col">Origem / Destino</th>
                    <th scope="col" class="text-center">Status</th>
                    <th scope="col" class="text-end">Valor</th>
                    <th scope="col" class="text-end" style="padding-right: 1.5rem;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transacoes)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                            Nenhum lançamento encontrado para o período ou filtros selecionados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transacoes as $t): ?>
                        <?php
                            $isReceita = $t['tipo'] === 'receita';
                            $podeEditar = $isAdmin || ((int)$t['usuario_id'] === $currentUserId);
                            $catCor = !empty($t['categoria_cor']) ? htmlspecialchars($t['categoria_cor'], ENT_QUOTES, 'UTF-8') : '#793CFE';
                            $catIcone = !empty($t['categoria_icone']) ? htmlspecialchars($t['categoria_icone'], ENT_QUOTES, 'UTF-8') : 'bi-tag';
                        ?>
                        <tr>
                            <!-- Data da Transação / Compra -->
                            <td style="padding-left: 1.5rem;" class="tabular-nums text-muted small">
                                <span class="fw-semibold text-dark"><?= FormatHelper::date($t['data_transacao']) ?></span>
                                <?php if (!empty($t['data_vencimento']) && $t['data_vencimento'] !== $t['data_transacao']): ?>
                                    <div class="text-muted" style="font-size: 0.72rem;">Venc: <?= FormatHelper::date($t['data_vencimento']) ?></div>
                                <?php endif; ?>
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
                                    <?= htmlspecialchars($t['descricao'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (!empty($t['total_parcelas']) && (int)$t['total_parcelas'] > 1): ?>
                                        <span class="badge text-bg-light border text-muted ms-1" style="font-size: 0.75rem;">
                                            <?= $t['numero_parcela'] ?>/<?= $t['total_parcelas'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted small">
                                    Por: <?= htmlspecialchars($t['usuario_nome'] ?? 'Sistema', ENT_QUOTES, 'UTF-8') ?>
                                    <?= !empty($t['meio_pagamento_nome']) ? '• ' . htmlspecialchars($t['meio_pagamento_nome'], ENT_QUOTES, 'UTF-8') : '' ?>
                                </div>
                            </td>

                            <!-- Origem / Destino (Conta ou Cartão) -->
                            <td>
                                <?php if (!empty($t['cartao_nome'])): ?>
                                    <span class="badge text-bg-info-subtle text-info border border-info-subtle">
                                        <i class="bi bi-credit-card-2-front me-1"></i>
                                        <?= htmlspecialchars($t['cartao_nome'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php elseif (!empty($t['conta_nome'])): ?>
                                    <span class="badge text-bg-light border text-dark">
                                        <i class="bi bi-bank me-1 text-muted"></i>
                                        <?= htmlspecialchars($t['conta_nome'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">Sem conta vinculada</span>
                                <?php endif; ?>
                            </td>

                            <!-- Status -->
                            <td class="text-center">
                                <?php if ($t['status'] === 'recebido' || $t['status'] === 'pago'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-check-circle me-1"></i> <?= $isReceita ? 'Recebido' : 'Pago' ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                        <i class="bi bi-clock me-1"></i> Pendente
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Valor -->
                            <td class="text-end tabular-nums fw-bold fs-6 <?= $isReceita ? 'text-success' : 'text-danger' ?>">
                                <?= $isReceita ? '+ ' : '- ' ?><?= FormatHelper::currency((float) $t['valor']) ?>
                            </td>

                            <!-- Ações -->
                            <td class="text-end" style="padding-right: 1.5rem;">
                                <div class="btn-group btn-group-sm">
                                    <?php if (empty($t['fatura_id'])): ?>
                                        <!-- Alternar Status Rápido -->
                                        <form action="<?= BASE_URL ?>/transacoes/status/<?= $t['id'] ?>" method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                                            <input type="hidden" name="redirect_to" value="<?= BASE_URL ?>/transacoes?mes=<?= $filters['mes'] ?>&ano=<?= $filters['ano'] ?>">
                                            <button type="submit" class="btn btn-outline-secondary" title="<?= ($t['status'] === 'pendente') ? 'Marcar como Efetivado' : 'Marcar como Pendente' ?>">
                                                <i class="bi <?= ($t['status'] === 'pendente') ? 'bi-check-lg text-success' : 'bi-arrow-counterclockwise text-muted' ?>"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($podeEditar): ?>
                                        <!-- Botão Editar Modal -->
                                        <button type="button" class="btn btn-outline-secondary btn-edit-transaction" data-id="<?= $t['id'] ?>" title="Editar Lançamento">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <!-- Botão Excluir -->
                                        <form action="<?= BASE_URL ?>/transacoes/excluir/<?= $t['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Deseja realmente excluir este lançamento?');">
                                            <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                                            <input type="hidden" name="redirect_to" value="<?= BASE_URL ?>/transacoes?mes=<?= $filters['mes'] ?>&ano=<?= $filters['ano'] ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Excluir">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Nova Receita -->
<div class="modal fade" id="modalNovaReceita" tabindex="-1" aria-labelledby="modalNovaReceitaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content symo-modal-content">
            <form action="<?= BASE_URL ?>/transacoes/salvar" method="POST">
                <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                <input type="hidden" name="tipo" value="receita">
                <input type="hidden" name="destino_tipo" value="conta">

                <div class="modal-header border-bottom bg-success text-white">
                    <h5 class="modal-title fw-bold" id="modalNovaReceitaLabel">
                        <i class="bi bi-arrow-down-circle me-1"></i> Nova Receita
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="rec_descricao" class="form-label fw-semibold">Descrição *</label>
                        <input type="text" class="form-control" id="rec_descricao" name="descricao" placeholder="Ex: Salário Mensal, Freelance, Rendimentos" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="rec_valor" class="form-label fw-semibold">Valor (R$) *</label>
                            <input type="text" class="form-control money-mask tabular-nums text-end fs-5 fw-bold text-success" id="rec_valor" name="valor" placeholder="R$ 0,00" required>
                        </div>
                        <div class="col-6">
                            <label for="rec_data" class="form-label fw-semibold">Data de Recebimento *</label>
                            <input type="date" class="form-control" id="rec_data" name="data_transacao" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="rec_categoria" class="form-label fw-semibold">Categoria *</label>
                            <select class="form-select" id="rec_categoria" name="categoria_id" required>
                                <?php foreach ($categoriasReceita as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="rec_conta" class="form-label fw-semibold">Conta de Destino *</label>
                            <select class="form-select" id="rec_conta" name="conta_id" required>
                                <?php foreach ($contas as $acc): ?>
                                    <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="rec_meio" class="form-label fw-semibold">Meio de Pagamento *</label>
                            <select class="form-select" id="rec_meio" name="meio_pagamento_id" required>
                                <?php foreach ($meiosPagamento as $mp): ?>
                                    <option value="<?= $mp['id'] ?>"><?= htmlspecialchars($mp['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="rec_status" class="form-label fw-semibold">Status Inicial</label>
                            <select class="form-select" id="rec_status" name="status">
                                <option value="recebido" selected>Recebido (Efetivado)</option>
                                <option value="pendente">Pendente (A Receber)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="rec_obs" class="form-label small text-muted">Observações</label>
                        <textarea class="form-control" id="rec_obs" name="observacoes" rows="2" placeholder="Notas adicionais (opcional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-semibold">Salvar Receita</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Nova Despesa (À Vista ou Parcelada) -->
<div class="modal fade" id="modalNovaDespesa" tabindex="-1" aria-labelledby="modalNovaDespesaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content symo-modal-content">
            <form action="<?= BASE_URL ?>/transacoes/salvar" method="POST">
                <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                <input type="hidden" name="tipo" value="despesa">

                <div class="modal-header border-bottom" style="background-color: var(--symo-primary-brand); color: white;">
                    <h5 class="modal-title fw-bold" id="modalNovaDespesaLabel">
                        <i class="bi bi-arrow-up-circle me-1"></i> Nova Despesa
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="desp_descricao" class="form-label fw-semibold">Descrição *</label>
                        <input type="text" class="form-control" id="desp_descricao" name="descricao" placeholder="Ex: Supermercado, Aluguel, Farmácia" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="desp_valor" class="form-label fw-semibold">Valor (R$) *</label>
                            <input type="text" class="form-control money-mask tabular-nums text-end fs-5 fw-bold text-danger" id="desp_valor" name="valor" placeholder="R$ 0,00" required>
                        </div>
                        <div class="col-6">
                            <label for="desp_data" class="form-label fw-semibold">Data de Vencimento *</label>
                            <input type="date" class="form-control" id="desp_data" name="data_transacao" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <!-- Tipo de Destino: Conta Corrente vs Cartão de Crédito -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block">Forma de Saída *</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="destino_tipo" id="destino_conta" value="conta" checked autocomplete="off">
                            <label class="btn btn-outline-primary" for="destino_conta">
                                <i class="bi bi-bank me-1"></i> Conta Bancária
                            </label>

                            <input type="radio" class="btn-check" name="destino_tipo" id="destino_cartao" value="cartao" autocomplete="off">
                            <label class="btn btn-outline-primary" for="destino_cartao">
                                <i class="bi bi-credit-card-2-front me-1"></i> Cartão de Crédito
                            </label>
                        </div>
                    </div>

                    <!-- Select Condicional: Conta Bancária -->
                    <div class="mb-3" id="wrapper_select_conta">
                        <label for="desp_conta" class="form-label fw-semibold">Conta para Débito *</label>
                        <select class="form-select" id="desp_conta" name="conta_id">
                            <?php foreach ($contas as $acc): ?>
                                <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Select Condicional: Cartão de Crédito -->
                    <div class="mb-3 d-none" id="wrapper_select_cartao">
                        <label for="desp_cartao" class="form-label fw-semibold">Cartão de Crédito *</label>
                        <select class="form-select" id="desp_cartao" name="cartao_credito_id">
                            <?php if (empty($cartoes)): ?>
                                <option value="">Nenhum cartão cadastrado</option>
                            <?php else: ?>
                                <?php foreach ($cartoes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome'], ENT_QUOTES, 'UTF-8') ?> (Disp: <?= FormatHelper::currency((float)$c['limite_disponivel']) ?>)</option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="desp_categoria" class="form-label fw-semibold">Categoria *</label>
                            <select class="form-select" id="desp_categoria" name="categoria_id" required>
                                <?php foreach ($categoriasDespesa as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="desp_meio" class="form-label fw-semibold">Meio de Pagamento *</label>
                            <select class="form-select" id="desp_meio" name="meio_pagamento_id" required>
                                <?php foreach ($meiosPagamento as $mp): ?>
                                    <option value="<?= $mp['id'] ?>"><?= htmlspecialchars($mp['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Opção de Parcelamento -->
                    <div class="p-3 bg-light border rounded mb-3">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="check_parcelado" name="parcelado" value="1">
                            <label class="form-check-label fw-semibold" for="check_parcelado">Compra Parcelada</label>
                        </div>
                        <div class="d-none" id="wrapper_parcelas">
                            <label for="desp_total_parcelas" class="form-label small fw-semibold">Quantidade de Parcelas (2 a 72x)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control text-center tabular-nums" id="desp_total_parcelas" name="total_parcelas" min="2" max="72" value="2">
                                <span class="input-group-text">parcelas mensais</span>
                            </div>
                            <small class="text-muted d-block mt-1">O sistema gerará automaticamente os lançamentos com sufixo [1/N] e datas mensais.</small>
                        </div>
                    </div>

                    <div class="row g-2 mb-3" id="wrapper_status_despesa">
                        <div class="col-12">
                            <label for="desp_status" class="form-label fw-semibold">Status Inicial</label>
                            <select class="form-select" id="desp_status" name="status">
                                <option value="pago" selected>Pago (Já liquidado)</option>
                                <option value="pendente">Pendente (A Pagar)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="desp_obs" class="form-label small text-muted">Observações</label>
                        <textarea class="form-control" id="desp_obs" name="observacoes" rows="2" placeholder="Notas adicionais (opcional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-symo-primary">Salvar Despesa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Editar Lançamento -->
<div class="modal fade" id="modalEditarTransacao" tabindex="-1" aria-labelledby="modalEditarTransacaoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content symo-modal-content">
            <form id="formEditarTransacao" action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalEditarTransacaoLabel">Editar Lançamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_descricao" class="form-label fw-semibold">Descrição *</label>
                        <input type="text" class="form-control" id="edit_descricao" name="descricao" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="edit_valor" class="form-label fw-semibold">Valor (R$) *</label>
                            <input type="text" class="form-control money-mask tabular-nums text-end fs-5 fw-bold" id="edit_valor" name="valor" required>
                        </div>
                        <div class="col-6">
                            <label for="edit_data_vencimento" class="form-label fw-semibold">Data *</label>
                            <input type="date" class="form-control" id="edit_data_vencimento" name="data_vencimento" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="edit_categoria_id" class="form-label fw-semibold">Categoria *</label>
                            <select class="form-select" id="edit_categoria_id" name="categoria_id" required>
                                <optgroup label="Despesas">
                                    <?php foreach ($categoriasDespesa as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Receitas">
                                    <?php foreach ($categoriasReceita as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="edit_meio_pagamento_id" class="form-label fw-semibold">Meio de Pagamento</label>
                            <select class="form-select" id="edit_meio_pagamento_id" name="meio_pagamento_id">
                                <?php foreach ($meiosPagamento as $mp): ?>
                                    <option value="<?= $mp['id'] ?>"><?= htmlspecialchars($mp['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="edit_conta_id" class="form-label fw-semibold">Conta Bancária</label>
                            <select class="form-select" id="edit_conta_id" name="conta_id">
                                <option value="">Nenhuma / Cartão</option>
                                <?php foreach ($contas as $acc): ?>
                                    <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="edit_status" class="form-label fw-semibold">Status</label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="pendente">Pendente</option>
                                <option value="pago">Pago</option>
                                <option value="recebido">Recebido</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="edit_observacoes" class="form-label small text-muted">Observações</label>
                        <textarea class="form-control" id="edit_observacoes" name="observacoes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-symo-primary">Atualizar Lançamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // 1. Controle de Destino na Nova Despesa (Conta vs Cartão)
        const radioConta = document.getElementById('destino_conta');
        const radioCartao = document.getElementById('destino_cartao');
        const wrapConta = document.getElementById('wrapper_select_conta');
        const wrapCartao = document.getElementById('wrapper_select_cartao');
        const wrapStatus = document.getElementById('wrapper_status_despesa');

        function toggleDestino() {
            if (radioCartao.checked) {
                wrapConta.classList.add('d-none');
                wrapCartao.classList.remove('d-none');
                wrapStatus.classList.add('d-none'); // Despesa no cartão é sempre pendente até fechar fatura
            } else {
                wrapConta.classList.remove('d-none');
                wrapCartao.classList.add('d-none');
                wrapStatus.classList.remove('d-none');
            }
        }

        if (radioConta && radioCartao) {
            radioConta.addEventListener('change', toggleDestino);
            radioCartao.addEventListener('change', toggleDestino);
        }

        // 2. Controle do Toggle de Parcelamento
        const checkParcelado = document.getElementById('check_parcelado');
        const wrapParcelas = document.getElementById('wrapper_parcelas');

        if (checkParcelado && wrapParcelas) {
            checkParcelado.addEventListener('change', () => {
                if (checkParcelado.checked) {
                    wrapParcelas.classList.remove('d-none');
                } else {
                    wrapParcelas.classList.add('d-none');
                }
            });
        }

        // 3. Edição Dinâmica de Lançamento
        const editButtons = document.querySelectorAll('.btn-edit-transaction');
        const modalEditEl = document.getElementById('modalEditarTransacao');
        const modalEdit = new bootstrap.Modal(modalEditEl);
        const formEdit = document.getElementById('formEditarTransacao');

        editButtons.forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                try {
                    const response = await fetch('<?= BASE_URL ?>/transacoes/obter/' + id);
                    if (!response.ok) {
                        alert('Erro ao carregar dados da transação.');
                        return;
                    }
                    const data = await response.json();

                    formEdit.action = '<?= BASE_URL ?>/transacoes/atualizar/' + data.id;
                    document.getElementById('edit_descricao').value = data.descricao;
                    document.getElementById('edit_valor').value = data.valor;
                    document.getElementById('edit_data_vencimento').value = data.data_vencimento;
                    document.getElementById('edit_categoria_id').value = data.categoria_id;
                    const selectMeioPagamento = document.getElementById('edit_meio_pagamento_id');
                    selectMeioPagamento.querySelectorAll('option[data-historico="true"]').forEach(option => option.remove());
                    const meioAtualDisponivel = Array.from(selectMeioPagamento.options)
                        .some(option => option.value === String(data.meio_pagamento_id));
                    if (!meioAtualDisponivel) {
                        const optionHistorica = new Option(`${data.meio_pagamento_nome} (Histórico)`, data.meio_pagamento_id, true, true);
                        optionHistorica.dataset.historico = 'true';
                        selectMeioPagamento.add(optionHistorica);
                    }
                    selectMeioPagamento.value = data.meio_pagamento_id;
                    document.getElementById('edit_conta_id').value = data.conta_id || '';
                    document.getElementById('edit_status').value = data.status;
                    document.getElementById('edit_observacoes').value = data.observacoes || '';

                    modalEdit.show();
                } catch (e) {
                    alert('Erro na requisição dos dados.');
                }
            });
        });
    });
</script>

