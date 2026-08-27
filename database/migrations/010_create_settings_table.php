<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 010_create_settings_table
 * Cria a tabela de configurações globais e preferências da aplicação.
 */
return new class {
    public function up(PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `settings` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(64) NOT NULL UNIQUE,
            `setting_value` TEXT NULL,
            `type` ENUM('string', 'integer', 'boolean', 'json') NOT NULL DEFAULT 'string',
            `description` VARCHAR(255) NULL,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_settings_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `settings`;");
    }
};
