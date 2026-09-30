<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Layout Principal Mestre
 *
 * Estrutura visual responsiva para todas as áreas autenticadas do sistema.
 * Carrega exclusivamente assets locais e implementa o grid fluído com max-width de 1440px.
 */
$pageTitle = $title ?? 'Symo — Gestão Financeira Pessoal e Compartilhada';
$appName = 'Symo';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Favicon — declarado antes dos CSS para ser processado com prioridade máxima -->
    <!-- O shortcut icon (PNG) funciona como fallback mantido em cache durante navegação MPA -->
    <link rel="shortcut icon" href="<?= ASSET_URL ?>/img/favicon.png" type="image/png">
    <link rel="icon" type="image/svg+xml" href="<?= ASSET_URL ?>/img/favicon.svg">

    <!-- Assets Locais Obrigatórios (Sem CDNs externas) -->
    <link rel="stylesheet" href="<?= ASSET_URL ?>/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ASSET_URL ?>/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= ASSET_URL ?>/css/custom.css">
</head>
<body>
    <div class="app-layout">
        <!-- 1. Sidebar Fixa para Desktop (>= 992px) -->
        <?php require APP_ROOT . '/app/Views/layouts/sidebar.php'; ?>

        <!-- 2. Sidebar Offcanvas para Mobile (< 992px) -->
        <div class="offcanvas offcanvas-start p-0 d-lg-none" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel" style="width: 280px; background: linear-gradient(180deg, #4a2ec2 0%, #260058 100%);">
            <div class="offcanvas-header p-3 border-bottom border-white-subtle">
                <img src="<?= ASSET_URL ?>/img/logo.svg" alt="Symo" height="32">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
            </div>
            <div class="offcanvas-body p-0 d-flex flex-column">
                <?php
                // Reutilizamos a sidebar mantendo a consistência dos links
                $currentUser = AuthHelper::user();
                $isAdmin     = AuthHelper::isAdmin();
                $currentUri  = Router::getCurrentUri();
                ?>
                <nav class="sidebar-menu">
                    <div class="sidebar-section-title">Visão Geral</div>
                    <a href="<?= BASE_URL ?>/dashboard" class="sidebar-nav-link <?= ($currentUri === '/dashboard') ? 'active' : '' ?>">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>

                    <div class="sidebar-section-title">Movimentações</div>
                    <a href="<?= BASE_URL ?>/transacoes" class="sidebar-nav-link <?= str_starts_with($currentUri, '/transacoes') ? 'active' : '' ?>">
                        <i class="bi bi-arrow-left-right"></i>
                        <span>Lançamentos</span>
                    </a>
                    <a href="<?= BASE_URL ?>/transferencias" class="sidebar-nav-link <?= str_starts_with($currentUri, '/transferencias') ? 'active' : '' ?>">
                        <i class="bi bi-arrow-repeat"></i>
                        <span>Transferências</span>
                    </a>
                    <a href="<?= BASE_URL ?>/cartoes" class="sidebar-nav-link <?= str_starts_with($currentUri, '/cartoes') ? 'active' : '' ?>">
                        <i class="bi bi-credit-card-2-front-fill"></i>
                        <span>Cartões de Crédito</span>
                    </a>

                    <div class="sidebar-section-title">Cadastros</div>
                    <a href="<?= BASE_URL ?>/contas" class="sidebar-nav-link <?= str_starts_with($currentUri, '/contas') ? 'active' : '' ?>">
                        <i class="bi bi-bank"></i>
                        <span>Contas Bancárias</span>
                    </a>
                    <a href="<?= BASE_URL ?>/categorias" class="sidebar-nav-link <?= str_starts_with($currentUri, '/categorias') ? 'active' : '' ?>">
                        <i class="bi bi-tags-fill"></i>
                        <span>Categorias</span>
                    </a>
                    <a href="<?= BASE_URL ?>/meios-pagamento" class="sidebar-nav-link <?= str_starts_with($currentUri, '/meios-pagamento') ? 'active' : '' ?>">
                        <i class="bi bi-wallet2"></i>
                        <span>Meios de Pagamento</span>
                    </a>

                    <div class="sidebar-section-title">Relatórios</div>
                    <a href="<?= BASE_URL ?>/relatorios" class="sidebar-nav-link <?= str_starts_with($currentUri, '/relatorios') ? 'active' : '' ?>">
                        <i class="bi bi-bar-chart-line-fill"></i>
                        <span>Relatórios Analíticos</span>
                    </a>

                    <?php if ($isAdmin): ?>
                        <div class="sidebar-section-title">Administração</div>
                        <a href="<?= BASE_URL ?>/usuarios" class="sidebar-nav-link <?= str_starts_with($currentUri, '/usuarios') ? 'active' : '' ?>">
                            <i class="bi bi-people-fill"></i>
                            <span>Gestão de Usuários</span>
                        </a>
                        <a href="<?= BASE_URL ?>/lixeira" class="sidebar-nav-link <?= str_starts_with($currentUri, '/lixeira') ? 'active' : '' ?>">
                            <i class="bi bi-trash3-fill"></i>
                            <span>Lixeira do Sistema</span>
                        </a>
                        <a href="<?= BASE_URL ?>/auditoria" class="sidebar-nav-link <?= str_starts_with($currentUri, '/auditoria') ? 'active' : '' ?>">
                            <i class="bi bi-shield-check"></i>
                            <span>Trilha de Auditoria</span>
                        </a>
                        <a href="<?= BASE_URL ?>/configuracoes" class="sidebar-nav-link <?= str_starts_with($currentUri, '/configuracoes') ? 'active' : '' ?>">
                            <i class="bi bi-gear-fill"></i>
                            <span>Configurações</span>
                        </a>
                    <?php endif; ?>
                </nav>

                <div class="sidebar-footer mt-auto">
                    <form method="post" action="<?= BASE_URL ?>/logout">
                        <?= CsrfHelper::inputField() ?>
                        <button type="submit" class="btn btn-outline-light w-100 btn-sm d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Sair do Sistema</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 3. Wrapper de Conteúdo Principal -->
        <div class="main-wrapper">
            <!-- Navbar Superior -->
            <?php require APP_ROOT . '/app/Views/layouts/navbar.php'; ?>

            <!-- Área de Conteúdo da View -->
            <main class="content-area">
                <!-- Alertas Flash -->
                <?= FlashHelper::render() ?>

                <!-- Conteúdo Específico da Tela -->
                <?= $content ?? '' ?>
            </main>
        </div>
    </div>

    <!-- Scripts Locais Obrigatórios (Sem CDNs externas) -->
    <script src="<?= ASSET_URL ?>/js/bootstrap.bundle.min.js"></script>
    <script src="<?= ASSET_URL ?>/js/chart.umd.min.js"></script>
    <script src="<?= ASSET_URL ?>/js/app.js"></script>
</body>
</html>
