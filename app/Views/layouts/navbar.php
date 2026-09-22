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

        <!-- Badge de Perfil -->
        <span class="<?= AuthHelper::isAdmin() ? 'badge-role-admin' : 'badge-role-operador' ?> d-none d-md-inline-block">
            <?= htmlspecialchars(strtoupper($currentUser['perfil'] ?? 'OPERADOR'), ENT_QUOTES, 'UTF-8') ?>
        </span>

        <!-- Dropdown de Usuário -->
        <div class="dropdown">
            <button class="btn p-0 d-flex align-items-center gap-2 border-0 bg-transparent" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <div style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, var(--symo-primary-brand), var(--symo-secondary-brand)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.95rem; box-shadow: 0 2px 6px rgba(111,2,255,0.2);">
                    <?= strtoupper(substr($currentUser['nome'] ?? 'U', 0, 1)) ?>
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
            <ul class="dropdown-menu dropdown-menu-end symo-modal-content border-0 shadow-sm mt-2" aria-labelledby="userDropdown" style="min-width: 200px; padding: 0.5rem;">
                <li class="px-3 py-2 border-bottom d-xl-none">
                    <p class="mb-0 fw-bold" style="font-size: 0.875rem;"><?= htmlspecialchars($currentUser['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                    <small class="text-muted"><?= htmlspecialchars($currentUser['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                </li>
                <li>
                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= BASE_URL ?>/dashboard">
                        <i class="bi bi-grid-1x2"></i>
                        <span>Dashboard</span>
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

<div class="modal fade" id="modalFeedback" tabindex="-1" aria-labelledby="modalFeedbackLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content symo-modal-content">
            <form action="<?= BASE_URL ?>/feedback/salvar" method="POST">
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
                            <option value="dashboard">Dashboard</option>
                            <option value="lancamentos">Lançamentos</option>
                            <option value="contas">Contas bancárias</option>
                            <option value="categorias">Categorias</option>
                            <option value="meios_pagamento">Meios de pagamento</option>
                            <option value="cartoes">Cartões de crédito</option>
                            <option value="transferencias">Transferências</option>
                            <option value="relatorios">Relatórios</option>
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
                    <div>
                        <label for="feedbackTexto" class="form-label fw-semibold">Feedback</label>
                        <textarea class="form-control" id="feedbackTexto" name="texto" rows="5" minlength="10" maxlength="3000" required></textarea>
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
