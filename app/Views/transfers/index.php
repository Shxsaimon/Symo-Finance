<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Transferências entre Contas
 */
$mesesNomes = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
$anoAtual = (int) date('Y');
?>
<!-- Cabeçalho e Ação Principal -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--symo-on-surface);">Transferências</h1>
        <p class="text-muted mb-0" style="font-size: 0.95rem;">
            Movimente valores entre contas e carteiras com conciliação automática e atômica de saldos.
        </p>
    </div>
    <div>
        <button type="button" class="btn-symo-primary d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovaTransferencia">
            <i class="bi bi-arrow-left-right"></i>
            <span>Nova Transferência</span>
        </button>
    </div>
</div>

<!-- Cards Resumo de Transferências do Período -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <div class="symo-card">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="label-caps" style="color: var(--symo-primary-brand);"><i class="bi bi-arrow-repeat me-1"></i> Volume Transferido</span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                    <?= $resumo['total_transferencias'] ?> operações
                </span>
            </div>
            <div class="kpi-value fs-2 fw-bold tabular-nums" style="color: var(--symo-on-surface);">
                <?= FormatHelper::currency((float) $resumo['volume_total']) ?>
            </div>
            <div class="text-muted small">
                Movimentação interna sem impacto em receitas ou despesas operacionais
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="symo-card">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="label-caps text-secondary"><i class="bi bi-shield-check me-1"></i> Integridade Bancária</span>
            </div>
            <div class="fs-4 fw-bold" style="color: var(--symo-on-surface);">
                <i class="bi bi-check2-circle text-success me-1"></i> Conciliação Atômica Ativa
            </div>
            <div class="text-muted small">
                O débito na origem e o crédito no destino ocorrem simultaneamente no banco de dados
            </div>
        </div>
    </div>
</div>

