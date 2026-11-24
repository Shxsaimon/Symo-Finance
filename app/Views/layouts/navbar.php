<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Componente Navbar Superior
 *
 * Barra de topo fixa com alternância para mobile, título contextual,
 * atalho de ação rápida e menu de perfil do usuário.
 */
$currentUser = AuthHelper::user();
$pageTitle   = $title ?? 'Painel Financeiro';
$pendingNotifications = !empty($currentUser['id'])
    ? Share::getPendingNotificationsForUser((int) $currentUser['id'])
    : [];
$hasPendingNotifications = !empty($pendingNotifications);
$currentUriForRedirect = Router::getCurrentUri() ?: '/dashboard';
?>
<header class="navbar-symo">
    <!-- Lado Esquerdo: Botão Mobile & Título Contextual -->
    <div class="d-flex align-items-center gap-3">
        <!-- Botão Hambúrguer Mobile (abre offcanvas) -->
        <button class="btn btn-link text-dark p-0 d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Abrir Menu Lateral">
            <i class="bi bi-list fs-2" style="color: var(--symo-primary-brand);"></i>
        </button>

        <h2 class="navbar-title d-none d-sm-block">
            <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>
        </h2>
    </div>

    <div class="navbar-actions">
        <button type="button" class="btn btn-link p-0 text-decoration-none fw-semibold" data-bs-toggle="modal" data-bs-target="#modalFeedback" style="font-size: 0.75rem; color: var(--symo-primary-brand);">
            Versão BETA
        </button>

        <!-- Badge de Perfil (Exclusivo DEV) -->
        <?php if (AuthHelper::isDev()): ?>
            <span class="badge-role-dev d-none d-md-inline-block">
                DEV
            </span>
        <?php endif; ?>

        <!-- Dropdown de Usuário -->
        <div class="dropdown">
            <button class="btn p-0 d-flex align-items-center gap-2 border-0 bg-transparent" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="position-relative" style="width: 38px; height: 38px;">
                    <?php if (!empty($currentUser['avatar'])): ?>
                        <img src="<?= BASE_URL ?>/media/avatar/<?= (int) $currentUser['id'] ?>" alt="Avatar" class="rounded-circle" style="width: 38px; height: 38px; object-fit: cover; box-shadow: 0 2px 6px rgba(111,2,255,0.2);">
                    <?php else: ?>
                        <div style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, var(--symo-primary-brand), var(--symo-secondary-brand)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.95rem; box-shadow: 0 2px 6px rgba(111,2,255,0.2);">
                            <?= htmlspecialchars(mb_strtoupper(mb_substr($currentUser['nome'] ?? 'U', 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($hasPendingNotifications): ?>
                        <span style="position: absolute; top: -1px; right: -1px; width: 11px; height: 11px; border-radius: 50%; background-color: var(--symo-primary-brand); border: 2px solid #ffffff;" title="Você possui notificações pendentes"></span>
                    <?php endif; ?>
                </div>
                <div class="text-start d-none d-xl-block">
                    <div style="font-size: 0.875rem; font-weight: 600; color: var(--symo-on-surface); line-height: 1.2;">
                        <?= htmlspecialchars($currentUser['nome'] ?? 'Usuário', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--symo-outline);">
                        <?= htmlspecialchars($currentUser['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
                <i class="bi bi-chevron-down ms-1" style="font-size: 0.75rem; color: var(--symo-outline);"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end symo-modal-content border-0 shadow-sm mt-2" aria-labelledby="userDropdown" style="min-width: 215px; padding: 0.5rem;">
                <li class="px-3 py-2 border-bottom d-xl-none">
                    <p class="mb-0 fw-bold" style="font-size: 0.875rem;"><?= htmlspecialchars($currentUser['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                    <small class="text-muted"><?= htmlspecialchars($currentUser['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                </li>
                <li>
                    <button type="button" class="dropdown-item py-2 d-flex align-items-center justify-content-between gap-2 border-0 bg-transparent w-100" data-bs-toggle="modal" data-bs-target="#modalNotificacoes">
                        <span class="d-flex align-items-center gap-2">
                            <span class="position-relative d-inline-flex align-items-center">
                                <i class="bi bi-bell"></i>
                                <?php if ($hasPendingNotifications): ?>
                                    <span style="position: absolute; top: -2px; right: -3px; width: 8px; height: 8px; border-radius: 50%; background-color: var(--symo-primary-brand);"></span>
                                <?php endif; ?>
                            </span>
                            <span>Notificações</span>
                        </span>
                        <?php if ($hasPendingNotifications): ?>
                            <span style="width: 8px; height: 8px; border-radius: 50%; background-color: var(--symo-primary-brand); display: inline-block;"></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li>
                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= BASE_URL ?>/dashboard">
                        <i class="bi bi-grid-1x2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= BASE_URL ?>/perfil">
                        <i class="bi bi-person-circle"></i>
                        <span>Perfil</span>
                    </a>
                </li>
                <?php if (AuthHelper::isAdmin()): ?>
                    <li>
                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= BASE_URL ?>/configuracoes">
                            <i class="bi bi-gear"></i>
                            <span>Configurações</span>
                        </a>
                    </li>
                <?php endif; ?>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <form method="post" action="<?= BASE_URL ?>/logout" class="m-0">
                        <?= CsrfHelper::inputField() ?>
                        <button type="submit" class="dropdown-item py-2 text-danger d-flex align-items-center gap-2 border-0 bg-transparent w-100">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Sair do Sistema</span>
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>

<!-- Modal de Notificações (Convites de Compartilhamento) -->
<div class="modal fade" id="modalNotificacoes" tabindex="-1" aria-labelledby="modalNotificacoesLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content symo-modal-content">
            <div class="modal-header border-bottom" style="background-color: var(--symo-primary-brand); color: white;">
                <h5 class="modal-title fw-bold" id="modalNotificacoesLabel">
                    <i class="bi bi-bell-fill me-2"></i>Notificações
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body p-4">
                <?php if (empty($pendingNotifications)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-bell-slash fs-1 d-block mb-2 text-secondary"></i>
                        <p class="mb-0 fw-semibold">Você não possui notificações pendentes.</p>
                        <small>Convites de contas bancárias e investimentos compartilhados aparecerão aqui.</small>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($pendingNotifications as $notif): ?>
                            <?php
                                $ehInvest = ($notif['recurso_tipo'] ?? 'conta') === 'investimento';
                                $iconeNotif = $ehInvest ? 'bi-piggy-bank-fill' : 'bi-bank';
                                $tipoLabelNotif = $ehInvest ? 'Investimento' : 'Conta Bancária';
                                $permLabelNotif = Share::PERMISSOES[$notif['permissao']] ?? $notif['permissao'];
                            ?>
                            <div class="p-3 border rounded-3 bg-light">
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width: 36px; height: 36px; border-radius: 8px; background-color: var(--symo-primary-fixed); color: var(--symo-on-primary-fixed); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                                            <i class="bi <?= $iconeNotif ?>"></i>
                                        </div>
                                        <div>
                                            <span class="badge text-bg-light border text-muted" style="font-size: 0.7rem;"><?= $tipoLabelNotif ?></span>
                                            <div class="fw-bold" style="color: var(--symo-on-surface);">
                                                <?= htmlspecialchars((string) ($notif['recurso_nome'] ?? 'Item compartilhado'), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.75rem;"><?= FormatHelper::date($notif['convidado_em']) ?></small>
                                </div>
                                <p class="small text-muted mb-2">
                                    <strong><?= htmlspecialchars((string) $notif['proprietario_nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    (<?= htmlspecialchars((string) $notif['proprietario_email'], ENT_QUOTES, 'UTF-8') ?>) convidou você com a permissão:
                                    <span class="fw-semibold" style="color: var(--symo-primary-brand);"><?= htmlspecialchars($permLabelNotif, ENT_QUOTES, 'UTF-8') ?></span>.
                                </p>
                                <div class="d-flex justify-content-end gap-2 mt-2">
                                    <form action="<?= BASE_URL ?>/compartilhamentos/recusar/<?= (int) $notif['id'] ?>" method="POST" class="d-inline">
                                        <?= CsrfHelper::inputField() ?>
                                        <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($currentUriForRedirect, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-x-lg me-1"></i>Recusar
                                        </button>
                                    </form>
                                    <form action="<?= BASE_URL ?>/compartilhamentos/aceitar/<?= (int) $notif['id'] ?>" method="POST" class="d-inline">
                                        <?= CsrfHelper::inputField() ?>
                                        <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($currentUriForRedirect, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="btn btn-sm btn-symo-primary">
                                            <i class="bi bi-check-lg me-1"></i>Aceitar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalFeedback" tabindex="-1" aria-labelledby="modalFeedbackLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content symo-modal-content">
            <form action="<?= BASE_URL ?>/feedback/salvar" method="POST" enctype="multipart/form-data" id="formFeedback">
                <?= CsrfHelper::inputField() ?>
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalFeedbackLabel">Enviar feedback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="feedbackTela" class="form-label fw-semibold">Tela</label>
                        <select class="form-select" id="feedbackTela" name="tela" required>
                            <option value="" selected disabled>Selecione a tela</option>
                            <option value="sistema">Sistema (geral)</option>
                            <option value="dashboard">Dashboard</option>
                            <option value="lancamentos">Lançamentos</option>
                            <option value="contas">Contas bancárias</option>
                            <option value="categorias">Categorias</option>
                            <option value="meios_pagamento">Meios de pagamento</option>
                            <option value="cartoes">Cartões de crédito</option>
                            <option value="transferencias">Transferências</option>
                            <option value="investimentos">Investimentos</option>
                            <option value="relatorios">Relatórios</option>
                            <option value="lixeira">Lixeira</option>
                            <option value="configuracoes">Configurações</option>
                            <option value="perfil">Perfil</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="feedbackTipo" class="form-label fw-semibold">Tipo de feedback</label>
                        <select class="form-select" id="feedbackTipo" name="tipo" required>
                            <option value="" selected disabled>Selecione o tipo</option>
                            <option value="correcao">Correção</option>
                            <option value="melhoria">Melhoria</option>
                            <option value="duvida">Dúvida</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="feedbackTexto" class="form-label fw-semibold">Feedback</label>
                        <textarea class="form-control" id="feedbackTexto" name="texto" rows="5" minlength="10" maxlength="3000" required></textarea>
                    </div>
                    <div>
                        <label class="form-label fw-semibold d-block">Imagens (opcional)</label>
                        <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2" id="btnAnexarImagensFeedback">
                            <i class="bi bi-paperclip"></i>
                            <span>Anexar imagens</span>
                        </button>
                        <input type="file" class="d-none" id="feedbackImagens" name="imagens[]" accept="image/jpeg,image/png,image/webp" multiple>
                        <div id="feedbackPreviews" class="d-flex flex-wrap gap-2 mt-2"></div>
                        <div class="form-text">Até 5 imagens, JPEG/PNG/WebP, 2 MB cada.</div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-symo-primary">Enviar feedback</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Lightbox de Pré-visualização de Imagens Anexadas ao Feedback -->
<div id="feedbackLightbox" class="d-none align-items-center justify-content-center" style="position: fixed; inset: 0; background: rgba(26,28,28,0.85); z-index: 2000; cursor: zoom-out;">
    <img id="feedbackLightboxImg" src="" alt="Pré-visualização ampliada da imagem anexada" style="max-width: 90vw; max-height: 90vh; border-radius: 0.5rem; box-shadow: 0 8px 30px rgba(0,0,0,0.4);">
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var inputImagens = document.getElementById('feedbackImagens');
    var btnAnexar = document.getElementById('btnAnexarImagensFeedback');
    var previewContainer = document.getElementById('feedbackPreviews');
    var lightbox = document.getElementById('feedbackLightbox');
    var lightboxImg = document.getElementById('feedbackLightboxImg');
    var formFeedback = document.getElementById('formFeedback');
    var modalFeedbackEl = document.getElementById('modalFeedback');
    var MAX_IMAGENS = 5;

    if (!inputImagens || !btnAnexar || !previewContainer || !lightbox || !lightboxImg) {
        return;
    }

    function limparPreviews() {
        previewContainer.querySelectorAll('img').forEach(function (img) {
            URL.revokeObjectURL(img.src);
        });
        previewContainer.innerHTML = '';
    }

    function fecharLightbox() {
        lightbox.classList.add('d-none');
        lightbox.classList.remove('d-flex');
        lightboxImg.src = '';
    }

    btnAnexar.addEventListener('click', function () {
        inputImagens.click();
    });

    inputImagens.addEventListener('change', function () {
        limparPreviews();

        Array.from(inputImagens.files || []).slice(0, MAX_IMAGENS).forEach(function (arquivo) {
            var url = URL.createObjectURL(arquivo);
            var thumb = document.createElement('img');
            thumb.src = url;
            thumb.alt = 'Pré-visualização de imagem anexada';
            thumb.title = 'Clique para ampliar';
            thumb.style.cssText = 'width: 48px; height: 48px; object-fit: cover; border-radius: 0.5rem; border: 1px solid var(--symo-outline-variant); cursor: zoom-in;';
            thumb.addEventListener('click', function () {
                lightboxImg.src = url;
                lightbox.classList.remove('d-none');
                lightbox.classList.add('d-flex');
            });
            previewContainer.appendChild(thumb);
        });
    });

    lightbox.addEventListener('click', fecharLightbox);

    if (modalFeedbackEl) {
        modalFeedbackEl.addEventListener('hidden.bs.modal', function () {
            if (formFeedback) {
                formFeedback.reset();
            }
            limparPreviews();
            fecharLightbox();
        });
    }
});
</script>
