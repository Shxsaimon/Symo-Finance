<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Categorias de Receitas e Despesas
 */
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--symo-on-surface);">Categorias Financeiras</h1>
        <p class="text-muted mb-0" style="font-size: 0.95rem;">
            Classifique suas receitas e despesas para obter relatórios e visões analíticas precisas.
        </p>
    </div>
    <div>
        <?php if ($isAdmin): ?>
            <button type="button" class="btn-symo-primary" data-bs-toggle="modal" data-bs-target="#modalNovaCategoria">
                <i class="bi bi-plus-lg"></i>
                <span>Nova Categoria</span>
            </button>
        <?php else: ?>
            <span class="badge text-bg-light border px-3 py-2 text-muted">
                <i class="bi bi-eye me-1"></i> Modo Consulta (Operador)
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- Abas de Navegação (Despesas e Receitas) -->
<ul class="nav nav-pills mb-4 gap-2" id="categoriasTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active px-4 py-2 fw-semibold d-flex align-items-center gap-2" id="despesas-tab" data-bs-toggle="pill" data-bs-target="#despesas-pane" type="button" role="tab" aria-controls="despesas-pane" aria-selected="true" style="border-radius: 8px;">
            <i class="bi bi-arrow-up-right text-danger"></i>
            <span>Despesas (<?= count($despesas) ?>)</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link px-4 py-2 fw-semibold d-flex align-items-center gap-2" id="receitas-tab" data-bs-toggle="pill" data-bs-target="#receitas-pane" type="button" role="tab" aria-controls="receitas-pane" aria-selected="false" style="border-radius: 8px;">
            <i class="bi bi-arrow-down-left text-success"></i>
            <span>Receitas (<?= count($receitas) ?>)</span>
        </button>
    </li>
</ul>

