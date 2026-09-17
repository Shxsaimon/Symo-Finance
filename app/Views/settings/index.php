<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Configurações Globais (settings/index.php)
 */
$csrfToken = CsrfHelper::generateToken();
$logoFilename = $settings['logotipo_aplicacao'] ?? null;
$logoUrl = !empty($logoFilename) ? BASE_URL . '/media/logo' : ASSET_URL . '/img/logo.svg';
$githubRun = $githubExecution['run'] ?? null;
$githubJobs = $githubExecution['jobs'] ?? [];
$githubStatus = $githubRun['status'] ?? 'never';
$githubConclusion = $githubRun['conclusion'] ?? null;
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--symo-on-surface);">Configurações do Sistema</h1>
        <p class="text-muted mb-0">Parâmetros globais, formatação monetária, identidade visual e integridade do banco de dados.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Coluna Esquerda: Preferências Gerais e Identidade Visual -->
    <div class="col-lg-7">
        <!-- 1. Preferências do Sistema -->
        <div class="symo-card shadow-sm border-0 p-4 mb-4">
            <h5 class="fw-bold mb-3" style="color: var(--symo-primary-brand);">
                <i class="bi bi-sliders me-2"></i>Preferências Globais
            </h5>

            <form action="<?= BASE_URL ?>/configuracoes/salvar" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="mb-3">
                    <label for="nome_aplicacao" class="form-label fw-semibold">Nome da Aplicação / Empresa</label>
                    <input type="text" class="form-control" id="nome_aplicacao" name="nome_aplicacao" value="<?= htmlspecialchars((string)($settings['nome_aplicacao'] ?? 'Symo'), ENT_QUOTES, 'UTF-8') ?>" required>
                    <small class="text-muted">Exibido na barra de navegação e títulos das páginas.</small>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="simbolo_moeda" class="form-label fw-semibold">Símbolo da Moeda</label>
                        <input type="text" class="form-control" id="simbolo_moeda" name="simbolo_moeda" value="<?= htmlspecialchars((string)($settings['simbolo_moeda'] ?? 'R$'), ENT_QUOTES, 'UTF-8') ?>" required maxlength="5">
                    </div>
                    <div class="col-md-4">
                        <label for="separador_decimal" class="form-label fw-semibold">Separador Decimal</label>
                        <select class="form-select" id="separador_decimal" name="separador_decimal">
                            <option value="," <?= (($settings['separador_decimal'] ?? ',') === ',') ? 'selected' : '' ?>>Vírgula (,)</option>
                            <option value="." <?= (($settings['separador_decimal'] ?? ',') === '.') ? 'selected' : '' ?>>Ponto (.)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="separador_milhar" class="form-label fw-semibold">Separador de Milhar</label>
                        <select class="form-select" id="separador_milhar" name="separador_milhar">
                            <option value="." <?= (($settings['separador_milhar'] ?? '.') === '.') ? 'selected' : '' ?>>Ponto (.)</option>
                            <option value="," <?= (($settings['separador_milhar'] ?? '.') === ',') ? 'selected' : '' ?>>Vírgula (,)</option>
                            <option value=" " <?= (($settings['separador_milhar'] ?? '.') === ' ') ? 'selected' : '' ?>>Espaço ( )</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="registros_por_pagina" class="form-label fw-semibold">Registros por Página (Paginação)</label>
                    <select class="form-select" id="registros_por_pagina" name="registros_por_pagina">
                        <option value="15" <?= (((int)($settings['registros_por_pagina'] ?? 25)) === 15) ? 'selected' : '' ?>>15 itens</option>
                        <option value="25" <?= (((int)($settings['registros_por_pagina'] ?? 25)) === 25) ? 'selected' : '' ?>>25 itens</option>
                        <option value="50" <?= (((int)($settings['registros_por_pagina'] ?? 25)) === 50) ? 'selected' : '' ?>>50 itens</option>
                        <option value="100" <?= (((int)($settings['registros_por_pagina'] ?? 25)) === 100) ? 'selected' : '' ?>>100 itens</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-symo-primary">
                        <i class="bi bi-check2-circle me-1"></i>Salvar Preferências
                    </button>
                </div>
            </form>
        </div>

        <!-- 2. Identidade Visual e Logotipo -->
        <div class="symo-card shadow-sm border-0 p-4">
            <h5 class="fw-bold mb-3" style="color: var(--symo-primary-brand);">
                <i class="bi bi-image me-2"></i>Identidade Visual & Logotipo
            </h5>

            <div class="row align-items-center g-4">
                <div class="col-sm-5 text-center">
                    <div class="p-3 bg-light rounded border d-flex align-items-center justify-content-center" style="min-height: 120px;">
                        <img src="<?= $logoUrl ?>" alt="Logotipo Symo" class="img-fluid" style="max-height: 70px; object-fit: contain;">
                    </div>
                    <small class="text-muted d-block mt-2">Logotipo em uso</small>
                </div>
                <div class="col-sm-7">
                    <form action="<?= BASE_URL ?>/configuracoes/upload-logo" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <div class="mb-3">
                            <label for="logotipo" class="form-label fw-semibold small">Enviar Novo Logotipo</label>
                            <input type="file" class="form-control form-control-sm" id="logotipo" name="logotipo" required accept="image/svg+xml, image/png, image/jpeg, image/webp">
                            <small class="text-muted">Formatos aceitos: SVG, PNG, JPEG ou WebP até 2 MB.</small>
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-upload me-1"></i>Atualizar Logotipo
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Coluna Direita: Status do Banco de Dados e Migrações -->
    <div class="col-lg-5">
        <div class="symo-card shadow-sm border-0 p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                <h5 class="fw-bold mb-0" style="color: var(--symo-primary-brand);">
                    <i class="bi bi-database-check me-2"></i>Status do Banco & Migrações
                </h5>
                <form action="<?= BASE_URL ?>/configuracoes/executar-migracoes" method="POST" class="d-flex align-items-center gap-2" onsubmit="return confirmarAcaoBanco(this);">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <label for="database_action" class="visually-hidden">Operação do banco</label>
                    <select id="database_action" name="database_action" class="form-select form-select-sm" required>
                        <option value="status">Verificar status</option>
                        <option value="migrate">Executar migrations</option>
                        <option value="seed">Executar seed</option>
                    </select>
                    <button type="submit" class="btn btn-outline-success btn-sm text-nowrap">
                        <i class="bi bi-play-circle me-1"></i>Enviar
                    </button>
                </form>
            </div>

            <p class="text-muted small mb-3">Escolha uma operação. O GitHub Actions executará a tarefa com o segredo do banco mantido fora da aplicação.</p>

            <div id="github-action-status" class="border rounded p-3 mb-3 small" data-status-url="<?= htmlspecialchars(BASE_URL . '/configuracoes/github-actions/status', ENT_QUOTES, 'UTF-8') ?>">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>Última execução no GitHub</strong>
                    <span id="github-run-badge" class="badge <?= $githubStatus === 'completed' && $githubConclusion === 'success' ? 'bg-success' : ($githubStatus === 'never' ? 'bg-secondary' : 'bg-warning text-dark') ?>">
                        <?= htmlspecialchars($githubStatus === 'never' ? 'Ainda não executado' : (string) $githubStatus, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
                <div id="github-run-details" class="text-muted">
                    <?php if ($githubRun): ?>
                        Operação: <span class="fw-semibold"><?= htmlspecialchars((string) ($githubRun['display_title'] ?? 'Workflow manual'), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($githubRun['html_url'])): ?>
                            <a href="<?= htmlspecialchars((string) $githubRun['html_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Abrir no GitHub</a>
                        <?php endif; ?>
                    <?php else: ?>
                        Nenhuma execução encontrada.
                    <?php endif; ?>
                </div>
                <div id="github-job-list" class="mt-2">
                    <?php foreach ($githubJobs as $job): ?>
                        <div><?= htmlspecialchars((string) ($job['name'] ?? 'Job'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($job['status'] ?? 'desconhecido'), ENT_QUOTES, 'UTF-8') ?><?= isset($job['conclusion']) && $job['conclusion'] ? ' (' . htmlspecialchars((string) $job['conclusion'], ENT_QUOTES, 'UTF-8') . ')' : '' ?><?php if (!empty($job['id'])): ?> <button type="button" class="btn btn-link btn-sm p-0 align-baseline js-github-log" data-log-url="<?= htmlspecialchars(BASE_URL . '/configuracoes/github-actions/logs/' . (int) $job['id'], ENT_QUOTES, 'UTF-8') ?>">Ver log</button><?php endif; ?></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead>
                        <tr class="table-light">
                            <th>Arquivo / Versão</th>
                            <th>Status</th>
                            <th>Lote</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($migrationsStatus)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-3 text-muted">Nenhuma migração encontrada.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($migrationsStatus as $m): ?>
                                <tr>
                                    <td>
                                        <code class="text-dark"><?= htmlspecialchars($m['arquivo'], ENT_QUOTES, 'UTF-8') ?></code>
                                        <?php if ($m['executada'] && !empty($m['executada_em'])): ?>
                                            <small class="text-muted d-block" style="font-size: 0.725rem;"><?= FormatHelper::datetime($m['executada_em']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($m['executada']): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill" style="font-size: 0.7rem;">
                                                <i class="bi bi-check-lg me-1"></i>Executada
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill" style="font-size: 0.7rem;">
                                                <i class="bi bi-clock me-1"></i>Pendente
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="tabular-nums text-muted">
                                        <?= $m['lote'] !== null ? '#' . $m['lote'] : '—' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="githubLogModal" tabindex="-1" aria-labelledby="githubLogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="githubLogModalLabel">Log do GitHub Actions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div id="github-log-feedback" class="text-muted small mb-2">Carregando log...</div>
                <pre id="github-log-content" class="bg-dark text-light rounded p-3 mb-0" style="max-height: 65vh; overflow: auto; white-space: pre-wrap; tab-size: 4;"></pre>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarAcaoBanco(formulario) {
    const acao = formulario.querySelector('[name="database_action"]').value;
    const mensagens = {
        status: 'Deseja consultar o status das migrations no GitHub Actions?',
        migrate: 'Deseja executar as migrations pendentes no banco de produção?',
        seed: 'Deseja executar o seed no banco de produção?'
    };
    return window.confirm(mensagens[acao] || 'Deseja enviar esta operação ao GitHub Actions?');
}

(function acompanharGitHubAction() {
    const painel = document.getElementById('github-action-status');
    if (!painel) return;

    const textoSeguro = (valor) => document.createTextNode(String(valor ?? ''));

    const atualizar = () => fetch(painel.dataset.statusUrl, { credentials: 'same-origin' })
        .then((resposta) => resposta.ok ? resposta.json() : null)
        .then((dados) => {
            if (!dados || !dados.run) return;
            const run = dados.run;
            const badge = document.getElementById('github-run-badge');
            const detalhes = document.getElementById('github-run-details');
            const jobs = document.getElementById('github-job-list');
            badge.textContent = run.status === 'completed' ? (run.conclusion || 'completed') : run.status;
            badge.className = 'badge ' + (run.status === 'completed' && run.conclusion === 'success' ? 'bg-success' : run.status === 'completed' ? 'bg-danger' : 'bg-warning text-dark');
            detalhes.replaceChildren(textoSeguro('Operação: '));
            const titulo = document.createElement('span');
            titulo.className = 'fw-semibold';
            titulo.appendChild(textoSeguro(run.display_title || 'Workflow manual'));
            detalhes.appendChild(titulo);
            if (run.html_url) {
                const link = document.createElement('a');
                link.href = run.html_url;
                link.target = '_blank';
                link.rel = 'noopener';
                link.appendChild(textoSeguro(' Abrir no GitHub'));
                detalhes.appendChild(link);
            }
            jobs.replaceChildren(...(dados.jobs || []).map((job) => {
                const linha = document.createElement('div');
                linha.appendChild(textoSeguro(job.name + ': ' + job.status + (job.conclusion ? ' (' + job.conclusion + ')' : '')));
                if (job.id) {
                    const botao = document.createElement('button');
                    botao.type = 'button';
                    botao.className = 'btn btn-link btn-sm p-0 align-baseline js-github-log';
                    botao.dataset.logUrl = painel.dataset.statusUrl.replace('/status', '/logs/' + Number(job.id));
                    botao.appendChild(textoSeguro('Ver log'));
                    linha.appendChild(botao);
                    botao.addEventListener('click', () => window.carregarLogGitHub(botao));
                }
                return linha;
            }));
        })
        .catch(() => undefined);

    atualizar();
    window.setInterval(atualizar, 10000);
})();

(function configurarLogsGitHub() {
    const modalElement = document.getElementById('githubLogModal');
    const feedback = document.getElementById('github-log-feedback');
    const content = document.getElementById('github-log-content');
    if (!modalElement || !feedback || !content || typeof bootstrap === 'undefined') return;

    const modal = new bootstrap.Modal(modalElement);
    window.carregarLogGitHub = (botao) => {
        feedback.textContent = 'Carregando log...';
        content.textContent = '';
        modal.show();

        fetch(botao.dataset.logUrl, { credentials: 'same-origin' })
            .then(async (resposta) => {
                const dados = await resposta.json();
                if (!resposta.ok || !dados.success) {
                    throw new Error(dados.message || 'Não foi possível carregar o log.');
                }
                return dados;
            })
            .then((dados) => {
                feedback.textContent = 'Log carregado com sucesso.';
                content.textContent = dados.log || '(O log está vazio.)';
            })
            .catch((erro) => {
                feedback.textContent = erro.message;
                content.textContent = '';
            });
    };

    document.querySelectorAll('.js-github-log').forEach((botao) => {
        botao.addEventListener('click', () => window.carregarLogGitHub(botao));
    });
})();
</script>

