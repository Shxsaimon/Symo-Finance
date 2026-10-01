<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

$tipoLabels = [
    'correcao' => 'Correção',
    'melhoria' => 'Melhoria',
    'duvida' => 'Dúvida',
];
$telaLabels = [
    'sistema' => 'Sistema (geral)',
    'dashboard' => 'Dashboard',
    'lancamentos' => 'Lançamentos',
    'contas' => 'Contas bancárias',
    'categorias' => 'Categorias',
    'meios_pagamento' => 'Meios de pagamento',
    'cartoes' => 'Cartões de crédito',
    'transferencias' => 'Transferências',
    'relatorios' => 'Relatórios',
    'lixeira' => 'Lixeira',
    'configuracoes' => 'Configurações',
    'perfil' => 'Perfil',
];
$statusLabels = [
    'novo' => 'Novo',
    'em_analise' => 'Em análise',
    'concluido' => 'Concluído',
    'descartado' => 'Descartado',
];
$statusClasses = [
    'novo' => 'bg-primary-subtle text-primary',
    'em_analise' => 'bg-warning-subtle text-warning-emphasis',
    'concluido' => 'bg-success-subtle text-success',
    'descartado' => 'bg-secondary-subtle text-secondary',
];
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--symo-on-surface);">Feedbacks Recebidos</h1>
        <p class="text-muted mb-0">Mensagens e imagens enviadas pelos usuários do Symo.</p>
    </div>
    <span class="badge rounded-pill text-bg-light border px-3 py-2">
        <?= number_format($totalRegistros, 0, ',', '.') ?> registro<?= $totalRegistros === 1 ? '' : 's' ?>
    </span>
</div>