<div class="tab-content" id="categoriasTabContent">
    <!-- 1. Aba de Despesas -->
    <div class="tab-pane fade show active" id="despesas-pane" role="tabpanel" aria-labelledby="despesas-tab" tabindex="0">
        <div class="symo-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-finance">
                    <thead class="table-light">
                        <tr class="label-caps">
                            <th scope="col" style="padding-left: 1.5rem;">Ícone & Nome</th>
                            <th scope="col">Tipo</th>
                            <th scope="col" class="text-center">Status</th>
                            <?php if ($isAdmin): ?>
                                <th scope="col" class="text-end" style="padding-right: 1.5rem;">Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($despesas)): ?>
                            <tr>
                                <td colspan="<?= $isAdmin ? 4 : 3 ?>" class="text-center py-5 text-muted">
                                    Nenhuma categoria de despesa cadastrada.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($despesas as $d): ?>
                                <tr>
                                    <td style="padding-left: 1.5rem;">
                                        <div class="d-flex align-items-center gap-3">
                                            <div style="width: 38px; height: 38px; border-radius: 8px; background-color: <?= htmlspecialchars($d['cor'], ENT_QUOTES, 'UTF-8') ?>20; color: <?= htmlspecialchars($d['cor'], ENT_QUOTES, 'UTF-8') ?>; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                                                <i class="bi <?= htmlspecialchars($d['icone'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                            </div>
                                            <span class="fw-bold" style="color: var(--symo-on-surface);">
                                                <?= htmlspecialchars($d['nome'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                            Despesa
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($d['status'] === 'ativa'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Ativa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Inativa</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($isAdmin): ?>
                                        <td class="text-end" style="padding-right: 1.5rem;">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-secondary btn-edit-category"
                                                        data-id="<?= $d['id'] ?>"
                                                        data-nome="<?= htmlspecialchars($d['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-tipo="despesa"
                                                        data-icone="<?= htmlspecialchars($d['icone'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-cor="<?= htmlspecialchars($d['cor'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-status="<?= htmlspecialchars($d['status'], ENT_QUOTES, 'UTF-8') ?>"
                                                        title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form action="<?= BASE_URL ?>/categorias/status/<?= $d['id'] ?>" method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                                                    <button type="submit" class="btn btn-outline-secondary" title="Alternar Status">
                                                        <i class="bi <?= $d['status'] === 'ativa' ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' ?>"></i>
                                                    </button>
                                                </form>
                                                <form action="<?= BASE_URL ?>/categorias/excluir/<?= $d['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Deseja realmente excluir esta categoria?');">
                                                    <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                                                    <button type="submit" class="btn btn-outline-danger" title="Excluir">
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
    </div>

    <!-- 2. Aba de Receitas -->
    <div class="tab-pane fade" id="receitas-pane" role="tabpanel" aria-labelledby="receitas-tab" tabindex="0">
        <div class="symo-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-finance">
                    <thead class="table-light">
                        <tr class="label-caps">
                            <th scope="col" style="padding-left: 1.5rem;">Ícone & Nome</th>
                            <th scope="col">Tipo</th>
                            <th scope="col" class="text-center">Status</th>
                            <?php if ($isAdmin): ?>
                                <th scope="col" class="text-end" style="padding-right: 1.5rem;">Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($receitas)): ?>
                            <tr>
                                <td colspan="<?= $isAdmin ? 4 : 3 ?>" class="text-center py-5 text-muted">
                                    Nenhuma categoria de receita cadastrada.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($receitas as $r): ?>
                                <tr>
                                    <td style="padding-left: 1.5rem;">
                                        <div class="d-flex align-items-center gap-3">
                                            <div style="width: 38px; height: 38px; border-radius: 8px; background-color: <?= htmlspecialchars($r['cor'], ENT_QUOTES, 'UTF-8') ?>20; color: <?= htmlspecialchars($r['cor'], ENT_QUOTES, 'UTF-8') ?>; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                                                <i class="bi <?= htmlspecialchars($r['icone'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                            </div>
                                            <span class="fw-bold" style="color: var(--symo-on-surface);">
                                                <?= htmlspecialchars($r['nome'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            Receita
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($r['status'] === 'ativa'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Ativa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Inativa</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($isAdmin): ?>
                                        <td class="text-end" style="padding-right: 1.5rem;">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-secondary btn-edit-category"
                                                        data-id="<?= $r['id'] ?>"
                                                        data-nome="<?= htmlspecialchars($r['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-tipo="receita"
                                                        data-icone="<?= htmlspecialchars($r['icone'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-cor="<?= htmlspecialchars($r['cor'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-status="<?= htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8') ?>"
                                                        title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form action="<?= BASE_URL ?>/categorias/status/<?= $r['id'] ?>" method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                                                    <button type="submit" class="btn btn-outline-secondary" title="Alternar Status">
                                                        <i class="bi <?= $r['status'] === 'ativa' ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' ?>"></i>
                                                    </button>
                                                </form>
                                                <form action="<?= BASE_URL ?>/categorias/excluir/<?= $r['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Deseja realmente excluir esta categoria?');">
                                                    <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                                                    <button type="submit" class="btn btn-outline-danger" title="Excluir">
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
    </div>
</div>

<?php if ($isAdmin): ?>
    <!-- Modal: Nova Categoria -->
    <div class="modal fade" id="modalNovaCategoria" tabindex="-1" aria-labelledby="modalNovaCategoriaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content symo-modal-content">
                <form action="<?= BASE_URL ?>/categorias/salvar" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold" id="modalNovaCategoriaLabel">Nova Categoria</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="cat_nome" class="form-label fw-semibold">Nome da Categoria *</label>
                            <input type="text" class="form-control" id="cat_nome" name="nome" placeholder="Ex: Alimentação, Salário, Saúde" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="cat_tipo" class="form-label fw-semibold">Tipo</label>
                                <select class="form-select" id="cat_tipo" name="tipo">
                                    <option value="despesa" selected>Despesa</option>
                                    <option value="receita">Receita</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="cat_cor" class="form-label fw-semibold">Cor</label>
                                <input type="color" class="form-control form-control-color w-100" id="cat_cor" name="cor" value="#793CFE">
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="cat_icone" class="form-label fw-semibold">Ícone (Bootstrap Icon)</label>
                                <select class="form-select" id="cat_icone" name="icone">
                                    <option value="bi-tag">bi-tag (Padrão)</option>
                                    <option value="bi-cart">bi-cart (Mercado)</option>
                                    <option value="bi-house">bi-house (Moradia)</option>
                                    <option value="bi-cash-coin">bi-cash-coin (Salário/Renda)</option>
                                    <option value="bi-heart-pulse">bi-heart-pulse (Saúde)</option>
                                    <option value="bi-mortarboard">bi-mortarboard (Educação)</option>
                                    <option value="bi-car-front">bi-car-front (Transporte)</option>
                                    <option value="bi-controller">bi-controller (Lazer)</option>
                                    <option value="bi-lightning">bi-lightning (Contas/Luz)</option>
                                    <option value="bi-graph-up-arrow">bi-graph-up (Investimentos)</option>
                                    <option value="bi-airplane">bi-airplane (Viagens)</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="cat_status" class="form-label fw-semibold">Status</label>
                                <select class="form-select" id="cat_status" name="status">
                                    <option value="ativa" selected>Ativa</option>
                                    <option value="inativa">Inativa</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-symo-primary">Salvar Categoria</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Editar Categoria -->
    <div class="modal fade" id="modalEditarCategoria" tabindex="-1" aria-labelledby="modalEditarCategoriaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content symo-modal-content">
                <form id="formEditarCategoria" action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold" id="modalEditarCategoriaLabel">Editar Categoria</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="edit_cat_nome" class="form-label fw-semibold">Nome da Categoria *</label>
                            <input type="text" class="form-control" id="edit_cat_nome" name="nome" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="edit_cat_tipo" class="form-label fw-semibold">Tipo</label>
                                <select class="form-select" id="edit_cat_tipo" name="tipo">
                                    <option value="despesa">Despesa</option>
                                    <option value="receita">Receita</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="edit_cat_cor" class="form-label fw-semibold">Cor</label>
                                <input type="color" class="form-control form-control-color w-100" id="edit_cat_cor" name="cor">
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="edit_cat_icone" class="form-label fw-semibold">Ícone</label>
                                <select class="form-select" id="edit_cat_icone" name="icone">
                                    <option value="bi-tag">bi-tag (Padrão)</option>
                                    <option value="bi-cart">bi-cart (Mercado)</option>
                                    <option value="bi-house">bi-house (Moradia)</option>
                                    <option value="bi-cash-coin">bi-cash-coin (Salário/Renda)</option>
                                    <option value="bi-heart-pulse">bi-heart-pulse (Saúde)</option>
                                    <option value="bi-mortarboard">bi-mortarboard (Educação)</option>
                                    <option value="bi-car-front">bi-car-front (Transporte)</option>
                                    <option value="bi-controller">bi-controller (Lazer)</option>
                                    <option value="bi-lightning">bi-lightning (Contas/Luz)</option>
                                    <option value="bi-graph-up-arrow">bi-graph-up (Investimentos)</option>
                                    <option value="bi-airplane">bi-airplane (Viagens)</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="edit_cat_status" class="form-label fw-semibold">Status</label>
                                <select class="form-select" id="edit_cat_status" name="status">
                                    <option value="ativa">Ativa</option>
                                    <option value="inativa">Inativa</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-symo-primary">Atualizar Categoria</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editButtons = document.querySelectorAll('.btn-edit-category');
            const formEditar = document.getElementById('formEditarCategoria');
            const modalEditarEl = document.getElementById('modalEditarCategoria');
            const modalEditar = new bootstrap.Modal(modalEditarEl);

            editButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-id');
                    const nome = btn.getAttribute('data-nome');
                    const tipo = btn.getAttribute('data-tipo');
                    const icone = btn.getAttribute('data-icone');
                    const cor = btn.getAttribute('data-cor');
                    const status = btn.getAttribute('data-status');

                    formEditar.action = '<?= BASE_URL ?>/categorias/atualizar/' + id;
                    document.getElementById('edit_cat_nome').value = nome;
                    document.getElementById('edit_cat_tipo').value = tipo;
                    document.getElementById('edit_cat_icone').value = icone;
                    document.getElementById('edit_cat_cor').value = cor;
                    document.getElementById('edit_cat_status').value = status;

                    modalEditar.show();
                });
            });
        });
    </script>
<?php endif; ?>
