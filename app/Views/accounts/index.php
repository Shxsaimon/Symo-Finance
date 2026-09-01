<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Contas Bancárias e Carteiras
 */
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--symo-on-surface);">Contas Bancárias e Carteiras</h1>
        <p class="text-muted mb-0" style="font-size: 0.95rem;">
            Gerencie suas contas correntes, poupanças, carteiras e aplicações.
        </p>
    </div>
    <div>
        <?php if ($isAdmin): ?>
            <button type="button" class="btn-symo-primary" data-bs-toggle="modal" data-bs-target="#modalNovaConta">
                <i class="bi bi-plus-lg"></i>
                <span>Nova Conta</span>
            </button>
        <?php else: ?>
            <span class="badge text-bg-light border px-3 py-2 text-muted">
                <i class="bi bi-eye me-1"></i> Modo Consulta (Operador)
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- Resumo Rápido de Saldo Consolidado -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-lg-4">
        <div class="symo-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="label-caps">Saldo Consolidado das Contas Ativas</span>
                <i class="bi bi-wallet2 text-primary fs-4" style="color: var(--symo-primary-brand) !important;"></i>
            </div>
            <div class="kpi-value fs-2 fw-bold <?= $saldoConsolidado >= 0 ? 'text-success' : 'text-danger' ?>">
                <?= FormatHelper::currency($saldoConsolidado) ?>
            </div>
            <div class="text-muted small">Disponível em contas ativas no sistema</div>
        </div>
    </div>
</div>

