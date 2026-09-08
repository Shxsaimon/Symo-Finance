<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Gestão de Usuários (users/index.php)
 */
$csrfToken = CsrfHelper::generateToken();
$loggedUserId = (int) ($currentUser['id'] ?? 0);
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--symo-on-surface);">Gestão de Usuários</h1>
        <p class="text-muted mb-0">Controle de membros, perfis de acesso (RBAC) e credenciais do sistema.</p>
    </div>
    <div>
        <button type="button" class="btn btn-symo-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalNovoUsuario">
            <i class="bi bi-person-plus-fill"></i>
            <span>Novo Usuário</span>
        </button>
    </div>
</div>

<!-- Tabela de Usuários Cadastrados -->
<div class="symo-card shadow-sm border-0 p-0 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 symo-table">
            <thead>
                <tr>
                    <th style="width: 60px;">Avatar</th>
                    <th>Nome Completo</th>
                    <th>E-mail</th>
                    <th>Perfil (RBAC)</th>
                    <th>Status</th>
                    <th>Data de Cadastro</th>
                    <th class="text-end" style="width: 140px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-1 d-block mb-2 text-secondary"></i>
                            Nenhum usuário encontrado no sistema.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <?php 
                        $isSelf = ((int)$u['id'] === $loggedUserId);
                        $avatarUrl = !empty($u['avatar']) ? BASE_URL . '/storage/uploads/avatars/' . htmlspecialchars($u['avatar'], ENT_QUOTES, 'UTF-8') : null;
                        ?>
                        <tr>
                            <td>
                                <?php if ($avatarUrl): ?>
                                    <img src="<?= $avatarUrl ?>" alt="<?= htmlspecialchars($u['nome'], ENT_QUOTES, 'UTF-8') ?>" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover; border: 2px solid var(--symo-neutral-200);">
                                <?php else: ?>
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: <?= $u['perfil'] === 'admin' ? 'linear-gradient(135deg, var(--symo-primary-brand), var(--symo-secondary-brand))' : 'linear-gradient(135deg, #3BD7D7, #0284C7)' ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem;">
                                        <?= strtoupper(substr($u['nome'] ?? 'U', 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold" style="color: var(--symo-on-surface);">
                                    <?= htmlspecialchars($u['nome'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($isSelf): ?>
                                        <span class="badge bg-light text-dark border ms-1" style="font-size: 0.7rem;">Você</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="text-muted"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td>
                                <?php if ($u['perfil'] === 'admin'): ?>
                                    <span class="badge-role-admin">
                                        <i class="bi bi-shield-lock-fill me-1"></i>ADMIN
                                    </span>
                                <?php else: ?>
                                    <span class="badge-role-operador">
                                        <i class="bi bi-person-fill me-1"></i>OPERADOR
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['status'] === 'ativo'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                        <i class="bi bi-check-circle-fill me-1"></i>Ativo
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 rounded-pill">
                                        <i class="bi bi-dash-circle-fill me-1"></i>Inativo
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted" style="font-size: 0.875rem;">
                                <?= FormatHelper::datetime($u['criado_em']) ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <!-- Botão Editar -->
                                    <button type="button" class="btn btn-outline-secondary" title="Editar Usuário"
                                            onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <!-- Botão Redefinir Senha -->
                                    <button type="button" class="btn btn-outline-secondary" title="Redefinir Senha"
                                            onclick="abrirModalSenha(<?= (int)$u['id'] ?>, '<?= htmlspecialchars($u['nome'], ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="bi bi-key"></i>
                                    </button>

                                    <!-- Botão Alternar Status (Desativado se for a própria conta) -->
                                    <?php if (!$isSelf): ?>
                                        <form action="<?= BASE_URL ?>/usuarios/toggle-status/<?= (int)$u['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Deseja realmente <?= $u['status'] === 'ativo' ? 'inativar' : 'ativar' ?> este usuário?');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <button type="submit" class="btn btn-outline-<?= $u['status'] === 'ativo' ? 'warning' : 'success' ?>" title="<?= $u['status'] === 'ativo' ? 'Inativar Usuário' : 'Ativar Usuário' ?>">
                                                <i class="bi <?= $u['status'] === 'ativo' ? 'bi-pause-circle' : 'bi-play-circle' ?>"></i>
                                            </button>
                                        </form>

                                        <!-- Botão Excluir -->
                                        <form action="<?= BASE_URL ?>/usuarios/excluir/<?= (int)$u['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este usuário?');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Excluir Usuário">
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

<!-- MODAL: NOVO USUÁRIO -->
<div class="modal fade" id="modalNovoUsuario" tabindex="-1" aria-labelledby="modalNovoUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content symo-modal-content border-0 shadow">
            <form action="<?= BASE_URL ?>/usuarios/salvar" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalNovoUsuarioLabel" style="color: var(--symo-primary-brand);">
                        <i class="bi bi-person-plus-fill me-2"></i>Cadastrar Novo Usuário
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="novo_nome" class="form-label fw-semibold">Nome Completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="novo_nome" name="nome" required minlength="3" placeholder="Ex: Maria Santos">
                    </div>
                    <div class="mb-3">
                        <label for="novo_email" class="form-label fw-semibold">E-mail de Acesso <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="novo_email" name="email" required placeholder="Ex: maria@empresa.com">
                    </div>
                    <div class="mb-3">
                        <label for="novo_senha" class="form-label fw-semibold">Senha Inicial <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="novo_senha" name="senha" required minlength="6" placeholder="Mínimo de 6 caracteres">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="novo_perfil" class="form-label fw-semibold">Perfil de Acesso (RBAC) <span class="text-danger">*</span></label>
                            <select class="form-select" id="novo_perfil" name="perfil" required>
                                <option value="operador">Operador (Acesso restrito)</option>
                                <option value="admin">Administrador (Total)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="novo_status" class="form-label fw-semibold">Status Inicial <span class="text-danger">*</span></label>
                            <select class="form-select" id="novo_status" name="status" required>
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label for="novo_avatar" class="form-label fw-semibold">Foto de Avatar (Opcional - Máx 2 MB)</label>
                        <input type="file" class="form-control" id="novo_avatar" name="avatar" accept="image/png, image/jpeg, image/webp">
                        <small class="text-muted">Formatos: JPG, PNG ou WebP até 2 MB.</small>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-symo-primary">Salvar Usuário</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: EDITAR USUÁRIO -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-labelledby="modalEditarUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content symo-modal-content border-0 shadow">
            <form id="formEditarUsuario" action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalEditarUsuarioLabel" style="color: var(--symo-primary-brand);">
                        <i class="bi bi-pencil-square me-2"></i>Editar Usuário
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_nome" class="form-label fw-semibold">Nome Completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_nome" name="nome" required minlength="3">
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label fw-semibold">E-mail de Acesso <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="edit_perfil" class="form-label fw-semibold">Perfil de Acesso (RBAC) <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_perfil" name="perfil" required>
                                <option value="operador">Operador (Acesso restrito)</option>
                                <option value="admin">Administrador (Total)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_status" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label for="edit_avatar" class="form-label fw-semibold">Alterar Foto de Avatar (Opcional)</label>
                        <input type="file" class="form-control" id="edit_avatar" name="avatar" accept="image/png, image/jpeg, image/webp">
                        <small class="text-muted">Selecione apenas se desejar substituir a foto atual.</small>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-symo-primary">Atualizar Dados</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: REDEFINIR SENHA -->
<div class="modal fade" id="modalRedefinirSenha" tabindex="-1" aria-labelledby="modalRedefinirSenhaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content symo-modal-content border-0 shadow">
            <form id="formRedefinirSenha" action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold text-danger" id="modalRedefinirSenhaLabel">
                        <i class="bi bi-key-fill me-1"></i>Redefinir Senha
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3" id="senha_usuario_info"></p>
                    <div class="mb-2">
                        <label for="nova_senha" class="form-label fw-semibold">Nova Senha <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="nova_senha" name="nova_senha" required minlength="6" placeholder="Mínimo 6 caracteres">
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger btn-sm">Redefinir Senha</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalEditar(user) {
    const form = document.getElementById('formEditarUsuario');
    form.action = '<?= BASE_URL ?>/usuarios/atualizar/' + user.id;
    document.getElementById('edit_nome').value = user.nome;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_perfil').value = user.perfil;
    document.getElementById('edit_status').value = user.status;
    
    // Se for ele próprio, desabilita edição de perfil e status
    const isSelf = (parseInt(user.id) === <?= $loggedUserId ?>);
    document.getElementById('edit_perfil').disabled = isSelf;
    document.getElementById('edit_status').disabled = isSelf;

    const modal = new bootstrap.Modal(document.getElementById('modalEditarUsuario'));
    modal.show();
}

function abrirModalSenha(userId, userName) {
    const form = document.getElementById('formRedefinirSenha');
    form.action = '<?= BASE_URL ?>/usuarios/redefinir-senha/' + userId;
    document.getElementById('senha_usuario_info').textContent = 'Defina uma nova senha de acesso para ' + userName + '.';
    document.getElementById('nova_senha').value = '';

    const modal = new bootstrap.Modal(document.getElementById('modalRedefinirSenha'));
    modal.show();
}
</script>

