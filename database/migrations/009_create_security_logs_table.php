<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 009_create_security_logs_table
 * Cria as tabelas de logs de segurança e logs de erros de sistema.
 */
return new class {
    public function up(PDO $pdo): void
    {
        // Tabela security_logs
        $sqlSec = "CREATE TABLE IF NOT EXISTS `security_logs` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `event` VARCHAR(80) NOT NULL,
            `email` VARCHAR(150) NULL,
            `ip_address` VARCHAR(45) NOT NULL,
            `user_agent` VARCHAR(255) NULL,
            `context` JSON NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_sec_event` (`event`),
            INDEX `idx_sec_ip` (`ip_address`),
            INDEX `idx_sec_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $pdo->exec($sqlSec);

        // Tabela error_logs (conforme FSD seção 19.1)
        $sqlErr = "CREATE TABLE IF NOT EXISTS `error_logs` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `message` TEXT NOT NULL,
            `code` VARCHAR(50) NULL,
            `file` VARCHAR(255) NULL,
            `line` INT UNSIGNED NULL,
            `trace` TEXT NULL,
            `url` VARCHAR(255) NULL,
            `method` VARCHAR(10) NULL,
            `ip_address` VARCHAR(45) NOT NULL,
            `user_id` INT UNSIGNED NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_err_created_at` (`created_at`),
            INDEX `idx_err_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $pdo->exec($sqlErr);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `error_logs`;");
        $pdo->exec("DROP TABLE IF EXISTS `security_logs`;");
    }
};