<!-- Filtros de Transferência -->
<div class="symo-card mb-4 p-3 bg-light border">
    <form method="GET" action="<?= BASE_URL ?>/transferencias" class="row g-2 align-items-end">
        <div class="col-6 col-md-3">
            <label for="filter_mes" class="form-label small fw-semibold mb-1">Mês</label>
            <select class="form-select form-select-sm" id="filter_mes" name="mes">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= (int)$filters['mes'] === $m ? 'selected' : '' ?>>
                        <?= $mesesNomes[$m] ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label for="filter_ano" class="form-label small fw-semibold mb-1">Ano</label>
            <select class="form-select form-select-sm" id="filter_ano" name="ano">
                <?php for ($a = $anoAtual - 2; $a <= $anoAtual + 2; $a++): ?>
                    <option value="<?= $a ?>" <?= (int)$filters['ano'] === $a ? 'selected' : '' ?>>
                        <?= $a ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label for="filter_conta" class="form-label small fw-semibold mb-1">Filtrar por Conta</label>
            <select class="form-select form-select-sm" id="filter_conta" name="conta_id">
                <option value="">Todas as contas</option>
                <?php foreach ($contas as $acc): ?>
                    <option value="<?= $acc['id'] ?>" <?= (int)$filters['conta_id'] === (int)$acc['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($acc['nome'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                <i class="bi bi-funnel me-1"></i> Filtrar
            </button>
            <a href="<?= BASE_URL ?>/transferencias" class="btn btn-sm btn-outline-secondary" title="Limpar Filtros">
                <i class="bi bi-x-lg"></i>
            </a>
        </div>
    </form>
</div>

<!-- Tabela de Transferências -->
<div class="symo-card p-0 overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
        <h5 class="mb-0 fw-bold fs-6" style="color: var(--symo-on-surface);">
            <i class="bi bi-arrow-left-right me-2 text-muted"></i>Histórico de Transferências
        </h5>
        <span class="badge text-bg-secondary rounded-pill"><?= count($transferencias) ?> registros</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-finance">
            <thead class="table-light">
                <tr class="label-caps">
                    <th scope="col" style="padding-left: 1.5rem;">Data</th>
                    <th scope="col">Conta de Origem (Débito)</th>
                    <th scope="col" class="text-center" style="width: 40px;"></th>
                    <th scope="col">Conta de Destino (Crédito)</th>
                    <th scope="col">Autor / Observação</th>
                    <th scope="col" class="text-end">Valor</th>
                    <th scope="col" class="text-end" style="padding-right: 1.5rem;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transferencias)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-arrow-repeat fs-1 d-block mb-2 text-secondary"></i>
                            Nenhuma transferência realizada no período selecionado.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transferencias as $t): ?>
                        <?php
                            $podeExcluir = $isAdmin || ((int)$t['usuario_id'] === $currentUserId);
                            $corOrigem = !empty($t['conta_origem_cor']) ? htmlspecialchars($t['conta_origem_cor'], ENT_QUOTES, 'UTF-8') : '#6F02FF';
                            $corDestino = !empty($t['conta_destino_cor']) ? htmlspecialchars($t['conta_destino_cor'], ENT_QUOTES, 'UTF-8') : '#3BD7D7';
                        ?>
                        <tr>
                            <!-- Data -->
                            <td style="padding-left: 1.5rem;" class="tabular-nums text-muted small">
                                <?= FormatHelper::date($t['data_transferencia']) ?>
                            </td>

                            <!-- Origem -->
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width: 10px; height: 10px; border-radius: 50%; background-color: <?= $corOrigem ?>;"></div>
                                    <span class="fw-semibold" style="color: var(--symo-on-surface);">
                                        <?= htmlspecialchars($t['conta_origem_nome'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                            </td>

                            <!-- Ícone Direcional -->
                            <td class="text-center text-muted">
                                <i class="bi bi-arrow-right fs-5 text-secondary"></i>
                            </td>

                            <!-- Destino -->
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width: 10px; height: 10px; border-radius: 50%; background-color: <?= $corDestino ?>;"></div>
                                    <span class="fw-semibold" style="color: var(--symo-on-surface);">
                                        <?= htmlspecialchars($t['conta_destino_nome'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                            </td>

                            <!-- Autor / Observações -->
                            <td>
                                <div class="small fw-semibold" style="color: var(--symo-on-surface);">
                                    <?= htmlspecialchars($t['usuario_nome'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <?php if (!empty($t['observacoes'])): ?>
                                    <div class="text-muted small">
                                        <?= htmlspecialchars($t['observacoes'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Valor -->
                            <td class="text-end tabular-nums fw-bold fs-6" style="color: var(--symo-primary-brand);">
                                <?= FormatHelper::currency((float) $t['valor']) ?>
                            </td>

                            <!-- Ações -->
                            <td class="text-end" style="padding-right: 1.5rem;">
                                <?php if ($podeExcluir): ?>
                                    <form action="<?= BASE_URL ?>/transferencias/excluir/<?= $t['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Deseja realmente estornar esta transferência? Os saldos de ambas as contas serão revertidos.');">
                                        <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                                        <input type="hidden" name="redirect_to" value="<?= BASE_URL ?>/transferencias?mes=<?= $filters['mes'] ?>&ano=<?= $filters['ano'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Estornar Transferência">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Estornar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge text-bg-light border text-muted small" title="Criado por outro usuário">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Nova Transferência -->
<div class="modal fade" id="modalNovaTransferencia" tabindex="-1" aria-labelledby="modalNovaTransferenciaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content symo-modal-content">
            <form id="formNovaTransferencia" action="<?= BASE_URL ?>/transferencias/salvar" method="POST">
                <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">

                <div class="modal-header border-bottom" style="background-color: var(--symo-primary-brand); color: white;">
                    <h5 class="modal-title fw-bold" id="modalNovaTransferenciaLabel">
                        <i class="bi bi-arrow-left-right me-1"></i> Nova Transferência entre Contas
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Contas: Origem e Destino -->
                    <div class="mb-3">
                        <label for="conta_origem_id" class="form-label fw-semibold">Conta de Origem (Débito) *</label>
                        <select class="form-select" id="conta_origem_id" name="conta_origem_id" required>
                            <option value="" data-saldo="0">Selecione a conta que sairá o dinheiro...</option>
                            <?php foreach ($contas as $acc): ?>
                                <option value="<?= $acc['id'] ?>" data-saldo="<?= (float)$acc['saldo_atual'] ?>">
                                    <?= htmlspecialchars($acc['nome'], ENT_QUOTES, 'UTF-8') ?> (Saldo: <?= FormatHelper::currency((float)$acc['saldo_atual']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="text-center my-2 text-muted">
                        <i class="bi bi-arrow-down fs-4"></i>
                    </div>

                    <div class="mb-3">
                        <label for="conta_destino_id" class="form-label fw-semibold">Conta de Destino (Crédito) *</label>
                        <select class="form-select" id="conta_destino_id" name="conta_destino_id" required>
                            <option value="">Selecione a conta que receberá o dinheiro...</option>
                            <?php foreach ($contas as $acc): ?>
                                <option value="<?= $acc['id'] ?>">
                                    <?= htmlspecialchars($acc['nome'], ENT_QUOTES, 'UTF-8') ?> (Saldo: <?= FormatHelper::currency((float)$acc['saldo_atual']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="transf_valor" class="form-label fw-semibold">Valor da Transferência *</label>
                            <input type="text" class="form-control money-mask tabular-nums text-end fs-5 fw-bold" id="transf_valor" name="valor" placeholder="R$ 0,00" required>
                        </div>
                        <div class="col-6">
                            <label for="transf_data" class="form-label fw-semibold">Data da Transferência *</label>
                            <input type="date" class="form-control" id="transf_data" name="data_transferencia" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="transf_obs" class="form-label small text-muted">Observações / Motivo</label>
                        <textarea class="form-control" id="transf_obs" name="observacoes" rows="2" placeholder="Ex: Reserva de emergência, aporte para investimentos"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-symo-primary">Confirmar Transferência</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('formNovaTransferencia');
        const selOrigem = document.getElementById('conta_origem_id');
        const selDestino = document.getElementById('conta_destino_id');
        const inputValor = document.getElementById('transf_valor');

        if (form && selOrigem && selDestino && inputValor) {
            form.addEventListener('submit', (e) => {
                // 1. Validação de contas distintas
                if (selOrigem.value !== '' && selOrigem.value === selDestino.value) {
                    e.preventDefault();
                    alert('A conta de origem e a conta de destino não podem ser iguais!');
                    selDestino.focus();
                    return;
                }

                // 2. Validação de saldo suficiente na conta de origem
                const selectedOption = selOrigem.options[selOrigem.selectedIndex];
                const saldoOrigem = parseFloat(selectedOption.getAttribute('data-saldo') || '0');

                // Converte valor monetário brasileiro (Ex: R$ 3.500,00 -> 3500.00)
                let valStr = inputValor.value.replace(/[^\d,]/g, '').replace(',', '.');
                let valor = parseFloat(valStr || '0');

                if (valor > saldoOrigem) {
                    e.preventDefault();
                    const saldoFormatado = saldoOrigem.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                    const valorFormatado = valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                    alert(`Saldo insuficiente na conta de origem selecionada!\n\nSaldo disponível: ${saldoFormatado}\nValor solicitado: ${valorFormatado}`);
                    inputValor.focus();
                }
            });
        }
    });
</script>

