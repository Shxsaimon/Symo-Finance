<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — View de detalhe do Investimento (posição, compartilhamento e histórico de movimentos)
 */
$mesesNomes = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$invId = (int) $investimento['id'];
$ativo = $investimento['status'] === 'ativo';
$csrf = $e(CsrfHelper::getToken());
$currentUserId = (int) AuthHelper::id();
$ehProprietario = ((int) ($investimento['eh_proprietario'] ?? 1) === 1);
$permissaoAcesso = (string) ($investimento['permissao_acesso'] ?? 'proprietario');
$acessoContaVinculada = (bool) ($investimento['acesso_conta_vinculada'] ?? true);
$podeMovimentar = (bool) ($investimento['pode_movimentar'] ?? $ehProprietario);
$participantes = $investimento['participantes'] ?? [];
$compartilhamentoId = (int) ($investimento['compartilhamento_id'] ?? 0);
$exibirNoMeuDash = (int) ($investimento['exibir_no_meu_dashboard'] ?? 1) === 1;
$exibirDashboardOwner = in_array($investimento['exibir_dashboard'] ?? true, [true, 1, '1', 't', 'true'], true);

$motivoBloqueioMovimento = '';
if (!$ehProprietario && !$podeMovimentar) {
    if ($permissaoAcesso === 'visualizar') {
        $motivoBloqueioMovimento = 'Sua permissão neste investimento é apenas de visualização.';
    } elseif (!$acessoContaVinculada) {
        $motivoBloqueioMovimento = 'Para realizar ajustes ou movimentos neste investimento, você também precisa ter acesso com permissão de lançamento à conta bancária vinculada (' . ($investimento['conta_nome'] ?? '') . ').';
    }
}

$mesAnterior = (new DateTimeImmutable(sprintf('%04d-%02d-01', $ano, $mes)))->modify('-1 month');
$mesSeguinte = (new DateTimeImmutable(sprintf('%04d-%02d-01', $ano, $mes)))->modify('+1 month');
$contaVinculadaNaLista = false;
foreach ($contas as $c) {
    if ((int) $c['id'] === (int) $investimento['conta_id']) {
        $contaVinculadaNaLista = true;
    }
}
$tiposBadge = ['aporte' => 'text-bg-primary', 'resgate' => 'text-bg-warning', 'rendimento' => 'text-bg-success', 'ajuste' => 'text-bg-secondary'];
?>
<nav aria-label="breadcrumb" class="mb-2">
    <a href="<?= BASE_URL ?>/investimentos" class="symo-back-link small"><i class="bi bi-arrow-left"></i> Investimentos</a>
</nav>

