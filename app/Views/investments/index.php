<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Investimentos (lista de posições)
 */
$mesesNomes = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$mesAnterior = (new DateTimeImmutable(sprintf('%04d-%02d-01', $ano, $mes)))->modify('-1 month');
$mesSeguinte = (new DateTimeImmutable(sprintf('%04d-%02d-01', $ano, $mes)))->modify('+1 month');
$qsFiltros = static function (array $filters): string {
    $q = array_filter([
        'status'      => $filters['status'] ?? '',
        'conta_id'    => $filters['conta_id'] ?? '',
        'classe'      => $filters['classe'] ?? '',
        'custodiante' => $filters['custodiante'] ?? '',
        'finalidade'  => $filters['finalidade'] ?? '',
    ], static fn ($v) => $v !== '' && $v !== null);
    return $q ? '&' . http_build_query($q) : '';
};
$qs = $qsFiltros($filters);
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--symo-on-surface);">Investimentos</h1>
        <p class="text-muted mb-0" style="font-size: 0.95rem;">
            Acompanhe aplicações e reservas, como caixinhas, sem misturá-las ao Saldo Atual.
        </p>
    </div>
    <button type="button" class="btn-symo-primary d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovoInvestimento">
        <i class="bi bi-plus-lg"></i>
        <span>Novo Investimento</span>
    </button>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <div class="symo-card h-100">
            <span class="label-caps" style="color: var(--symo-primary-brand);"><i class="bi bi-piggy-bank-fill me-1"></i> Total Investido</span>
            <div class="fs-2 fw-bold tabular-nums"><?= FormatHelper::currency($totalInvestido) ?></div>
            <div class="text-muted small">Soma dos investimentos ativos. Não entra no Saldo Atual.</div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="symo-card h-100">
            <div class="d-flex justify-content-between align-items-center">
                <span class="label-caps text-secondary"><i class="bi bi-graph-up-arrow me-1"></i> Rendimentos do Mês</span>
                <div class="symo-month-nav">
                    <a href="<?= BASE_URL ?>/investimentos?mes=<?= $mesAnterior->format('n') ?>&amp;ano=<?= $mesAnterior->format('Y') ?><?= $e($qs) ?>" aria-label="Mês anterior"><i class="bi bi-chevron-left"></i></a>
                    <span><?= $mesesNomes[$mes] ?>/<?= $ano ?></span>
                    <a href="<?= BASE_URL ?>/investimentos?mes=<?= $mesSeguinte->format('n') ?>&amp;ano=<?= $mesSeguinte->format('Y') ?><?= $e($qs) ?>" aria-label="Próximo mês"><i class="bi bi-chevron-right"></i></a>
                </div>
            </div>
            <div class="fs-2 fw-bold tabular-nums text-success"><?= FormatHelper::currency($rendimentoMes) ?></div>
            <div class="text-muted small">Rendimentos informados no mês. Atualizações de valor não entram aqui.</div>
        </div>
    </div>
</div>

