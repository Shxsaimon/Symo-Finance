<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 007_criar_tabelas_cartoes_e_faturas
 * Cria as tabelas de cartões de crédito e faturas mensais, e vincula foreign keys com transações.
 */
return new class {
    public function up(PDO $pdo): void
    {
        // 1. Tabela cartoes_credito
        $sqlCartoes = "CREATE TABLE IF NOT EXISTS `cartoes_credito` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nome` VARCHAR(100) NOT NULL,
            `limite_credito` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `dia_fechamento` TINYINT UNSIGNED NOT NULL,
            `dia_vencimento` TINYINT UNSIGNED NOT NULL,
            `conta_padrao_id` INT UNSIGNED NULL,
            `cor` VARCHAR(7) NOT NULL DEFAULT '#3BD7D7',
            `status` ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `excluido_em` DATETIME NULL,
            `excluido_por` INT UNSIGNED NULL,
            INDEX `idx_cartoes_status` (`status`),
            INDEX `idx_cartoes_excluido_em` (`excluido_em`),
            CONSTRAINT `fk_cartoes_conta_padrao` FOREIGN KEY (`conta_padrao_id`) REFERENCES `contas` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $pdo->exec($sqlCartoes);

        // 2. Tabela faturas_cartao
        $sqlFaturas = "CREATE TABLE IF NOT EXISTS `faturas_cartao` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `cartao_credito_id` INT UNSIGNED NOT NULL,
            `mes_referencia` TINYINT UNSIGNED NOT NULL,
            `ano_referencia` SMALLINT UNSIGNED NOT NULL,
            `data_fechamento` DATE NOT NULL,
            `data_vencimento` DATE NOT NULL,
            `valor_total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `status` ENUM('aberta', 'fechada', 'paga') NOT NULL DEFAULT 'aberta',
            `pago_em` DATETIME NULL,
            `conta_pagamento_id` INT UNSIGNED NULL,
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_faturas_periodo` (`cartao_credito_id`, `ano_referencia`, `mes_referencia`),
            INDEX `idx_faturas_status` (`status`),
            CONSTRAINT `fk_faturas_cartao` FOREIGN KEY (`cartao_credito_id`) REFERENCES `cartoes_credito` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_faturas_conta_pagamento` FOREIGN KEY (`conta_pagamento_id`) REFERENCES `contas` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $pdo->exec($sqlFaturas);

        // 3. Adiciona FKs na tabela transacoes para cartoes_credito e faturas_cartao
        try {
            $pdo->exec("ALTER TABLE `transacoes` 
                ADD CONSTRAINT `fk_trans_cartao_credito` FOREIGN KEY (`cartao_credito_id`) REFERENCES `cartoes_credito` (`id`) ON DELETE SET NULL,
                ADD CONSTRAINT `fk_trans_fatura` FOREIGN KEY (`fatura_id`) REFERENCES `faturas_cartao` (`id`) ON DELETE SET NULL;");
        } catch (PDOException $e) {
            // Ignora se a constraint já existir
        }
    }

    public function down(PDO $pdo): void
    {
        try {
            $pdo->exec("ALTER TABLE `transacoes` DROP FOREIGN KEY `fk_trans_cartao_credito`;");
            $pdo->exec("ALTER TABLE `transacoes` DROP FOREIGN KEY `fk_trans_fatura`;");
        } catch (PDOException $e) {
            // Ignora se não existir
        }

        $pdo->exec("DROP TABLE IF EXISTS `faturas_cartao`;");
        $pdo->exec("DROP TABLE IF EXISTS `cartoes_credito`;");
    }
};
