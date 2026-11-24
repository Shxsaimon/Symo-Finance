<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Contas Bancárias e Carteiras
 */
$csrf = CsrfHelper::getToken();
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--symo-on-surface);">Contas Bancárias e Carteiras</h1>
        <p class="text-muted mb-0" style="font-size: 0.95rem;">
            Gerencie suas contas correntes, poupanças, carteiras e contas compartilhadas.
        </p>
    </div>
    <div>
        <button type="button" class="btn-symo-primary" data-bs-toggle="modal" data-bs-target="#modalNovaConta">
            <i class="bi bi-plus-lg"></i>
            <span>Nova Conta</span>
        </button>
    </div>
</div>

<!-- Resumo Rápido de Saldos -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md">
        <div class="symo-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="label-caps">Saldo Atual</span>
                <i class="bi bi-wallet2 text-primary fs-4" style="color: var(--symo-primary-brand) !important;"></i>
            </div>
            <div class="kpi-value fs-2 fw-bold <?= $saldoAtual >= 0 ? 'text-success' : 'text-danger' ?>">
                <?= FormatHelper::currency($saldoAtual) ?>
            </div>
            <div class="text-muted small">Valor total das contas ativas marcadas para exibição no Dashboard.</div>
        </div>
    </div>
    <div class="col-12 col-md">
        <div class="symo-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="label-caps">Investimentos</span>
                <i class="bi bi-graph-up-arrow fs-4" style="color: var(--symo-primary-brand);"></i>
            </div>
            <div class="kpi-value fs-2 fw-bold <?= $saldoInvestimentos >= 0 ? 'text-success' : 'text-danger' ?>">
                <?= FormatHelper::currency($saldoInvestimentos) ?>
            </div>
            <div class="text-muted small">Valor investido nas posições ativas da aba Investimentos, fora do Saldo Atual.</div>
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
                    <th scope="col" class="text-end" style="padding-right: 1.5rem;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contas)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-wallet fs-1 d-block mb-2 text-secondary"></i>
                            Nenhuma conta cadastrada até o momento.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($contas as $c): ?>
                        <?php
                            $ehProprietario = ((int) ($c['eh_proprietario'] ?? 1) === 1);
                            $participantes = $c['participantes'] ?? [];
                            $qtdAtivos = count(array_filter($participantes, static fn ($p) => ($p['status'] ?? '') === 'ativo'));
                            $qtdPendentes = count(array_filter($participantes, static fn ($p) => ($p['status'] ?? '') === 'pendente'));
                        ?>
                        <tr>
                            <td style="padding-left: 1.5rem;">
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width: 14px; height: 14px; border-radius: 4px; background-color: <?= htmlspecialchars($c['cor'], ENT_QUOTES, 'UTF-8') ?>; flex-shrink: 0;" title="Cor da conta"></div>
                                    <div>
                                        <div class="fw-bold d-flex align-items-center flex-wrap gap-1" style="color: var(--symo-on-surface);">
                                            <span><?= htmlspecialchars($c['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if (!$ehProprietario): ?>
                                                <span class="badge rounded-pill" style="background-color: var(--symo-primary-fixed); color: var(--symo-on-primary-fixed); font-size: 0.7rem;" title="Conta compartilhada com você">
                                                    <i class="bi bi-people-fill me-1"></i>Compartilhada por <?= htmlspecialchars((string) ($c['proprietario_nome'] ?? 'Titular'), ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            <?php elseif ($qtdAtivos > 0 || $qtdPendentes > 0): ?>
                                                <span class="badge rounded-pill" style="background-color: var(--symo-primary-fixed); color: var(--symo-on-primary-fixed); font-size: 0.7rem;" title="Você compartilha esta conta">
                                                    <i class="bi bi-people-fill me-1"></i>Compartilhada (<?= $qtdAtivos ?><?= $qtdPendentes > 0 ? " + {$qtdPendentes} pend." : '' ?>)
                                                </span>
                                            <?php endif; ?>
                                            <?php if ((int) $c['considerar_no_saldo_atual'] === 0): ?>
                                                <span class="badge bg-light text-muted border fw-normal" style="font-size: 0.68rem;" title="Oculta do Dashboard">
                                                    <i class="bi bi-eye-slash me-1"></i>Fora do Dashboard
                                                </span>
                                            <?php endif; ?>
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
                            <td class="text-end" style="padding-right: 1.5rem;">
                                <div class="btn-group btn-group-sm">
                                    <!-- Botão Compartilhamento (Proprietário gerencia participantes / Participante vê acesso e preferência de Dashboard) -->
                                    <button type="button" class="btn btn-outline-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalCompartilharConta_<?= (int) $c['id'] ?>"
                                            title="<?= $ehProprietario ? 'Compartilhar Conta' : 'Opções de Compartilhamento' ?>">
                                        <i class="bi bi-people"></i>
                                    </button>

                                    <?php if ($ehProprietario): ?>
                                        <!-- Botão Editar -->
                                        <button type="button" class="btn btn-outline-secondary btn-edit-account" 
                                                data-id="<?= $c['id'] ?>"
                                                data-nome="<?= htmlspecialchars($c['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-instituicao="<?= htmlspecialchars($c['instituicao'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-tipo="<?= htmlspecialchars($c['tipo'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-exibir-dashboard="<?= (int) $c['considerar_no_saldo_atual'] ?>"
                                                data-cor="<?= htmlspecialchars($c['cor'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-status="<?= htmlspecialchars($c['status'], ENT_QUOTES, 'UTF-8') ?>"
                                                title="Editar Conta">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <!-- Alternar Status (Ativa/Inativa) -->
                                        <form action="<?= BASE_URL ?>/contas/status/<?= $c['id'] ?>" method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <button type="submit" class="btn btn-outline-secondary" title="<?= $c['status'] === 'ativa' ? 'Inativar Conta' : 'Ativar Conta' ?>">
                                                <i class="bi <?= $c['status'] === 'ativa' ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' ?>"></i>
                                            </button>
                                        </form>

                                        <!-- Botão Excluir -->
                                        <form action="<?= BASE_URL ?>/contas/excluir/<?= $c['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Deseja realmente excluir esta conta? Se houver lançamentos associados, a exclusão será impedida.');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Excluir Conta">
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

<!-- Modais de Compartilhamento por Conta -->
<?php foreach ($contas as $c): ?>
    <?php
        $cId = (int) $c['id'];
        $ehProprietario = ((int) ($c['eh_proprietario'] ?? 1) === 1);
        $participantes = $c['participantes'] ?? [];
        $compId = (int) ($c['compartilhamento_id'] ?? 0);
        $exibirNoMeuDash = in_array($c['compartilhamento_exibir_dashboard'] ?? true, [true, 1, '1', 't', 'true'], true);
        $permissaoLabel = Share::PERMISSOES[$c['compartilhamento_permissao'] ?? 'visualizar'] ?? 'Apenas visualização';
    ?>
    <div class="modal fade" id="modalCompartilharConta_<?= $cId ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content symo-modal-content">
                <div class="modal-header border-bottom" style="background-color: var(--symo-primary-brand); color: white;">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-people-fill me-2"></i>Compartilhamento — <?= htmlspecialchars($c['nome'], ENT_QUOTES, 'UTF-8') ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <?php if ($ehProprietario): ?>
                        <!-- Formulário de Convite (Proprietário) -->
                        <form action="<?= BASE_URL ?>/compartilhamentos/convidar" method="POST" class="mb-4 pb-3 border-bottom">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="recurso_tipo" value="conta">
                            <input type="hidden" name="recurso_id" value="<?= $cId ?>">
                            <input type="hidden" name="redirect_to" value="/contas">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">E-mail do Usuário Symo</label>
                                <input type="email" name="email" class="form-control" placeholder="usuario@exemplo.com" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Permissão na Conta</label>
                                <select name="permissao" class="form-select" required>
                                    <?php foreach (Share::PERMISSOES as $kPerm => $lblPerm): ?>
                                        <option value="<?= htmlspecialchars($kPerm, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($lblPerm, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    O participante verá apenas os lançamentos criados a partir do momento em que aceitar o convite.
                                </div>
                            </div>
                            <button type="submit" class="btn-symo-primary w-100 justify-content-center">
                                <i class="bi bi-send me-1"></i>Enviar Convite
                            </button>
                        </form>

                        <!-- Lista de Participantes -->
                        <h6 class="fw-bold mb-3">Participantes desta conta</h6>
                        <?php if (empty($participantes)): ?>
                            <div class="text-center py-3 text-muted bg-light rounded border">
                                Nenhum participante convidado para esta conta.
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($participantes as $p): ?>
                                    <div class="p-3 border rounded bg-light">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars((string) $p['participante_nome'], ENT_QUOTES, 'UTF-8') ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars((string) $p['participante_email'], ENT_QUOTES, 'UTF-8') ?></div>
                                            </div>
                                            <?php if ($p['status'] === 'ativo'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Convite Pendente</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <form action="<?= BASE_URL ?>/compartilhamentos/permissao/<?= (int) $p['id'] ?>" method="POST" class="d-flex align-items-center gap-1 flex-grow-1">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                <input type="hidden" name="recurso_tipo" value="conta">
                                                <input type="hidden" name="recurso_id" value="<?= $cId ?>">
                                                <select name="permissao" class="form-select" onchange="this.form.submit()">
                                                    <?php foreach (Share::PERMISSOES as $kPerm => $lblPerm): ?>
                                                        <option value="<?= htmlspecialchars($kPerm, ENT_QUOTES, 'UTF-8') ?>" <?= $p['permissao'] === $kPerm ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($lblPerm, ENT_QUOTES, 'UTF-8') ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>
                                            <form action="<?= BASE_URL ?>/compartilhamentos/revogar/<?= (int) $p['id'] ?>" method="POST" onsubmit="return confirm('Remover o acesso deste participante à conta?');">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                <input type="hidden" name="recurso_tipo" value="conta">
                                                <input type="hidden" name="recurso_id" value="<?= $cId ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Remover participante">
                                                    <i class="bi bi-person-x"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Visão do Participante -->
                        <div class="p-3 border rounded bg-light mb-3">
                            <div class="small text-muted mb-1">Proprietário da Conta</div>
                            <div class="fw-bold"><?= htmlspecialchars((string) ($c['proprietario_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-muted small mb-2"><?= htmlspecialchars((string) ($c['proprietario_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            <hr class="my-2">
                            <div class="small text-muted mb-1">Sua permissão atual</div>
                            <span class="badge" style="background-color: var(--symo-primary-fixed); color: var(--symo-on-primary-fixed);">
                                <?= htmlspecialchars($permissaoLabel, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>

                        <form action="<?= BASE_URL ?>/compartilhamentos/dashboard/<?= $compId ?>" method="POST" class="mb-4">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="recurso_tipo" value="conta">
                            <input type="hidden" name="recurso_id" value="<?= $cId ?>">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="switchDashConta_<?= $cId ?>" name="exibir_dashboard" value="1" <?= $exibirNoMeuDash ? 'checked' : '' ?> onchange="this.form.submit()">
                                <label class="form-check-label fw-semibold" for="switchDashConta_<?= $cId ?>">Exibir no Dashboard</label>
                            </div>
                            <div class="form-text">
                                Quando marcado, o saldo e as receitas/despesas visíveis desta conta compartilhada são incluídos no seu Dashboard.
                            </div>
                        </form>

                        <form action="<?= BASE_URL ?>/compartilhamentos/sair/<?= $compId ?>" method="POST" onsubmit="return confirm('Deseja realmente sair do compartilhamento desta conta?');">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="recurso_tipo" value="conta">
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="bi bi-box-arrow-right me-1"></i>Sair desta conta compartilhada
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- Modal: Nova Conta Bancária -->
<div class="modal fade" id="modalNovaConta" tabindex="-1" aria-labelledby="modalNovaContaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content symo-modal-content">
            <form action="<?= BASE_URL ?>/contas/salvar" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
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
                                <option value="dinheiro">Dinheiro Físico</option>
                                <option value="outros">Outros</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="saldo_inicial" class="form-label fw-semibold">Saldo Inicial</label>
                            <input type="text" class="form-control money-mask tabular-nums text-end" id="saldo_inicial" name="saldo_inicial" placeholder="R$ 0,00" value="R$ 0,00">
                        </div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="exibir_dashboard" name="exibir_dashboard" value="1" checked>
                        <label class="form-check-label fw-semibold" for="exibir_dashboard">Exibir no Dashboard</label>
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
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
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
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="edit_exibir_dashboard" name="exibir_dashboard" value="1">
                        <label class="form-check-label fw-semibold" for="edit_exibir_dashboard">Exibir no Dashboard</label>
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
        const exibirDashboardEditarConta = document.getElementById('edit_exibir_dashboard');

        editButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                const nome = btn.getAttribute('data-nome');
                const instituicao = btn.getAttribute('data-instituicao');
                const tipo = btn.getAttribute('data-tipo');
                const exibirDashboard = btn.getAttribute('data-exibir-dashboard') === '1';
                const cor = btn.getAttribute('data-cor');
                const status = btn.getAttribute('data-status');

                formEditar.action = '<?= BASE_URL ?>/contas/atualizar/' + id;
                document.getElementById('edit_nome').value = nome;
                document.getElementById('edit_instituicao').value = instituicao;
                document.getElementById('edit_tipo').value = tipo;
                exibirDashboardEditarConta.checked = exibirDashboard;
                document.getElementById('edit_cor').value = cor;
                document.getElementById('edit_status').value = status;

                modalEditar.show();
            });
        });
    });
</script>
