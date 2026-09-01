<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View do Dashboard Financeiro (Fase 5: Layout Mestre e Design System)
 */
$user = AuthHelper::user();
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--symo-on-surface);">
            Olá, <?= htmlspecialchars(explode(' ', $user['nome'] ?? 'Usuário')[0], ENT_QUOTES, 'UTF-8') ?> 👋
        </h1>
        <p class="text-muted mb-0" style="font-size: 0.95rem;">
            Seja bem-vindo(a) ao painel de controle financeiro do <strong>Symo</strong>.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge text-bg-light border px-3 py-2 d-flex align-items-center gap-2">
            <i class="bi bi-calendar3" style="color: var(--symo-primary-brand);"></i>
            <span><?= date('d/m/Y') ?></span>
        </span>
        <?php if (AuthHelper::isAdmin()): ?>
            <span class="badge-role-admin">
                <i class="bi bi-shield-lock-fill me-1"></i> Administrador
            </span>
        <?php else: ?>
            <span class="badge-role-operador">
                <i class="bi bi-person-fill me-1"></i> Operador
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- 1. Cards de Indicadores Financeiros Rápidos (Grid de 12 Colunas) -->
<div class="row g-3 mb-4">
    <!-- Card 1: Saldo Consolidado -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="symo-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="label-caps">Saldo Atual Consolidado</span>
                <div class="icon-box" style="background-color: var(--symo-primary-fixed); color: var(--symo-on-primary-fixed); width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-wallet2 fs-5"></i>
                </div>
            </div>
            <div class="kpi-value fs-3 fw-bold mb-1" style="color: var(--symo-primary-brand);">
                R$ 0,00
            </div>
            <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.8rem;">
                <i class="bi bi-info-circle"></i>
                <span>Disponível em contas ativas</span>
            </div>
        </div>
    </div>

    <!-- Card 2: Receitas do Mês -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="symo-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="label-caps">Receitas do Mês</span>
                <div class="icon-box" style="background-color: #d1fae5; color: #047857; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-arrow-down-left fs-5"></i>
                </div>
            </div>
            <div class="kpi-value fs-3 fw-bold mb-1 text-success">
                R$ 0,00
            </div>
            <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.8rem;">
                <i class="bi bi-check2-circle"></i>
                <span>Previsto para o período</span>
            </div>
        </div>
    </div>

    <!-- Card 3: Despesas do Mês -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="symo-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="label-caps">Despesas do Mês</span>
                <div class="icon-box" style="background-color: #ffdad6; color: #ba1a1a; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-arrow-up-right fs-5"></i>
                </div>
            </div>
            <div class="kpi-value fs-3 fw-bold mb-1 text-danger">
                R$ 0,00
            </div>
            <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.8rem;">
                <i class="bi bi-clock"></i>
                <span>Compromissos do mês</span>
            </div>
        </div>
    </div>

    <!-- Card 4: Balanço Líquido -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="symo-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="label-caps">Balanço Líquido</span>
                <div class="icon-box" style="background-color: #e0f2fe; color: #0284c7; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-pie-chart fs-5"></i>
                </div>
            </div>
            <div class="kpi-value fs-3 fw-bold mb-1" style="color: var(--symo-on-surface);">
                R$ 0,00
            </div>
            <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.8rem;">
                <i class="bi bi-activity"></i>
                <span>Resultado operacional</span>
            </div>
        </div>
    </div>
</div>

