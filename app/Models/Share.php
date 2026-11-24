<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Model de Compartilhamento de Contas e Investimentos (Share)
 *
 * Gerencia convites para usuários Symo, níveis de permissão ('visualizar', 'proprios', 'total'),
 * histórico de múltiplos períodos de participação (aceito_em / encerrado_em), notificações pendentes
 * e preferência individual de exibição no Dashboard.
 */
class Share extends Model
{
    protected string $tabela = 'compartilhamentos';
    protected string $chavePrimaria = 'id';

    public const PERMISSOES = [
        'visualizar' => 'Apenas visualização',
        'proprios'   => 'Lançar e alterar apenas os próprios',
        'total'      => 'Acesso total aos lançamentos',
    ];

    /**
     * Retorna os convites pendentes recebidos pelo usuário autenticado (para o Modal de Notificações).
     *
     * @param int $userId
     * @return array<int, array<string, mixed>>
     */
    public static function getPendingNotificationsForUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        try {
            $db = Database::getConnection();
            $sql = "SELECT cp.*,
                           u.nome AS proprietario_nome,
                           u.email AS proprietario_email,
                           CASE
                               WHEN cp.recurso_tipo = 'conta' THEN c.nome
                               WHEN cp.recurso_tipo = 'investimento' THEN i.nome
                           END AS recurso_nome
                    FROM compartilhamentos cp
                    INNER JOIN usuarios u ON u.id = cp.proprietario_id
                    LEFT JOIN contas c ON cp.recurso_tipo = 'conta' AND c.id = cp.recurso_id AND c.excluido_em IS NULL
                    LEFT JOIN investimentos i ON cp.recurso_tipo = 'investimento' AND i.id = cp.recurso_id AND i.excluido_em IS NULL
                    WHERE cp.participante_id = :user_id
                      AND cp.status = 'pendente'
                      AND (
                          (cp.recurso_tipo = 'conta' AND c.id IS NOT NULL)
                          OR (cp.recurso_tipo = 'investimento' AND i.id IS NOT NULL)
                      )
                    ORDER BY cp.convidado_em DESC, cp.id DESC";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable) {
            // Caso a migration 024 ainda não tenha sido executada no ambiente, evita quebrar a navbar
            return [];
        }
    }

    /**
     * Retorna a lista de participantes (pendentes e ativos) de um recurso específico.
     *
     * @param string $recursoTipo 'conta' ou 'investimento'
     * @param int $recursoId
     * @return array<int, array<string, mixed>>
     */
    public function getParticipantsForResource(string $recursoTipo, int $recursoId): array
    {
        try {
            $sql = "SELECT cp.*,
                           u.nome AS participante_nome,
                           u.email AS participante_email,
                           u.avatar AS participante_avatar
                    FROM compartilhamentos cp
                    INNER JOIN usuarios u ON u.id = cp.participante_id
                    WHERE cp.recurso_tipo = :tipo
                      AND cp.recurso_id = :recurso_id
                      AND cp.status IN ('pendente', 'ativo')
                      AND u.excluido_em IS NULL
                    ORDER BY cp.status ASC, u.nome ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':tipo', $recursoTipo, PDO::PARAM_STR);
            $stmt->bindValue(':recurso_id', $recursoId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Retorna os participantes agrupados por ID do recurso para exibição rápida em listagens.
     *
     * @param string $recursoTipo 'conta' ou 'investimento'
     * @param array<int, int> $recursoIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function getParticipantsByResourceIds(string $recursoTipo, array $recursoIds): array
    {
        $recursoIds = array_values(array_unique(array_filter(array_map('intval', $recursoIds), fn ($id) => $id > 0)));
        if (empty($recursoIds)) {
            return [];
        }

        try {
            $placeholders = [];
            $params = [':tipo' => $recursoTipo];
            foreach ($recursoIds as $idx => $rId) {
                $ph = ":rid_{$idx}";
                $placeholders[] = $ph;
                $params[$ph] = $rId;
            }
            $inList = implode(', ', $placeholders);

            $sql = "SELECT cp.*,
                           u.nome AS participante_nome,
                           u.email AS participante_email
                    FROM compartilhamentos cp
                    INNER JOIN usuarios u ON u.id = cp.participante_id
                    WHERE cp.recurso_tipo = :tipo
                      AND cp.recurso_id IN ({$inList})
                      AND cp.status IN ('pendente', 'ativo')
                      AND u.excluido_em IS NULL
                    ORDER BY cp.status ASC, u.nome ASC";
            $stmt = $this->db->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll();

            $grouped = [];
            foreach ($rows as $row) {
                $rid = (int) $row['recurso_id'];
                $grouped[$rid][] = $row;
            }
            return $grouped;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Obtém o vínculo ativo de um participante em um recurso (conta ou investimento).
     *
     * @param string $recursoTipo 'conta' ou 'investimento'
     * @param int $recursoId
     * @param int $participanteId
     * @return array<string, mixed>|null
     */
    public function getActiveShare(string $recursoTipo, int $recursoId, int $participanteId): ?array
    {
        try {
            $sql = "SELECT cp.*, u.nome AS proprietario_nome
                    FROM compartilhamentos cp
                    INNER JOIN usuarios u ON u.id = cp.proprietario_id
                    WHERE cp.recurso_tipo = :tipo
                      AND cp.recurso_id = :recurso_id
                      AND cp.participante_id = :participante_id
                      AND cp.status = 'ativo'
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':tipo', $recursoTipo, PDO::PARAM_STR);
            $stmt->bindValue(':recurso_id', $recursoId, PDO::PARAM_INT);
            $stmt->bindValue(':participante_id', $participanteId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Verifica se um timestamp ($criadoEm) recai dentro de algum período válido em que o participante
     * esteve ativo no compartilhamento daquele recurso.
     */
    public function isTimestampWithinParticipantPeriods(
        string $recursoTipo,
        int $recursoId,
        int $participanteId,
        string $criadoEm
    ): bool {
        $sql = "SELECT 1
                FROM compartilhamento_periodos p
                WHERE p.recurso_tipo = :tipo
                  AND p.recurso_id = :recurso_id
                  AND p.participante_id = :participante_id
                  AND :criado_em >= p.aceito_em
                  AND (p.encerrado_em IS NULL OR :criado_em_fim <= p.encerrado_em)
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':tipo', $recursoTipo, PDO::PARAM_STR);
        $stmt->bindValue(':recurso_id', $recursoId, PDO::PARAM_INT);
        $stmt->bindValue(':participante_id', $participanteId, PDO::PARAM_INT);
        $stmt->bindValue(':criado_em', $criadoEm, PDO::PARAM_STR);
        $stmt->bindValue(':criado_em_fim', $criadoEm, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Cria ou reenvia um convite de compartilhamento para um usuário existente do Symo.
     *
     * @param string $recursoTipo 'conta' ou 'investimento'
     * @param int $recursoId
     * @param string $emailConvidado
     * @param string $permissao 'visualizar', 'proprios' ou 'total'
     * @param int $proprietarioId
     * @return array{success: bool, message: string}
     */
    public function invite(
        string $recursoTipo,
        int $recursoId,
        string $emailConvidado,
        string $permissao,
        int $proprietarioId
    ): array {
        if (!in_array($recursoTipo, ['conta', 'investimento'], true)) {
            return ['success' => false, 'message' => 'Tipo de recurso inválido para compartilhamento.'];
        }
        if (!isset(self::PERMISSOES[$permissao])) {
            return ['success' => false, 'message' => 'Nível de permissão inválido.'];
        }

        $emailLimpo = trim(mb_strtolower($emailConvidado));
        if ($emailLimpo === '' || !filter_var($emailLimpo, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Informe um endereço de e-mail válido.'];
        }

        $recurso = $this->findOwnedResource($recursoTipo, $recursoId, $proprietarioId);
        if (!$recurso) {
            return ['success' => false, 'message' => 'Recurso não encontrado ou você não é o proprietário.'];
        }

        // Busca usuário convidado no BD do Symo
        $userModel = new User();
        $convidado = $userModel->findByEmail($emailLimpo);
        if (!$convidado || ($convidado['status'] ?? '') !== 'ativo') {
            return [
                'success' => false,
                'message' => 'Este e-mail não corresponde a nenhum usuário ativo cadastrado no Symo Finance.',
            ];
        }

        $participanteId = (int) $convidado['id'];
        if ($participanteId === $proprietarioId) {
            return ['success' => false, 'message' => 'Você já é o proprietário deste item e não pode convidar a si mesmo.'];
        }

        // Verifica se já existe registro de compartilhamento entre este recurso e participante
        $stmtCheck = $this->db->prepare(
            "SELECT * FROM compartilhamentos
             WHERE recurso_tipo = :tipo AND recurso_id = :recurso_id AND participante_id = :part_id
             LIMIT 1"
        );
        $stmtCheck->bindValue(':tipo', $recursoTipo, PDO::PARAM_STR);
        $stmtCheck->bindValue(':recurso_id', $recursoId, PDO::PARAM_INT);
        $stmtCheck->bindValue(':part_id', $participanteId, PDO::PARAM_INT);
        $stmtCheck->execute();
        $existente = $stmtCheck->fetch();

        if ($existente) {
            if ($existente['status'] === 'ativo') {
                return ['success' => false, 'message' => 'Este usuário já participa ativamente deste compartilhamento.'];
            }
            if ($existente['status'] === 'pendente') {
                return ['success' => false, 'message' => 'Já existe um convite pendente para este usuário.'];
            }

            // Reconvite após saída, revogação ou recusa: preserva os períodos antigos em compartilhamento_periodos!
            $stmtUp = $this->db->prepare(
                "UPDATE compartilhamentos
                 SET permissao = :permissao,
                     status = 'pendente',
                     convidado_em = NOW(),
                     aceito_em = NULL,
                     encerrado_em = NULL,
                     atualizado_em = NOW()
                 WHERE id = :id AND proprietario_id = :prop_id"
            );
            $stmtUp->bindValue(':permissao', $permissao, PDO::PARAM_STR);
            $stmtUp->bindValue(':id', (int) $existente['id'], PDO::PARAM_INT);
            $stmtUp->bindValue(':prop_id', $proprietarioId, PDO::PARAM_INT);
            $stmtUp->execute();

            $shareId = (int) $existente['id'];
            $this->logAudit('UPDATE', $shareId, $existente, [
                'status'    => 'pendente',
                'permissao' => $permissao,
                'acao'      => 'RECONVITE',
            ], $proprietarioId);
        } else {
            $stmtIns = $this->db->prepare(
                "INSERT INTO compartilhamentos (
                    recurso_tipo, recurso_id, proprietario_id, participante_id,
                    permissao, status, exibir_dashboard, convidado_em, atualizado_em
                 ) VALUES (
                    :tipo, :recurso_id, :prop_id, :part_id,
                    :permissao, 'pendente', TRUE, NOW(), NOW()
                 )"
            );
            $stmtIns->bindValue(':tipo', $recursoTipo, PDO::PARAM_STR);
            $stmtIns->bindValue(':recurso_id', $recursoId, PDO::PARAM_INT);
            $stmtIns->bindValue(':prop_id', $proprietarioId, PDO::PARAM_INT);
            $stmtIns->bindValue(':part_id', $participanteId, PDO::PARAM_INT);
            $stmtIns->bindValue(':permissao', $permissao, PDO::PARAM_STR);
            $stmtIns->execute();

            $shareId = (int) $this->db->lastInsertId();
            $this->logAudit('INSERT', $shareId, null, [
                'recurso_tipo'    => $recursoTipo,
                'recurso_id'      => $recursoId,
                'participante_id' => $participanteId,
                'permissao'       => $permissao,
                'status'          => 'pendente',
            ], $proprietarioId);
        }

        // Disparo de e-mail transacional de aviso (não bloqueante caso Resend esteja em modo sandbox sem domínio)
        try {
            $owner = AuthHelper::user();
            $ownerName = (string) ($owner['nome'] ?? 'Um usuário do Symo');
            MailHelper::sendShareInvite(
                (string) $convidado['email'],
                (string) $convidado['nome'],
                $ownerName,
                $recursoTipo,
                (string) $recurso['nome'],
                self::PERMISSOES[$permissao]
            );
        } catch (Throwable $e) {
            LoggerHelper::error($e, ['context' => 'Share::invite - MailHelper']);
        }

        return [
            'success' => true,
            'message' => "Convite enviado para {$convidado['nome']} ({$convidado['email']}). Ele já está disponível nas Notificações do usuário!",
        ];
    }

    /**
     * Aceita um convite pendente e abre um novo período de visibilidade em compartilhamento_periodos.
     */
    public function acceptInvite(int $shareId, int $participanteId): array
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM compartilhamentos
                 WHERE id = :id AND participante_id = :part_id AND status = 'pendente'
                 FOR UPDATE"
            );
            $stmt->bindValue(':id', $shareId, PDO::PARAM_INT);
            $stmt->bindValue(':part_id', $participanteId, PDO::PARAM_INT);
            $stmt->execute();
            $share = $stmt->fetch();

            if (!$share) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Convite não encontrado ou já processado.'];
            }

            $stmtUp = $this->db->prepare(
                "UPDATE compartilhamentos
                 SET status = 'ativo', aceito_em = NOW(), encerrado_em = NULL, atualizado_em = NOW()
                 WHERE id = :id"
            );
            $stmtUp->bindValue(':id', $shareId, PDO::PARAM_INT);
            $stmtUp->execute();

            // Registra a nova janela temporal de participação em compartilhamento_periodos
            $stmtPer = $this->db->prepare(
                "INSERT INTO compartilhamento_periodos (
                    compartilhamento_id, recurso_tipo, recurso_id, participante_id, aceito_em, encerrado_em
                 ) VALUES (
                    :comp_id, :tipo, :recurso_id, :part_id, NOW(), NULL
                 )"
            );
            $stmtPer->bindValue(':comp_id', $shareId, PDO::PARAM_INT);
            $stmtPer->bindValue(':tipo', $share['recurso_tipo'], PDO::PARAM_STR);
            $stmtPer->bindValue(':recurso_id', (int) $share['recurso_id'], PDO::PARAM_INT);
            $stmtPer->bindValue(':part_id', $participanteId, PDO::PARAM_INT);
            $stmtPer->execute();

            $this->logAudit('UPDATE', $shareId, ['status' => 'pendente'], ['status' => 'ativo'], $participanteId);
            $this->db->commit();

            return ['success' => true, 'message' => 'Convite aceito com sucesso! O item compartilhado já está disponível para você.'];
        } catch (Throwable $e) {
            $this->db->rollBack();
            LoggerHelper::error($e, ['context' => 'Share::acceptInvite']);
            return ['success' => false, 'message' => 'Não foi possível aceitar o convite.'];
        }
    }

    /**
     * Recusa um convite pendente.
     */
    public function declineInvite(int $shareId, int $participanteId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM compartilhamentos
             WHERE id = :id AND participante_id = :part_id AND status = 'pendente'
             LIMIT 1"
        );
        $stmt->bindValue(':id', $shareId, PDO::PARAM_INT);
        $stmt->bindValue(':part_id', $participanteId, PDO::PARAM_INT);
        $stmt->execute();
        $share = $stmt->fetch();

        if (!$share) {
            return ['success' => false, 'message' => 'Convite não encontrado ou já processado.'];
        }

        $stmtUp = $this->db->prepare(
            "UPDATE compartilhamentos
             SET status = 'recusado', encerrado_em = NOW(), atualizado_em = NOW()
             WHERE id = :id"
        );
        $stmtUp->bindValue(':id', $shareId, PDO::PARAM_INT);
        $stmtUp->execute();

        $this->logAudit('UPDATE', $shareId, ['status' => 'pendente'], ['status' => 'recusado'], $participanteId);

        return ['success' => true, 'message' => 'Convite recusado.'];
    }

    /**
     * Atualiza o nível de permissão de um participante (exclusivo do proprietário).
     */
    public function updatePermission(int $shareId, string $novaPermissao, int $proprietarioId): array
    {
        if (!isset(self::PERMISSOES[$novaPermissao])) {
            return ['success' => false, 'message' => 'Nível de permissão inválido.'];
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM compartilhamentos
             WHERE id = :id AND proprietario_id = :prop_id AND status IN ('pendente', 'ativo')
             LIMIT 1"
        );
        $stmt->bindValue(':id', $shareId, PDO::PARAM_INT);
        $stmt->bindValue(':prop_id', $proprietarioId, PDO::PARAM_INT);
        $stmt->execute();
        $share = $stmt->fetch();

        if (!$share) {
            return ['success' => false, 'message' => 'Compartilhamento não encontrado.'];
        }

        $stmtUp = $this->db->prepare(
            "UPDATE compartilhamentos SET permissao = :perm, atualizado_em = NOW() WHERE id = :id"
        );
        $stmtUp->bindValue(':perm', $novaPermissao, PDO::PARAM_STR);
        $stmtUp->bindValue(':id', $shareId, PDO::PARAM_INT);
        $stmtUp->execute();

        $this->logAudit('UPDATE', $shareId, ['permissao' => $share['permissao']], ['permissao' => $novaPermissao], $proprietarioId);

        return ['success' => true, 'message' => 'Permissão do participante atualizada com sucesso.'];
    }

    /**
     * Revoga o acesso de um participante ou cancela um convite pendente (exclusivo do proprietário).
     * Fecha o período aberto em compartilhamento_periodos sem apagá-lo.
     */
    public function revoke(int $shareId, int $proprietarioId): array
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM compartilhamentos
                 WHERE id = :id AND proprietario_id = :prop_id AND status IN ('pendente', 'ativo')
                 FOR UPDATE"
            );
            $stmt->bindValue(':id', $shareId, PDO::PARAM_INT);
            $stmt->bindValue(':prop_id', $proprietarioId, PDO::PARAM_INT);
            $stmt->execute();
            $share = $stmt->fetch();

            if (!$share) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Vínculo de compartilhamento não encontrado.'];
            }

            $stmtUp = $this->db->prepare(
                "UPDATE compartilhamentos
                 SET status = 'revogado', encerrado_em = NOW(), atualizado_em = NOW()
                 WHERE id = :id"
            );
            $stmtUp->bindValue(':id', $shareId, PDO::PARAM_INT);
            $stmtUp->execute();

            // Fecha qualquer período aberto deste compartilhamento preservando a janela histórica
            $stmtPer = $this->db->prepare(
                "UPDATE compartilhamento_periodos
                 SET encerrado_em = NOW()
                 WHERE compartilhamento_id = :comp_id AND encerrado_em IS NULL"
            );
            $stmtPer->bindValue(':comp_id', $shareId, PDO::PARAM_INT);
            $stmtPer->execute();

            $this->logAudit('UPDATE', $shareId, ['status' => $share['status']], ['status' => 'revogado'], $proprietarioId);
            $this->db->commit();

            return ['success' => true, 'message' => 'Acesso do participante removido com sucesso.'];
        } catch (Throwable $e) {
            $this->db->rollBack();
            LoggerHelper::error($e, ['context' => 'Share::revoke']);
            return ['success' => false, 'message' => 'Não foi possível remover o participante.'];
        }
    }

    /**
     * Permite ao próprio participante sair voluntariamente de um compartilhamento ativo,
     * fechando o período atual em compartilhamento_periodos.
     */
    public function leave(int $shareId, int $participanteId): array
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM compartilhamentos
                 WHERE id = :id AND participante_id = :part_id AND status = 'ativo'
                 FOR UPDATE"
            );
            $stmt->bindValue(':id', $shareId, PDO::PARAM_INT);
            $stmt->bindValue(':part_id', $participanteId, PDO::PARAM_INT);
            $stmt->execute();
            $share = $stmt->fetch();

            if (!$share) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Compartilhamento ativo não encontrado.'];
            }

            $stmtUp = $this->db->prepare(
                "UPDATE compartilhamentos
                 SET status = 'saiu', encerrado_em = NOW(), atualizado_em = NOW()
                 WHERE id = :id"
            );
            $stmtUp->bindValue(':id', $shareId, PDO::PARAM_INT);
            $stmtUp->execute();

            $stmtPer = $this->db->prepare(
                "UPDATE compartilhamento_periodos
                 SET encerrado_em = NOW()
                 WHERE compartilhamento_id = :comp_id AND encerrado_em IS NULL"
            );
            $stmtPer->bindValue(':comp_id', $shareId, PDO::PARAM_INT);
            $stmtPer->execute();

            $this->logAudit('UPDATE', $shareId, ['status' => 'ativo'], ['status' => 'saiu'], $participanteId);
            $this->db->commit();

            return ['success' => true, 'message' => 'Você saiu do compartilhamento com sucesso.'];
        } catch (Throwable $e) {
            $this->db->rollBack();
            LoggerHelper::error($e, ['context' => 'Share::leave']);
            return ['success' => false, 'message' => 'Não foi possível sair do compartilhamento.'];
        }
    }

    /**
     * Atualiza a preferência individual do participante de exibir ou ocultar o recurso compartilhado no Dashboard dele.
     */
    public function updateParticipantDashboardVisibility(int $shareId, bool $exibirDashboard, int $participanteId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM compartilhamentos
             WHERE id = :id AND participante_id = :part_id AND status = 'ativo'
             LIMIT 1"
        );
        $stmt->bindValue(':id', $shareId, PDO::PARAM_INT);
        $stmt->bindValue(':part_id', $participanteId, PDO::PARAM_INT);
        $stmt->execute();
        $share = $stmt->fetch();

        if (!$share) {
            return ['success' => false, 'message' => 'Compartilhamento não encontrado.'];
        }

        $stmtUp = $this->db->prepare(
            "UPDATE compartilhamentos SET exibir_dashboard = :exibir, atualizado_em = NOW() WHERE id = :id"
        );
        $stmtUp->bindValue(':exibir', $exibirDashboard, PDO::PARAM_BOOL);
        $stmtUp->bindValue(':id', $shareId, PDO::PARAM_INT);
        $stmtUp->execute();

        return ['success' => true, 'message' => 'Preferência de exibição no Dashboard atualizada.'];
    }

    private function findOwnedResource(string $recursoTipo, int $recursoId, int $proprietarioId): ?array
    {
        $table = $recursoTipo === 'investimento' ? 'investimentos' : 'contas';
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$table}` WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NULL LIMIT 1"
        );
        $stmt->bindValue(':id', $recursoId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $proprietarioId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }
}
