<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Detalhes do Cartão e Histórico de Faturas
 */
$corCartao = !empty($cartao['cor']) ? htmlspecialchars($cartao['cor'], ENT_QUOTES, 'UTF-8') : '#3BD7D7';
$porcentagem = (float) $cartao['porcentagem_usada'];
$barraCor = ($porcentagem > 80) ? 'bg-danger' : (($porcentagem > 50) ? 'bg-warning' : 'bg-success');
?>
<!-- Cabeçalho de Navegação -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2">
            <div style="width: 16px; height: 16px; border-radius: 4px; background-color: <?= $corCartao ?>;"></div>
            <h1 class="h3 fw-bold mb-0" style="color: var(--symo-on-surface);">
                <?= htmlspecialchars($cartao['nome'], ENT_QUOTES, 'UTF-8') ?>
            </h1>
            <?php if ($cartao['status'] === 'ativo'): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Ativo</span>
            <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Inativo</span>
            <?php endif; ?>
        </div>
        <p class="text-muted mb-0 small">
            Conta padrão: <?= !empty($cartao['conta_padrao_nome']) ? htmlspecialchars($cartao['conta_padrao_nome'], ENT_QUOTES, 'UTF-8') : 'Não definida' ?>
        </p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/cartoes" class="btn btn-symo-primary">
            <i class="bi bi-grid me-1"></i> Todos os Cartões
        </a>
    </div>
</div>

<!-- Resumo de Limites e Ciclo -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="symo-card">
            <span class="label-caps">Limite Total</span>
            <div class="kpi-value fs-3 fw-bold tabular-nums" style="color: var(--symo-on-surface);">
                <?= FormatHelper::currency((float) $cartao['limite_credito']) ?>
            </div>
            <div class="text-muted small">Limite concedido pela instituição</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="symo-card">
            <span class="label-caps">Limite Utilizado</span>
            <div class="kpi-value fs-3 fw-bold tabular-nums text-danger">
                <?= FormatHelper::currency((float) $cartao['limite_usado']) ?>
            </div>
            <div class="progress mt-2" style="height: 6px;">
                <div class="progress-bar <?= $barraCor ?>" style="width: <?= $porcentagem ?>%"></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="symo-card">
            <span class="label-caps">Limite Disponível</span>
            <div class="kpi-value fs-3 fw-bold tabular-nums text-success">
                <?= FormatHelper::currency((float) $cartao['limite_disponivel']) ?>
            </div>
            <div class="text-muted small">
                Fecha dia <strong><?= sprintf('%02d', (int) $cartao['dia_fechamento']) ?></strong> | Vence dia <strong><?= sprintf('%02d', (int) $cartao['dia_vencimento']) ?></strong>
            </div>
        </div>
    </div>
</div>

