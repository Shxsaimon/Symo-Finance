<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Model de Investimentos (Investment)
 *
 * Gerencia posições de investimento (próprias e compartilhadas) e seus movimentos.
 * Aportes e resgates movimentam a conta vinculada de forma atômica; rendimentos
 * e ajustes alteram somente a posição. Participantes de investimentos compartilhados
 * só podem realizar ou alterar movimentos caso possuam permissão no investimento
 * E acesso ativo com permissão de lançamento à conta bancária vinculada.
 */
class Investment extends Model
{
    protected string $tabela = 'investimentos';

    public const CLASSES = [
        'renda_fixa'     => 'Renda fixa',
        'renda_variavel' => 'Renda variável',
        'fundos'         => 'Fundos',
        'previdencia'    => 'Previdência',
        'criptoativos'   => 'Criptoativos',
        'outros'         => 'Outros',
        'nao_informado'  => 'Não informado',
    ];

    public const TIPOS_MOVIMENTO = [
        'aporte'     => 'Aporte',
        'resgate'    => 'Resgate',
        'rendimento' => 'Rendimento',
        'ajuste'     => 'Atualização de valor',
    ];

    public function find(int $id): ?array
    {
        $userId = (int) AuthHelper::id();

        $stmt = $this->db->prepare(
            "SELECT i.*,
                    c.nome AS conta_nome, c.cor AS conta_cor, c.status AS conta_status,
                    u.nome AS proprietario_nome, u.email AS proprietario_email,
                    cp.id AS compartilhamento_id,
                    cp.permissao AS compartilhamento_permissao,
                    cp.exibir_dashboard AS compartilhamento_exibir_dashboard,
                    CASE WHEN i.usuario_id = :uid_owner THEN 1 ELSE 0 END AS eh_proprietario,
                    CASE WHEN i.usuario_id = :uid_perm THEN 'proprietario' ELSE cp.permissao END AS permissao_acesso,
                    CASE
                        WHEN i.usuario_id = :uid_dash AND i.exibir_dashboard = TRUE THEN 1
                        WHEN i.usuario_id <> :uid_dash_part AND cp.exibir_dashboard = TRUE THEN 1
                        ELSE 0
                    END AS exibir_no_meu_dashboard
             FROM investimentos i
             INNER JOIN contas c ON c.id = i.conta_id
             INNER JOIN usuarios u ON u.id = i.usuario_id
             LEFT JOIN compartilhamentos cp
                 ON cp.recurso_tipo = 'investimento'
                AND cp.recurso_id = i.id
                AND cp.participante_id = :uid_join
                AND cp.status = 'ativo'
             WHERE i.id = :id
               AND i.excluido_em IS NULL
               AND (i.usuario_id = :uid_where OR cp.id IS NOT NULL)
             LIMIT 1"
        );
        $stmt->bindValue(':uid_owner', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_perm', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_dash', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_dash_part', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_join', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':uid_where', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $row['acesso_conta_vinculada'] = $this->hasLaunchAccessToLinkedAccount((int) $row['conta_id'], $userId);
        $row['pode_movimentar'] = ((int) $row['eh_proprietario'] === 1)
            || (in_array($row['permissao_acesso'], ['proprios', 'total'], true) && $row['acesso_conta_vinculada']);

        if ((int) $row['eh_proprietario'] === 1) {
            $row['participantes'] = (new Share())->getParticipantsForResource('investimento', (int) $row['id']);
        } else {
            $row['participantes'] = [];
        }

        return $row;
    }

    /**
     * Verifica se o usuário é proprietário ou participante ativo com permissão 'proprios' ou 'total'
     * na conta bancária vinculada ao investimento.
     */
    public function hasLaunchAccessToLinkedAccount(int $contaId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1
             FROM contas c
             LEFT JOIN compartilhamentos cp
                 ON cp.recurso_tipo = 'conta'
                AND cp.recurso_id = c.id
                AND cp.participante_id = :uid_join
                AND cp.status = 'ativo'
             WHERE c.id = :conta_id
               AND c.excluido_em IS NULL
               AND (
                   c.usuario_id = :uid_owner
                   OR cp.permissao IN ('proprios', 'total')
               )
             LIMIT 1"
        );
        $stmt->bindValue(':uid_join', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':conta_id', $contaId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_owner', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }

    public function findWithTrashed(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT i.*, c.nome AS conta_nome, c.cor AS conta_cor, c.status AS conta_status
             FROM investimentos i
             LEFT JOIN contas c ON c.id = i.conta_id
             WHERE i.id = :id AND i.usuario_id = :usuario_id
             LIMIT 1'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', (int) AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * @param array<string, mixed> $filters conta_id, custodiante, classe, finalidade, status
     * @return array<int, array<string, mixed>>
     */
    public function getFiltered(array $filters): array
    {
        $userId = (int) AuthHelper::id();

        $sql = "SELECT i.*,
                       c.nome AS conta_nome, c.cor AS conta_cor, c.status AS conta_status,
                       u.nome AS proprietario_nome,
                       cp.id AS compartilhamento_id,
                       cp.permissao AS compartilhamento_permissao,
                       cp.exibir_dashboard AS compartilhamento_exibir_dashboard,
                       CASE WHEN i.usuario_id = :uid_owner THEN 1 ELSE 0 END AS eh_proprietario,
                       CASE WHEN i.usuario_id = :uid_perm THEN 'proprietario' ELSE cp.permissao END AS permissao_acesso
                FROM investimentos i
                INNER JOIN contas c ON c.id = i.conta_id
                INNER JOIN usuarios u ON u.id = i.usuario_id
                LEFT JOIN compartilhamentos cp
                    ON cp.recurso_tipo = 'investimento'
                   AND cp.recurso_id = i.id
                   AND cp.participante_id = :uid_join
                   AND cp.status = 'ativo'
                WHERE i.excluido_em IS NULL
                  AND (i.usuario_id = :uid_where OR cp.id IS NOT NULL)";

        $params = [
            ':uid_owner' => [$userId, PDO::PARAM_INT],
            ':uid_perm'  => [$userId, PDO::PARAM_INT],
            ':uid_join'  => [$userId, PDO::PARAM_INT],
            ':uid_where' => [$userId, PDO::PARAM_INT],
        ];

        $status = $filters['status'] ?? 'ativo';
        if (in_array($status, ['ativo', 'encerrado'], true)) {
            $sql .= ' AND i.status = :status';
            $params[':status'] = [$status, PDO::PARAM_STR];
        }
        if (!empty($filters['conta_id'])) {
            $sql .= ' AND i.conta_id = :conta_id';
            $params[':conta_id'] = [(int) $filters['conta_id'], PDO::PARAM_INT];
        }
        if (!empty($filters['classe']) && isset(self::CLASSES[$filters['classe']])) {
            $sql .= ' AND i.classe = :classe';
            $params[':classe'] = [(string) $filters['classe'], PDO::PARAM_STR];
        }
        if (!empty($filters['custodiante'])) {
            $sql .= ' AND LOWER(i.custodiante) LIKE :custodiante';
            $params[':custodiante'] = ['%' . mb_strtolower(trim((string) $filters['custodiante'])) . '%', PDO::PARAM_STR];
        }
        if (!empty($filters['finalidade'])) {
            $sql .= ' AND LOWER(i.finalidade) LIKE :finalidade';
            $params[':finalidade'] = ['%' . mb_strtolower(trim((string) $filters['finalidade'])) . '%', PDO::PARAM_STR];
        }
        $sql .= ' ORDER BY eh_proprietario DESC, i.nome ASC';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $name => [$value, $type]) {
            $stmt->bindValue($name, $value, $type);
        }
        $stmt->execute();
        $investimentos = $stmt->fetchAll();

        $ownedIds = [];
        foreach ($investimentos as $inv) {
            if ((int) $inv['eh_proprietario'] === 1) {
                $ownedIds[] = (int) $inv['id'];
            }
        }
        $participantsByInv = (new Share())->getParticipantsByResourceIds('investimento', $ownedIds);
        foreach ($investimentos as &$inv) {
            $invId = (int) $inv['id'];
            $inv['participantes'] = $participantsByInv[$invId] ?? [];
        }
        unset($inv);

        return $investimentos;
    }

    /**
     * Contas elegíveis para vínculo ao criar/editar investimento (somente contas próprias do proprietário).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getEligibleAccounts(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM contas
             WHERE usuario_id = :usuario_id AND status = 'ativa' AND excluido_em IS NULL AND tipo <> 'investimento'
             ORDER BY nome ASC"
        );
        $stmt->bindValue(':usuario_id', (int) AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Total das posições ativas do usuário (próprias e compartilhadas ativas).
     *
     * @param bool $forDashboardOnly Se true, aplica a preferência exibir_dashboard (somente no Dashboard).
     */
    public function getTotalInvested(bool $forDashboardOnly = false): float
    {
        $userId = (int) AuthHelper::id();

        if ($forDashboardOnly) {
            $sql = "SELECT COALESCE(SUM(i.saldo_atual), 0)
                    FROM investimentos i
                    LEFT JOIN compartilhamentos cp
                        ON cp.recurso_tipo = 'investimento'
                       AND cp.recurso_id = i.id
                       AND cp.participante_id = :uid_join
                       AND cp.status = 'ativo'
                    WHERE i.status = 'ativo'
                      AND i.excluido_em IS NULL
                      AND (
                          (i.usuario_id = :uid_owner AND i.exibir_dashboard = TRUE)
                          OR (cp.id IS NOT NULL AND cp.exibir_dashboard = TRUE)
                      )";
        } else {
            $sql = "SELECT COALESCE(SUM(i.saldo_atual), 0)
                    FROM investimentos i
                    LEFT JOIN compartilhamentos cp
                        ON cp.recurso_tipo = 'investimento'
                       AND cp.recurso_id = i.id
                       AND cp.participante_id = :uid_join
                       AND cp.status = 'ativo'
                    WHERE i.status = 'ativo'
                      AND i.excluido_em IS NULL
                      AND (i.usuario_id = :uid_owner OR cp.id IS NOT NULL)";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid_join', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_owner', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (float) $stmt->fetchColumn();
    }

    public function getActiveCount(): int
    {
        $userId = (int) AuthHelper::id();
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM investimentos i
             LEFT JOIN compartilhamentos cp
                 ON cp.recurso_tipo = 'investimento'
                AND cp.recurso_id = i.id
                AND cp.participante_id = :uid_join
                AND cp.status = 'ativo'
             WHERE i.status = 'ativo' AND i.excluido_em IS NULL
               AND (i.usuario_id = :uid_owner OR cp.id IS NOT NULL)"
        );
        $stmt->bindValue(':uid_join', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_owner', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /** Soma dos rendimentos do mês informado (respeitando períodos de compartilhamento para participantes). */
    public function getMonthlyYield(int $mes, int $ano): float
    {
        $userId = (int) AuthHelper::id();
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = (new DateTimeImmutable($inicio))->modify('+1 month')->format('Y-m-d');

        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(m.valor), 0)
             FROM investimento_movimentos m
             INNER JOIN investimentos i ON i.id = m.investimento_id AND i.excluido_em IS NULL
             LEFT JOIN compartilhamentos cp
                 ON cp.recurso_tipo = 'investimento'
                AND cp.recurso_id = i.id
                AND cp.participante_id = :uid_join
                AND cp.status = 'ativo'
             WHERE m.tipo = 'rendimento'
               AND m.excluido_em IS NULL
               AND m.data_movimento >= :inicio
               AND m.data_movimento < :fim
               AND (
                   i.usuario_id = :uid_owner
                   OR (
                       cp.id IS NOT NULL
                       AND EXISTS (
                           SELECT 1 FROM compartilhamento_periodos p
                           WHERE p.recurso_tipo = 'investimento'
                             AND p.recurso_id = i.id
                             AND p.participante_id = :uid_period
                             AND m.criado_em >= p.aceito_em
                             AND (p.encerrado_em IS NULL OR m.criado_em <= p.encerrado_em)
                       )
                   )
               )"
        );
        $stmt->bindValue(':uid_join', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':inicio', $inicio, PDO::PARAM_STR);
        $stmt->bindValue(':fim', $fim, PDO::PARAM_STR);
        $stmt->bindValue(':uid_owner', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_period', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (float) $stmt->fetchColumn();
    }

    /**
     * Retorna os movimentos de um investimento respeitando os períodos de visibilidade caso seja participante.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMovements(int $investimentoId): array
    {
        $userId = (int) AuthHelper::id();

        $stmt = $this->db->prepare(
            "SELECT m.*, c.nome AS conta_nome, u.nome AS usuario_nome
             FROM investimento_movimentos m
             INNER JOIN investimentos i ON i.id = m.investimento_id
             LEFT JOIN contas c ON c.id = m.conta_id
             LEFT JOIN usuarios u ON u.id = m.usuario_id
             LEFT JOIN compartilhamentos cp
                 ON cp.recurso_tipo = 'investimento'
                AND cp.recurso_id = i.id
                AND cp.participante_id = :uid_join
                AND cp.status = 'ativo'
             WHERE m.investimento_id = :investimento_id
               AND m.excluido_em IS NULL
               AND (
                   i.usuario_id = :uid_owner
                   OR (
                       cp.id IS NOT NULL
                       AND EXISTS (
                           SELECT 1 FROM compartilhamento_periodos p
                           WHERE p.recurso_tipo = 'investimento'
                             AND p.recurso_id = i.id
                             AND p.participante_id = :uid_period
                             AND m.criado_em >= p.aceito_em
                             AND (p.encerrado_em IS NULL OR m.criado_em <= p.encerrado_em)
                       )
                   )
               )
             ORDER BY m.data_movimento DESC, m.id DESC"
        );
        $stmt->bindValue(':uid_join', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':investimento_id', $investimentoId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_owner', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_period', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Resumo de rendimentos por posição no mês (respeitando períodos de visibilidade).
     */
    public function getPositionYield(int $investimentoId, int $mes, int $ano): float
    {
        $userId = (int) AuthHelper::id();
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = (new DateTimeImmutable($inicio))->modify('+1 month')->format('Y-m-d');

        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(m.valor), 0)
             FROM investimento_movimentos m
             INNER JOIN investimentos i ON i.id = m.investimento_id
             LEFT JOIN compartilhamentos cp
                 ON cp.recurso_tipo = 'investimento'
                AND cp.recurso_id = i.id
                AND cp.participante_id = :uid_join
                AND cp.status = 'ativo'
             WHERE m.investimento_id = :investimento_id
               AND m.tipo = 'rendimento'
               AND m.excluido_em IS NULL
               AND m.data_movimento >= :inicio
               AND m.data_movimento < :fim
               AND (
                   i.usuario_id = :uid_owner
                   OR (
                       cp.id IS NOT NULL
                       AND EXISTS (
                           SELECT 1 FROM compartilhamento_periodos p
                           WHERE p.recurso_tipo = 'investimento'
                             AND p.recurso_id = i.id
                             AND p.participante_id = :uid_period
                             AND m.criado_em >= p.aceito_em
                             AND (p.encerrado_em IS NULL OR m.criado_em <= p.encerrado_em)
                       )
                   )
               )"
        );
        $stmt->bindValue(':uid_join', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':investimento_id', $investimentoId, PDO::PARAM_INT);
        $stmt->bindValue(':inicio', $inicio, PDO::PARAM_STR);
        $stmt->bindValue(':fim', $fim, PDO::PARAM_STR);
        $stmt->bindValue(':uid_owner', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_period', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (float) $stmt->fetchColumn();
    }

    /**
     * Cadastra uma posição. O saldo inicial não movimenta nenhuma conta.
     *
     * @param array<string, mixed> $data
     */
    public function createPosition(array $data, int $userId): int
    {
        $dados = $this->normalizePosition($data);
        $this->assertEligibleAccount($dados['conta_id'], $userId);

        $saldoInicial = round((float) ($data['saldo_inicial'] ?? 0.0), 2);
        if ($saldoInicial < 0) {
            throw new InvalidArgumentException('O saldo inicial não pode ser negativo.');
        }
        $dataSaldo = $this->normalizeDate($data['data_saldo_inicial'] ?? null);
        $exibirDashboard = (bool) ($data['exibir_dashboard'] ?? true);

        $stmt = $this->db->prepare(
            'INSERT INTO investimentos (usuario_id, conta_id, nome, custodiante, classe, finalidade,
                                        saldo_inicial, data_saldo_inicial, saldo_atual, exibir_dashboard, status, criado_em, atualizado_em)
             VALUES (:usuario_id, :conta_id, :nome, :custodiante, :classe, :finalidade,
                     :saldo_inicial, :data_saldo, :saldo_atual, :exibir_dashboard, :status, NOW(), NOW())'
        );
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':conta_id', $dados['conta_id'], PDO::PARAM_INT);
        $stmt->bindValue(':nome', $dados['nome'], PDO::PARAM_STR);
        $stmt->bindValue(':custodiante', $dados['custodiante'], $dados['custodiante'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':classe', $dados['classe'], PDO::PARAM_STR);
        $stmt->bindValue(':finalidade', $dados['finalidade'], $dados['finalidade'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':saldo_inicial', $saldoInicial);
        $stmt->bindValue(':data_saldo', $dataSaldo, PDO::PARAM_STR);
        $stmt->bindValue(':saldo_atual', $saldoInicial);
        $stmt->bindValue(':exibir_dashboard', $exibirDashboard, PDO::PARAM_BOOL);
        $stmt->bindValue(':status', 'ativo', PDO::PARAM_STR);
        $stmt->execute();

        $id = (int) $this->db->lastInsertId();
        $this->logAudit('INSERT', $id, null, $dados + [
            'saldo_inicial'      => $saldoInicial,
            'data_saldo_inicial' => $dataSaldo,
            'exibir_dashboard'   => $exibirDashboard,
        ], $userId);

        return $id;
    }

    /**
     * Atualiza dados cadastrais e a conta vinculada (exclusivo do proprietário).
     *
     * @param array<string, mixed> $data
     */
    public function updatePosition(int $id, array $data, int $userId): void
    {
        $antiga = $this->find($id);
        if (!$antiga || (int) $antiga['usuario_id'] !== $userId) {
            throw new InvalidArgumentException('Investimento não encontrado ou você não é o proprietário.');
        }

        $dados = $this->normalizePosition($data);
        if ($dados['conta_id'] !== (int) $antiga['conta_id']) {
            $this->assertEligibleAccount($dados['conta_id'], $userId);
        }
        $exibirDashboard = array_key_exists('exibir_dashboard', $data)
            ? (bool) $data['exibir_dashboard']
            : (bool) ($antiga['exibir_dashboard'] ?? true);

        $stmt = $this->db->prepare(
            'UPDATE investimentos
             SET conta_id = :conta_id, nome = :nome, custodiante = :custodiante, classe = :classe,
                 finalidade = :finalidade, exibir_dashboard = :exibir_dashboard, atualizado_em = NOW()
             WHERE id = :id AND usuario_id = :usuario_id'
        );
        $stmt->bindValue(':conta_id', $dados['conta_id'], PDO::PARAM_INT);
        $stmt->bindValue(':nome', $dados['nome'], PDO::PARAM_STR);
        $stmt->bindValue(':custodiante', $dados['custodiante'], $dados['custodiante'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':classe', $dados['classe'], PDO::PARAM_STR);
        $stmt->bindValue(':finalidade', $dados['finalidade'], $dados['finalidade'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':exibir_dashboard', $exibirDashboard, PDO::PARAM_BOOL);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $this->logAudit('UPDATE', $id, [
            'conta_id' => (int) $antiga['conta_id'], 'nome' => $antiga['nome'], 'custodiante' => $antiga['custodiante'],
            'classe' => $antiga['classe'], 'finalidade' => $antiga['finalidade'],
            'exibir_dashboard' => (bool) ($antiga['exibir_dashboard'] ?? true),
        ], $dados + ['exibir_dashboard' => $exibirDashboard], $userId);
    }

    public function reactivate(int $id, int $userId): void
    {
        $posicao = $this->find($id);
        if (!$posicao || (int) $posicao['usuario_id'] !== $userId) {
            throw new InvalidArgumentException('Investimento não encontrado ou você não é o proprietário.');
        }
        if ($posicao['status'] === 'ativo') {
            return;
        }

        $stmt = $this->db->prepare(
            "UPDATE investimentos SET status = 'ativo', atualizado_em = NOW() WHERE id = :id AND usuario_id = :usuario_id"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $this->logAudit('UPDATE', $id, ['status' => 'encerrado'], ['status' => 'ativo'], $userId);
    }

    public function deactivate(int $id, int $userId): void
    {
        $posicao = $this->find($id);
        if (!$posicao || (int) $posicao['usuario_id'] !== $userId) {
            throw new InvalidArgumentException('Investimento não encontrado ou você não é o proprietário.');
        }
        if ($posicao['status'] === 'encerrado') {
            return;
        }
        if (round((float) $posicao['saldo_atual'], 2) > 0) {
            throw new InvalidArgumentException('Só é possível desativar um investimento com valor atual zerado.');
        }

        $stmt = $this->db->prepare(
            "UPDATE investimentos SET status = 'encerrado', atualizado_em = NOW() WHERE id = :id AND usuario_id = :usuario_id"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $this->logAudit('UPDATE', $id, ['status' => 'ativo'], ['status' => 'encerrado'], $userId);
    }

    /**
     * Exclui logicamente uma posição de investimento (exclusivo do proprietário).
     * Exige que o saldo atual seja exatamente zero.
     */
    public function deletePosition(int $id, int $userId): array
    {
        $this->db->beginTransaction();
        try {
            $posicao = $this->lockOwnedPosition($id, $userId);
            if (round((float) $posicao['saldo_atual'], 2) > 0) {
                throw new InvalidArgumentException('Para excluir o investimento, resgate ou ajuste seu saldo para R$ 0,00 primeiro.');
            }

            $now = date('Y-m-d H:i:s');

            $stmt = $this->db->prepare(
                'UPDATE investimentos SET excluido_em = :excluido_em, excluido_por = :por, atualizado_em = NOW()
                 WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NULL'
            );
            $stmt->bindValue(':excluido_em', $now, PDO::PARAM_STR);
            $stmt->bindValue(':por', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            // Marca todos os movimentos ativos vinculados com o mesmo timestamp da exclusão do pai
            $stmtMov = $this->db->prepare(
                'UPDATE investimento_movimentos SET excluido_em = :excluido_em, excluido_por = :por, atualizado_em = NOW()
                 WHERE investimento_id = :investimento_id AND excluido_em IS NULL'
            );
            $stmtMov->bindValue(':excluido_em', $now, PDO::PARAM_STR);
            $stmtMov->bindValue(':por', $userId, PDO::PARAM_INT);
            $stmtMov->bindValue(':investimento_id', $id, PDO::PARAM_INT);
            $stmtMov->execute();

            $this->logAudit('SOFT_DELETE', $id, $posicao, [
                'excluido_em'  => $now,
                'excluido_por' => $userId,
            ], $userId);

            $this->db->commit();
            return ['success' => true, 'message' => 'Investimento excluído com sucesso e enviado para a lixeira.'];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Registra um movimento de forma atômica.
     * Exige que o autor seja o proprietário OU participante ativo com permissão 'proprios'/'total'
     * E acesso com permissão de lançamento à conta bancária vinculada.
     *
     * @param array<string, mixed> $data tipo, valor (ajuste: novo_total), data_movimento, observacoes
     */
    public function addMovement(int $investimentoId, array $data, int $userId): int
    {
        $tipo = (string) ($data['tipo'] ?? '');
        if (!isset(self::TIPOS_MOVIMENTO[$tipo])) {
            throw new InvalidArgumentException('Tipo de movimento inválido.');
        }
        $dataMov = $this->normalizeDate($data['data_movimento'] ?? null);
        $obs = !empty($data['observacoes']) ? trim((string) $data['observacoes']) : null;

        $this->db->beginTransaction();
        try {
            $posicao = $this->lockAccessiblePositionForMovement($investimentoId, $userId);
            if ($posicao['status'] !== 'ativo') {
                throw new InvalidArgumentException('Reative o investimento antes de registrar novos movimentos.');
            }

            if ($tipo === 'ajuste') {
                $novoTotal = round((float) ($data['novo_total'] ?? 0.0), 2);
                if ($novoTotal < 0) {
                    throw new InvalidArgumentException('O novo valor total não pode ser negativo.');
                }
                $valor = round($novoTotal - (float) $posicao['saldo_atual'], 2);
                if ($valor == 0.0) {
                    throw new InvalidArgumentException('O novo valor é igual ao saldo atual do investimento.');
                }
            } else {
                $valor = round((float) ($data['valor'] ?? 0.0), 2);
                if ($valor <= 0) {
                    throw new InvalidArgumentException('O valor deve ser maior que zero.');
                }
            }

            $contaId = null;
            if (in_array($tipo, ['aporte', 'resgate'], true)) {
                $contaId = (int) $posicao['conta_id'];
                $this->assertActiveLinkedAccount($contaId);
                $this->moveAccount($contaId, $tipo === 'aporte' ? -$valor : $valor);
            }

            $stmt = $this->db->prepare(
                'INSERT INTO investimento_movimentos (usuario_id, investimento_id, conta_id, tipo, valor, data_movimento, observacoes, criado_em, atualizado_em)
                 VALUES (:usuario_id, :investimento_id, :conta_id, :tipo, :valor, :data_mov, :obs, NOW(), NOW())'
            );
            $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':investimento_id', $investimentoId, PDO::PARAM_INT);
            $stmt->bindValue(':conta_id', $contaId, $contaId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->bindValue(':valor', $valor);
            $stmt->bindValue(':data_mov', $dataMov, PDO::PARAM_STR);
            $stmt->bindValue(':obs', $obs, $obs !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->execute();
            $movId = (int) $this->db->lastInsertId();

            $this->recalculate($investimentoId);
            $this->auditMovement('INSERT', $movId, null, [
                'investimento_id' => $investimentoId, 'conta_id' => $contaId, 'tipo' => $tipo,
                'valor' => $valor, 'data_movimento' => $dataMov, 'observacoes' => $obs,
            ], $userId);

            $this->db->commit();

            return $movId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Edita valor, data e observações de um movimento, recalculando posição e conta.
     *
     * @param array<string, mixed> $data valor (ajuste aceita sinal), data_movimento, observacoes
     */
    public function updateMovement(int $movId, array $data, int $userId): int
    {
        $this->db->beginTransaction();
        try {
            $mov = $this->lockMovementById($movId);
            $investimentoId = (int) $mov['investimento_id'];
            $posicao = $this->lockAccessiblePositionForMovement($investimentoId, $userId);
            $this->assertCanMutateMovement($mov, $posicao, $userId);

            $valor = round((float) ($data['valor'] ?? 0.0), 2);
            if ($mov['tipo'] === 'ajuste') {
                if ($valor == 0.0) {
                    throw new InvalidArgumentException('O valor do ajuste deve ser diferente de zero.');
                }
            } elseif ($valor <= 0) {
                throw new InvalidArgumentException('O valor deve ser maior que zero.');
            }
            $dataMov = $this->normalizeDate($data['data_movimento'] ?? null);
            $obs = !empty($data['observacoes']) ? trim((string) $data['observacoes']) : null;

            if (in_array($mov['tipo'], ['aporte', 'resgate'], true)) {
                $contaId = (int) $mov['conta_id'];
                $this->assertActiveLinkedAccount($contaId);
                $diferenca = $valor - (float) $mov['valor'];
                $this->moveAccount($contaId, $mov['tipo'] === 'aporte' ? -$diferenca : $diferenca);
            }

            $stmt = $this->db->prepare(
                'UPDATE investimento_movimentos SET valor = :valor, data_movimento = :data_mov, observacoes = :obs, atualizado_em = NOW()
                 WHERE id = :id'
            );
            $stmt->bindValue(':valor', $valor);
            $stmt->bindValue(':data_mov', $dataMov, PDO::PARAM_STR);
            $stmt->bindValue(':obs', $obs, $obs !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':id', $movId, PDO::PARAM_INT);
            $stmt->execute();

            $this->recalculate($investimentoId);
            $this->auditMovement('UPDATE', $movId, [
                'valor' => (float) $mov['valor'], 'data_movimento' => $mov['data_movimento'], 'observacoes' => $mov['observacoes'],
            ], ['valor' => $valor, 'data_movimento' => $dataMov, 'observacoes' => $obs], $userId);

            $this->db->commit();

            return $investimentoId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Exclui logicamente um movimento, revertendo o efeito na conta e recalculando a posição.
     */
    public function deleteMovement(int $movId, int $userId): int
    {
        $this->db->beginTransaction();
        try {
            $mov = $this->lockMovementById($movId);
            $investimentoId = (int) $mov['investimento_id'];
            $posicao = $this->lockAccessiblePositionForMovement($investimentoId, $userId);
            $this->assertCanMutateMovement($mov, $posicao, $userId);

            if (in_array($mov['tipo'], ['aporte', 'resgate'], true)) {
                $valor = (float) $mov['valor'];
                $this->moveAccount((int) $mov['conta_id'], $mov['tipo'] === 'aporte' ? $valor : -$valor);
            }

            $stmt = $this->db->prepare(
                'UPDATE investimento_movimentos SET excluido_em = NOW(), excluido_por = :por, atualizado_em = NOW()
                 WHERE id = :id'
            );
            $stmt->bindValue(':por', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':id', $movId, PDO::PARAM_INT);
            $stmt->execute();

            $this->recalculate($investimentoId);
            $this->auditMovement('SOFT_DELETE', $movId, [
                'tipo' => $mov['tipo'], 'valor' => (float) $mov['valor'], 'data_movimento' => $mov['data_movimento'],
            ], null, $userId);

            $this->db->commit();

            return $investimentoId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function hasPositionsForAccount(int $contaId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT (SELECT COUNT(*) FROM investimentos WHERE conta_id = :conta_id AND excluido_em IS NULL)
                  + (SELECT COUNT(*) FROM investimento_movimentos WHERE conta_id = :conta_mov AND excluido_em IS NULL)'
        );
        $stmt->bindValue(':conta_id', $contaId, PDO::PARAM_INT);
        $stmt->bindValue(':conta_mov', $contaId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{conta_id: int, nome: string, custodiante: ?string, classe: string, finalidade: ?string}
     */
    private function normalizePosition(array $data): array
    {
        $nome = trim((string) ($data['nome'] ?? ''));
        if ($nome === '' || mb_strlen($nome) > 120) {
            throw new InvalidArgumentException('Informe um nome de até 120 caracteres.');
        }
        $contaId = (int) ($data['conta_id'] ?? 0);
        if ($contaId <= 0) {
            throw new InvalidArgumentException('Selecione a conta vinculada.');
        }
        $classe = (string) ($data['classe'] ?? 'nao_informado');
        if (!isset(self::CLASSES[$classe])) {
            $classe = 'nao_informado';
        }
        $custodiante = trim((string) ($data['custodiante'] ?? ''));
        $finalidade = trim((string) ($data['finalidade'] ?? ''));
        if (mb_strlen($custodiante) > 120 || mb_strlen($finalidade) > 120) {
            throw new InvalidArgumentException('Custodiante e finalidade aceitam até 120 caracteres.');
        }

        return [
            'conta_id'    => $contaId,
            'nome'        => $nome,
            'custodiante' => $custodiante !== '' ? $custodiante : null,
            'classe'      => $classe,
            'finalidade'  => $finalidade !== '' ? $finalidade : null,
        ];
    }

    private function normalizeDate(mixed $value): string
    {
        $texto = trim((string) ($value ?? ''));
        if ($texto === '') {
            return date('Y-m-d');
        }
        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $texto);
        if ($data === false || $data->format('Y-m-d') !== $texto) {
            throw new InvalidArgumentException('Data inválida.');
        }

        return $texto;
    }

    private function assertEligibleAccount(int $contaId, int $userId): void
    {
        $stmt = $this->db->prepare(
            "SELECT tipo, status FROM contas WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NULL"
        );
        $stmt->bindValue(':id', $contaId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $conta = $stmt->fetch();

        if (!$conta || $conta['status'] !== 'ativa' || $conta['tipo'] === 'investimento') {
            throw new InvalidArgumentException('Selecione uma conta ativa de sua propriedade que não seja do tipo Investimento.');
        }
    }

    private function assertActiveLinkedAccount(int $contaId): void
    {
        $stmt = $this->db->prepare(
            "SELECT status FROM contas WHERE id = :id AND excluido_em IS NULL"
        );
        $stmt->bindValue(':id', $contaId, PDO::PARAM_INT);
        $stmt->execute();
        $status = $stmt->fetchColumn();

        if ($status !== 'ativa') {
            throw new InvalidArgumentException('A conta vinculada está inativa. Reative-a ou troque a conta do investimento.');
        }
    }

    /** @return array<string, mixed> */
    private function lockOwnedPosition(int $id, int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM investimentos WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NULL FOR UPDATE'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        if (!$row) {
            throw new InvalidArgumentException('Investimento não encontrado.');
        }

        return $row;
    }

    /**
     * Bloqueia a posição de investimento e valida se o usuário é o proprietário
     * OU participante ativo com permissão de lançamento ('proprios' ou 'total')
     * E acesso com permissão de lançamento à conta bancária vinculada.
     *
     * @return array<string, mixed>
     */
    private function lockAccessiblePositionForMovement(int $id, int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM investimentos WHERE id = :id AND excluido_em IS NULL FOR UPDATE'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        if (!$row) {
            throw new InvalidArgumentException('Investimento não encontrado.');
        }

        if ((int) $row['usuario_id'] === $userId) {
            $row['permissao_acesso'] = 'proprietario';
            return $row;
        }

        $share = (new Share())->getActiveShare('investimento', $id, $userId);
        if (!$share || !in_array($share['permissao'], ['proprios', 'total'], true)) {
            throw new InvalidArgumentException('Você não possui permissão para registrar ou alterar movimentos neste investimento.');
        }

        if (!$this->hasLaunchAccessToLinkedAccount((int) $row['conta_id'], $userId)) {
            throw new InvalidArgumentException('Para realizar ajustes ou movimentos neste investimento, você também precisa ter acesso com permissão de lançamento à conta bancária vinculada.');
        }

        $row['permissao_acesso'] = (string) $share['permissao'];
        return $row;
    }

    /**
     * Valida se o usuário pode editar ou excluir um movimento específico do investimento.
     *
     * @param array<string, mixed> $mov
     * @param array<string, mixed> $posicao
     */
    private function assertCanMutateMovement(array $mov, array $posicao, int $userId): void
    {
        if ((int) $posicao['usuario_id'] === $userId) {
            return;
        }

        $shareModel = new Share();
        if (!$shareModel->isTimestampWithinParticipantPeriods('investimento', (int) $posicao['id'], $userId, (string) $mov['criado_em'])) {
            throw new InvalidArgumentException('Este movimento pertence a um período fora da sua participação no investimento.');
        }

        $permissao = (string) ($posicao['permissao_acesso'] ?? 'visualizar');
        if ($permissao === 'total') {
            return;
        }
        if ($permissao === 'proprios' && (int) $mov['usuario_id'] === $userId) {
            return;
        }

        throw new InvalidArgumentException('Sua permissão neste investimento permite alterar apenas movimentos criados por você.');
    }

    /** @return array<string, mixed> */
    private function lockMovementById(int $id): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM investimento_movimentos WHERE id = :id AND excluido_em IS NULL FOR UPDATE'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        if (!$row) {
            throw new InvalidArgumentException('Movimento não encontrado.');
        }

        return $row;
    }

    /** Aplica variação ao saldo da conta vinculada; débitos nunca deixam o saldo negativo. */
    private function moveAccount(int $contaId, float $delta): void
    {
        if ($delta == 0.0) {
            return;
        }

        $stmt = $this->db->prepare(
            'UPDATE contas SET saldo_atual = saldo_atual + :delta, atualizado_em = NOW()
             WHERE id = :id AND excluido_em IS NULL AND saldo_atual + :delta_minimo >= 0'
        );
        $stmt->bindValue(':delta', $delta);
        $stmt->bindValue(':delta_minimo', $delta < 0 ? $delta : 0.0);
        $stmt->bindValue(':id', $contaId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() !== 1) {
            throw new InvalidArgumentException('Saldo insuficiente na conta vinculada para esta operação.');
        }
    }

    /** Recalcula o saldo da posição a partir do saldo inicial e de todos os movimentos ativos da posição. */
    public function recalculate(int $investimentoId, ?int $userId = null): void
    {
        $stmt = $this->db->prepare(
            "SELECT i.saldo_inicial + COALESCE(SUM(CASE WHEN m.tipo = 'resgate' THEN -m.valor ELSE m.valor END), 0)
             FROM investimentos i
             LEFT JOIN investimento_movimentos m ON m.investimento_id = i.id AND m.excluido_em IS NULL
             WHERE i.id = :id
             GROUP BY i.id, i.saldo_inicial"
        );
        $stmt->bindValue(':id', $investimentoId, PDO::PARAM_INT);
        $stmt->execute();
        $saldo = round((float) $stmt->fetchColumn(), 2);

        if ($saldo < 0) {
            throw new InvalidArgumentException('O saldo do investimento não pode ficar negativo.');
        }

        $upd = $this->db->prepare(
            'UPDATE investimentos SET saldo_atual = :saldo, atualizado_em = NOW() WHERE id = :id'
        );
        $upd->bindValue(':saldo', $saldo);
        $upd->bindValue(':id', $investimentoId, PDO::PARAM_INT);
        $upd->execute();
    }

    /**
     * @param array<string, mixed>|null $antigos
     * @param array<string, mixed>|null $novos
     */
    private function auditMovement(string $acao, int $movId, ?array $antigos, ?array $novos, int $userId): void
    {
        $tabelaOriginal = $this->tabela;
        $this->tabela = 'investimento_movimentos';
        $this->logAudit($acao, $movId, $antigos, $novos, $userId);
        $this->tabela = $tabelaOriginal;
    }
}