<!-- Tabela de Contas Bancárias -->
<div class="symo-card p-0 overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
        <h5 class="mb-0 fw-bold fs-6" style="color: var(--symo-on-surface);">
            <i class="bi bi-bank me-2 text-muted"></i>Relação de Contas
        </h5>
        <span class="badge text-bg-secondary rounded-pill"><?= count($contas) ?> contas</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-finance">
            <thead class="table-light">
                <tr class="label-caps">
                    <th scope="col" style="padding-left: 1.5rem;">Conta / Instituição</th>
                    <th scope="col">Tipo</th>
                    <th scope="col" class="text-end">Saldo Inicial</th>
                    <th scope="col" class="text-end">Saldo Atual</th>
                    <th scope="col" class="text-center">Status</th>
                    <?php if ($isAdmin): ?>
                        <th scope="col" class="text-end" style="padding-right: 1.5rem;">Ações</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contas)): ?>
                    <tr>
                        <td colspan="<?= $isAdmin ? 6 : 5 ?>" class="text-center py-5 text-muted">
                            <i class="bi bi-wallet fs-1 d-block mb-2 text-secondary"></i>
                            Nenhuma conta cadastrada até o momento.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($contas as $c): ?>
                        <tr>
                            <td style="padding-left: 1.5rem;">
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width: 14px; height: 14px; border-radius: 4px; background-color: <?= htmlspecialchars($c['cor'], ENT_QUOTES, 'UTF-8') ?>; flex-shrink: 0;" title="Cor da conta"></div>
                                    <div>
                                        <div class="fw-bold" style="color: var(--symo-on-surface);">
                                            <?= htmlspecialchars($c['nome'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="text-muted small">
                                            <?= htmlspecialchars($c['instituicao'] ?? 'Instituição não informada', ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge text-bg-light border text-capitalize">
                                    <?= htmlspecialchars($c['tipo'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="text-end tabular-nums text-muted">
                                <?= FormatHelper::currency((float) $c['saldo_inicial']) ?>
                            </td>
                            <td class="text-end tabular-nums fw-bold <?= (float)$c['saldo_atual'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= FormatHelper::currency((float) $c['saldo_atual']) ?>
                            </td>
                            <td class="text-center">
                                <?php if ($c['status'] === 'ativa'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Ativa</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Inativa</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($isAdmin): ?>
                                <td class="text-end" style="padding-right: 1.5rem;">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Botão Editar -->
                                        <button type="button" class="btn btn-outline-secondary btn-edit-account" 
                                                data-id="<?= $c['id'] ?>"
                                                data-nome="<?= htmlspecialchars($c['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-instituicao="<?= htmlspecialchars($c['instituicao'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-tipo="<?= htmlspecialchars($c['tipo'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-cor="<?= htmlspecialchars($c['cor'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-status="<?= htmlspecialchars($c['status'], ENT_QUOTES, 'UTF-8') ?>"
                                                title="Editar Conta">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <!-- Alternar Status (Ativa/Inativa) -->
                                        <form action="<?= BASE_URL ?>/contas/status/<?= $c['id'] ?>" method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                                            <button type="submit" class="btn btn-outline-secondary" title="<?= $c['status'] === 'ativa' ? 'Inativar Conta' : 'Ativar Conta' ?>">
                                                <i class="bi <?= $c['status'] === 'ativa' ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' ?>"></i>
                                            </button>
                                        </form>

                                        <!-- Botão Excluir -->
                                        <form action="<?= BASE_URL ?>/contas/excluir/<?= $c['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Deseja realmente excluir esta conta? Se houver lançamentos associados, a exclusão será impedida.');">
                                            <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Excluir Conta">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($isAdmin): ?>
    <!-- Modal: Nova Conta Bancária -->
    <div class="modal fade" id="modalNovaConta" tabindex="-1" aria-labelledby="modalNovaContaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content symo-modal-content">
                <form action="<?= BASE_URL ?>/contas/salvar" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold" id="modalNovaContaLabel">Cadastrar Nova Conta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="nome" class="form-label fw-semibold">Nome da Conta *</label>
                            <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex: NuBank, Carteira Física, Bradesco" required>
                        </div>
                        <div class="mb-3">
                            <label for="instituicao" class="form-label fw-semibold">Instituição Financeira</label>
                            <input type="text" class="form-control" id="instituicao" name="instituicao" placeholder="Ex: Nubank, Itaú, Santander">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="tipo" class="form-label fw-semibold">Tipo de Conta</label>
                                <select class="form-select" id="tipo" name="tipo">
                                    <option value="corrente" selected>Conta Corrente</option>
                                    <option value="poupanca">Poupança</option>
                                    <option value="investimento">Investimentos</option>
                                    <option value="dinheiro">Dinheiro Físico</option>
                                    <option value="outros">Outros</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="saldo_inicial" class="form-label fw-semibold">Saldo Inicial</label>
                                <input type="text" class="form-control money-mask tabular-nums text-end" id="saldo_inicial" name="saldo_inicial" placeholder="R$ 0,00" value="R$ 0,00">
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="cor" class="form-label fw-semibold">Cor de Identificação</label>
                                <input type="color" class="form-control form-control-color w-100" id="cor" name="cor" value="#6F02FF" title="Escolha uma cor">
                            </div>
                            <div class="col-6">
                                <label for="status" class="form-label fw-semibold">Status Inicial</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="ativa" selected>Ativa</option>
                                    <option value="inativa">Inativa</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-symo-primary">Salvar Conta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Editar Conta Bancária -->
    <div class="modal fade" id="modalEditarConta" tabindex="-1" aria-labelledby="modalEditarContaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content symo-modal-content">
                <form id="formEditarConta" action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold" id="modalEditarContaLabel">Editar Conta Bancária</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="edit_nome" class="form-label fw-semibold">Nome da Conta *</label>
                            <input type="text" class="form-control" id="edit_nome" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_instituicao" class="form-label fw-semibold">Instituição Financeira</label>
                            <input type="text" class="form-control" id="edit_instituicao" name="instituicao">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="edit_tipo" class="form-label fw-semibold">Tipo de Conta</label>
                                <select class="form-select" id="edit_tipo" name="tipo">
                                    <option value="corrente">Conta Corrente</option>
                                    <option value="poupanca">Poupança</option>
                                    <option value="investimento">Investimentos</option>
                                    <option value="dinheiro">Dinheiro Físico</option>
                                    <option value="outros">Outros</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="edit_cor" class="form-label fw-semibold">Cor</label>
                                <input type="color" class="form-control form-control-color w-100" id="edit_cor" name="cor">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label fw-semibold">Status</label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="ativa">Ativa</option>
                                <option value="inativa">Inativa</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-symo-primary">Atualizar Conta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editButtons = document.querySelectorAll('.btn-edit-account');
            const formEditar = document.getElementById('formEditarConta');
            const modalEditarEl = document.getElementById('modalEditarConta');
            const modalEditar = new bootstrap.Modal(modalEditarEl);

            editButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-id');
                    const nome = btn.getAttribute('data-nome');
                    const instituicao = btn.getAttribute('data-instituicao');
                    const tipo = btn.getAttribute('data-tipo');
                    const cor = btn.getAttribute('data-cor');
                    const status = btn.getAttribute('data-status');

                    formEditar.action = '<?= BASE_URL ?>/contas/atualizar/' + id;
                    document.getElementById('edit_nome').value = nome;
                    document.getElementById('edit_instituicao').value = instituicao;
                    document.getElementById('edit_tipo').value = tipo;
                    document.getElementById('edit_cor').value = cor;
                    document.getElementById('edit_status').value = status;

                    modalEditar.show();
                });
            });
        });
    </script>
<?php endif; ?>
