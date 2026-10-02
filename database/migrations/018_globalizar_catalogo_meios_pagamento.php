<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 018_globalizar_catalogo_meios_pagamento
 * Consolida meios de pagamento e preserva preferências e referências históricas.
 */
return new class {
    /** @var array<int, array{nome: string, eh_credito: int}> */
    private const CATALOGO_PADRAO = [
        ['nome' => 'Dinheiro', 'eh_credito' => 0],
        ['nome' => 'Pix', 'eh_credito' => 0],
        ['nome' => 'Cartão de Débito', 'eh_credito' => 0],
        ['nome' => 'Cartão de Crédito', 'eh_credito' => 1],
        ['nome' => 'Boleto Bancário', 'eh_credito' => 0],
        ['nome' => 'Transferência Bancária', 'eh_credito' => 0],
    ];

    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if (!in_array($driver, ['pgsql', 'mysql'], true)) {
            throw new RuntimeException('Driver de banco não suportado para a consolidação do catálogo.');
        }

        $transactional = $driver === 'pgsql';
        $ownsTransaction = $transactional && !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            if (!$this->columnExists($pdo, $driver, 'usuario_id')) {
                $this->createPreferencesTable($pdo, $driver);
                $this->insertDefaultsIfEmpty($pdo);
                $this->ensureUniqueCatalogIndex($pdo, $driver);
                if ($ownsTransaction && $pdo->inTransaction()) {
                    $pdo->commit();
                }
                return;
            }

            $metodos = $this->loadMethods($pdo);
            if ($metodos === []) {
                $this->createPreferencesTable($pdo, $driver);
                $this->dropOwnerScope($pdo, $driver);
                $this->insertDefaultsIfEmpty($pdo);
                $this->ensureUniqueCatalogIndex($pdo, $driver);
                if ($ownsTransaction && $pdo->inTransaction()) {
                    $pdo->commit();
                }
                return;
            }

            $canonicos = $this->resolveCanonicalMethods($metodos);
            $this->validateLegacyMethods($metodos, $canonicos);

            $preferenciasExistentes = $this->preferenceRowsExist($pdo, $driver);
            $this->validateTransactionOwnership($pdo, $canonicos, $preferenciasExistentes);

            $idsOriginais = array_map(static fn(array $metodo): int => (int) $metodo['id'], $metodos);
            $idsCanonicos = array_values($canonicos);
            $referenciasAntes = $this->countTransactionReferences($pdo, $idsOriginais);

            $this->createPreferencesTable($pdo, $driver);
            if (!$transactional && !$pdo->inTransaction()) {
                $pdo->beginTransaction();
            }

            $this->preserveUserPreferences($pdo, $driver, $metodos, $canonicos);
            $mapaIds = $this->buildIdMap($metodos, $canonicos);
            $this->remapTransactions($pdo, $mapaIds);

            $idsDuplicados = array_keys(array_filter(
                $mapaIds,
                static fn(int $canonico, int $origem): bool => $canonico !== $origem,
                ARRAY_FILTER_USE_BOTH
            ));

            if ($this->countTransactionReferences($pdo, $idsDuplicados) !== 0) {
                throw new RuntimeException('A consolidação encontrou referências antigas ainda ativas.');
            }

            if ($this->countTransactionReferences($pdo, $idsCanonicos) !== $referenciasAntes) {
                throw new RuntimeException('A consolidação não preservou todas as referências de transações.');
            }

            $this->deleteDuplicateMethods($pdo, $idsDuplicados);
            $this->activateCanonicalMethods($pdo, $idsCanonicos);

            if (!$transactional && $pdo->inTransaction()) {
                $pdo->commit();
            }

            $this->ensureUniqueCatalogIndex($pdo, $driver);
            $this->dropOwnerScope($pdo, $driver);

            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->commit();
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function down(PDO $pdo): void
    {
        throw new RuntimeException('A consolidação dos meios de pagamento não possui reversão automática.');
    }

    /** @return array<int, array<string, mixed>> */
    private function loadMethods(PDO $pdo): array
    {
        $sql = "SELECT mp.id, mp.usuario_id, mp.nome, mp.eh_credito, mp.status, mp.excluido_em, mp.excluido_por, u.perfil
                FROM meios_pagamento mp
                INNER JOIN usuarios u ON u.id = mp.usuario_id
                ORDER BY mp.id";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @param array<int, array<string, mixed>> $metodos
     *  @return array<string, int>
     */
    private function resolveCanonicalMethods(array $metodos): array
    {
        $canonicos = [];
        foreach (self::CATALOGO_PADRAO as $padrao) {
            $chave = $this->methodKey($padrao['nome'], $padrao['eh_credito']);
            $matches = array_values(array_filter(
                $metodos,
                static fn(array $metodo): bool => $metodo['perfil'] === 'dev'
                    && $metodo['nome'] === $padrao['nome']
                    && (int) $metodo['eh_credito'] === $padrao['eh_credito']
            ));

            if (count($matches) !== 1 || $matches[0]['excluido_em'] !== null) {
                throw new RuntimeException('Não foi possível determinar unicamente o catálogo canônico.');
            }

            $canonicos[$chave] = (int) $matches[0]['id'];
        }

        return $canonicos;
    }

    /** @param array<int, array<string, mixed>> $metodos
     *  @param array<string, int> $canonicos
     */
    private function validateLegacyMethods(array $metodos, array $canonicos): void
    {
        $porUsuario = [];
        $chavesPermitidas = [];
        foreach (self::CATALOGO_PADRAO as $padrao) {
            $chavesPermitidas[$this->methodKey($padrao['nome'], $padrao['eh_credito'])] = true;
        }

        foreach ($metodos as $metodo) {
            $chave = $this->methodKey((string) $metodo['nome'], (int) $metodo['eh_credito']);
            if (!isset($chavesPermitidas[$chave])) {
                throw new RuntimeException('Há opções fora do catálogo padrão; a migration foi interrompida.');
            }
            if (!in_array($metodo['status'], ['ativo', 'inativo'], true)) {
                throw new RuntimeException('Há estados de disponibilidade não reconhecidos; a migration foi interrompida.');
            }

            $chaveUsuario = (int) $metodo['usuario_id'] . ':' . $chave;
            if (isset($porUsuario[$chaveUsuario])) {
                throw new RuntimeException('Há opções duplicadas no mesmo escopo; a migration foi interrompida.');
            }
            $porUsuario[$chaveUsuario] = true;

            if (!isset($canonicos[$chave])) {
                throw new RuntimeException('Há uma opção sem correspondência canônica; a migration foi interrompida.');
            }
        }
    }

    /** @param array<string, int> $canonicos */
    private function validateTransactionOwnership(PDO $pdo, array $canonicos, bool $preferencesExist): void
    {
        $canonicoIds = array_values($canonicos);
        $placeholders = implode(',', array_fill(0, count($canonicoIds), '?'));
        $sql = "SELECT 1 FROM transacoes t
                INNER JOIN meios_pagamento mp ON mp.id = t.meio_pagamento_id
                WHERE t.usuario_id <> mp.usuario_id
                  AND mp.id NOT IN ({$placeholders})
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($canonicoIds);
        if ($stmt->fetchColumn() !== false) {
            throw new RuntimeException('Há transações vinculadas a um escopo incompatível; a migration foi interrompida.');
        }

        $stmt = $pdo->query("SELECT 1 FROM transacoes t
            INNER JOIN meios_pagamento mp ON mp.id = t.meio_pagamento_id
            WHERE t.usuario_id <> mp.usuario_id LIMIT 1");
        if ($stmt->fetchColumn() === false) {
            return;
        }

        if (!$preferencesExist) {
            throw new RuntimeException('Há transações vinculadas a um escopo incompatível; a migration foi interrompida.');
        }

        $stmt = $pdo->query("SELECT 1 FROM transacoes t
            INNER JOIN meios_pagamento mp ON mp.id = t.meio_pagamento_id
            LEFT JOIN meios_pagamento_usuario mpu
                ON mpu.usuario_id = t.usuario_id AND mpu.meio_pagamento_id = mp.id
            WHERE t.usuario_id <> mp.usuario_id AND mpu.usuario_id IS NULL
            LIMIT 1");
        if ($stmt->fetchColumn() !== false) {
            throw new RuntimeException('A consolidação parcial não possui preferências suficientes para continuar.');
        }
    }

    /** @param array<int, array<string, mixed>> $metodos
     *  @param array<string, int> $canonicos
     */
    private function preserveUserPreferences(PDO $pdo, string $driver, array $metodos, array $canonicos): void
    {
        $sql = $driver === 'pgsql'
            ? 'INSERT INTO meios_pagamento_usuario (usuario_id, meio_pagamento_id, status) VALUES (:usuario_id, :meio_id, :status) ON CONFLICT (usuario_id, meio_pagamento_id) DO NOTHING'
            : 'INSERT IGNORE INTO meios_pagamento_usuario (usuario_id, meio_pagamento_id, status) VALUES (:usuario_id, :meio_id, :status)';
        $stmt = $pdo->prepare($sql);

        foreach ($metodos as $metodo) {
            $chave = $this->methodKey((string) $metodo['nome'], (int) $metodo['eh_credito']);
            $status = $metodo['excluido_em'] !== null || $metodo['status'] === 'inativo'
                ? 'inativo'
                : 'ativo';
            $stmt->execute([
                'usuario_id' => (int) $metodo['usuario_id'],
                'meio_id'    => $canonicos[$chave],
                'status'     => $status,
            ]);
        }
    }

    /** @param array<int, array<string, mixed>> $metodos
     *  @param array<string, int> $canonicos
     *  @return array<int, int>
     */
    private function buildIdMap(array $metodos, array $canonicos): array
    {
        $mapa = [];
        foreach ($metodos as $metodo) {
            $chave = $this->methodKey((string) $metodo['nome'], (int) $metodo['eh_credito']);
            $mapa[(int) $metodo['id']] = $canonicos[$chave];
        }
        return $mapa;
    }

    /** @param array<int, int> $mapaIds */
    private function remapTransactions(PDO $pdo, array $mapaIds): void
    {
        $stmt = $pdo->prepare('UPDATE transacoes SET meio_pagamento_id = :canonico WHERE meio_pagamento_id = :origem');
        foreach ($mapaIds as $origem => $canonico) {
            if ($origem === $canonico) {
                continue;
            }
            $stmt->execute(['canonico' => $canonico, 'origem' => $origem]);
        }
    }

    /** @param array<int, int> $ids */
    private function countTransactionReferences(PDO $pdo, array $ids): int
    {
        if ($ids === []) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transacoes WHERE meio_pagamento_id IN ({$placeholders})");
        $stmt->execute(array_values($ids));
        return (int) $stmt->fetchColumn();
    }

    /** @param array<int, int> $idsDuplicados */
    private function deleteDuplicateMethods(PDO $pdo, array $idsDuplicados): void
    {
        if ($idsDuplicados === []) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($idsDuplicados), '?'));
        $stmt = $pdo->prepare("DELETE FROM meios_pagamento WHERE id IN ({$placeholders})");
        $stmt->execute(array_values($idsDuplicados));
    }

    /** @param array<int, int> $idsCanonicos */
    private function activateCanonicalMethods(PDO $pdo, array $idsCanonicos): void
    {
        $stmt = $pdo->prepare("UPDATE meios_pagamento
            SET status = 'ativo', excluido_em = NULL, excluido_por = NULL, atualizado_em = CURRENT_TIMESTAMP
            WHERE id = :id");
        foreach ($idsCanonicos as $id) {
            $stmt->execute(['id' => $id]);
            $check = $pdo->prepare("SELECT 1 FROM meios_pagamento
                WHERE id = :id AND status = 'ativo' AND excluido_em IS NULL LIMIT 1");
            $check->execute(['id' => $id]);
            if ($check->fetchColumn() === false) {
                throw new RuntimeException('Não foi possível validar uma opção canônica.');
            }
        }
    }

    private function createPreferencesTable(PDO $pdo, string $driver): void
    {
        if ($driver === 'pgsql') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS meios_pagamento_usuario (
                usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
                meio_pagamento_id INTEGER NOT NULL REFERENCES meios_pagamento(id) ON DELETE RESTRICT,
                status VARCHAR(20) NOT NULL DEFAULT 'ativo' CHECK (status IN ('ativo', 'inativo')),
                criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (usuario_id, meio_pagamento_id)
            )");
            return;
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS `meios_pagamento_usuario` (
            `usuario_id` INT UNSIGNED NOT NULL,
            `meio_pagamento_id` INT UNSIGNED NOT NULL,
            `status` ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`usuario_id`, `meio_pagamento_id`),
            CONSTRAINT `fk_meios_pagamento_usuario_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_meios_pagamento_usuario_meio` FOREIGN KEY (`meio_pagamento_id`) REFERENCES `meios_pagamento` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function ensureUniqueCatalogIndex(PDO $pdo, string $driver): void
    {
        if ($driver === 'pgsql') {
            $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS uq_meios_pagamento_nome_credito ON meios_pagamento (nome, eh_credito)');
            return;
        }

        $stmt = $pdo->query("SHOW INDEX FROM `meios_pagamento` WHERE Key_name = 'uq_meios_pagamento_nome_credito'");
        if ($stmt->fetch(PDO::FETCH_ASSOC) === false) {
            $pdo->exec('ALTER TABLE `meios_pagamento` ADD UNIQUE KEY `uq_meios_pagamento_nome_credito` (`nome`, `eh_credito`)');
        }
    }

    private function dropOwnerScope(PDO $pdo, string $driver): void
    {
        if (!$this->columnExists($pdo, $driver, 'usuario_id')) {
            return;
        }

        if ($driver === 'pgsql') {
            $pdo->exec('ALTER TABLE meios_pagamento DROP CONSTRAINT IF EXISTS meios_pagamento_usuario_id_fkey');
            $pdo->exec('DROP INDEX IF EXISTS idx_meios_pagamento_usuario_id');
            $pdo->exec('ALTER TABLE meios_pagamento DROP COLUMN IF EXISTS usuario_id');
            return;
        }

        $foreignKeys = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'meios_pagamento'
              AND COLUMN_NAME = 'usuario_id' AND REFERENCED_TABLE_NAME IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($foreignKeys as $constraint) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $constraint)) {
                throw new RuntimeException('A migration encontrou uma constraint inesperada.');
            }
            $pdo->exec('ALTER TABLE `meios_pagamento` DROP FOREIGN KEY `' . $constraint . '`');
        }

        $index = $pdo->query("SHOW INDEX FROM `meios_pagamento` WHERE Key_name = 'idx_meios_pagamento_usuario_id'")->fetch(PDO::FETCH_ASSOC);
        if ($index !== false) {
            $pdo->exec('ALTER TABLE `meios_pagamento` DROP INDEX `idx_meios_pagamento_usuario_id`');
        }
        $pdo->exec('ALTER TABLE `meios_pagamento` DROP COLUMN `usuario_id`');
    }

    private function insertDefaultsIfEmpty(PDO $pdo): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM meios_pagamento')->fetchColumn();
        if ($count !== 0) {
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO meios_pagamento (nome, eh_credito, status, criado_em, atualizado_em)
            VALUES (:nome, :eh_credito, 'ativo', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        foreach (self::CATALOGO_PADRAO as $metodo) {
            $stmt->execute($metodo);
        }
    }

    private function columnExists(PDO $pdo, string $driver, string $column): bool
    {
        if ($driver === 'pgsql') {
            $stmt = $pdo->prepare("SELECT 1 FROM information_schema.columns
                WHERE table_schema = current_schema() AND table_name = 'meios_pagamento' AND column_name = :coluna");
            $stmt->execute(['coluna' => $column]);
            return $stmt->fetchColumn() !== false;
        }

        $stmt = $pdo->prepare("SELECT 1 FROM information_schema.columns
            WHERE table_schema = DATABASE() AND table_name = 'meios_pagamento' AND column_name = :coluna");
        $stmt->execute(['coluna' => $column]);
        return $stmt->fetchColumn() !== false;
    }

    private function preferenceRowsExist(PDO $pdo, string $driver): bool
    {
        $stmt = $pdo->query("SELECT 1 FROM information_schema.tables
            WHERE table_schema = " . ($driver === 'pgsql' ? 'current_schema()' : 'DATABASE()') . "
              AND table_name = 'meios_pagamento_usuario'");
        if ($stmt->fetchColumn() === false) {
            return false;
        }
        return (int) $pdo->query('SELECT COUNT(*) FROM meios_pagamento_usuario')->fetchColumn() > 0;
    }

    private function methodKey(string $nome, int $ehCredito): string
    {
        return $nome . "\0" . $ehCredito;
    }
};