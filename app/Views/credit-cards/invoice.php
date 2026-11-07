<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Detalhes da Fatura e Lançamentos
 */
$corCartao = !empty($fatura['cartao_cor']) ? htmlspecialchars($fatura['cartao_cor'], ENT_QUOTES, 'UTF-8') : '#3BD7D7';
?>
<!-- Cabeçalho de Navegação -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
        <a href="<?= BASE_URL ?>/cartoes/detalhes/<?= $fatura['cartao_credito_id'] ?>" class="btn btn-outline-secondary btn-sm rounded-circle p-2" title="Voltar ao Cartão">
            <i class="bi bi-arrow-left fs-5"></i>
        </a>
        <div>
            <div class="d-flex align-items-center gap-2">
                <div style="width: 14px; height: 14px; border-radius: 4px; background-color: <?= $corCartao ?>;"></div>
                <h1 class="h3 fw-bold mb-0" style="color: var(--symo-on-surface);">
                    Fatura <?= sprintf('%02d/%04d', (int) $fatura['mes_referencia'], (int) $fatura['ano_referencia']) ?>
                </h1>
                <?php if ($fatura['status'] === 'paga'): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                        <i class="bi bi-check-circle me-1"></i> Paga
                    </span>
                <?php elseif ($fatura['status'] === 'fechada'): ?>
                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">
                        <i class="bi bi-lock me-1"></i> Fechada
                    </span>
                <?php else: ?>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                        <i class="bi bi-clock me-1"></i> Aberta
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-muted mb-0 small">
                Cartão: <strong><?= htmlspecialchars($fatura['cartao_nome'], ENT_QUOTES, 'UTF-8') ?></strong>
            </p>
        </div>
    </div>
    <div class="d-flex gap-2">
        <?php if ($fatura['status'] === 'fechada' && (float) $fatura['valor_total'] > 0): ?>
            <button type="button" class="btn btn-success fw-semibold" data-bs-toggle="modal" data-bs-target="#modalPagarFaturaInvoice">
                <i class="bi bi-cash-stack me-1"></i> Pagar Fatura
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Resumo da Fatura -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="symo-card">
            <span class="label-caps">Total da Fatura</span>
            <div class="kpi-value fs-2 fw-bold tabular-nums text-danger">
                <?= FormatHelper::currency((float) $fatura['valor_total']) ?>
            </div>
            <div class="text-muted small">Soma de todos os lançamentos deste ciclo</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="symo-card">
            <span class="label-caps">Fechamento e Vencimento</span>
            <div class="fs-5 fw-bold tabular-nums" style="color: var(--symo-on-surface);">
                Vence em <?= FormatHelper::date($fatura['data_vencimento']) ?>
            </div>
            <div class="text-muted small">
                Fechamento em: <?= FormatHelper::date($fatura['data_fechamento']) ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="symo-card">
            <span class="label-caps">Status de Liquidação</span>
            <?php if ($fatura['status'] === 'paga'): ?>
                <div class="fs-5 fw-bold text-success">
                    <i class="bi bi-check-all me-1"></i> Liquidada
                </div>
                <div class="text-muted small">
                    Paga em <?= FormatHelper::dateTime($fatura['pago_em']) ?> via <?= htmlspecialchars($fatura['conta_pagamento_nome'] ?? 'Conta bancária', ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php else: ?>
                <div class="fs-5 fw-bold text-warning">
                    <i class="bi bi-hourglass-split me-1"></i> Aguardando Pagamento
                </div>
                <div class="text-muted small">Disponível para quitação após o fechamento</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Tabela de Lançamentos da Fatura -->
<div class="symo-card p-0 overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
        <h5 class="mb-0 fw-bold fs-6" style="color: var(--symo-on-surface);">
            <i class="bi bi-list-check me-2 text-muted"></i>Lançamentos da Fatura
        </h5>
        <span class="badge text-bg-secondary rounded-pill"><?= count($fatura['transacoes']) ?> compras</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-finance">
            <thead class="table-light">
                <tr class="label-caps">
                    <th scope="col" style="padding-left: 1.5rem;">Data da Compra</th>
                    <th scope="col">Categoria</th>
                    <th scope="col">Descrição</th>
                    <th scope="col">Lançado por</th>
                    <th scope="col" class="text-end" style="padding-right: 1.5rem;">Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fatura['transacoes'])): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-cart-x fs-1 d-block mb-2 text-secondary"></i>
                            Nenhuma despesa vinculada a esta fatura até o momento.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($fatura['transacoes'] as $t): ?>
                        <tr>
                            <td style="padding-left: 1.5rem;" class="tabular-nums text-muted">
                                <?= FormatHelper::date($t['data_transacao']) ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width: 24px; height: 24px; border-radius: 4px; background-color: <?= htmlspecialchars($t['categoria_cor'] ?? '#793CFE', ENT_QUOTES, 'UTF-8') ?>20; color: <?= htmlspecialchars($t['categoria_cor'] ?? '#793CFE', ENT_QUOTES, 'UTF-8') ?>; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;">
                                        <i class="bi <?= htmlspecialchars($t['categoria_icone'] ?? 'bi-tag', ENT_QUOTES, 'UTF-8') ?>"></i>
                                    </div>
                                    <span class="small fw-semibold" style="color: var(--symo-on-surface);">
                                        <?= htmlspecialchars($t['categoria_nome'] ?? 'Geral', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="fw-bold" style="color: var(--symo-on-surface);">
                                    <?= htmlspecialchars(FormatHelper::transactionDescription($t), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <?php if (!empty($t['total_parcelas']) && (int)$t['total_parcelas'] > 1): ?>
                                    <span class="badge text-bg-light border ms-1">
                                        <?= $t['numero_parcela'] ?>/<?= $t['total_parcelas'] ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small">
                                <?= htmlspecialchars($t['usuario_nome'] ?? 'Sistema', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="text-end tabular-nums fw-bold text-danger" style="padding-right: 1.5rem;">
                                <?= FormatHelper::currency((float) $t['valor']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Pagar Fatura -->
<div class="modal fade" id="modalPagarFaturaInvoice" tabindex="-1" aria-labelledby="modalPagarFaturaInvoiceLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content symo-modal-content">
            <form action="<?= BASE_URL ?>/faturas/pagar/<?= $fatura['id'] ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                <input type="hidden" name="redirect_to" value="<?= BASE_URL ?>/faturas/<?= $fatura['id'] ?>">
                
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalPagarFaturaInvoiceLabel">Pagar Fatura de Cartão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-info-circle-fill fs-5"></i>
                        <div>
                            Fatura de <strong><?= sprintf('%02d/%04d', (int) $fatura['mes_referencia'], (int) $fatura['ano_referencia']) ?></strong> no valor de <strong class="tabular-nums"><?= FormatHelper::currency((float) $fatura['valor_total']) ?></strong>.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="inv_conta_id" class="form-label fw-semibold">Conta Bancária para Débito *</label>
                        <select class="form-select" id="inv_conta_id" name="conta_pagamento_id" required>
                            <option value="">Selecione uma conta ativa...</option>
                            <?php foreach ($contas as $acc): ?>
                                <option value="<?= $acc['id'] ?>" <?= (!empty($fatura['conta_pagamento_id']) && (int)$fatura['conta_pagamento_id'] === (int)$acc['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($acc['nome'], ENT_QUOTES, 'UTF-8') ?> (Saldo: <?= FormatHelper::currency((float) $acc['saldo_atual']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="inv_data_pagamento" class="form-label fw-semibold">Data do Pagamento *</label>
                        <input type="date" class="form-control" id="inv_data_pagamento" name="data_pagamento" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-semibold">
                        <i class="bi bi-check-circle me-1"></i> Confirmar Pagamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
