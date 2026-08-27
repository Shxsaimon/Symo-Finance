<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 003_create_categories_table
 * Cria a tabela de categorias de receitas e despesas.
 */
return new class {
    public function up(PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `categories` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `type` ENUM('receita', 'despesa') NOT NULL,
            `icon` VARCHAR(50) NOT NULL DEFAULT 'bi-tag',
            `color` VARCHAR(7) NOT NULL DEFAULT '#793CFE',
            `status` ENUM('ativa', 'inativa') NOT NULL DEFAULT 'ativa',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` DATETIME NULL,
            `deleted_by` INT UNSIGNED NULL,
            INDEX `idx_categories_type` (`type`),
            INDEX `idx_categories_status` (`status`),
            INDEX `idx_categories_deleted_at` (`deleted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `categories`;");
    }
};