<div class="symo-card mb-4 p-3 bg-light border">
    <form method="GET" action="<?= BASE_URL ?>/investimentos" class="row g-2 align-items-end">
        <input type="hidden" name="mes" value="<?= (int) $mes ?>">
        <input type="hidden" name="ano" value="<?= (int) $ano ?>">
        <div class="col-6 col-md-2">
            <label for="f_status" class="form-label small fw-semibold mb-1">Status</label>
            <select class="form-select form-select-sm" id="f_status" name="status">
                <option value="ativo" <?= $filters['status'] === 'ativo' ? 'selected' : '' ?>>Ativos</option>
                <option value="encerrado" <?= $filters['status'] === 'encerrado' ? 'selected' : '' ?>>Encerrados</option>
                <option value="todos" <?= $filters['status'] === 'todos' ? 'selected' : '' ?>>Todos</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label for="f_conta" class="form-label small fw-semibold mb-1">Conta</label>
            <select class="form-select form-select-sm" id="f_conta" name="conta_id">
                <option value="">Todas</option>
                <?php foreach ($todasContas as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (int) ($filters['conta_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= $e($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label for="f_classe" class="form-label small fw-semibold mb-1">Classe</label>
            <select class="form-select form-select-sm" id="f_classe" name="classe">
                <option value="">Todas</option>
                <?php foreach (Investment::CLASSES as $k => $label): ?>
                    <option value="<?= $e($k) ?>" <?= $filters['classe'] === $k ? 'selected' : '' ?>><?= $e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label for="f_cust" class="form-label small fw-semibold mb-1">Custodiante</label>
            <input type="text" class="form-control form-control-sm" id="f_cust" name="custodiante" maxlength="120" value="<?= $e($filters['custodiante']) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label for="f_fin" class="form-label small fw-semibold mb-1">Finalidade</label>
            <input type="text" class="form-control form-control-sm" id="f_fin" name="finalidade" maxlength="120" value="<?= $e($filters['finalidade']) ?>">
        </div>
        <div class="col-6 col-md-2 d-flex gap-2">
            <button type="submit" class="btn-symo-primary justify-content-center flex-grow-1 py-1"><i class="bi bi-funnel"></i> Filtrar</button>
            <a href="<?= BASE_URL ?>/investimentos" class="btn btn-sm btn-outline-secondary" title="Limpar filtros"><i class="bi bi-x-lg"></i></a>
        </div>
    </form>
</div>

<div class="symo-card p-0 overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
        <h5 class="mb-0 fw-bold fs-6"><i class="bi bi-piggy-bank me-2 text-muted"></i>Posições</h5>
        <span class="badge text-bg-secondary rounded-pill"><?= count($investimentos) ?> registros</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="label-caps">
                    <th scope="col" style="padding-left: 1.5rem;">Investimento</th>
                    <th scope="col">Conta vinculada</th>
                    <th scope="col">Classe</th>
                    <th scope="col">Finalidade</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end" style="padding-right: 1.5rem;">Valor atual</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($investimentos)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-piggy-bank fs-1 d-block mb-2 text-secondary"></i>
                            Nenhum investimento encontrado para os filtros selecionados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($investimentos as $inv): ?>
                        <tr>
                            <td style="padding-left: 1.5rem;">
                                <a href="<?= BASE_URL ?>/investimentos/<?= (int) $inv['id'] ?>" class="fw-semibold text-decoration-none" style="color: var(--symo-primary-brand);"><?= $e($inv['nome']) ?></a>
                                <?php if (!empty($inv['custodiante'])): ?>
                                    <div class="text-muted small"><?= $e($inv['custodiante']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $e($inv['conta_nome']) ?>
                                <?php if ($inv['conta_status'] !== 'ativa'): ?>
                                    <span class="badge text-bg-warning ms-1">Inativa</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $e(Investment::CLASSES[$inv['classe']] ?? 'Não informado') ?></td>
                            <td><?= !empty($inv['finalidade']) ? $e($inv['finalidade']) : '<span class="text-muted">—</span>' ?></td>
                            <td>
                                <span class="badge <?= $inv['status'] === 'ativo' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $inv['status'] === 'ativo' ? 'Ativo' : 'Encerrado' ?></span>
                            </td>
                            <td class="text-end tabular-nums fw-bold" style="padding-right: 1.5rem;"><?= FormatHelper::currency((float) $inv['saldo_atual']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalNovoInvestimento" tabindex="-1" aria-labelledby="modalNovoInvestimentoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content symo-modal-content">
            <form action="<?= BASE_URL ?>/investimentos/salvar" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $e(CsrfHelper::getToken()) ?>">
                <div class="modal-header border-bottom" style="background-color: var(--symo-primary-brand); color: white;">
                    <h5 class="modal-title fw-bold" id="modalNovoInvestimentoLabel"><i class="bi bi-piggy-bank-fill me-1"></i> Novo Investimento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="inv_nome" class="form-label fw-semibold">Nome *</label>
                        <input type="text" class="form-control" id="inv_nome" name="nome" maxlength="120" required placeholder="Ex: Reserva de emergência, Tesouro Selic">
                    </div>
                    <div class="mb-3">
                        <label for="inv_conta" class="form-label fw-semibold">Conta vinculada *</label>
                        <select class="form-select" id="inv_conta" name="conta_id" required>
                            <option value="">Selecione a conta usada nos aportes e resgates...</option>
                            <?php foreach ($contas as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= $e($c['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Aportes saem desta conta e resgates voltam para ela.</div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="inv_cust" class="form-label fw-semibold">Custodiante</label>
                            <input type="text" class="form-control" id="inv_cust" name="custodiante" maxlength="120" placeholder="Ex: Nubank, Tesouro Direto">
                        </div>
                        <div class="col-6">
                            <label for="inv_classe" class="form-label fw-semibold">Classe</label>
                            <select class="form-select" id="inv_classe" name="classe">
                                <?php foreach (Investment::CLASSES as $k => $label): ?>
                                    <option value="<?= $e($k) ?>" <?= $k === 'nao_informado' ? 'selected' : '' ?>><?= $e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="inv_fin" class="form-label fw-semibold">Finalidade</label>
                        <input type="text" class="form-control" id="inv_fin" name="finalidade" maxlength="120" placeholder="Ex: Reserva, Viagem">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label for="inv_saldo" class="form-label fw-semibold">Valor atual</label>
                            <input type="text" class="form-control money-mask tabular-nums text-end" id="inv_saldo" name="saldo_inicial" placeholder="R$ 0,00">
                        </div>
                        <div class="col-6">
                            <label for="inv_data" class="form-label fw-semibold">Data</label>
                            <input type="date" class="form-control" id="inv_data" name="data_saldo_inicial" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-text">O valor inicial não movimenta nenhuma conta.</div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-symo-primary">Salvar Investimento</button>
                </div>
            </form>
        </div>
    </div>
</div>