<!-- Histórico de Faturas -->
<div class="symo-card p-0 overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
        <h5 class="mb-0 fw-bold fs-6" style="color: var(--symo-on-surface);">
            <i class="bi bi-receipt me-2 text-muted"></i>Histórico de Faturas Mensais
        </h5>
        <span class="badge text-bg-secondary rounded-pill"><?= count($faturas) ?> faturas</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-finance">
            <thead class="table-light">
                <tr class="label-caps">
                    <th scope="col" style="padding-left: 1.5rem;">Mês de Referência</th>
                    <th scope="col">Fechamento</th>
                    <th scope="col">Vencimento</th>
                    <th scope="col" class="text-end">Valor Total</th>
                    <th scope="col" class="text-center">Status</th>
                    <th scope="col" class="text-end" style="padding-right: 1.5rem;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($faturas)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-receipt fs-1 d-block mb-2 text-secondary"></i>
                            Nenhuma fatura registrada para este cartão até o momento.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($faturas as $f): ?>
                        <tr>
                            <td style="padding-left: 1.5rem;" class="fw-bold" style="color: var(--symo-on-surface);">
                                <i class="bi bi-calendar-month me-2" style="color: var(--symo-primary-brand);"></i>
                                <?= sprintf('%02d/%04d', (int) $f['mes_referencia'], (int) $f['ano_referencia']) ?>
                            </td>
                            <td class="tabular-nums text-muted">
                                <?= FormatHelper::date($f['data_fechamento']) ?>
                            </td>
                            <td class="tabular-nums fw-semibold">
                                <?= FormatHelper::date($f['data_vencimento']) ?>
                            </td>
                            <td class="text-end tabular-nums fw-bold <?= (float)$f['valor_total'] > 0 ? 'text-danger' : 'text-muted' ?>">
                                <?= FormatHelper::currency((float) $f['valor_total']) ?>
                            </td>
                            <td class="text-center">
                                <?php if ($f['status'] === 'paga'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-check-circle me-1"></i> Paga
                                    </span>
                                <?php elseif ($f['status'] === 'fechada'): ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">
                                        <i class="bi bi-lock me-1"></i> Fechada
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                        <i class="bi bi-clock me-1"></i> Aberta
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end" style="padding-right: 1.5rem;">
                                <div class="btn-group btn-group-sm">
                                    <!-- Ver Lançamentos da Fatura -->
                                    <a href="<?= BASE_URL ?>/faturas/<?= $f['id'] ?>" class="btn btn-outline-secondary" title="Ver Lançamentos da Fatura">
                                        <i class="bi bi-list-ul"></i>
                                    </a>

                                    <?php if ($f['status'] === 'fechada' && (float) $f['valor_total'] > 0): ?>
                                        <!-- Pagar Fatura -->
                                        <button type="button" class="btn btn-success btn-pay-invoice"
                                                data-id="<?= $f['id'] ?>"
                                                data-mes="<?= sprintf('%02d/%04d', (int) $f['mes_referencia'], (int) $f['ano_referencia']) ?>"
                                                data-valor="<?= htmlspecialchars(FormatHelper::currency((float) $f['valor_total']), ENT_QUOTES, 'UTF-8') ?>"
                                                data-conta-padrao="<?= $cartao['conta_padrao_id'] ?? '' ?>"
                                                title="Pagar Fatura">
                                            <i class="bi bi-cash-stack me-1"></i> Pagar
                                        </button>

                                    <?php elseif ($f['status'] === 'paga'): ?>
                                        <!-- Informações de Quitação -->
                                        <?php
                                            $infoQuitacao = !empty($f['conta_pagamento_nome'])
                                                ? 'Paga em ' . FormatHelper::dateTime($f['pago_em']) . ' via ' . htmlspecialchars($f['conta_pagamento_nome'], ENT_QUOTES, 'UTF-8')
                                                : ((float) $f['valor_total'] <= 0 ? 'Fatura sem lançamentos (liquidada automaticamente)' : 'Paga em ' . FormatHelper::dateTime($f['pago_em']));
                                        ?>
                                        <button type="button" class="btn btn-outline-success disabled" title="<?= $infoQuitacao ?>">
                                            <i class="bi bi-check2-all"></i>
                                        </button>
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

<!-- Modal: Pagar Fatura -->
<div class="modal fade" id="modalPagarFatura" tabindex="-1" aria-labelledby="modalPagarFaturaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content symo-modal-content">
            <form id="formPagarFatura" action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                <input type="hidden" name="redirect_to" value="<?= BASE_URL ?>/cartoes/detalhes/<?= $cartao['id'] ?>">
                
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalPagarFaturaLabel">Pagar Fatura de Cartão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-info-circle-fill fs-5"></i>
                        <div>
                            Fatura de <strong id="pay_fatura_mes">--/----</strong> no valor de <strong id="pay_fatura_valor" class="tabular-nums">R$ 0,00</strong>.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="pay_conta_id" class="form-label fw-semibold">Conta Bancária para Débito *</label>
                        <select class="form-select" id="pay_conta_id" name="conta_pagamento_id" required>
                            <option value="">Selecione uma conta ativa...</option>
                            <?php foreach ($contas as $acc): ?>
                                <option value="<?= $acc['id'] ?>">
                                    <?= htmlspecialchars($acc['nome'], ENT_QUOTES, 'UTF-8') ?> (Saldo: <?= FormatHelper::currency((float) $acc['saldo_atual']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="pay_data_pagamento" class="form-label fw-semibold">Data do Pagamento *</label>
                        <input type="date" class="form-control" id="pay_data_pagamento" name="data_pagamento" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <p class="text-muted small mb-0">
                        <i class="bi bi-shield-check text-success me-1"></i>
                        A liquidação debita o valor da fatura do saldo da conta bancária e restabelece o limite do cartão sob transação atômica.
                    </p>
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

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const payButtons = document.querySelectorAll('.btn-pay-invoice');
        const formPagar = document.getElementById('formPagarFatura');
        const modalPagarEl = document.getElementById('modalPagarFatura');
        const modalPagar = new bootstrap.Modal(modalPagarEl);

        payButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                const mes = btn.getAttribute('data-mes');
                const valor = btn.getAttribute('data-valor');
                const contaPadrao = btn.getAttribute('data-conta-padrao');

                formPagar.action = '<?= BASE_URL ?>/faturas/pagar/' + id;
                document.getElementById('pay_fatura_mes').textContent = mes;
                document.getElementById('pay_fatura_valor').textContent = valor;
                
                if (contaPadrao) {
                    document.getElementById('pay_conta_id').value = contaPadrao;
                }

                modalPagar.show();
            });
        });
    });
</script>
