<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View da Lixeira do Sistema (trash/index.php)
 */
$csrfToken = CsrfHelper::generateToken();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--symo-on-surface);">Lixeira do Sistema</h1>
        <p class="text-muted mb-0">Restauração e expurgo definitivo de itens excluídos logicamente (Soft Delete).</p>
    </div>
</div>

<!-- Abas de Navegação -->
<div class="symo-card shadow-sm border-0 p-0 overflow-hidden mb-4">
    <div class="border-bottom bg-light px-4 pt-3">
        <ul class="nav nav-tabs border-0" id="trashTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= ($activeTab === 'transacoes') ? 'active fw-bold' : '' ?>" href="<?= BASE_URL ?>/lixeira?aba=transacoes">
                    <i class="bi bi-arrow-left-right me-1"></i>Transações Excluídas
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill ms-1"><?= $counts['transacoes'] ?></span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= ($activeTab === 'contas') ? 'active fw-bold' : '' ?>" href="<?= BASE_URL ?>/lixeira?aba=contas">
                    <i class="bi bi-bank me-1"></i>Contas Bancárias
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill ms-1"><?= $counts['contas'] ?></span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= ($activeTab === 'categorias') ? 'active fw-bold' : '' ?>" href="<?= BASE_URL ?>/lixeira?aba=categorias">
                    <i class="bi bi-tags me-1"></i>Categorias
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill ms-1"><?= $counts['categorias'] ?></span>
                </a>
            </li>
        </ul>
    </div>

    <div class="p-0">
        <!-- 1. ABA DE TRANSAÇÕES -->
        <?php if ($activeTab === 'transacoes'): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 symo-table">
                    <thead>
                        <tr>
                            <th style="width: 100px;">Data Orig.</th>
                            <th>Descrição</th>
                            <th>Categoria / Origem</th>
                            <th>Tipo</th>
                            <th class="text-end">Valor (R$)</th>
                            <th>Excluído por / Em</th>
                            <th class="text-end" style="width: 170px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transacoes)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-trash3 fs-1 d-block mb-2 text-secondary"></i>
                                    A lixeira de transações está vazia.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transacoes as $t): ?>
                                <tr>
                                    <td class="tabular-nums text-muted" style="font-size: 0.85rem;">
                                        <?= FormatHelper::date($t['data_transacao']) ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold" style="color: var(--symo-on-surface);">
                                            <?= htmlspecialchars($t['descricao'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.85rem;">
                                            <span class="fw-semibold"><?= htmlspecialchars($t['categoria_nome'] ?? 'Sem categoria', ENT_QUOTES, 'UTF-8') ?></span>
                                            <small class="text-muted d-block"><?= htmlspecialchars($t['conta_nome'] ?? ($t['cartao_nome'] ? 'Cartão: ' . $t['cartao_nome'] : '—'), ENT_QUOTES, 'UTF-8') ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $t['tipo'] === 'receita' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> px-2 py-1 rounded-pill">
                                            <?= ucfirst($t['tipo']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end tabular-nums fw-bold <?= $t['tipo'] === 'receita' ? 'text-success' : 'text-danger' ?>">
                                        <?= ($t['tipo'] === 'receita' ? '+ ' : '- ') . FormatHelper::currency((float)$t['valor']) ?>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.8rem;">
                                            <span class="fw-semibold"><?= htmlspecialchars($t['excluido_por_nome'] ?? 'Sistema', ENT_QUOTES, 'UTF-8') ?></span>
                                            <small class="text-muted d-block"><?= FormatHelper::datetime($t['excluido_em']) ?></small>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <!-- Restaurar Transação -->
                                        <form action="<?= BASE_URL ?>/lixeira/restaurar" method="POST" class="d-inline" onsubmit="return confirm('Deseja restaurar esta transação? O saldo da conta será recalculado.');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="entidade" value="transacoes">
                                            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                            <button type="submit" class="btn btn-outline-success btn-sm" title="Restaurar Transação">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Restaurar
                                            </button>
                                        </form>

                                        <!-- Excluir Definitivo -->
                                        <form action="<?= BASE_URL ?>/lixeira/expurgar" method="POST" class="d-inline" onsubmit="return confirm('ATENÇÃO: A exclusão definitiva não pode ser desfeita. Continuar?');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="entidade" value="transacoes">
                                            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Expurgar Definitivamente">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <!-- 2. ABA DE CONTAS BANCÁRIAS -->
        <?php elseif ($activeTab === 'contas'): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 symo-table">
                    <thead>
                        <tr>
                            <th>Nome da Conta</th>
                            <th>Saldo na Exclusão</th>
                            <th>Excluído por / Em</th>
                            <th class="text-end" style="width: 170px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contas)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-bank fs-1 d-block mb-2 text-secondary"></i>
                                    Nenhuma conta bancária na lixeira.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($contas as $c): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div style="width: 12px; height: 12px; border-radius: 50%; background-color: <?= htmlspecialchars($c['cor'] ?? '#6F02FF', ENT_QUOTES, 'UTF-8') ?>;"></div>
                                            <span class="fw-bold" style="color: var(--symo-on-surface);"><?= htmlspecialchars($c['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </td>
                                    <td class="tabular-nums fw-bold">
                                        <?= FormatHelper::currency((float)$c['saldo_atual']) ?>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.8rem;">
                                            <span class="fw-semibold"><?= htmlspecialchars($c['excluido_por_nome'] ?? 'Sistema', ENT_QUOTES, 'UTF-8') ?></span>
                                            <small class="text-muted d-block"><?= FormatHelper::datetime($c['excluido_em']) ?></small>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <!-- Restaurar Conta -->
                                        <form action="<?= BASE_URL ?>/lixeira/restaurar" method="POST" class="d-inline" onsubmit="return confirm('Deseja restaurar esta conta bancária?');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="entidade" value="contas">
                                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                            <button type="submit" class="btn btn-outline-success btn-sm" title="Restaurar Conta">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Restaurar
                                            </button>
                                        </form>

                                        <!-- Excluir Definitivo -->
                                        <form action="<?= BASE_URL ?>/lixeira/expurgar" method="POST" class="d-inline" onsubmit="return confirm('ATENÇÃO: A exclusão definitiva não pode ser desfeita. Continuar?');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="entidade" value="contas">
                                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Expurgar Definitivamente">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <!-- 3. ABA DE CATEGORIAS -->
        <?php elseif ($activeTab === 'categorias'): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 symo-table">
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th>Tipo</th>
                            <th>Excluído por / Em</th>
                            <th class="text-end" style="width: 170px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categorias)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-tags fs-1 d-block mb-2 text-secondary"></i>
                                    Nenhuma categoria na lixeira.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categorias as $cat): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge p-2 rounded-circle" style="background-color: <?= htmlspecialchars($cat['cor'] ?? '#793CFE', ENT_QUOTES, 'UTF-8') ?>; color: white;">
                                                <i class="bi <?= htmlspecialchars($cat['icone'] ?? 'bi-tag', ENT_QUOTES, 'UTF-8') ?>"></i>
                                            </span>
                                            <span class="fw-bold" style="color: var(--symo-on-surface);"><?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $cat['tipo'] === 'receita' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> px-2 py-1 rounded-pill">
                                            <?= ucfirst($cat['tipo']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.8rem;">
                                            <span class="fw-semibold"><?= htmlspecialchars($cat['excluido_por_nome'] ?? 'Sistema', ENT_QUOTES, 'UTF-8') ?></span>
                                            <small class="text-muted d-block"><?= FormatHelper::datetime($cat['excluido_em']) ?></small>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <!-- Restaurar Categoria -->
                                        <form action="<?= BASE_URL ?>/lixeira/restaurar" method="POST" class="d-inline" onsubmit="return confirm('Deseja restaurar esta categoria?');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="entidade" value="categorias">
                                            <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                            <button type="submit" class="btn btn-outline-success btn-sm" title="Restaurar Categoria">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Restaurar
                                            </button>
                                        </form>

                                        <!-- Excluir Definitivo -->
                                        <form action="<?= BASE_URL ?>/lixeira/expurgar" method="POST" class="d-inline" onsubmit="return confirm('ATENÇÃO: A exclusão definitiva não pode ser desfeita. Continuar?');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="entidade" value="categorias">
                                            <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Expurgar Definitivamente">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

