<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View do Dashboard Financeiro (Fase 10: KPIs e Gráficos Interativos Chart.js)
 */
$user = AuthHelper::user();
$mesesNomes = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
$anoAtual = (int) date('Y');
$balancoPositivo = ((float)$resumoMes['saldo_periodo'] >= 0);
?>
<!-- Cabeçalho e Seletor de Período -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--symo-on-surface);">
            Olá, <?= htmlspecialchars(explode(' ', $user['nome'] ?? 'Usuário')[0], ENT_QUOTES, 'UTF-8') ?> 👋
        </h1>
        <p class="text-muted mb-0" style="font-size: 0.95rem;">
            Visão panorâmica em tempo real da saúde financeira do ambiente <strong>Symo</strong>.
        </p>
    </div>

    <!-- Filtro de Período Dinâmico -->
    <form method="GET" action="<?= BASE_URL ?>/dashboard" class="d-flex align-items-center gap-2">
        <select name="mes" class="form-select form-select-sm bg-white shadow-sm fw-semibold" onchange="this.form.submit()">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= ($mes === $m) ? 'selected' : '' ?>>
                    <?= $mesesNomes[$m] ?>
                </option>
            <?php endfor; ?>
        </select>

        <select name="ano" class="form-select form-select-sm bg-white shadow-sm fw-semibold" onchange="this.form.submit()">
            <?php for ($a = $anoAtual - 2; $a <= $anoAtual + 2; $a++): ?>
                <option value="<?= $a ?>" <?= ($ano === $a) ? 'selected' : '' ?>>
                    <?= $a ?>
                </option>
            <?php endfor; ?>
        </select>

        <a href="<?= BASE_URL ?>/transacoes" class="btn-symo-primary btn-sm d-flex align-items-center gap-1 text-nowrap shadow-sm">
            <i class="bi bi-plus-lg"></i>
            <span>Novo Lançamento</span>
        </a>
    </form>
</div>

