<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 006_create_transfers_table
 * Cria a tabela de transferências financeiras entre contas.
 */
return new class {
    public function up(PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `transfers` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED NOT NULL,
            `source_account_id` INT UNSIGNED NOT NULL,
            `destination_account_id` INT UNSIGNED NOT NULL,
            `amount` DECIMAL(15,2) NOT NULL,
            `transfer_date` DATE NOT NULL,
            `notes` TEXT NULL,
            `source_transaction_id` INT UNSIGNED NULL,
            `destination_transaction_id` INT UNSIGNED NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` DATETIME NULL,
            `deleted_by` INT UNSIGNED NULL,
            INDEX `idx_transfers_date` (`transfer_date`),
            INDEX `idx_transfers_deleted_at` (`deleted_at`),
            CONSTRAINT `fk_transf_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_transf_src` FOREIGN KEY (`source_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_transf_dst` FOREIGN KEY (`destination_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `transfers`;");
    }
};
