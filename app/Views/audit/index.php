<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Trilha de Auditoria (audit/index.php)
 */
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--symo-on-surface);">Trilha de Auditoria</h1>
        <p class="text-muted mb-0">Rastreabilidade completa de alterações, criação e exclusões no banco de dados.</p>
    </div>
</div>

<!-- KPIs Rápidos de Auditoria -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="symo-card shadow-sm border-0 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="badge-icon-wrapper" style="background: rgba(111, 2, 255, 0.1); color: var(--symo-primary-brand);">
                    <i class="bi bi-shield-check fs-4"></i>
                </div>
                <div>
                    <span class="text-muted d-block small">Total de Eventos</span>
                    <span class="fs-4 fw-bold tabular-nums" style="color: var(--symo-on-surface);"><?= number_format($metricas['total_eventos'], 0, ',', '.') ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="symo-card shadow-sm border-0 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="badge-icon-wrapper" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">
                    <i class="bi bi-plus-circle fs-4"></i>
                </div>
                <div>
                    <span class="text-muted d-block small">Criações (INSERT)</span>
                    <span class="fs-4 fw-bold tabular-nums text-success"><?= number_format($metricas['total_insert'], 0, ',', '.') ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="symo-card shadow-sm border-0 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="badge-icon-wrapper" style="background: rgba(59, 130, 246, 0.1); color: #3B82F6;">
                    <i class="bi bi-pencil-square fs-4"></i>
                </div>
                <div>
                    <span class="text-muted d-block small">Atualizações (UPDATE)</span>
                    <span class="fs-4 fw-bold tabular-nums text-primary"><?= number_format($metricas['total_update'], 0, ',', '.') ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="symo-card shadow-sm border-0 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="badge-icon-wrapper" style="background: rgba(239, 68, 68, 0.1); color: #EF4444;">
                    <i class="bi bi-trash fs-4"></i>
                </div>
                <div>
                    <span class="text-muted d-block small">Exclusões (DELETE/PURGE)</span>
                    <span class="fs-4 fw-bold tabular-nums text-danger"><?= number_format($metricas['total_delete'] + $metricas['total_purge'], 0, ',', '.') ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Painel de Filtros -->
