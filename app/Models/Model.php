<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Classe Base Model
 *
 * Provê a camada de persistência via PDO com Prepared Statements obrigatórios,
 * controle de transações atômicas, tratamento de Soft Delete (excluido_em) e registro de auditoria (auditoria_logs).
 */
abstract class Model
{
    protected PDO $db;
    protected string $tabela = '';
    protected string $chavePrimaria = 'id';

    /** @var array<int, string> */
    private const TABELAS_COM_PROPRIETARIO = [
        'contas',
        'categorias',
        'cartoes_credito',
    ];

    /**
     * Construtor da camada Model. Inicializa a instância Singleton do PDO.
     */
    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Inicia uma transação no banco de dados.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    /**
     * Confirma as alterações da transação ativa.
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->db->commit();
    }

    /**
     * Reverte as alterações da transação ativa.
     *
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->db->rollBack();
    }

    /**
     * Verifica se há uma transação ativa.
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->db->inTransaction();
    }

    /**
     * Busca um registro pelo ID primário (respeitando Soft Delete se a coluna excluido_em existir).
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        if (empty($this->tabela)) {
            throw new RuntimeException('Nome da tabela não definido no Model.');
        }

        $sql = "SELECT * FROM `{$this->tabela}` WHERE `{$this->chavePrimaria}` = :id";
        
        if ($this->temSoftDelete()) {
            $sql .= " AND excluido_em IS NULL";
        }

        $usuarioId = $this->usuarioLogadoParaEscopo();
        if ($usuarioId !== null) {
            $sql .= " AND usuario_id = :usuario_escopo";
        } elseif ($this->tabelaComProprietario()) {
            return null;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        if ($usuarioId !== null) {
            $stmt->bindValue(':usuario_escopo', $usuarioId, PDO::PARAM_INT);
        }
        $stmt->execute();

        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Busca um registro pelo ID primário incluindo registros marcados como excluídos (Soft Delete).
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function findWithTrashed(int $id): ?array
    {
        if (empty($this->tabela)) {
            throw new RuntimeException('Nome da tabela não definido no Model.');
        }

        $sql = "SELECT * FROM `{$this->tabela}` WHERE `{$this->chavePrimaria}` = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Retorna todos os registros não excluídos ordenados.
     *
     * @param string $orderBy Cláusula de ordenação segura
     * @return array<int, array<string, mixed>>
     */
    public function all(string $orderBy = 'id DESC'): array
    {
        if (empty($this->tabela)) {
            throw new RuntimeException('Nome da tabela não definido no Model.');
        }

        $sql = "SELECT * FROM `{$this->tabela}`";

        if ($this->temSoftDelete()) {
            $sql .= " WHERE excluido_em IS NULL";
        }

        $usuarioId = $this->usuarioLogadoParaEscopo();
        if ($this->tabelaComProprietario()) {
            if ($usuarioId === null) {
                return [];
            }
            $sql .= $this->temSoftDelete() ? " AND" : " WHERE";
            $sql .= " usuario_id = :usuario_escopo";
        }

        $sql .= " ORDER BY " . $this->sanitizeOrderBy($orderBy);

        $stmt = $this->db->prepare($sql);
        if ($usuarioId !== null && $this->tabelaComProprietario()) {
            $stmt->bindValue(':usuario_escopo', $usuarioId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Executa Soft Delete em um registro marcando excluido_em e excluido_por.
     *
     * @param int $id
     * @param int $usuarioId ID do usuário que solicitou a exclusão
     * @return bool
     */
    public function softDelete(int $id, int $usuarioId): bool
    {
        if (empty($this->tabela) || !$this->temSoftDelete()) {
            throw new RuntimeException("Tabela '{$this->tabela}' não suporta soft delete.");
        }

        $registroAntigo = $this->find($id);
        if (!$registroAntigo) {
            return false;
        }

        $sql = "UPDATE `{$this->tabela}` 
                SET excluido_em = NOW(), excluido_por = :usuario_id, atualizado_em = NOW() 
                WHERE `{$this->chavePrimaria}` = :id AND excluido_em IS NULL";

        $sql .= " AND usuario_id = :usuario_escopo";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_escopo', $usuarioId, PDO::PARAM_INT);
        $sucesso = $stmt->execute();

        if ($sucesso && $stmt->rowCount() > 0) {
            $this->logAudit('SOFT_DELETE', $id, $registroAntigo, ['excluido_em' => date('Y-m-d H:i:s'), 'excluido_por' => $usuarioId], $usuarioId);
            return true;
        }

        return false;
    }

    protected function tabelaComProprietario(): bool
    {
        return in_array($this->tabela, self::TABELAS_COM_PROPRIETARIO, true);
    }

    protected function usuarioLogadoParaEscopo(): ?int
    {
        return $this->tabelaComProprietario() ? AuthHelper::id() : null;
    }

    /**
     * Grava um registro na tabela de auditoria (auditoria_logs).
     *
     * @param string $acao 'INSERT', 'UPDATE', 'SOFT_DELETE', 'RESTORE', 'PURGE'
     * @param int $registroId ID do registro afetado
     * @param array<string, mixed>|null $valoresAntigos Dados anteriores
     * @param array<string, mixed>|null $valoresNovos Novos dados
     * @param int|null $usuarioId ID do usuário autor (obtido da sessão se omitido)
     * @return void
     */
    public function logAudit(string $acao, int $registroId, ?array $valoresAntigos, ?array $valoresNovos, ?int $usuarioId = null): void
    {
        try {
            $usuarioId = $usuarioId ?? ($_SESSION['user_id'] ?? null);
            $enderecoIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            $sql = "INSERT INTO auditoria_logs (usuario_id, tabela, registro_id, acao, valores_antigos, valores_novos, endereco_ip, criado_em)
                    VALUES (:usuario_id, :tabela, :registro_id, :acao, :valores_antigos, :valores_novos, :endereco_ip, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':usuario_id', $usuarioId, $usuarioId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':tabela', $this->tabela, PDO::PARAM_STR);
            $stmt->bindValue(':registro_id', $registroId, PDO::PARAM_INT);
            $stmt->bindValue(':acao', $acao, PDO::PARAM_STR);
            $stmt->bindValue(':valores_antigos', $valoresAntigos !== null ? json_encode($valoresAntigos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null, $valoresAntigos !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':valores_novos', $valoresNovos !== null ? json_encode($valoresNovos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null, $valoresNovos !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':endereco_ip', $enderecoIp, PDO::PARAM_STR);
            $stmt->execute();
        } catch (Throwable $e) {
            // Em caso de falha na gravação de auditoria, registramos no log de erro mas não travamos o fluxo
            LoggerHelper::error($e, ['context' => 'Model::logAudit', 'tabela' => $this->tabela, 'acao' => $acao]);
        }
    }

    /**
     * Define se a tabela deste model utiliza a convenção de Soft Delete.
     *
     * @return bool
     */
    protected function temSoftDelete(): bool
    {
        $tabelasComSoftDelete = [
            'usuarios', 'contas', 'categorias', 'meios_pagamento', 
            'cartoes_credito', 'transacoes', 'transferencias'
        ];
        return in_array($this->tabela, $tabelasComSoftDelete, true);
    }

    /**
     * Sanitiza a cláusula ORDER BY para prevenir injeções de SQL.
     *
     * @param string $orderBy
     * @return string
     */
    protected function sanitizeOrderBy(string $orderBy): string
    {
        $pattern = '/^[a-zA-Z0-9_]+(\s+(ASC|DESC|asc|desc))?(\s*,\s*[a-zA-Z0-9_]+(\s+(ASC|DESC|asc|desc))?)*$/';
        if (preg_match($pattern, trim($orderBy))) {
            return $orderBy;
        }
        return "{$this->chavePrimaria} DESC";
    }
}
