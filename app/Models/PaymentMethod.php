<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Model de Meios de Pagamento (PaymentMethod)
 *
 * Mantém o catálogo global e as preferências individuais de disponibilidade.
 */
class PaymentMethod extends Model
{
    protected string $tabela = 'meios_pagamento';
    protected string $chavePrimaria = 'id';

    /** @return array<int, array<string, mixed>> */
    public function getActive(): array
    {
        return $this->getActiveForUser((int) AuthHelper::id());
    }

    /** @return array<int, array<string, mixed>> */
    public function getActivePaymentMethods(): array
    {
        return $this->getActive();
    }

    /** @return array<int, array<string, mixed>> */
    public function getActiveForUser(int $userId): array
    {
        $this->assertCurrentUser($userId);
        $sql = "SELECT mp.id, mp.nome, mp.eh_credito, mp.status AS status_global,
                       COALESCE(mpu.status, 'ativo') AS status
                FROM meios_pagamento mp
                LEFT JOIN meios_pagamento_usuario mpu
                    ON mpu.meio_pagamento_id = mp.id AND mpu.usuario_id = :usuario_id
                WHERE mp.excluido_em IS NULL AND mp.status = 'ativo'
                  AND COALESCE(mpu.status, 'ativo') = 'ativo'
                ORDER BY mp.nome ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int, array<string, mixed>> */
    public function getForUser(int $userId): array
    {
        $this->assertCurrentUser($userId);
        $sql = "SELECT mp.id, mp.nome, mp.eh_credito, mp.status AS status_global,
                       COALESCE(mpu.status, 'ativo') AS status,
                       CASE WHEN mp.excluido_em IS NULL THEN 0 ELSE 1 END AS arquivado
                FROM meios_pagamento mp
                LEFT JOIN meios_pagamento_usuario mpu
                    ON mpu.meio_pagamento_id = mp.id AND mpu.usuario_id = :usuario_id
                ORDER BY mp.nome ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function getForHistoricalTransaction(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, nome, eh_credito, excluido_em FROM meios_pagamento WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $method = $stmt->fetch(PDO::FETCH_ASSOC);
        return $method === false ? null : $method;
    }

    public function isAvailableForUser(int $id, int $userId): bool
    {
        $this->assertCurrentUser($userId);
        $sql = "SELECT 1 FROM meios_pagamento mp
                LEFT JOIN meios_pagamento_usuario mpu
                    ON mpu.meio_pagamento_id = mp.id AND mpu.usuario_id = :usuario_id
                WHERE mp.id = :id AND mp.excluido_em IS NULL AND mp.status = 'ativo'
                  AND COALESCE(mpu.status, 'ativo') = 'ativo'
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() !== false;
    }

    public function createPaymentMethod(array $data, int $userId): int
    {
        $this->requireDev($userId);
        $nome = trim((string) ($data['nome'] ?? ''));
        if ($nome === '' || mb_strlen($nome) > 80) {
            throw new InvalidArgumentException('Informe um nome válido para o meio de pagamento.');
        }

        $ehCredito = !empty($data['eh_credito']) ? 1 : 0;
        if ($this->nameExists($nome, $ehCredito)) {
            throw new InvalidArgumentException('Já existe uma opção global com esse nome e modalidade.');
        }

        $sql = "INSERT INTO meios_pagamento (nome, eh_credito, status, criado_em, atualizado_em)
                VALUES (:nome, :eh_credito, 'ativo', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            $sql .= ' RETURNING id';
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['nome' => $nome, 'eh_credito' => $ehCredito]);
        $newId = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql'
            ? (int) $stmt->fetchColumn()
            : (int) $this->db->lastInsertId();

        $this->logAudit('INSERT', $newId, null, [
            'nome' => $nome,
            'eh_credito' => $ehCredito,
        ], $userId);

        return $newId;
    }

    public function updatePaymentMethod(int $id, array $data, int $userId): bool
    {
        $this->requireDev($userId);
        $old = $this->find($id);
        if (!$old) {
            return false;
        }

        $nome = trim((string) ($data['nome'] ?? ''));
        if ($nome === '' || mb_strlen($nome) > 80) {
            throw new InvalidArgumentException('Informe um nome válido para o meio de pagamento.');
        }

        $ehCredito = !empty($data['eh_credito']) ? 1 : 0;
        if ($this->nameExists($nome, $ehCredito, $id)) {
            throw new InvalidArgumentException('Já existe outra opção global com esse nome e modalidade.');
        }

        $stmt = $this->db->prepare("UPDATE meios_pagamento
            SET nome = :nome, eh_credito = :eh_credito, atualizado_em = CURRENT_TIMESTAMP
            WHERE id = :id AND excluido_em IS NULL");
        $stmt->execute(['nome' => $nome, 'eh_credito' => $ehCredito, 'id' => $id]);
        $new = $this->find($id);
        if ($new === null) {
            return false;
        }

        $this->logAudit('UPDATE', $id, $old, $new, $userId);
        return true;
    }

    public function toggleStatus(int $id, int $userId): bool
    {
        $this->assertCurrentUser($userId);
        if ($this->getForHistoricalTransaction($id) === null || !$this->isGloballyAvailable($id)) {
            return false;
        }

        $oldStatus = $this->getPersonalStatus($id, $userId);
        if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            $sql = "INSERT INTO meios_pagamento_usuario (usuario_id, meio_pagamento_id, status)
                    VALUES (:usuario_id, :meio_id, 'inativo')
                    ON CONFLICT (usuario_id, meio_pagamento_id) DO UPDATE
                    SET status = CASE WHEN meios_pagamento_usuario.status = 'ativo' THEN 'inativo' ELSE 'ativo' END,
                        atualizado_em = CURRENT_TIMESTAMP";
        } else {
            $sql = "INSERT INTO meios_pagamento_usuario (usuario_id, meio_pagamento_id, status)
                    VALUES (:usuario_id, :meio_id, 'inativo')
                    ON DUPLICATE KEY UPDATE
                    status = IF(status = 'ativo', 'inativo', 'ativo'), atualizado_em = CURRENT_TIMESTAMP";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['usuario_id' => $userId, 'meio_id' => $id]);

        $newStatus = $this->getPersonalStatus($id, $userId);
        $this->logAudit('UPDATE', $id, ['status_pessoal' => $oldStatus], ['status_pessoal' => $newStatus], $userId);
        return true;
    }

    /** @return array{success: bool, message: string} */
    public function deactivateGlobally(int $id, int $userId): array
    {
        $this->requireDev($userId);
        $old = $this->find($id);
        if (!$old) {
            return ['success' => false, 'message' => 'Meio de pagamento não encontrado.'];
        }

        $stmt = $this->db->prepare("UPDATE meios_pagamento SET status = 'inativo', atualizado_em = CURRENT_TIMESTAMP WHERE id = :id AND status = 'ativo' AND excluido_em IS NULL");
        $stmt->execute(['id' => $id]);
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'A opção já está inativa ou não existe.'];
        }

        $this->logAudit('UPDATE', $id, $old, ['status' => 'inativo'], $userId);
        return ['success' => true, 'message' => 'Meio de pagamento inativado globalmente.'];
    }

    /** @return array{success: bool, message: string} */
    public function activateGlobally(int $id, int $userId): array
    {
        $this->requireDev($userId);
        $old = $this->find($id);
        if (!$old) {
            return ['success' => false, 'message' => 'Meio de pagamento não encontrado.'];
        }

        $stmt = $this->db->prepare("UPDATE meios_pagamento SET status = 'ativo', atualizado_em = CURRENT_TIMESTAMP WHERE id = :id AND excluido_em IS NULL AND status = 'inativo'");
        $stmt->execute(['id' => $id]);
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'O meio de pagamento não está disponível para reativação.'];
        }

