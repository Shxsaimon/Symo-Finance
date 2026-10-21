<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Perfil do Usuário (users/profile.php)
 */
$csrfToken = CsrfHelper::generateToken();
$avatarUrl = !empty($user['avatar']) ? BASE_URL . '/media/avatar/' . (int) $user['id'] : null;
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--symo-on-surface);">Meu Perfil</h1>
        <p class="text-muted mb-0">Atualize suas informações cadastrais, foto de avatar e senha de acesso.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Coluna Esquerda: Cartão de Apresentação -->
    <div class="col-lg-4">
        <div class="symo-card shadow-sm border-0 text-center p-4">
            <div class="mb-3 d-flex justify-content-center">
                <?php if ($avatarUrl): ?>
                    <img src="<?= $avatarUrl ?>" alt="<?= htmlspecialchars($user['nome'], ENT_QUOTES, 'UTF-8') ?>" class="rounded-circle shadow-sm" style="width: 110px; height: 110px; object-fit: cover; border: 3px solid var(--symo-primary-brand);">
                <?php else: ?>
                    <div style="width: 110px; height: 110px; border-radius: 50%; background: linear-gradient(135deg, var(--symo-primary-brand), var(--symo-secondary-brand)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 2.5rem; margin: 0 auto; box-shadow: 0 4px 12px rgba(111,2,255,0.25);">
                        <?= htmlspecialchars(mb_strtoupper(mb_substr($user['nome'] ?? 'U', 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
            </div>
            <h4 class="fw-bold mb-1" style="color: var(--symo-on-surface);"><?= htmlspecialchars($user['nome'], ENT_QUOTES, 'UTF-8') ?></h4>
            <p class="text-muted mb-3"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></p>
            
            <div class="d-flex justify-content-center gap-2 mb-3">
                <span class="<?= $user['perfil'] === 'admin' ? 'badge-role-admin' : 'badge-role-operador' ?>">
                    <i class="bi <?= $user['perfil'] === 'admin' ? 'bi-shield-lock-fill' : 'bi-person-fill' ?> me-1"></i>
                    <?= strtoupper($user['perfil']) ?>
                </span>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                    <i class="bi bi-check-circle-fill me-1"></i><?= ucfirst($user['status']) ?>
                </span>
            </div>

            <hr class="my-3">
            <div class="text-start" style="font-size: 0.875rem;">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Membro desde:</span>
                    <span class="fw-semibold"><?= FormatHelper::date($user['criado_em']) ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Última atualização:</span>
                    <span class="fw-semibold"><?= FormatHelper::datetime($user['atualizado_em']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Coluna Direita: Formulário de Atualização -->
    <div class="col-lg-8">
        <div class="symo-card shadow-sm border-0 p-4">
            <h5 class="fw-bold mb-3" style="color: var(--symo-primary-brand);">
                <i class="bi bi-person-gear me-2"></i>Editar Dados Pessoais
            </h5>

            <form action="<?= BASE_URL ?>/perfil/salvar" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="nome" class="form-label fw-semibold">Nome Completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($user['nome'], ENT_QUOTES, 'UTF-8') ?>" required minlength="3">
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label fw-semibold">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>" required maxlength="150" autocomplete="email">
                        <small class="text-muted">O e-mail deve ser válido e exclusivo. Não é necessária confirmação por e-mail.</small>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="avatar" class="form-label fw-semibold">Alterar Foto de Avatar (Máx 2 MB)</label>
                    <input type="file" class="form-control" id="avatar" name="avatar" accept="image/svg+xml, image/png, image/jpeg, image/webp">
                    <small class="text-muted">Envie uma foto quadrada nos formatos JPG, PNG ou WebP.</small>
                </div>

                <hr class="my-4">

                <h5 class="fw-bold mb-3" style="color: var(--symo-primary-brand);">
                    <i class="bi bi-shield-lock me-2"></i>Alterar Senha de Acesso
                </h5>
                <p class="text-muted small mb-3">Preencha os campos abaixo apenas se desejar trocar a sua senha atual.</p>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="senha_atual" class="form-label fw-semibold">Senha Atual</label>
                        <input type="password" class="form-control" id="senha_atual" name="senha_atual" autocomplete="current-password" placeholder="Obrigatória para alterar">
                    </div>
                    <div class="col-md-4">
                        <label for="nova_senha" class="form-label fw-semibold">Nova Senha</label>
                        <input type="password" class="form-control" id="nova_senha" name="nova_senha" minlength="8" autocomplete="new-password" placeholder="Mínimo de 8 caracteres">
                    </div>
                    <div class="col-md-4">
                        <label for="confirmacao_senha" class="form-label fw-semibold">Confirmar Nova Senha</label>
                        <input type="password" class="form-control" id="confirmacao_senha" name="confirmacao_senha" minlength="8" autocomplete="new-password" placeholder="Repita a nova senha">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-symo-primary px-4">
                        <i class="bi bi-check2-circle me-1"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