<div class="symo-card shadow-sm border-0 p-4 mb-4">
    <form action="<?= BASE_URL ?>/auditoria" method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label for="filtro_tabela" class="form-label fw-semibold small text-muted">Tabela Auditada</label>
            <select class="form-select form-select-sm" id="filtro_tabela" name="tabela">
                <option value="">Todas as Tabelas</option>
                <?php foreach ($tabelas as $tab): ?>
                    <option value="<?= htmlspecialchars($tab, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['tabela'] === $tab) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tab, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label for="filtro_acao" class="form-label fw-semibold small text-muted">Ação</label>
            <select class="form-select form-select-sm" id="filtro_acao" name="acao">
                <option value="">Todas as Ações</option>
                <option value="INSERT" <?= ($filters['acao'] === 'INSERT') ? 'selected' : '' ?>>INSERT (Criação)</option>
                <option value="UPDATE" <?= ($filters['acao'] === 'UPDATE') ? 'selected' : '' ?>>UPDATE (Edição)</option>
                <option value="SOFT_DELETE" <?= ($filters['acao'] === 'SOFT_DELETE') ? 'selected' : '' ?>>SOFT_DELETE (Lixeira)</option>
                <option value="RESTORE" <?= ($filters['acao'] === 'RESTORE') ? 'selected' : '' ?>>RESTORE (Restauração)</option>
                <option value="PURGE" <?= ($filters['acao'] === 'PURGE') ? 'selected' : '' ?>>PURGE (Expurgo Físico)</option>
            </select>
        </div>

        <div class="col-md-2">
            <label for="filtro_usuario" class="form-label fw-semibold small text-muted">Usuário Autor</label>
            <select class="form-select form-select-sm" id="filtro_usuario" name="usuario_id">
                <option value="">Todos os Usuários</option>
                <?php foreach ($usuarios as $u): ?>
                    <option value="<?= (int)$u['id'] ?>" <?= ($filters['usuario_id'] === (int)$u['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['nome'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label for="filtro_busca" class="form-label fw-semibold small text-muted">Busca nos Dados (JSON/IP)</label>
            <input type="text" class="form-control form-control-sm" id="filtro_busca" name="busca" value="<?= htmlspecialchars($filters['busca'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar termo ou IP...">
        </div>

        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-symo-primary btn-sm w-100">
                <i class="bi bi-funnel-fill me-1"></i>Filtrar
            </button>
            <a href="<?= BASE_URL ?>/auditoria" class="btn btn-outline-secondary btn-sm" title="Limpar Filtros">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        </div>
    </form>
</div>

<!-- Tabela de Logs de Auditoria -->
<div class="symo-card shadow-sm border-0 p-0 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 symo-table">
            <thead>
                <tr>
                    <th style="width: 140px;">Data e Hora</th>
                    <th>Usuário Autor</th>
                    <th>IP</th>
                    <th>Ação</th>
                    <th>Tabela</th>
                    <th>ID Registro</th>
                    <th class="text-end" style="width: 130px;">Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-shield-slash fs-1 d-block mb-2 text-secondary"></i>
                            Nenhum registro de auditoria localizado para os filtros informados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="tabular-nums" style="font-size: 0.85rem; font-weight: 500;">
                                <?= FormatHelper::datetime($log['criado_em']) ?>
                            </td>
                            <td>
                                <div class="fw-semibold" style="color: var(--symo-on-surface); font-size: 0.875rem;">
                                    <?= htmlspecialchars($log['usuario_nome'] ?? 'Sistema / Anônimo', ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <?php if (!empty($log['usuario_perfil'])): ?>
                                    <small class="text-muted text-uppercase" style="font-size: 0.7rem;"><?= htmlspecialchars($log['usuario_perfil'], ENT_QUOTES, 'UTF-8') ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="tabular-nums text-muted" style="font-size: 0.825rem;">
                                <?= htmlspecialchars($log['endereco_ip'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>
                                <?php
                                $badgeClass = match ($log['acao']) {
                                    'INSERT'      => 'bg-success-subtle text-success border-success-subtle',
                                    'UPDATE'      => 'bg-primary-subtle text-primary border-primary-subtle',
                                    'SOFT_DELETE' => 'bg-warning-subtle text-warning border-warning-subtle',
                                    'RESTORE'     => 'bg-info-subtle text-info border-info-subtle',
                                    'PURGE'       => 'bg-danger-subtle text-danger border-danger-subtle',
                                    default       => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                                };
                                ?>
                                <span class="badge border px-2 py-1 rounded-pill <?= $badgeClass ?>" style="font-size: 0.75rem;">
                                    <?= htmlspecialchars($log['acao'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <code class="px-2 py-1 bg-light rounded text-dark" style="font-size: 0.825rem;">
                                    <?= htmlspecialchars($log['tabela'], ENT_QUOTES, 'UTF-8') ?>
                                </code>
                            </td>
                            <td class="tabular-nums fw-bold text-muted">
                                #<?= (int)$log['registro_id'] ?>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1"
                                        onclick="verDetalhesAuditoria(<?= (int)$log['id'] ?>)">
                                    <i class="bi bi-code-square"></i>
                                    <span>JSON Diff</span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <?php if ($totalPaginas > 1): ?>
        <div class="p-3 border-top d-flex justify-content-between align-items-center bg-light">
            <small class="text-muted">Mostrando página <?= $paginaAtual ?> de <?= $totalPaginas ?> (Total de <?= $totalRegistros ?> eventos)</small>
            <nav aria-label="Navegação de páginas">
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($paginaAtual > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $paginaAtual - 1])) ?>">Anterior</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $paginaAtual - 2); $i <= min($totalPaginas, $paginaAtual + 2); $i++): ?>
                        <li class="page-item <?= ($i === $paginaAtual) ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($paginaAtual < $totalPaginas): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $paginaAtual + 1])) ?>">Próxima</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL: VISUALIZADOR DE JSON DIFF -->
<div class="modal fade" id="modalJsonDiff" tabindex="-1" aria-labelledby="modalJsonDiffLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content symo-modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="modalJsonDiffLabel" style="color: var(--symo-primary-brand);">
                    <i class="bi bi-file-earmark-diff me-2"></i>Comparativo de Dados (JSON Diff)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3 p-3 bg-light rounded border">
                    <div class="col-sm-3">
                        <small class="text-muted d-block">ID do Log</small>
                        <span class="fw-bold" id="diff_log_id">-</span>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Tabela & Registro</small>
                        <span class="fw-bold" id="diff_tabela_registro">-</span>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Ação Executada</small>
                        <span class="fw-bold" id="diff_acao">-</span>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Autor & IP</small>
                        <span class="fw-bold" id="diff_autor_ip">-</span>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-danger">
                                <i class="bi bi-dash-circle me-1"></i>Estado Anterior (Valores Antigos)
                            </span>
                        </div>
                        <pre class="p-3 bg-dark text-light rounded border" id="diff_valores_antigos" style="min-height: 220px; max-height: 380px; overflow-y: auto; font-family: monospace; font-size: 0.85rem; white-space: pre-wrap;"></pre>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-success">
                                <i class="bi bi-plus-circle me-1"></i>Estado Posterior (Novos Valores)
                            </span>
                        </div>
                        <pre class="p-3 bg-dark text-light rounded border" id="diff_valores_novos" style="min-height: 220px; max-height: 380px; overflow-y: auto; font-family: monospace; font-size: 0.85rem; white-space: pre-wrap;"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
function verDetalhesAuditoria(logId) {
    fetch('<?= BASE_URL ?>/auditoria/detalhes/' + logId)
        .then(response => response.json())
        .then(res => {
            if (!res.success) {
                alert(res.message || 'Erro ao carregar detalhes.');
                return;
            }

            const data = res.data;
            document.getElementById('diff_log_id').textContent = '#' + data.id + ' (' + data.criado_em + ')';
            document.getElementById('diff_tabela_registro').textContent = data.tabela + ' [ID: ' + data.registro_id + ']';
            document.getElementById('diff_acao').textContent = data.acao;
            document.getElementById('diff_autor_ip').textContent = (data.usuario_nome || 'Sistema') + ' (' + data.endereco_ip + ')';

            const formatJson = (val) => {
                if (!val) return '/* Nenhum dado gravado */';
                try {
                    return JSON.stringify(val, null, 2);
                } catch (e) {
                    return String(val);
                }
            };

            document.getElementById('diff_valores_antigos').textContent = formatJson(data.valores_antigos_parsed);
            document.getElementById('diff_valores_novos').textContent = formatJson(data.valores_novos_parsed);

            const modal = new bootstrap.Modal(document.getElementById('modalJsonDiff'));
            modal.show();
        })
        .catch(err => {
            alert('Falha na comunicação com o servidor: ' + err.message);
        });
}
</script>

