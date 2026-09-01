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

    <!-- Lado Direito: Ações e Menu de Perfil -->
    <div class="navbar-actions">
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
                    <a class="dropdown-item py-2 text-danger d-flex align-items-center gap-2" href="<?= BASE_URL ?>/logout">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Sair do Sistema</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