<div class="symo-card shadow-sm border-0 p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 symo-table">
            <thead>
                <tr>
                    <th style="min-width: 150px;">Recebido em</th>
                    <th style="min-width: 190px;">Usuário</th>
                    <th style="min-width: 150px;">Origem</th>
                    <th style="min-width: 115px;">Tipo</th>
                    <th style="min-width: 130px;">Mensagem</th>
                    <th style="min-width: 180px;">Imagens</th>
                    <th style="min-width: 110px;">Status</th>
                    <th class="text-end" style="min-width: 240px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($feedbacks)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-chat-square-text fs-1 d-block mb-2"></i>
                            Ainda não há feedbacks recebidos.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($feedbacks as $feedback): ?>
                        <tr>
                            <td class="tabular-nums small text-nowrap">
                                <?= htmlspecialchars(FormatHelper::datetime($feedback['criado_em']), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($feedback['usuario_nome'] ?? 'Usuário removido', ENT_QUOTES, 'UTF-8') ?></div>
                                <small class="text-muted"><?= htmlspecialchars($feedback['usuario_email'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td><?= htmlspecialchars($telaLabels[$feedback['tela']] ?? $feedback['tela'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($tipoLabels[$feedback['tipo']] ?? $feedback['tipo'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <button type="button" class="btn-symo-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTextoFeedback"
                                        data-feedback-text="<?= htmlspecialchars($feedback['texto'], ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="bi bi-eye"></i><span>Ler mensagem</span>
                                </button>
                            </td>
                            <td>
                                <?php $imageCount = 0; ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($feedback['imagens'] as $imageIndex => $imageUrl): ?>
                                        <?php if (!is_string($imageUrl) || !filter_var($imageUrl, FILTER_VALIDATE_URL)) { continue; } ?>
                                        <?php $imageCount++; ?>
                                        <button type="button" class="border-0 bg-transparent p-0" data-bs-toggle="modal" data-bs-target="#modalImagemFeedback"
                                                data-feedback-image="<?= BASE_URL ?>/feedback/imagem/<?= (int) $feedback['id'] ?>/<?= (int) $imageIndex ?>"
                                                aria-label="Ampliar imagem anexada">
                                            <img src="<?= BASE_URL ?>/feedback/imagem/<?= (int) $feedback['id'] ?>/<?= (int) $imageIndex ?>"
                                                 alt="Imagem anexada ao feedback" loading="lazy"
                                                 style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1px solid var(--symo-outline-variant, #ccc3da);">
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($imageCount === 0): ?><span class="text-muted small">Sem anexos</span><?php endif; ?>
                            </td>
                            <td>
                                <?php $status = (string) ($feedback['status'] ?? 'novo'); ?>
                                <span class="badge rounded-pill <?= $statusClasses[$status] ?? 'bg-secondary-subtle text-secondary' ?>">
                                    <?= htmlspecialchars($statusLabels[$status] ?? $status, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex align-items-center gap-1" role="group" aria-label="Alterar status do feedback">
                                    <form method="POST" action="<?= BASE_URL ?>/feedbacks/status/<?= (int) $feedback['id'] ?>" class="d-inline">
                                        <?= CsrfHelper::inputField() ?>
                                        <input type="hidden" name="pagina" value="<?= (int) $paginaAtual ?>">
                                        <input type="hidden" name="status" value="em_analise">
                                        <button type="submit" class="btn-symo-secondary btn-symo-status" title="Marcar como em análise" aria-label="Marcar como em análise" <?= $status === 'em_analise' ? 'disabled' : '' ?>>
                                            <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="<?= BASE_URL ?>/feedbacks/status/<?= (int) $feedback['id'] ?>" class="d-inline">
                                        <?= CsrfHelper::inputField() ?>
                                        <input type="hidden" name="pagina" value="<?= (int) $paginaAtual ?>">
                                        <input type="hidden" name="status" value="concluido">
                                        <button type="submit" class="btn-symo-primary btn-symo-status" title="Marcar como concluído" aria-label="Marcar como concluído" <?= $status === 'concluido' ? 'disabled' : '' ?>>
                                            <i class="bi bi-check-lg" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPaginas > 1): ?>
        <div class="p-3 border-top d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 bg-light">
            <small class="text-muted">Página <?= (int) $paginaAtual ?> de <?= (int) $totalPaginas ?> · <?= number_format($totalRegistros, 0, ',', '.') ?> feedbacks</small>
            <nav aria-label="Paginação de feedbacks">
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($paginaAtual > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?= BASE_URL ?>/feedbacks?pagina=<?= (int) ($paginaAtual - 1) ?>" aria-label="Página anterior">Anterior</a></li>
                    <?php endif; ?>
                    <?php if ($paginaAtual < $totalPaginas): ?>
                        <li class="page-item"><a class="page-link" href="<?= BASE_URL ?>/feedbacks?pagina=<?= (int) ($paginaAtual + 1) ?>" aria-label="Próxima página">Próxima</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="modalTextoFeedback" tabindex="-1" aria-labelledby="modalTextoFeedbackLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content symo-modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5 fw-bold" id="modalTextoFeedbackLabel">Mensagem do usuário</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="textoFeedbackCompleto" style="white-space: pre-wrap; overflow-wrap: anywhere;"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalImagemFeedback" tabindex="-1" aria-labelledby="modalImagemFeedbackLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content symo-modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5 fw-bold" id="modalImagemFeedbackLabel">Imagem anexada</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body text-center">
                <img id="imagemFeedbackAmpliada" src="" alt="Imagem anexada ampliada" class="img-fluid" style="max-height: 75vh; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('modalTextoFeedback').addEventListener('show.bs.modal', function (event) {
    document.getElementById('textoFeedbackCompleto').textContent = event.relatedTarget.dataset.feedbackText || '';
});
document.getElementById('modalImagemFeedback').addEventListener('show.bs.modal', function (event) {
    document.getElementById('imagemFeedbackAmpliada').src = event.relatedTarget.dataset.feedbackImage || '';
});
document.getElementById('modalImagemFeedback').addEventListener('hidden.bs.modal', function () {
    document.getElementById('imagemFeedbackAmpliada').src = '';
});
</script>