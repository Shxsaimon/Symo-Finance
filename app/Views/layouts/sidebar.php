<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Componente de Sidebar de Navegação
 *
 * Exibe a árvore de módulos com marcação de rota ativa e restrição RBAC
 * para as funcionalidades administrativas exclusivas do Administrador.
 */
$currentUser = AuthHelper::user();
$isAdmin     = AuthHelper::isAdmin();
$isDev       = AuthHelper::isDev();
$currentUri  = Router::getCurrentUri();

// Helper local para marcar item ativo
$isActive = function (string $path) use ($currentUri): string {
    return ($currentUri === $path || str_starts_with($currentUri, $path . '/')) ? 'active' : '';
};
?>
<aside class="sidebar-symo">
    <!-- Logotipo e Marca -->
    <a href="<?= BASE_URL ?>/dashboard" class="sidebar-brand">
        <img src="<?= ASSET_URL ?>/img/logo.svg" alt="Symo Financeiro">
    </a>

    <nav class="sidebar-menu">
        <a href="<?= BASE_URL ?>/dashboard" class="sidebar-nav-link <?= $isActive('/dashboard') ?>">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>

        <!-- 2. Movimentações Financeiras -->
        <div class="sidebar-section-title">Movimentações</div>
        <a href="<?= BASE_URL ?>/transacoes" class="sidebar-nav-link <?= $isActive('/transacoes') ?>">
            <i class="bi bi-arrow-left-right"></i>
            <span>Lançamentos</span>
        </a>
        <a href="<?= BASE_URL ?>/transferencias" class="sidebar-nav-link <?= $isActive('/transferencias') ?>">
            <i class="bi bi-arrow-repeat"></i>
            <span>Transferências</span>
        </a>
        <a href="<?= BASE_URL ?>/cartoes" class="sidebar-nav-link <?= ($isActive('/cartoes') || str_starts_with($currentUri, '/faturas')) ? 'active' : '' ?>">
            <i class="bi bi-credit-card-2-front-fill"></i>
            <span>Cartões de Crédito</span>
        </a>
        <a href="<?= BASE_URL ?>/investimentos" class="sidebar-nav-link <?= $isActive('/investimentos') ?>">
            <i class="bi bi-piggy-bank-fill"></i>
            <span>Investimentos</span>
        </a>

        <!-- 3. Cadastros Básicos -->
        <div class="sidebar-section-title">Cadastros</div>
        <a href="<?= BASE_URL ?>/contas" class="sidebar-nav-link <?= $isActive('/contas') ?>">
            <i class="bi bi-bank"></i>
            <span>Contas Bancárias</span>
        </a>
        <a href="<?= BASE_URL ?>/categorias" class="sidebar-nav-link <?= $isActive('/categorias') ?>">
            <i class="bi bi-tags-fill"></i>
            <span>Categorias</span>
        </a>
        <a href="<?= BASE_URL ?>/meios-pagamento" class="sidebar-nav-link <?= $isActive('/meios-pagamento') ?>">
            <i class="bi bi-wallet2"></i>
            <span>Meios de Pagamento</span>
        </a>

        <!-- 4. Relatórios -->
        <div class="sidebar-section-title">Relatórios</div>
        <a href="<?= BASE_URL ?>/relatorios" class="sidebar-nav-link <?= $isActive('/relatorios') ?>">
            <i class="bi bi-bar-chart-line-fill"></i>
            <span>Relatórios Analíticos</span>
        </a>

        <!-- 5. Administração -->
        <div class="sidebar-section-title">Administração</div>
        <?php if ($isDev || $isAdmin): ?>
            <?php if ($isDev): ?>
            <a href="<?= BASE_URL ?>/usuarios" class="sidebar-nav-link <?= $isActive('/usuarios') ?>">
                <i class="bi bi-people-fill"></i>
                <span>Gestão de Usuários</span>
            </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/lixeira" class="sidebar-nav-link <?= $isActive('/lixeira') ?>">
                <i class="bi bi-trash3-fill"></i>
                <span>Lixeira do Sistema</span>
            </a>
            <?php if ($isDev): ?>
            <a href="<?= BASE_URL ?>/auditoria" class="sidebar-nav-link <?= $isActive('/auditoria') ?>">
                <i class="bi bi-shield-check"></i>
                <span>Trilha de Auditoria</span>
            </a>
            <a href="<?= BASE_URL ?>/feedbacks" class="sidebar-nav-link <?= $isActive('/feedbacks') ?>">
                <i class="bi bi-chat-left-text-fill"></i>
                <span>Feedbacks Recebidos</span>
            </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/configuracoes" class="sidebar-nav-link <?= $isActive('/configuracoes') ?>">
                <i class="bi bi-gear-fill"></i>
                <span>Configurações</span>
            </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/perfil" class="sidebar-nav-link <?= $isActive('/perfil') ?>">
            <i class="bi bi-person-circle"></i>
            <span>Perfil</span>
        </a>
    </nav>

    <!-- Rodapé da Sidebar: Usuário Autenticado -->
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <?= strtoupper(substr($currentUser['nome'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="sidebar-user-info">
                <p class="sidebar-user-name" title="<?= htmlspecialchars($currentUser['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($currentUser['nome'] ?? 'Usuário', ENT_QUOTES, 'UTF-8') ?>
                </p>
                <p class="sidebar-user-role">
                    <?= htmlspecialchars($currentUser['perfil'] ?? 'Operador', ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
            <form method="post" action="<?= BASE_URL ?>/logout" class="sidebar-logout-form">
                <?= CsrfHelper::inputField() ?>
                <button type="submit" title="Sair do Sistema" class="sidebar-logout-button">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
</aside>
