<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 021_adicionar_soft_delete_investimentos
 * Adiciona suporte a exclusão lógica (soft delete) na tabela investimentos.
 */
return new class {
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            $columnExists = $pdo->query(
                "SELECT 1
                 FROM information_schema.columns
                 WHERE table_schema = current_schema()
                   AND table_name = 'investimentos'
                   AND column_name = 'excluido_em'"
            )->fetchColumn() !== false;

            if (!$columnExists) {
                $pdo->exec(
                    'ALTER TABLE investimentos
                     ADD COLUMN excluido_em TIMESTAMP NULL,
                     ADD COLUMN excluido_por INTEGER NULL REFERENCES usuarios(id) ON DELETE SET NULL'
                );
                $pdo->exec('CREATE INDEX IF NOT EXISTS idx_investimentos_excluido_em ON investimentos (usuario_id, excluido_em)');
            }
            return;
        }

        if ($driver === 'mysql') {
            $columnExists = $pdo->query(
                "SHOW COLUMNS FROM `investimentos` LIKE 'excluido_em'"
            )->fetchColumn() !== false;

            if (!$columnExists) {
                $pdo->exec(
                    'ALTER TABLE `investimentos`
                     ADD COLUMN `excluido_em` DATETIME NULL AFTER `atualizado_em`,
                     ADD COLUMN `excluido_por` INT UNSIGNED NULL AFTER `excluido_em`,
                     ADD INDEX `idx_investimentos_excluido_em` (`usuario_id`, `excluido_em`),
                     ADD CONSTRAINT `fk_investimentos_excluido_por` FOREIGN KEY (`excluido_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL'
                );
            }
            return;
        }

        throw new RuntimeException('Driver de banco não suportado para a migration de soft delete de investimentos.');
    }

    public function down(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            $pdo->exec('DROP INDEX IF EXISTS idx_investimentos_excluido_em');
            $pdo->exec('ALTER TABLE investimentos DROP COLUMN IF EXISTS excluido_por, DROP COLUMN IF EXISTS excluido_em');
            return;
        }

        if ($driver === 'mysql') {
            $pdo->exec('ALTER TABLE `investimentos` DROP FOREIGN KEY `fk_investimentos_excluido_por`');
            $pdo->exec('ALTER TABLE `investimentos` DROP INDEX `idx_investimentos_excluido_em`');
            $pdo->exec('ALTER TABLE `investimentos` DROP COLUMN `excluido_por`, DROP COLUMN `excluido_em`');
        }
    }
};