<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 fw-bold mb-1 d-flex align-items-center flex-wrap gap-2" style="color: var(--symo-on-surface);">
            <span><?= $e($investimento['nome']) ?></span>
            <span class="badge fs-6 align-middle <?= $ativo ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $ativo ? 'Ativo' : 'Encerrado' ?></span>
            <?php if (!$ehProprietario): ?>
                <span class="badge rounded-pill" style="background-color: var(--symo-primary-fixed); color: var(--symo-on-primary-fixed); font-size: 0.75rem;">
                    <i class="bi bi-people-fill me-1"></i>Compartilhado por <?= $e($investimento['proprietario_nome'] ?? 'Titular') ?>
                </span>
            <?php endif; ?>
            <?php if (!$exibirNoMeuDash): ?>
                <span class="badge bg-light text-muted border fw-normal" style="font-size: 0.72rem;">
                    <i class="bi bi-eye-slash me-1"></i>Fora do Dashboard
                </span>
            <?php endif; ?>
        </h1>
        <p class="text-muted mb-0 small">
            <?= $e(Investment::CLASSES[$investimento['classe']] ?? 'Não informado') ?>
            <?php if (!empty($investimento['custodiante'])): ?> · <?= $e($investimento['custodiante']) ?><?php endif; ?>
            <?php if (!empty($investimento['finalidade'])): ?> · <?= $e($investimento['finalidade']) ?><?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($ehProprietario): ?>
            <button type="button" class="btn-symo-primary" data-bs-toggle="modal" data-bs-target="#modalEditarInvestimento">
                <i class="bi bi-pencil"></i> Editar
            </button>
        <?php endif; ?>

        <?php if ($ativo): ?>
            <?php if ($podeMovimentar): ?>
                <button type="button" class="btn-symo-primary" data-bs-toggle="modal" data-bs-target="#modalMovimento" data-tipo="aporte"><i class="bi bi-box-arrow-in-down"></i> Aporte</button>
                <button type="button" class="btn-symo-primary" data-bs-toggle="modal" data-bs-target="#modalMovimento" data-tipo="resgate"><i class="bi bi-box-arrow-up"></i> Resgate</button>
                <button type="button" class="btn-symo-primary" data-bs-toggle="modal" data-bs-target="#modalMovimento" data-tipo="rendimento"><i class="bi bi-graph-up-arrow"></i> Rendimento</button>
                <button type="button" class="btn-symo-primary" data-bs-toggle="modal" data-bs-target="#modalMovimento" data-tipo="ajuste"><i class="bi bi-arrow-repeat"></i> Atualizar valor</button>
            <?php else: ?>
                <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?= $e($motivoBloqueioMovimento) ?>">
                    <button type="button" class="btn-symo-primary opacity-50" disabled style="pointer-events: none;"><i class="bi bi-box-arrow-in-down"></i> Aporte</button>
                </span>
                <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?= $e($motivoBloqueioMovimento) ?>">
                    <button type="button" class="btn-symo-primary opacity-50" disabled style="pointer-events: none;"><i class="bi bi-box-arrow-up"></i> Resgate</button>
                </span>
                <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?= $e($motivoBloqueioMovimento) ?>">
                    <button type="button" class="btn-symo-primary opacity-50" disabled style="pointer-events: none;"><i class="bi bi-graph-up-arrow"></i> Rendimento</button>
                </span>
                <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?= $e($motivoBloqueioMovimento) ?>">
                    <button type="button" class="btn-symo-primary opacity-50" disabled style="pointer-events: none;"><i class="bi bi-arrow-repeat"></i> Atualizar valor</button>
                </span>
            <?php endif; ?>

            <?php if ($ehProprietario && (float) $investimento['saldo_atual'] <= 0): ?>
                <form action="<?= BASE_URL ?>/investimentos/desativar/<?= $invId ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <button type="submit" class="btn-symo-primary"><i class="bi bi-slash-circle"></i> Desativar</button>
                </form>
            <?php endif; ?>
        <?php elseif ($ehProprietario): ?>
            <form action="<?= BASE_URL ?>/investimentos/reativar/<?= $invId ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <button type="submit" class="btn-symo-primary"><i class="bi bi-arrow-counterclockwise"></i> Reativar</button>
            </form>
        <?php endif; ?>

        <?php if ($ehProprietario): ?>
            <?php if ((float) $investimento['saldo_atual'] <= 0): ?>
                <form action="<?= BASE_URL ?>/investimentos/excluir/<?= $invId ?>" method="POST" onsubmit="return confirm('Deseja excluir este investimento? Ele será enviado para a lixeira e você poderá restaurá-lo a qualquer momento.');">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <button type="submit" class="btn-symo-primary"><i class="bi bi-trash"></i> Excluir</button>
                </form>
            <?php else: ?>
                <button type="button" class="btn-symo-primary opacity-50" onclick="alert('Para excluir o investimento, resgate ou ajuste seu saldo para R$ 0,00 primeiro.');" title="Zere o saldo para permitir a exclusão">
                    <i class="bi bi-trash"></i> Excluir
                </button>
            <?php endif; ?>
        <?php endif; ?>

        <button type="button" class="btn-symo-primary" data-bs-toggle="modal" data-bs-target="#modalCompartilharInvestimento">
            <i class="bi bi-people"></i> Compartilhar
        </button>
    </div>
</div>