<!-- 2. Apresentação do Design System e Componentes da Fase 5 -->
<div class="row g-4">
    <!-- Coluna Esquerda: Visão da Fase Concluída -->
    <div class="col-12 col-lg-8">
        <div class="symo-card mb-4">
            <div class="symo-card-header">
                <h3 class="symo-card-title d-flex align-items-center gap-2">
                    <i class="bi bi-layers-fill" style="color: var(--symo-primary-brand);"></i>
                    <span>Design System & Layout Mestre (Fase 5 Concluída)</span>
                </h3>
                <span class="badge text-bg-success">Ativo & Operacional</span>
            </div>
            <p class="text-muted" style="font-size: 0.95rem; line-height: 1.6;">
                O layout mestre corporativo do <strong>Symo</strong> foi estruturado com total autonomia técnica e fidelidade às especificações de <a href="<?= BASE_URL ?>/docs/DESIGN.md" class="text-decoration-none fw-bold" style="color: var(--symo-primary-brand);">docs/DESIGN.md</a>.
            </p>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <div class="p-3 border rounded-3 bg-light">
                        <div class="fw-bold mb-1" style="color: var(--symo-primary-brand);">
                            <i class="bi bi-check2-all me-1"></i> Autonomia de Assets
                        </div>
                        <p class="small text-muted mb-0">
                            Zero dependência de CDNs externas em tempo de execução. Bootstrap 5.3, Bootstrap Icons e Chart.js residem 100% na pasta <code>public/</code>.
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 border rounded-3 bg-light">
                        <div class="fw-bold mb-1" style="color: var(--symo-primary-brand);">
                            <i class="bi bi-layout-sidebar-inset me-1"></i> Sidebar 280px & Pílula
                        </div>
                        <p class="small text-muted mb-0">
                            Navegação lateral fixa de 280px no desktop e offcanvas responsivo no mobile, com indicador ativo em pílula de 4px na borda esquerda.
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 border rounded-3 bg-light">
                        <div class="fw-bold mb-1" style="color: var(--symo-primary-brand);">
                            <i class="bi bi-shield-lock me-1"></i> Menus RBAC Segregados
                        </div>
                        <p class="small text-muted mb-0">
                            Menus administrativos (Usuários, Lixeira, Auditoria e Configurações) isolados e visíveis exclusivamente para perfil <code>admin</code>.
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 border rounded-3 bg-light">
                        <div class="fw-bold mb-1" style="color: var(--symo-primary-brand);">
                            <i class="bi bi-123 me-1"></i> Numerais Tabulares
                        </div>
                        <p class="small text-muted mb-0">
                            Tipografia <em>Inter</em> com regra <code>tabular-nums</code> aplicada em valores monetários e tabelas financeiras.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Coluna Direita: Atalhos Rápidos & Próxima Fase -->
    <div class="col-12 col-lg-4">
        <!-- Card de Atalhos Rápidos -->
        <div class="symo-card mb-4">
            <div class="symo-card-header">
                <h4 class="symo-card-title" style="font-size: 0.95rem;">
                    <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Próximas Entregas
                </h4>
            </div>
            <p class="small text-muted mb-3">
                Com o layout mestre e os componentes base prontos, os próximos módulos de negócio serão encaixados nesta estrutura:
            </p>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                    <span><strong>Fase 6:</strong> Cadastros Básicos (Contas/Categorias)</span>
                    <span class="badge text-bg-secondary">Pendente</span>
                </li>
                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                    <span><strong>Fase 7:</strong> Cartões e Faturas</span>
                    <span class="badge text-bg-secondary">Pendente</span>
                </li>
                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                    <span><strong>Fase 8:</strong> Transações e Parcelamento</span>
                    <span class="badge text-bg-secondary">Pendente</span>
                </li>
            </ul>
        </div>

        <!-- Card de Sessão Ativa -->
        <div class="symo-card">
            <div class="symo-card-header">
                <h4 class="symo-card-title" style="font-size: 0.95rem;">
                    <i class="bi bi-person-badge-fill me-1" style="color: var(--symo-secondary-brand);"></i> Sessão Atual
                </h4>
            </div>
            <div class="small">
                <p class="mb-1"><strong>E-mail:</strong> <?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mb-1"><strong>Perfil:</strong> <?= strtoupper(htmlspecialchars($user['perfil'] ?? '', ENT_QUOTES, 'UTF-8')) ?></p>
                <p class="mb-3"><strong>Timeout:</strong> 60 minutos de inatividade</p>
                <a href="<?= BASE_URL ?>/logout" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Encerrar Sessão</span>
                </a>
            </div>
        </div>
    </div>
</div>
