<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Model de Contas Bancárias e Carteiras (Account)
 *
 * Gerencia as contas financeiras (próprias e compartilhadas),
 * saldos, exibição no Dashboard, integridade referencial e trilha de auditoria.
 */
class Account extends Model
{
    protected string $tabela = 'contas';
    protected string $chavePrimaria = 'id';

    /**
     * Retorna as contas ativas apenas de propriedade direta do usuário autenticado
     * (utilizado em Transferências e Cartões de Crédito, onde o compartilhamento não se aplica).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getOwnedActiveAccounts(): array
    {
        $sql = "SELECT c.*,
                       1 AS eh_proprietario,
                       'proprietario' AS permissao_acesso
                FROM `contas` c
                WHERE c.usuario_id = :usuario_id
                  AND c.status = 'ativa'
                  AND c.excluido_em IS NULL
                ORDER BY c.nome ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', (int) AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retorna todas as contas ativas acessíveis ao usuário autenticado (próprias e compartilhadas ativas).
     *
     * @param bool $onlyLaunchable Se true, exclui contas compartilhadas com permissão 'visualizar'
     * @return array<int, array<string, mixed>>
     */
    public function getActiveAccounts(bool $onlyLaunchable = false): array
    {
        $userId = (int) AuthHelper::id();
        $permFilter = $onlyLaunchable ? "AND cp.permissao IN ('proprios', 'total')" : '';

        $sql = "SELECT c.*,
                       u.nome AS proprietario_nome,
                       cp.id AS compartilhamento_id,
                       CASE WHEN c.usuario_id = :uid_owner_flag THEN 1 ELSE 0 END AS eh_proprietario,
                       CASE WHEN c.usuario_id = :uid_perm_flag THEN 'proprietario' ELSE cp.permissao END AS permissao_acesso
                FROM `contas` c
                INNER JOIN `usuarios` u ON u.id = c.usuario_id
                LEFT JOIN `compartilhamentos` cp
                    ON cp.recurso_tipo = 'conta'
                   AND cp.recurso_id = c.id
                   AND cp.participante_id = :uid_join
                   AND cp.status = 'ativo'
                WHERE c.status = 'ativa'
                  AND c.excluido_em IS NULL
                  AND (
                      c.usuario_id = :uid_where
                      OR (cp.id IS NOT NULL {$permFilter})
                  )
                ORDER BY eh_proprietario DESC, c.nome ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid_owner_flag', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_perm_flag', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_join', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_where', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retorna todas as contas do usuário (próprias e compartilhadas ativas) com metadados de compartilhamento
     * e indicador de exibição no Dashboard.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllAccounts(): array
    {
        $userId = (int) AuthHelper::id();

        $sql = "SELECT c.*,
                       u.nome AS proprietario_nome,
                       u.email AS proprietario_email,
                       cp.id AS compartilhamento_id,
                       cp.permissao AS compartilhamento_permissao,
                       cp.exibir_dashboard AS compartilhamento_exibir_dashboard,
                       CASE WHEN c.usuario_id = :uid_owner THEN 1 ELSE 0 END AS eh_proprietario,
                       CASE WHEN c.usuario_id = :uid_perm THEN 'proprietario' ELSE cp.permissao END AS permissao_acesso,
                       CASE
                           WHEN c.tipo = 'investimento' THEN 0
                           WHEN c.usuario_id = :uid_dash AND c.exibir_dashboard = TRUE THEN 1
                           WHEN c.usuario_id <> :uid_dash_part AND cp.exibir_dashboard = TRUE THEN 1
                           ELSE 0
                       END AS considerar_no_saldo_atual
                FROM `contas` c
                INNER JOIN `usuarios` u ON u.id = c.usuario_id
                LEFT JOIN `compartilhamentos` cp
                    ON cp.recurso_tipo = 'conta'
                   AND cp.recurso_id = c.id
                   AND cp.participante_id = :uid_join
                   AND cp.status = 'ativo'
                WHERE c.excluido_em IS NULL
                  AND (c.usuario_id = :uid_where OR cp.id IS NOT NULL)
                ORDER BY eh_proprietario DESC, c.nome ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid_owner', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_perm', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_dash', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_dash_part', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_join', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_where', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $accounts = $stmt->fetchAll();

        // Anexa lista de participantes nas contas em que o usuário é proprietário (ou contagem de compartilhamento)
        $ownedIds = [];
        foreach ($accounts as $acc) {
            if ((int) $acc['eh_proprietario'] === 1) {
                $ownedIds[] = (int) $acc['id'];
            }
        }

        $participantsByAccount = (new Share())->getParticipantsByResourceIds('conta', $ownedIds);
        foreach ($accounts as &$acc) {
            $accId = (int) $acc['id'];
            $acc['participantes'] = $participantsByAccount[$accId] ?? [];
        }
        unset($acc);

        return $accounts;
    }

    /**
     * Calcula o Saldo Atual das contas ativas marcadas para exibição no Dashboard
     * (próprias com exibir_dashboard = TRUE e compartilhadas ativas com exibir_dashboard = TRUE).
     *
     * @return float
     */
    public function getCurrentBalance(): float
    {
        $userId = (int) AuthHelper::id();

        $sql = "SELECT COALESCE(SUM(c.saldo_atual), 0) AS saldo_atual_total
                FROM `contas` c
                LEFT JOIN `compartilhamentos` cp
                    ON cp.recurso_tipo = 'conta'
                   AND cp.recurso_id = c.id
                   AND cp.participante_id = :uid_join
                   AND cp.status = 'ativo'
                WHERE c.status = 'ativa'
                  AND c.excluido_em IS NULL
                  AND c.tipo <> 'investimento'
                  AND (
                      (c.usuario_id = :uid_owner AND c.exibir_dashboard = TRUE)
                      OR (cp.id IS NOT NULL AND cp.exibir_dashboard = TRUE)
                  )";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid_join', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_owner', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        return (float) ($result['saldo_atual_total'] ?? 0.0);
    }

    /**
     * Calcula o saldo das contas ativas legadas do tipo investimento.
     */
    public function getInvestmentBalance(): float
    {
        $sql = "SELECT COALESCE(SUM(saldo_atual), 0) AS saldo_investimentos
                FROM `contas`
                WHERE usuario_id = :usuario_id
                    AND status = 'ativa'
                    AND excluido_em IS NULL
                    AND tipo = 'investimento'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', (int) AuthHelper::id(), PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        return (float) ($result['saldo_investimentos'] ?? 0.0);
    }

    /**
     * Conta as contas ativas que entram no Saldo Atual do Dashboard.
     */
    public function getCurrentBalanceAccountsCount(): int
    {
        $userId = (int) AuthHelper::id();

        $sql = "SELECT COUNT(*)
                FROM `contas` c
                LEFT JOIN `compartilhamentos` cp
                    ON cp.recurso_tipo = 'conta'
                   AND cp.recurso_id = c.id
                   AND cp.participante_id = :uid_join
                   AND cp.status = 'ativo'
                WHERE c.status = 'ativa'
                  AND c.excluido_em IS NULL
                  AND c.tipo <> 'investimento'
                  AND (
                      (c.usuario_id = :uid_owner AND c.exibir_dashboard = TRUE)
                      OR (cp.id IS NOT NULL AND cp.exibir_dashboard = TRUE)
                  )";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid_join', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_owner', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Cadastra uma nova conta bancária ou carteira, inicializando o saldo atual igual ao saldo inicial.
     *
     * @param array<string, mixed> $data
     * @param int $userId ID do usuário autor da ação
     * @return int ID da nova conta criada
     */
    public function createAccount(array $data, int $userId): int
    {
        $saldoInicial = (float) ($data['saldo_inicial'] ?? 0.0);
        $tipo = (string) ($data['tipo'] ?? 'corrente');
        $exibirDashboard = $tipo !== 'investimento' && (bool) ($data['exibir_dashboard'] ?? true);

        $sql = "INSERT INTO `contas` (usuario_id, nome, instituicao, tipo, saldo_inicial, saldo_atual, exibir_dashboard, cor, status, criado_em, atualizado_em)
            VALUES (:usuario_id, :nome, :instituicao, :tipo, :saldo_inicial, :saldo_atual, :exibir_dashboard, :cor, :status, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':nome', trim((string) $data['nome']), PDO::PARAM_STR);
        $stmt->bindValue(':instituicao', !empty($data['instituicao']) ? trim((string) $data['instituicao']) : null, !empty($data['instituicao']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
        $stmt->bindValue(':saldo_inicial', $saldoInicial);
        $stmt->bindValue(':saldo_atual', $saldoInicial);
        $stmt->bindValue(':exibir_dashboard', $exibirDashboard, PDO::PARAM_BOOL);
        $stmt->bindValue(':cor', !empty($data['cor']) ? trim((string) $data['cor']) : '#6F02FF', PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'] ?? 'ativa', PDO::PARAM_STR);
        $stmt->execute();

        $novoId = (int) $this->db->lastInsertId();

        $this->logAudit('INSERT', $novoId, null, [
            'nome'             => $data['nome'],
            'instituicao'      => $data['instituicao'] ?? null,
            'tipo'             => $tipo,
            'saldo_inicial'    => $saldoInicial,
            'saldo_atual'      => $saldoInicial,
            'exibir_dashboard' => $exibirDashboard,
            'status'           => $data['status'] ?? 'ativa'
        ], $userId);

        return $novoId;
    }

    /**
     * Atualiza os dados cadastrais de uma conta existente (exclusivo do proprietário).
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @param int $userId
     * @return bool
     */
    public function updateAccount(int $id, array $data, int $userId): bool
    {
        $contaAntiga = $this->find($id);
        if (!$contaAntiga || (int) $contaAntiga['usuario_id'] !== $userId) {
            return false;
        }

        $tipo = (string) ($data['tipo'] ?? $contaAntiga['tipo']);
        $exibirDashboard = $tipo !== 'investimento'
            && (bool) ($data['exibir_dashboard'] ?? $contaAntiga['exibir_dashboard']);

        $sql = "UPDATE `contas` 
                SET nome = :nome, 
                    instituicao = :instituicao, 
                    tipo = :tipo, 
                    exibir_dashboard = :exibir_dashboard,
                    cor = :cor, 
                    status = :status, 
                    atualizado_em = NOW() 
                WHERE id = :id AND usuario_id = :usuario_id AND excluido_em IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':nome', trim((string) $data['nome']), PDO::PARAM_STR);
        $stmt->bindValue(':instituicao', !empty($data['instituicao']) ? trim((string) $data['instituicao']) : null, !empty($data['instituicao']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
        $stmt->bindValue(':exibir_dashboard', $exibirDashboard, PDO::PARAM_BOOL);
        $stmt->bindValue(':cor', !empty($data['cor']) ? trim((string) $data['cor']) : $contaAntiga['cor'], PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'] ?? $contaAntiga['status'], PDO::PARAM_STR);
        $success = $stmt->execute();

        if ($success) {
            $contaNova = $this->find($id);
            $this->logAudit('UPDATE', $id, $contaAntiga, $contaNova, $userId);
        }

        return $success;
    }

    /**
     * Alterna o status de uma conta entre 'ativa' e 'inativa' (exclusivo do proprietário).
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function toggleStatus(int $id, int $userId): bool
    {
        $conta = $this->find($id);
        if (!$conta || (int) $conta['usuario_id'] !== $userId) {
            return false;
        }

        $novoStatus = ($conta['status'] === 'ativa') ? 'inativa' : 'ativa';

        $sql = "UPDATE `contas` SET status = :status, atualizado_em = NOW() WHERE id = :id AND usuario_id = :usuario_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':status', $novoStatus, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $success = $stmt->execute();

        if ($success) {
            $this->logAudit('UPDATE_STATUS', $id, ['status' => $conta['status']], ['status' => $novoStatus], $userId);
        }

        return $success;
    }

    /**
     * Verifica se a conta possui qualquer transação (de qualquer autor), transferência ou investimento vinculado.
     *
     * @param int $id
     * @return bool
     */
    public function hasTransactions(int $id): bool
    {
        $sqlTrans = "SELECT COUNT(*) as total FROM `transacoes` WHERE conta_id = :id AND excluido_em IS NULL";
        $stmt = $this->db->prepare($sqlTrans);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $resTrans = $stmt->fetch();
        if ((int) ($resTrans['total'] ?? 0) > 0) {
            return true;
        }

        $sqlTransf = "SELECT COUNT(*) as total FROM `transferencias` 
                  WHERE (conta_origem_id = :id_origem OR conta_destino_id = :id_destino) AND excluido_em IS NULL";
        $stmtTransf = $this->db->prepare($sqlTransf);
        $stmtTransf->bindValue(':id_origem', $id, PDO::PARAM_INT);
        $stmtTransf->bindValue(':id_destino', $id, PDO::PARAM_INT);
        $stmtTransf->execute();
        $resTransf = $stmtTransf->fetch();
        if (((int) ($resTransf['total'] ?? 0)) > 0) {
            return true;
        }

        return (new Investment())->hasPositionsForAccount($id);
    }

    /**
     * Realiza a exclusão lógica segura da conta caso ela não possua movimentações vinculadas (exclusivo do proprietário).
     *
     * @param int $id
     * @param int $userId
     * @return array{success: bool, message: string}
     */
    public function deleteAccount(int $id, int $userId): array
    {
        $conta = $this->find($id);
        if (!$conta || (int) $conta['usuario_id'] !== $userId) {
            return ['success' => false, 'message' => 'Conta bancária não encontrada ou você não é o proprietário.'];
        }

        if ($this->hasTransactions($id)) {
            return [
                'success' => false,
                'message' => 'Esta conta possui lançamentos, transferências ou investimentos vinculados e não pode ser excluída. Recomendamos inativá-la.'
            ];
        }

        $deleted = $this->softDelete($id, $userId);
        if ($deleted) {
            $this->logAudit('SOFT_DELETE', $id, $conta, ['excluido_em' => date('Y-m-d H:i:s'), 'excluido_por' => $userId], $userId);
            return ['success' => true, 'message' => 'Conta bancária excluída com sucesso.'];
        }

        return ['success' => false, 'message' => 'Não foi possível excluir a conta.'];
    }
}