<?php if ($motivoBloqueioMovimento !== ''): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
        <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
        <div class="small">
            <strong>Movimentações desabilitadas:</strong> <?= $e($motivoBloqueioMovimento) ?>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="symo-card h-100">
            <span class="label-caps" style="color: var(--symo-primary-brand);">Valor atual</span>
            <div class="fs-2 fw-bold tabular-nums"><?= FormatHelper::currency((float) $investimento['saldo_atual']) ?></div>
            <div class="text-muted small">Valor inicial de <?= FormatHelper::currency((float) $investimento['saldo_inicial']) ?> em <?= FormatHelper::date($investimento['data_saldo_inicial']) ?>.</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="symo-card h-100">
            <span class="label-caps text-secondary">Conta vinculada</span>
            <div class="fs-5 fw-bold"><?= $e($investimento['conta_nome']) ?></div>
            <?php if ($investimento['conta_status'] !== 'ativa'): ?>
                <div class="small text-warning"><i class="bi bi-exclamation-triangle"></i> Conta inativa. Troque a conta para novos aportes e resgates.</div>
            <?php elseif (!$acessoContaVinculada): ?>
                <div class="small text-muted"><i class="bi bi-lock"></i> Conta não compartilhada com permissão de lançamento para você.</div>
            <?php else: ?>
                <div class="text-muted small">Usada nos próximos aportes e resgates.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="symo-card h-100">
            <div class="d-flex justify-content-between align-items-center">
                <span class="label-caps text-secondary">Rendimentos do mês</span>
                <div class="symo-month-nav">
                    <a href="<?= BASE_URL ?>/investimentos/<?= $invId ?>?mes=<?= $mesAnterior->format('n') ?>&amp;ano=<?= $mesAnterior->format('Y') ?>" aria-label="Mês anterior"><i class="bi bi-chevron-left"></i></a>
                    <span><?= $mesesNomes[$mes] ?>/<?= $ano ?></span>
                    <a href="<?= BASE_URL ?>/investimentos/<?= $invId ?>?mes=<?= $mesSeguinte->format('n') ?>&amp;ano=<?= $mesSeguinte->format('Y') ?>" aria-label="Próximo mês"><i class="bi bi-chevron-right"></i></a>
                </div>
            </div>
            <div class="fs-4 fw-bold tabular-nums text-success"><?= FormatHelper::currency($rendimentoMes) ?></div>
        </div>
    </div>
</div>

<div class="symo-card p-0 overflow-hidden">
    <div class="p-3 border-bottom bg-light">
        <h5 class="mb-0 fw-bold fs-6"><i class="bi bi-clock-history me-2 text-muted"></i>Histórico de movimentos</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="label-caps">
                    <th scope="col" style="padding-left: 1.5rem;">Data</th>
                    <th scope="col">Tipo</th>
                    <th scope="col">Conta</th>
                    <th scope="col">Observações / Autor</th>
                    <th scope="col" class="text-end">Valor</th>
                    <th scope="col" class="text-end" style="padding-right: 1.5rem;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movimentos)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum movimento registrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($movimentos as $m): ?>
                        <?php
                            $valor = (float) $m['valor'];
                            $podeEditarEsteMov = $podeMovimentar && (
                                $ehProprietario
                                || $permissaoAcesso === 'total'
                                || ($permissaoAcesso === 'proprios' && (int) $m['usuario_id'] === $currentUserId)
                            );
                        ?>
                        <tr>
                            <td style="padding-left: 1.5rem;"><?= FormatHelper::date($m['data_movimento']) ?></td>
                            <td><span class="badge <?= $tiposBadge[$m['tipo']] ?? 'text-bg-secondary' ?>"><?= $e(Investment::TIPOS_MOVIMENTO[$m['tipo']] ?? $m['tipo']) ?></span></td>
                            <td><?= !empty($m['conta_nome']) ? $e($m['conta_nome']) : '<span class="text-muted">—</span>' ?></td>
                            <td>
                                <div><?= !empty($m['observacoes']) ? $e($m['observacoes']) : '<span class="text-muted">—</span>' ?></div>
                                <div class="text-muted small">Por: <?= $e($m['usuario_nome'] ?? 'Usuário') ?></div>
                            </td>
                            <td class="text-end tabular-nums fw-semibold"><?= FormatHelper::currency($valor) ?></td>
                            <td class="text-end" style="padding-right: 1.5rem;">
                                <?php if ($podeEditarEsteMov): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-editar-mov" data-bs-toggle="modal" data-bs-target="#modalEditarMovimento"
                                        data-id="<?= (int) $m['id'] ?>"
                                        data-tipo="<?= $e($m['tipo']) ?>"
                                        data-valor="<?= $e(number_format($valor, 2, ',', '')) ?>"
                                        data-data="<?= $e(substr((string) $m['data_movimento'], 0, 10)) ?>"
                                        data-obs="<?= $e($m['observacoes'] ?? '') ?>" aria-label="Editar movimento"><i class="bi bi-pencil"></i></button>
                                    <form action="<?= BASE_URL ?>/investimentos/movimentos/excluir/<?= (int) $m['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Excluir este movimento? Os saldos serão recalculados.');">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Excluir movimento"><i class="bi bi-trash"></i></button>
                                    </form>
                                <?php else: ?>
                                    <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" title="<?= $e($motivoBloqueioMovimento !== '' ? $motivoBloqueioMovimento : 'Sem permissão para alterar este movimento.') ?>">
                                        <button type="button" class="btn btn-sm btn-outline-secondary opacity-50" disabled style="pointer-events: none;"><i class="bi bi-pencil"></i></button>
                                        <button type="button" class="btn btn-sm btn-outline-danger opacity-50" disabled style="pointer-events: none;"><i class="bi bi-trash"></i></button>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Compartilhamento do Investimento -->
