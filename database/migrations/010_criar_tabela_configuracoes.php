<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 010_criar_tabela_configuracoes
 * Cria a tabela de configurações globais e preferências da aplicação.
 */
return new class {
    public function up(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `configuracoes` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `chave` VARCHAR(64) NOT NULL UNIQUE,
            `valor` TEXT NULL,
            `tipo` ENUM('string', 'integer', 'boolean', 'json') NOT NULL DEFAULT 'string',
            `descricao` VARCHAR(255) NULL,
            `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_configuracoes_chave` (`chave`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `configuracoes`;");
    }
};
