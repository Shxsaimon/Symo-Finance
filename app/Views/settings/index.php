<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de Configurações Globais (settings/index.php)
 */
$csrfToken = CsrfHelper::generateToken();
$logoFilename = $settings['logotipo_aplicacao'] ?? null;
$logoUrl = !empty($logoFilename) ? BASE_URL . '/storage/uploads/logos/' . htmlspecialchars((string)$logoFilename, ENT_QUOTES, 'UTF-8') : BASE_URL . '/public/img/logo.svg';
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
                            <input type="file" class="form-control form-control-sm" id="logotipo" name="logotipo" required accept="image/png, image/jpeg, image/webp">
                            <small class="text-muted">Recomendado: SVG, PNG transparente ou WebP até 2 MB.</small>
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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0" style="color: var(--symo-primary-brand);">
                    <i class="bi bi-database-check me-2"></i>Status do Banco & Migrações
                </h5>
                <form action="<?= BASE_URL ?>/configuracoes/executar-migracoes" method="POST" class="d-inline" onsubmit="return confirm('Deseja executar as migrações pendentes no banco de dados?');">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <button type="submit" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-play-circle me-1"></i>Executar Migrações
                    </button>
                </form>
            </div>

            <p class="text-muted small mb-3">Abaixo estão listadas todas as migrações estruturais do projeto e seu estado de execução no schema.</p>

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