<!-- 1. CARDS DE INDICADORES FINANCEIROS (KPIS) -->
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
            <div class="kpi-value fs-3 fw-bold mb-1 tabular-nums" style="color: var(--symo-primary-brand);">
                <?= FormatHelper::currency((float) $saldoConsolidado) ?>
            </div>
            <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.8rem;">
                <i class="bi bi-bank"></i>
                <span><?= count($contas) ?> conta(s) bancária(s) ativas</span>
            </div>
        </div>
    </div>

    <!-- Card 2: Receitas do Mês -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="symo-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="label-caps">Receitas de <?= $mesesNomes[$mes] ?></span>
                <div class="icon-box" style="background-color: #d1fae5; color: #047857; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-arrow-down-left fs-5"></i>
                </div>
            </div>
            <div class="kpi-value fs-3 fw-bold mb-1 text-success tabular-nums">
                <?= FormatHelper::currency((float) $resumoMes['total_receitas']) ?>
            </div>
            <div class="d-flex justify-content-between text-muted" style="font-size: 0.8rem;">
                <span class="text-success"><i class="bi bi-check2"></i> Realizadas: <?= FormatHelper::currency((float) $resumoMes['receitas_recebidas']) ?></span>
                <?php if ((float)$resumoMes['receitas_pendentes'] > 0): ?>
                    <span class="text-secondary"><i class="bi bi-clock"></i> Pendente: <?= FormatHelper::currency((float) $resumoMes['receitas_pendentes']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Card 3: Despesas do Mês -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="symo-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="label-caps">Despesas de <?= $mesesNomes[$mes] ?></span>
                <div class="icon-box" style="background-color: #ffdad6; color: #ba1a1a; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-arrow-up-right fs-5"></i>
                </div>
            </div>
            <div class="kpi-value fs-3 fw-bold mb-1 text-danger tabular-nums">
                <?= FormatHelper::currency((float) $resumoMes['total_despesas']) ?>
            </div>
            <div class="d-flex justify-content-between text-muted" style="font-size: 0.8rem;">
                <span class="text-danger"><i class="bi bi-check2"></i> Pagas: <?= FormatHelper::currency((float) $resumoMes['despesas_pagas']) ?></span>
                <?php if ((float)$resumoMes['despesas_pendentes'] > 0): ?>
                    <span class="text-secondary"><i class="bi bi-clock"></i> Pendente: <?= FormatHelper::currency((float) $resumoMes['despesas_pendentes']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Card 4: Balanço Líquido -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="symo-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="label-caps">Balanço Líquido</span>
                <div class="icon-box" style="background-color: <?= $balancoPositivo ? '#e0f2fe' : '#fee2e2' ?>; color: <?= $balancoPositivo ? '#0284c7' : '#dc2626' ?>; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-pie-chart fs-5"></i>
                </div>
            </div>
            <div class="kpi-value fs-3 fw-bold mb-1 tabular-nums <?= $balancoPositivo ? 'text-success' : 'text-danger' ?>">
                <?= FormatHelper::currency((float) $resumoMes['saldo_periodo']) ?>
            </div>
            <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.8rem;">
                <i class="bi bi-activity"></i>
                <span><?= $balancoPositivo ? 'Superávit financeiro' : 'Déficit no período' ?></span>
            </div>
        </div>
    </div>
</div>

<!-- 2. GRÁFICOS INTERATIVOS COM CHART.JS LOCAL -->
<div class="row g-4 mb-4">
    <!-- Gráfico 1: Evolução Financeira (Últimos 6 Meses) -->
    <div class="col-12 col-lg-8">
        <div class="symo-card h-100">
            <div class="symo-card-header d-flex justify-content-between align-items-center mb-3">
                <h3 class="symo-card-title fs-6 mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-graph-up-arrow" style="color: var(--symo-primary-brand);"></i>
                    <span>Evolução Financeira (Últimos 6 Meses)</span>
                </h3>
                <div class="d-flex align-items-center gap-3 small text-muted">
                    <span class="d-flex align-items-center gap-1"><span style="width: 12px; height: 12px; background: #10B981; border-radius: 3px; display: inline-block;"></span> Receitas</span>
                    <span class="d-flex align-items-center gap-1"><span style="width: 12px; height: 12px; background: #EF4444; border-radius: 3px; display: inline-block;"></span> Despesas</span>
                </div>
            </div>
            <div style="position: relative; height: 280px;">
                <canvas id="chartEvolucaoMensal"></canvas>
            </div>
        </div>
    </div>

    <!-- Gráfico 2: Despesas por Categoria (Donut) -->
    <div class="col-12 col-lg-4">
        <div class="symo-card h-100">
            <div class="symo-card-header mb-3">
                <h3 class="symo-card-title fs-6 mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-pie-chart-fill" style="color: var(--symo-secondary-brand);"></i>
                    <span>Despesas por Categoria (<?= $mesesNomes[$mes] ?>)</span>
                </h3>
            </div>
            <?php if (empty($despesasCategoria)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-tags fs-1 d-block mb-2 text-secondary"></i>
                    Nenhuma despesa registrada neste mês.
                </div>
            <?php else: ?>
                <div style="position: relative; height: 210px;" class="mb-3">
                    <canvas id="chartCategorias"></canvas>
                </div>
                <!-- Lista de Top Categorias -->
                <div class="small" style="max-height: 120px; overflow-y: auto;">
                    <?php foreach (array_slice($despesasCategoria, 0, 4) as $cat): ?>
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                            <div class="d-flex align-items-center gap-2 text-truncate">
                                <span style="width: 10px; height: 10px; background-color: <?= htmlspecialchars($cat['cor'], ENT_QUOTES, 'UTF-8') ?>; border-radius: 50%; display: inline-block;"></span>
                                <span class="fw-semibold text-truncate"><?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <span class="tabular-nums fw-bold ms-2"><?= FormatHelper::currency((float)$cat['total_valor']) ?> (<?= $cat['percentual'] ?>%)</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 3. WIDGETS INFERIORES: PRÓXIMOS COMPROMISSOS E CARTÕES DE CRÉDITO -->
<div class="row g-4">
    <!-- Widget 1: Compromissos a Vencer / Vencidos -->
    <div class="col-12 col-lg-7">
        <div class="symo-card h-100">
            <div class="symo-card-header d-flex justify-content-between align-items-center mb-3">
                <h3 class="symo-card-title fs-6 mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-alarm-fill text-warning"></i>
                    <span>Compromissos Pendentes (Próximos 7 Dias & Vencidos)</span>
                </h3>
                <a href="<?= BASE_URL ?>/transacoes?status=pendente" class="btn btn-sm btn-outline-secondary py-0" style="font-size: 0.8rem;">
                    Ver Todos
                </a>
            </div>

            <?php if (empty($proximosVencimentos)): ?>
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-check-circle-fill fs-3 text-success d-block mb-1"></i>
                    Tudo em dia! Nenhum compromisso pendente para os próximos dias.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-sm" style="font-size: 0.88rem;">
                        <thead class="table-light">
                            <tr class="label-caps">
                                <th>Vencimento</th>
                                <th>Descrição / Categoria</th>
                                <th>Conta / Cartão</th>
                                <th class="text-end">Valor</th>
                                <th class="text-center">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proximosVencimentos as $item): ?>
                                <?php
                                    $dias = (int) $item['dias_para_vencer'];
                                    $isReceita = ($item['tipo'] === 'receita');
                                ?>
                                <tr>
                                    <!-- Vencimento com Badge de Urgência -->
                                    <td class="tabular-nums">
                                        <?= FormatHelper::date($item['data_vencimento_efetiva'] ?? $item['data_vencimento']) ?>
                                        <?php if ($dias < 0): ?>
                                            <span class="badge text-bg-danger d-block mt-1" style="font-size: 0.7rem;">Venceu há <?= abs($dias) ?>d</span>
                                        <?php elseif ($dias === 0): ?>
                                            <span class="badge text-bg-warning text-dark d-block mt-1" style="font-size: 0.7rem;">Vence Hoje</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-light border text-muted d-block mt-1" style="font-size: 0.7rem;">Em <?= $dias ?>d</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Descrição / Categoria -->
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width: 180px;"><?= htmlspecialchars($item['descricao'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="small text-muted d-flex align-items-center gap-1">
                                            <i class="bi <?= htmlspecialchars($item['categoria_icone'] ?? 'bi-tag', ENT_QUOTES, 'UTF-8') ?>" style="color: <?= htmlspecialchars($item['categoria_cor'] ?? '#6F02FF', ENT_QUOTES, 'UTF-8') ?>;"></i>
                                            <span><?= htmlspecialchars($item['categoria_nome'] ?? 'Sem categoria', ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </td>

                                    <!-- Conta / Cartão -->
                                    <td class="small text-muted">
                                        <?php if (!empty($item['conta_nome'])): ?>
                                            <i class="bi bi-bank me-1"></i><?= htmlspecialchars($item['conta_nome'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php elseif (!empty($item['cartao_nome'])): ?>
                                            <i class="bi bi-credit-card me-1"></i><?= htmlspecialchars($item['cartao_nome'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>

                                    <!-- Valor -->
                                    <td class="text-end tabular-nums fw-bold <?= $isReceita ? 'text-success' : 'text-danger' ?>">
                                        <?= $isReceita ? '+' : '-' ?> <?= FormatHelper::currency((float) $item['valor']) ?>
                                    </td>

                                    <!-- Ação -->
                                    <td class="text-center">
                                        <?php if (!empty($item['cartao_credito_id'])): ?>
                                            <a href="<?= BASE_URL ?>/cartoes/ver/<?= $item['cartao_credito_id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2 text-nowrap" style="font-size: 0.75rem;" title="Ver fatura do cartão">
                                                <i class="bi bi-receipt"></i> Fatura
                                            </a>
                                        <?php else: ?>
                                            <form action="<?= BASE_URL ?>/transacoes/status/<?= $item['id'] ?>" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= CsrfHelper::getToken() ?>">
                                                <input type="hidden" name="redirect_to" value="<?= BASE_URL ?>/dashboard">
                                                <button type="submit" class="btn btn-sm btn-outline-success py-0 px-2" title="<?= $isReceita ? 'Marcar como Recebido' : 'Marcar como Pago' ?>" style="font-size: 0.75rem;">
                                                    <i class="bi bi-check-lg"></i> <?= $isReceita ? 'Receber' : 'Pagar' ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Widget 2: Cartões de Crédito e Faturas -->
    <div class="col-12 col-lg-5">
        <div class="symo-card h-100">
            <div class="symo-card-header d-flex justify-content-between align-items-center mb-3">
                <h3 class="symo-card-title fs-6 mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-credit-card-2-front-fill" style="color: var(--symo-primary-brand);"></i>
                    <span>Cartões de Crédito & Limites</span>
                </h3>
                <a href="<?= BASE_URL ?>/cartoes" class="btn btn-sm btn-outline-secondary py-0" style="font-size: 0.8rem;">
                    Gerenciar
                </a>
            </div>

            <?php if (empty($cartoes)): ?>
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-credit-card fs-3 d-block mb-1 text-secondary"></i>
                    Nenhum cartão cadastrado.
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($cartoes as $card): ?>
                        <div class="p-3 border rounded-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width: 12px; height: 12px; border-radius: 50%; background-color: <?= htmlspecialchars($card['cor'], ENT_QUOTES, 'UTF-8') ?>;"></div>
                                    <span class="fw-bold" style="color: var(--symo-on-surface);"><?= htmlspecialchars($card['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <span class="badge text-bg-light border small">
                                    Fecha dia <?= $card['dia_fechamento'] ?> / Vence dia <?= $card['dia_vencimento'] ?>
                                </span>
                            </div>

                            <!-- Barra de Limite Usado -->
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Usado: <strong class="tabular-nums text-dark"><?= FormatHelper::currency((float)$card['limite_usado']) ?></strong></span>
                                <span>Limite: <strong class="tabular-nums text-dark"><?= FormatHelper::currency((float)$card['limite_credito']) ?></strong></span>
                            </div>
                            <div class="progress mb-2" style="height: 6px;">
                                <div class="progress-bar <?= ($card['porcentagem_usada'] > 80) ? 'bg-danger' : 'bg-primary' ?>" 
                                     role="progressbar" 
                                     style="width: <?= $card['porcentagem_usada'] ?>%;" 
                                     aria-valuenow="<?= $card['porcentagem_usada'] ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center small">
                                <span class="text-success fw-semibold">Disponível: <?= FormatHelper::currency((float)$card['limite_disponivel']) ?></span>
                                <a href="<?= BASE_URL ?>/cartoes/detalhes/<?= $card['id'] ?>" class="text-decoration-none fw-semibold" style="color: var(--symo-primary-brand); font-size: 0.8rem;">
                                    Ver Fatura <i class="bi bi-chevron-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 4. SCRIPTS DE RENDERIZAÇÃO DO CHART.JS LOCAL -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // -------------------------------------------------------------------------
    // GRÁFICO 1: EVOLUÇÃO MENSAL (BARRAS AGRUPADAS)
    // -------------------------------------------------------------------------
    const ctxEvolucao = document.getElementById('chartEvolucaoMensal');
    if (ctxEvolucao) {
        const evolucaoDados = <?= json_encode($evolucaoMensal, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        
        const labels = evolucaoDados.map(item => item.label);
        const dataReceitas = evolucaoDados.map(item => item.total_receitas);
        const dataDespesas = evolucaoDados.map(item => item.total_despesas);

        new Chart(ctxEvolucao, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Receitas',
                        data: dataReceitas,
                        backgroundColor: '#10B981',
                        borderRadius: 6,
                        barPercentage: 0.6,
                        categoryPercentage: 0.7
                    },
                    {
                        label: 'Despesas',
                        data: dataDespesas,
                        backgroundColor: '#EF4444',
                        borderRadius: 6,
                        barPercentage: 0.6,
                        categoryPercentage: 0.7
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Inter',
                                size: 12
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#F3F4F6'
                        },
                        ticks: {
                            font: {
                                family: 'Inter',
                                size: 11
                            },
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------------------
    // GRÁFICO 2: DESPESAS POR CATEGORIA (DONUT)
    // -------------------------------------------------------------------------
    const ctxCategorias = document.getElementById('chartCategorias');
    if (ctxCategorias) {
        const categoriasDados = <?= json_encode($despesasCategoria, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        
        if (categoriasDados && categoriasDados.length > 0) {
            const catLabels = categoriasDados.map(item => item.nome);
            const catValues = categoriasDados.map(item => item.total_valor);
            const catColors = categoriasDados.map(item => item.cor || '#6F02FF');

            new Chart(ctxCategorias, {
                type: 'doughnut',
                data: {
                    labels: catLabels,
                    datasets: [{
                        data: catValues,
                        backgroundColor: catColors,
                        borderWidth: 2,
                        borderColor: '#FFFFFF'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed;
                                    const formatted = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
                                    return ` ${context.label}: ${formatted}`;
                                }
                            }
                        }
                    }
                }
            });
        }
    }
});
</script>
