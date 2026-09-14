<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 011_criar_tabela_migracoes
 * Cria a tabela de controle e histórico de migrações do banco de dados.
 */
return new class {
    public function up(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `migracoes` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `migracao` VARCHAR(255) NOT NULL UNIQUE,
            `lote` INT UNSIGNED NOT NULL,
            `executado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_migracoes_migracao` (`migracao`),
            INDEX `idx_migracoes_lote` (`lote`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `migracoes`;");
    }
};
