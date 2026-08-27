<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 007_create_credit_cards_and_invoices_table
 * Cria as tabelas de cartões de crédito e faturas mensais, e vincula foreign keys com transações.
 */
return new class {
    public function up(PDO $pdo): void
    {
        // 1. Tabela credit_cards
        $sqlCards = "CREATE TABLE IF NOT EXISTS `credit_cards` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `credit_limit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `closing_day` TINYINT UNSIGNED NOT NULL,
            `due_day` TINYINT UNSIGNED NOT NULL,
            `default_account_id` INT UNSIGNED NULL,
            `color` VARCHAR(7) NOT NULL DEFAULT '#3BD7D7',
            `status` ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` DATETIME NULL,
            `deleted_by` INT UNSIGNED NULL,
            INDEX `idx_credit_cards_status` (`status`),
            INDEX `idx_credit_cards_deleted_at` (`deleted_at`),
            CONSTRAINT `fk_credit_cards_account` FOREIGN KEY (`default_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $pdo->exec($sqlCards);

        // 2. Tabela credit_card_invoices
        $sqlInvoices = "CREATE TABLE IF NOT EXISTS `credit_card_invoices` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `credit_card_id` INT UNSIGNED NOT NULL,
            `reference_month` TINYINT UNSIGNED NOT NULL,
            `reference_year` SMALLINT UNSIGNED NOT NULL,
            `closing_date` DATE NOT NULL,
            `due_date` DATE NOT NULL,
            `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `status` ENUM('aberta', 'fechada', 'paga') NOT NULL DEFAULT 'aberta',
            `paid_at` DATETIME NULL,
            `payment_account_id` INT UNSIGNED NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_invoices_card_period` (`credit_card_id`, `reference_year`, `reference_month`),
            INDEX `idx_invoices_status` (`status`),
            CONSTRAINT `fk_invoices_card` FOREIGN KEY (`credit_card_id`) REFERENCES `credit_cards` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_invoices_account` FOREIGN KEY (`payment_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $pdo->exec($sqlInvoices);

        // 3. Adiciona FKs na tabela transactions para credit_cards e credit_card_invoices se a tabela existir
        try {
            $pdo->exec("ALTER TABLE `transactions` 
                ADD CONSTRAINT `fk_trans_credit_card` FOREIGN KEY (`credit_card_id`) REFERENCES `credit_cards` (`id`) ON DELETE SET NULL,
                ADD CONSTRAINT `fk_trans_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `credit_card_invoices` (`id`) ON DELETE SET NULL;");
        } catch (PDOException $e) {
            // Ignora se a constraint já existir
        }
    }

    public function down(PDO $pdo): void
    {
        try {
            $pdo->exec("ALTER TABLE `transactions` DROP FOREIGN KEY `fk_trans_credit_card`;");
            $pdo->exec("ALTER TABLE `transactions` DROP FOREIGN KEY `fk_trans_invoice`;");
        } catch (PDOException $e) {
            // Ignora se não existir
        }

        $pdo->exec("DROP TABLE IF EXISTS `credit_card_invoices`;");
        $pdo->exec("DROP TABLE IF EXISTS `credit_cards`;");
    }
};