        $this->logAudit('UPDATE', $id, $old, ['status' => 'ativo'], $userId);
        return ['success' => true, 'message' => 'Meio de pagamento reativado globalmente.'];
    }

    private function assertCurrentUser(int $userId): void
    {
        if (AuthHelper::id() !== $userId) {
            throw new RuntimeException('Não é permitido acessar preferências de outro usuário.');
        }
    }

    private function nameExists(string $nome, int $ehCredito, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM meios_pagamento WHERE LOWER(nome) = LOWER(:nome) AND eh_credito = :eh_credito';
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
        }
        $stmt = $this->db->prepare($sql . ' LIMIT 1');
        $params = ['nome' => $nome, 'eh_credito' => $ehCredito];
        if ($exceptId !== null) {
            $params['id'] = $exceptId;
        }
        $stmt->execute($params);
        return $stmt->fetchColumn() !== false;
    }

    private function isGloballyAvailable(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM meios_pagamento WHERE id = :id AND status = 'ativo' AND excluido_em IS NULL LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchColumn() !== false;
    }

    private function getPersonalStatus(int $id, int $userId): string
    {
        $stmt = $this->db->prepare("SELECT COALESCE(status, 'ativo') FROM meios_pagamento_usuario
            WHERE usuario_id = :usuario_id AND meio_pagamento_id = :meio_id");
        $stmt->execute(['usuario_id' => $userId, 'meio_id' => $id]);
        $status = $stmt->fetchColumn();
        return $status === false ? 'ativo' : (string) $status;
    }

    private function requireDev(int $userId): void
    {
        if (!AuthHelper::isDev() || (int) AuthHelper::id() !== $userId) {
            throw new RuntimeException('A manutenção do catálogo é exclusiva do perfil dev.');
        }
    }
}
