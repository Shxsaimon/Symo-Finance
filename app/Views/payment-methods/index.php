<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Meios de Pagamento
 */
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--symo-on-surface);">Meios de Pagamento</h1>
        <p class="text-muted mb-0" style="font-size: 0.95rem;">
            Opções do catálogo global e disponibilidade para seus novos lançamentos.
        </p>
    </div>
    <div>
        <?php if ($isDev): ?>
            <button type="button" class="btn-symo-primary" data-bs-toggle="modal" data-bs-target="#modalNovoMeio">
                <i class="bi bi-plus-lg"></i>
                <span>Novo Meio de Pagamento</span>
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Tabela de Meios de Pagamento -->
<div class="symo-card p-0 overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
        <h5 class="mb-0 fw-bold fs-6" style="color: var(--symo-on-surface);">
            <i class="bi bi-wallet2 me-2 text-muted"></i>Meios Cadastrados
        </h5>
        <span class="badge text-bg-secondary rounded-pill"><?= count($meios) ?> cadastrados</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-finance">
            <thead class="table-light">
                <tr class="label-caps">
                    <th scope="col" style="padding-left: 1.5rem;">Nome</th>
                    <th scope="col">Modalidade</th>
                    <th scope="col" class="text-center">Disponibilidade</th>
                    <th scope="col" class="text-end" style="padding-right: 1.5rem;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($meios)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            Nenhum meio de pagamento cadastrado.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($meios as $m): ?>
                        <tr>
                            <td style="padding-left: 1.5rem;" class="fw-bold" style="color: var(--symo-on-surface);">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi <?= (int)$m['eh_credito'] === 1 ? 'bi-credit-card-2-front text-primary' : 'bi-cash-stack text-success' ?> fs-5"></i>
                                    <span><?= htmlspecialchars($m['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if ((int) $m['eh_credito'] === 1): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                        Cartão de Crédito
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                        À Vista / Débito / Pix
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($m['status_global'] === 'inativo' || (int) $m['arquivado'] === 1): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Inativo globalmente</span>
                                <?php elseif ($m['status'] === 'ativo'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Ativo para você</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Inativo para você</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end" style="padding-right: 1.5rem;">
                                <div class="btn-group btn-group-sm">
                                    <?php if ($m['status_global'] === 'ativo' && (int) $m['arquivado'] !== 1): ?>
                                        <form action="<?= BASE_URL ?>/meios-pagamento/status/<?= $m['id'] ?>" method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                                            <button type="submit" class="btn btn-outline-secondary" title="<?= $m['status'] === 'ativo' ? 'Inativar para mim' : 'Ativar para mim' ?>" aria-label="<?= $m['status'] === 'ativo' ? 'Inativar para mim' : 'Ativar para mim' ?>">
                                                <i class="bi <?= $m['status'] === 'ativo' ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' ?>"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($isDev && (int) $m['arquivado'] !== 1): ?>
                                        <button type="button" class="btn btn-outline-secondary btn-edit-payment"
                                                data-id="<?= $m['id'] ?>"
                                                data-nome="<?= htmlspecialchars($m['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-credito="<?= $m['eh_credito'] ?>"
                                                title="Editar opção global" aria-label="Editar opção global">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if ($m['status_global'] === 'ativo'): ?>
                                            <form action="<?= BASE_URL ?>/meios-pagamento/inativar-global/<?= $m['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Inativar globalmente? A opção deixará de aparecer em novos lançamentos, mas continuará no histórico.');">
                                                <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Inativar globalmente" aria-label="Inativar globalmente">
                                                    <i class="bi bi-toggle-off"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form action="<?= BASE_URL ?>/meios-pagamento/reativar/<?= $m['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Reativar globalmente para os usuários? Preferências pessoais inativas continuarão inativas.');">
                                                <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                                                <button type="submit" class="btn btn-outline-success" title="Reativar globalmente" aria-label="Reativar globalmente">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
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

<?php if ($isDev): ?>
    <!-- Modal: Novo Meio de Pagamento -->
    <div class="modal fade" id="modalNovoMeio" tabindex="-1" aria-labelledby="modalNovoMeioLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content symo-modal-content">
                <form action="<?= BASE_URL ?>/meios-pagamento/salvar" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold" id="modalNovoMeioLabel">Novo Meio de Pagamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="meio_nome" class="form-label fw-semibold">Nome do Meio *</label>
                            <input type="text" class="form-control" id="meio_nome" name="nome" placeholder="Ex: Pix, Dinheiro, Cartão Visa" required>
                        </div>
                        <div class="mb-3">
                            <label for="meio_credito" class="form-label fw-semibold">Modalidade</label>
                            <select class="form-select" id="meio_credito" name="eh_credito">
                                <option value="0" selected>À Vista / Débito / Transferência / Pix</option>
                                <option value="1">Cartão de Crédito (Gera fatura)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-symo-primary">Salvar Meio</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Editar Meio de Pagamento -->
    <div class="modal fade" id="modalEditarMeio" tabindex="-1" aria-labelledby="modalEditarMeioLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content symo-modal-content">
                <form id="formEditarMeio" action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold" id="modalEditarMeioLabel">Editar Meio de Pagamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="edit_meio_nome" class="form-label fw-semibold">Nome do Meio *</label>
                            <input type="text" class="form-control" id="edit_meio_nome" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_meio_credito" class="form-label fw-semibold">Modalidade</label>
                            <select class="form-select" id="edit_meio_credito" name="eh_credito">
                                <option value="0">À Vista / Débito / Transferência / Pix</option>
                                <option value="1">Cartão de Crédito (Gera fatura)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-symo-primary">Atualizar Meio</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editButtons = document.querySelectorAll('.btn-edit-payment');
            const formEditar = document.getElementById('formEditarMeio');
            const modalEditarEl = document.getElementById('modalEditarMeio');
            const modalEditar = new bootstrap.Modal(modalEditarEl);

            editButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-id');
                    const nome = btn.getAttribute('data-nome');
                    const credito = btn.getAttribute('data-credito');

                    formEditar.action = '<?= BASE_URL ?>/meios-pagamento/atualizar/' + id;
                    document.getElementById('edit_meio_nome').value = nome;
                    document.getElementById('edit_meio_credito').value = credito;

                    modalEditar.show();
                });
            });
        });
    </script>
<?php endif; ?>
