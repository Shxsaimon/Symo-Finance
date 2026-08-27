<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 002_create_accounts_table
 * Cria a tabela de contas financeiras e carteiras.
 */
return new class {
    public function up(PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `accounts` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `bank_name` VARCHAR(100) NULL,
            `type` ENUM('corrente', 'poupanca', 'investimento', 'dinheiro', 'outros') NOT NULL DEFAULT 'corrente',
            `initial_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `current_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `color` VARCHAR(7) NOT NULL DEFAULT '#6F02FF',
            `status` ENUM('ativa', 'inativa') NOT NULL DEFAULT 'ativa',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` DATETIME NULL,
            `deleted_by` INT UNSIGNED NULL,
            INDEX `idx_accounts_status` (`status`),
            INDEX `idx_accounts_deleted_at` (`deleted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `accounts`;");
    }
};