<div class="modal fade" id="modalCompartilharInvestimento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content symo-modal-content">
            <div class="modal-header border-bottom" style="background-color: var(--symo-primary-brand); color: white;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-people-fill me-2"></i>Compartilhamento — <?= $e($investimento['nome']) ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body p-4">
                <?php if ($ehProprietario): ?>
                    <form action="<?= BASE_URL ?>/compartilhamentos/convidar" method="POST" class="mb-4 pb-3 border-bottom">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="recurso_tipo" value="investimento">
                        <input type="hidden" name="recurso_id" value="<?= $invId ?>">
                        <input type="hidden" name="redirect_to" value="/investimentos/<?= $invId ?>">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">E-mail do Usuário Symo</label>
                            <input type="email" name="email" class="form-control" placeholder="usuario@exemplo.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Permissão no Investimento</label>
                            <select name="permissao" class="form-select" required>
                                <?php foreach (Share::PERMISSOES as $kPerm => $lblPerm): ?>
                                    <option value="<?= $e($kPerm) ?>"><?= $e($lblPerm) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Para que o convidado possa realizar ajustes ou movimentos, ele também precisará ter acesso ativo com permissão de lançamento à conta bancária vinculada (<strong><?= $e($investimento['conta_nome']) ?></strong>).
                            </div>
                        </div>
                        <button type="submit" class="btn-symo-primary w-100 justify-content-center">
                            <i class="bi bi-send me-1"></i>Enviar Convite
                        </button>
                    </form>

                    <h6 class="fw-bold mb-3">Participantes deste investimento</h6>
                    <?php if (empty($participantes)): ?>
                        <div class="text-center py-3 text-muted bg-light rounded border">
                            Nenhum participante convidado para este investimento.
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($participantes as $p): ?>
                                <div class="p-3 border rounded bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <div class="fw-semibold"><?= $e($p['participante_nome']) ?></div>
                                            <div class="text-muted small"><?= $e($p['participante_email']) ?></div>
                                        </div>
                                        <?php if ($p['status'] === 'ativo'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Convite Pendente</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <form action="<?= BASE_URL ?>/compartilhamentos/permissao/<?= (int) $p['id'] ?>" method="POST" class="d-flex align-items-center gap-1 flex-grow-1">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <input type="hidden" name="recurso_tipo" value="investimento">
                                            <input type="hidden" name="recurso_id" value="<?= $invId ?>">
                                            <input type="hidden" name="redirect_to" value="/investimentos/<?= $invId ?>">
                                            <select name="permissao" class="form-select" onchange="this.form.submit()">
                                                <?php foreach (Share::PERMISSOES as $kPerm => $lblPerm): ?>
                                                    <option value="<?= $e($kPerm) ?>" <?= $p['permissao'] === $kPerm ? 'selected' : '' ?>><?= $e($lblPerm) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                        <form action="<?= BASE_URL ?>/compartilhamentos/revogar/<?= (int) $p['id'] ?>" method="POST" onsubmit="return confirm('Remover o acesso deste participante ao investimento?');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <input type="hidden" name="recurso_tipo" value="investimento">
                                            <input type="hidden" name="recurso_id" value="<?= $invId ?>">
                                            <input type="hidden" name="redirect_to" value="/investimentos/<?= $invId ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Remover participante">
                                                <i class="bi bi-person-x"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="p-3 border rounded bg-light mb-3">
                        <div class="small text-muted mb-1">Proprietário do Investimento</div>
                        <div class="fw-bold"><?= $e($investimento['proprietario_nome'] ?? '') ?></div>
                        <div class="text-muted small mb-2"><?= $e($investimento['proprietario_email'] ?? '') ?></div>
                        <hr class="my-2">
                        <div class="small text-muted mb-1">Sua permissão atual</div>
                        <span class="badge" style="background-color: var(--symo-primary-fixed); color: var(--symo-on-primary-fixed);">
                            <?= $e(Share::PERMISSOES[$permissaoAcesso] ?? 'Apenas visualização') ?>
                        </span>
                    </div>

                    <form action="<?= BASE_URL ?>/compartilhamentos/dashboard/<?= $compartilhamentoId ?>" method="POST" class="mb-4">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="recurso_tipo" value="investimento">
                        <input type="hidden" name="recurso_id" value="<?= $invId ?>">
                        <input type="hidden" name="redirect_to" value="/investimentos/<?= $invId ?>">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="switchDashInv" name="exibir_dashboard" value="1" <?= $exibirNoMeuDash ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="form-check-label fw-semibold" for="switchDashInv">Exibir no Dashboard</label>
                        </div>
                        <div class="form-text">
                            Controla se o valor deste investimento soma no card de Investimentos do seu Dashboard.
                        </div>
                    </form>

                    <form action="<?= BASE_URL ?>/compartilhamentos/sair/<?= $compartilhamentoId ?>" method="POST" onsubmit="return confirm('Deseja realmente sair do compartilhamento deste investimento?');">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="recurso_tipo" value="investimento">
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-box-arrow-right me-1"></i>Sair deste investimento compartilhado
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<?php if ($ehProprietario): ?>
<div class="modal fade" id="modalEditarInvestimento" tabindex="-1" aria-labelledby="modalEditarInvestimentoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content symo-modal-content">
            <form action="<?= BASE_URL ?>/investimentos/atualizar/<?= $invId ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="modal-header border-bottom" style="background-color: var(--symo-primary-brand); color: white;">
                    <h5 class="modal-title fw-bold" id="modalEditarInvestimentoLabel">Editar Investimento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="ed_nome" class="form-label fw-semibold">Nome *</label>
                        <input type="text" class="form-control" id="ed_nome" name="nome" maxlength="120" required value="<?= $e($investimento['nome']) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="ed_conta" class="form-label fw-semibold">Conta vinculada *</label>
                        <select class="form-select" id="ed_conta" name="conta_id" required>
                            <?php if (!$contaVinculadaNaLista): ?>
                                <option value="" selected disabled>Atual: <?= $e($investimento['conta_nome']) ?> (indisponível)</option>
                            <?php endif; ?>
                            <?php foreach ($contas as $c): ?>
                                <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) $investimento['conta_id'] ? 'selected' : '' ?>><?= $e($c['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">A troca vale apenas para movimentos futuros; o histórico mantém a conta original.</div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="ed_cust" class="form-label fw-semibold">Custodiante</label>
                            <input type="text" class="form-control" id="ed_cust" name="custodiante" maxlength="120" value="<?= $e($investimento['custodiante'] ?? '') ?>">
                        </div>
                        <div class="col-6">
                            <label for="ed_classe" class="form-label fw-semibold">Classe</label>
                            <select class="form-select" id="ed_classe" name="classe">
                                <?php foreach (Investment::CLASSES as $k => $label): ?>
                                    <option value="<?= $e($k) ?>" <?= $investimento['classe'] === $k ? 'selected' : '' ?>><?= $e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="ed_fin" class="form-label fw-semibold">Finalidade</label>
                        <input type="text" class="form-control" id="ed_fin" name="finalidade" maxlength="120" value="<?= $e($investimento['finalidade'] ?? '') ?>">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="ed_exibir_dashboard" name="exibir_dashboard" value="1" <?= $exibirDashboardOwner ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="ed_exibir_dashboard">Exibir no Dashboard</label>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-symo-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="modalMovimento" tabindex="-1" aria-labelledby="modalMovimentoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content symo-modal-content">
            <form action="<?= BASE_URL ?>/investimentos/movimentos/salvar/<?= $invId ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="tipo" id="mov_tipo" value="aporte">
                <div class="modal-header border-bottom" style="background-color: var(--symo-primary-brand); color: white;">
                    <h5 class="modal-title fw-bold" id="modalMovimentoLabel">Novo movimento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted" id="mov_ajuda"></p>
                    <div class="mb-3" id="mov_grupo_valor">
                        <label for="mov_valor" class="form-label fw-semibold" id="mov_label_valor">Valor *</label>
                        <input type="text" class="form-control money-mask tabular-nums text-end" id="mov_valor" name="valor" placeholder="R$ 0,00">
                    </div>
                    <div class="mb-3 d-none" id="mov_grupo_total">
                        <label for="mov_total" class="form-label fw-semibold">Novo valor total *</label>
                        <input type="text" class="form-control money-mask tabular-nums text-end" id="mov_total" name="novo_total" placeholder="R$ 0,00">
                    </div>
                    <div class="mb-3">
                        <label for="mov_data" class="form-label fw-semibold">Data *</label>
                        <input type="date" class="form-control" id="mov_data" name="data_movimento" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-1">
                        <label for="mov_obs" class="form-label fw-semibold">Observações</label>
                        <textarea class="form-control" id="mov_obs" name="observacoes" rows="2" maxlength="500"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-symo-primary">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarMovimento" tabindex="-1" aria-labelledby="modalEditarMovimentoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content symo-modal-content">
            <form id="formEditarMovimento" action="<?= BASE_URL ?>/investimentos/movimentos/atualizar/0" method="POST" data-base-action="<?= BASE_URL ?>/investimentos/movimentos/atualizar/">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="modal-header border-bottom" style="background-color: var(--symo-primary-brand); color: white;">
                    <h5 class="modal-title fw-bold" id="modalEditarMovimentoLabel">Editar movimento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted" id="em_ajuda"></p>
                    <div class="mb-3">
                        <label for="em_valor" class="form-label fw-semibold">Valor *</label>
                        <input type="text" class="form-control tabular-nums text-end" id="em_valor" name="valor" inputmode="decimal" required>
                    </div>
                    <div class="mb-3">
                        <label for="em_data" class="form-label fw-semibold">Data *</label>
                        <input type="date" class="form-control" id="em_data" name="data_movimento" required>
                    </div>
                    <div class="mb-1">
                        <label for="em_obs" class="form-label fw-semibold">Observações</label>
                        <textarea class="form-control" id="em_obs" name="observacoes" rows="2" maxlength="500"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-symo-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Inicializa tooltips do Bootstrap para botões desabilitados
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    var titulos = { aporte: 'Registrar aporte', resgate: 'Registrar resgate', rendimento: 'Registrar rendimento', ajuste: 'Atualizar valor' };
    var ajudas = {
        aporte: 'O valor sai da conta vinculada e aumenta o investimento.',
        resgate: 'O valor volta para a conta vinculada e reduz o investimento.',
        rendimento: 'Não movimenta a conta vinculada; aumenta o valor do investimento.',
        ajuste: 'Informe o valor total atual. Não movimenta a conta e não conta como rendimento.'
    };
    var modalMov = document.getElementById('modalMovimento');
    if (modalMov) {
        modalMov.addEventListener('show.bs.modal', function (ev) {
            var tipo = (ev.relatedTarget && ev.relatedTarget.getAttribute('data-tipo')) || 'aporte';
            document.getElementById('mov_tipo').value = tipo;
            document.getElementById('modalMovimentoLabel').textContent = titulos[tipo];
            document.getElementById('mov_ajuda').textContent = ajudas[tipo];
            var ajuste = tipo === 'ajuste';
            document.getElementById('mov_grupo_valor').classList.toggle('d-none', ajuste);
            document.getElementById('mov_grupo_total').classList.toggle('d-none', !ajuste);
            document.getElementById('mov_valor').required = !ajuste;
            document.getElementById('mov_total').required = ajuste;
        });
    }

    var modalEd = document.getElementById('modalEditarMovimento');
    if (modalEd) {
        modalEd.addEventListener('show.bs.modal', function (ev) {
            var b = ev.relatedTarget;
            if (!b) { return; }
            var form = document.getElementById('formEditarMovimento');
            form.action = form.getAttribute('data-base-action') + b.getAttribute('data-id');
            document.getElementById('em_valor').value = b.getAttribute('data-valor');
            document.getElementById('em_data').value = b.getAttribute('data-data');
            document.getElementById('em_obs').value = b.getAttribute('data-obs');
            document.getElementById('em_ajuda').textContent = b.getAttribute('data-tipo') === 'ajuste'
                ? 'Informe a diferença do ajuste; use sinal negativo para redução.'
                : 'Alterar o valor também ajusta o saldo da conta vinculada, quando houver.';
        });
    }
});
</script>
