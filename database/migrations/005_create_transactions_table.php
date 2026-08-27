<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 005_create_transactions_table
 * Cria a tabela de transações financeiras (receitas e despesas).
 */
return new class {
    public function up(PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `transactions` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED NOT NULL,
            `type` ENUM('receita', 'despesa') NOT NULL,
            `description` VARCHAR(255) NOT NULL,
            `amount` DECIMAL(15,2) NOT NULL,
            `transaction_date` DATE NOT NULL,
            `due_date` DATE NOT NULL,
            `payment_date` DATE NULL,
            `status` ENUM('pendente', 'pago', 'recebido') NOT NULL DEFAULT 'pendente',
            `account_id` INT UNSIGNED NULL,
            `category_id` INT UNSIGNED NOT NULL,
            `payment_method_id` INT UNSIGNED NOT NULL,
            `credit_card_id` INT UNSIGNED NULL,
            `invoice_id` INT UNSIGNED NULL,
            `installment_group_id` VARCHAR(64) NULL,
            `installment_number` TINYINT UNSIGNED NULL DEFAULT 1,
            `installment_total` TINYINT UNSIGNED NULL DEFAULT 1,
            `notes` TEXT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` DATETIME NULL,
            `deleted_by` INT UNSIGNED NULL,
            INDEX `idx_trans_date` (`transaction_date`),
            INDEX `idx_trans_due_date` (`due_date`),
            INDEX `idx_trans_status` (`status`),
            INDEX `idx_trans_type` (`type`),
            INDEX `idx_trans_account` (`account_id`),
            INDEX `idx_trans_category` (`category_id`),
            INDEX `idx_trans_credit_card` (`credit_card_id`),
            INDEX `idx_trans_invoice` (`invoice_id`),
            INDEX `idx_trans_installment` (`installment_group_id`),
            INDEX `idx_trans_deleted_at` (`deleted_at`),
            INDEX `idx_trans_dashboard_composite` (`deleted_at`, `status`, `due_date`, `type`),
            CONSTRAINT `fk_trans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_trans_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_trans_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_trans_payment_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `transactions`;");
    }
};
