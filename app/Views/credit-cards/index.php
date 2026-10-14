<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Cartões de Crédito (Visão Geral e Cards de Limites)
 */
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--symo-on-surface);">Cartões de Crédito</h1>
        <p class="text-muted mb-0" style="font-size: 0.95rem;">
            Acompanhe seus limites de crédito, faturas mensais e datas de fechamento e vencimento.
        </p>
    </div>
    <div>
        <?php if ($isAdmin): ?>
            <button type="button" class="btn-symo-primary" data-bs-toggle="modal" data-bs-target="#modalNovoCartao">
                <i class="bi bi-plus-lg"></i>
                <span>Novo Cartão</span>
            </button>
        <?php else: ?>
            <span class="badge text-bg-light border px-3 py-2 text-muted">
                <i class="bi bi-eye me-1"></i> Modo Consulta (Operador)
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- Grid de Cartões de Crédito -->
<?php if (empty($cartoes)): ?>
    <div class="symo-card text-center py-5">
        <i class="bi bi-credit-card-2-front fs-1 text-secondary d-block mb-3"></i>
        <h4 class="h5 fw-bold" style="color: var(--symo-on-surface);">Nenhum cartão de crédito cadastrado</h4>
        <p class="text-muted mb-4" style="max-width: 480px; margin: 0 auto;">
            Cadastre seus cartões de crédito para controlar compras parceladas, limites disponíveis e o fechamento de faturas mensais.
        </p>
        <?php if ($isAdmin): ?>
            <button type="button" class="btn-symo-primary" data-bs-toggle="modal" data-bs-target="#modalNovoCartao">
                <i class="bi bi-plus-lg"></i>
                <span>Cadastrar Primeiro Cartão</span>
            </button>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="row g-4 mb-4">
        <?php foreach ($cartoes as $c): ?>
            <?php 
                $corCartao = !empty($c['cor']) ? htmlspecialchars($c['cor'], ENT_QUOTES, 'UTF-8') : '#3BD7D7';
                $porcentagem = (float) $c['porcentagem_usada'];
                $barraCor = 'bg-success';
                if ($porcentagem > 80) {
                    $barraCor = 'bg-danger';
                } elseif ($porcentagem > 50) {
                    $barraCor = 'bg-warning';
                }
            ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="symo-card h-100 d-flex flex-column justify-content-between position-relative overflow-hidden" style="border-top: 4px solid <?= $corCartao ?>;">
                    <div>
                        <!-- Cabeçalho do Cartão -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width: 36px; height: 36px; border-radius: 8px; background-color: <?= $corCartao ?>20; color: <?= $corCartao ?>; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                                    <i class="bi bi-credit-card-2-front"></i>
                                </div>
                                <div>
                                    <h2 class="h6 fw-bold mb-0" style="color: var(--symo-on-surface);">
                                        <?= htmlspecialchars($c['nome'], ENT_QUOTES, 'UTF-8') ?>
                                    </h2>
                                    <span class="text-muted small">
                                        <?= !empty($c['conta_padrao_nome']) ? 'Conta: ' . htmlspecialchars($c['conta_padrao_nome'], ENT_QUOTES, 'UTF-8') : 'Sem conta padrão' ?>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <?php if ($c['status'] === 'ativo'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Inativo</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Barra de Limite -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center small mb-1">
                                <span class="text-muted">Limite Utilizado:</span>
                                <span class="fw-bold tabular-nums" style="color: var(--symo-on-surface);">
                                    <?= FormatHelper::currency((float) $c['limite_usado']) ?> (<?= $porcentagem ?>%)
                                </span>
                            </div>
                            <div class="progress" role="progressbar" aria-label="Limite Usado" aria-valuenow="<?= $porcentagem ?>" aria-valuemin="0" aria-valuemax="100" style="height: 8px; border-radius: 4px;">
                                <div class="progress-bar <?= $barraCor ?>" style="width: <?= $porcentagem ?>%"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center small mt-1 text-muted">
                                <span>Disponível: <strong class="text-success tabular-nums"><?= FormatHelper::currency((float) $c['limite_disponivel']) ?></strong></span>
                                <span>Total: <strong class="tabular-nums"><?= FormatHelper::currency((float) $c['limite_credito']) ?></strong></span>
                            </div>
                        </div>

                        <!-- Datas do Ciclo -->
                        <div class="p-2 rounded bg-light border mb-3 d-flex justify-content-between align-items-center small">
                            <div>
                                <i class="bi bi-calendar-check text-muted me-1"></i>
                                <span>Fecha: <strong>Dia <?= sprintf('%02d', (int) $c['dia_fechamento']) ?></strong></span>
                            </div>
                            <div class="border-start ps-3">
                                <i class="bi bi-calendar-event text-danger me-1"></i>
                                <span>Vence: <strong>Dia <?= sprintf('%02d', (int) $c['dia_vencimento']) ?></strong></span>
                            </div>
                        </div>

                        <!-- Fatura Atual Aberta -->
                        <?php if (!empty($c['fatura_atual'])): ?>
                            <div class="d-flex justify-content-between align-items-center p-2 rounded mb-3" style="background-color: var(--symo-surface-container-low, #f5f4f4);">
                                <div>
                                    <div class="text-muted small">Fatura Atual (<?= sprintf('%02d/%04d', $c['fatura_atual']['mes_referencia'], $c['fatura_atual']['ano_referencia']) ?>):</div>
                                    <div class="fw-bold tabular-nums text-danger fs-6">
                                        <?= FormatHelper::currency((float) $c['fatura_atual']['valor_total']) ?>
                                    </div>
                                </div>
                                <div>
                                    <?php if (($c['fatura_atual']['status'] ?? '') === 'fechada'): ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">Fechada</span>
                                    <?php elseif (($c['fatura_atual']['status'] ?? '') === 'paga'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Paga</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-warning-subtle text-warning border border-warning-subtle">Aberta</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Rodapé de Ações -->
                    <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                        <a href="<?= BASE_URL ?>/cartoes/detalhes/<?= $c['id'] ?>" class="btn btn-sm btn-symo-primary fw-semibold">
                            <i class="bi bi-receipt me-1"></i> Ver Faturas
                        </a>

                        <?php if ($isAdmin): ?>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary btn-edit-card"
                                        data-id="<?= $c['id'] ?>"
                                        data-nome="<?= htmlspecialchars($c['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-limite="<?= htmlspecialchars(FormatHelper::currency((float) $c['limite_credito']), ENT_QUOTES, 'UTF-8') ?>"
                                        data-fechamento="<?= $c['dia_fechamento'] ?>"
                                        data-vencimento="<?= $c['dia_vencimento'] ?>"
                                        data-conta="<?= $c['conta_padrao_id'] ?? '' ?>"
                                        data-cor="<?= $c['cor'] ?>"
                                        data-status="<?= $c['status'] ?>"
                                        title="Editar Cartão">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="<?= BASE_URL ?>/cartoes/status/<?= $c['id'] ?>" method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                                    <button type="submit" class="btn btn-outline-secondary" title="Alternar Status">
                                        <i class="bi <?= $c['status'] === 'ativo' ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' ?>"></i>
                                    </button>
                                </form>
                                <form action="<?= BASE_URL ?>/cartoes/excluir/<?= $c['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Deseja realmente excluir este cartão de crédito?');">
                                    <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                                    <button type="submit" class="btn btn-outline-danger" title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($isAdmin): ?>
    <!-- Modal: Novo Cartão de Crédito -->
    <div class="modal fade" id="modalNovoCartao" tabindex="-1" aria-labelledby="modalNovoCartaoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content symo-modal-content">
                <form action="<?= BASE_URL ?>/cartoes/salvar" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold" id="modalNovoCartaoLabel">Cadastrar Cartão de Crédito</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="nome" class="form-label fw-semibold">Nome do Cartão *</label>
                            <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex: Nubank Mastercard, Itaú Platinum" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="limite_credito" class="form-label fw-semibold">Limite Total *</label>
                                <input type="text" class="form-control money-mask tabular-nums text-end" id="limite_credito" name="limite_credito" placeholder="R$ 0,00" value="R$ 1.000,00" required>
                            </div>
                            <div class="col-6">
                                <label for="conta_padrao_id" class="form-label fw-semibold">Conta Débito Padrão</label>
                                <select class="form-select" id="conta_padrao_id" name="conta_padrao_id">
                                    <option value="">Nenhuma (Selecionar no pagamento)</option>
                                    <?php foreach ($contas as $acc): ?>
                                        <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="dia_fechamento" class="form-label fw-semibold">Dia de Fechamento *</label>
                                <input type="number" class="form-control tabular-nums text-center" id="dia_fechamento" name="dia_fechamento" min="1" max="31" value="1" required>
                                <small class="text-muted">Dia do mês (1 a 31)</small>
                            </div>
                            <div class="col-6">
                                <label for="dia_vencimento" class="form-label fw-semibold">Dia de Vencimento *</label>
                                <input type="number" class="form-control tabular-nums text-center" id="dia_vencimento" name="dia_vencimento" min="1" max="31" value="10" required>
                                <small class="text-muted">Dia do mês (1 a 31)</small>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="cor" class="form-label fw-semibold">Cor do Cartão</label>
                                <input type="color" class="form-control form-control-color w-100" id="cor" name="cor" value="#3BD7D7" title="Escolha uma cor">
                            </div>
                            <div class="col-6">
                                <label for="status" class="form-label fw-semibold">Status Inicial</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="ativo" selected>Ativo</option>
                                    <option value="inativo">Inativo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-symo-primary">Salvar Cartão</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Editar Cartão de Crédito -->
    <div class="modal fade" id="modalEditarCartao" tabindex="-1" aria-labelledby="modalEditarCartaoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content symo-modal-content">
                <form id="formEditarCartao" action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold" id="modalEditarCartaoLabel">Editar Cartão de Crédito</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="edit_card_nome" class="form-label fw-semibold">Nome do Cartão *</label>
                            <input type="text" class="form-control" id="edit_card_nome" name="nome" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="edit_card_limite" class="form-label fw-semibold">Limite Total *</label>
                                <input type="text" class="form-control money-mask tabular-nums text-end" id="edit_card_limite" name="limite_credito" required>
                            </div>
                            <div class="col-6">
                                <label for="edit_card_conta" class="form-label fw-semibold">Conta Débito Padrão</label>
                                <select class="form-select" id="edit_card_conta" name="conta_padrao_id">
                                    <option value="">Nenhuma</option>
                                    <?php foreach ($contas as $acc): ?>
                                        <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="edit_card_fechamento" class="form-label fw-semibold">Dia Fechamento</label>
                                <input type="number" class="form-control tabular-nums text-center" id="edit_card_fechamento" name="dia_fechamento" min="1" max="31" required>
                            </div>
                            <div class="col-6">
                                <label for="edit_card_vencimento" class="form-label fw-semibold">Dia Vencimento</label>
                                <input type="number" class="form-control tabular-nums text-center" id="edit_card_vencimento" name="dia_vencimento" min="1" max="31" required>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="edit_card_cor" class="form-label fw-semibold">Cor</label>
                                <input type="color" class="form-control form-control-color w-100" id="edit_card_cor" name="cor">
                            </div>
                            <div class="col-6">
                                <label for="edit_card_status" class="form-label fw-semibold">Status</label>
                                <select class="form-select" id="edit_card_status" name="status">
                                    <option value="ativo">Ativo</option>
                                    <option value="inativo">Inativo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-symo-primary">Atualizar Cartão</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editButtons = document.querySelectorAll('.btn-edit-card');
            const formEditar = document.getElementById('formEditarCartao');
            const modalEditarEl = document.getElementById('modalEditarCartao');
            const modalEditar = new bootstrap.Modal(modalEditarEl);

            editButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-id');
                    const nome = btn.getAttribute('data-nome');
                    const limite = btn.getAttribute('data-limite');
                    const fechamento = btn.getAttribute('data-fechamento');
                    const vencimento = btn.getAttribute('data-vencimento');
                    const conta = btn.getAttribute('data-conta');
                    const cor = btn.getAttribute('data-cor');
                    const status = btn.getAttribute('data-status');

                    formEditar.action = '<?= BASE_URL ?>/cartoes/atualizar/' + id;
                    document.getElementById('edit_card_nome').value = nome;
                    document.getElementById('edit_card_limite').value = limite;
                    document.getElementById('edit_card_fechamento').value = fechamento;
                    document.getElementById('edit_card_vencimento').value = vencimento;
                    document.getElementById('edit_card_conta').value = conta;
                    document.getElementById('edit_card_cor').value = cor;
                    document.getElementById('edit_card_status').value = status;

                    modalEditar.show();
                });
            });
        });
    </script>
<?php endif; ?>
