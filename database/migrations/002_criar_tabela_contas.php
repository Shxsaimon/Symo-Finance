<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 002_criar_tabela_contas
 * Cria a tabela de contas financeiras e carteiras.
 */
return new class {
    public function up(PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `contas` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nome` VARCHAR(100) NOT NULL,
            `instituicao` VARCHAR(100) NULL,
            `tipo` ENUM('corrente', 'poupanca', 'investimento', 'dinheiro', 'outros') NOT NULL DEFAULT 'corrente',
            `saldo_inicial` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `saldo_atual` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `cor` VARCHAR(7) NOT NULL DEFAULT '#6F02FF',
            `status` ENUM('ativa', 'inativa') NOT NULL DEFAULT 'ativa',
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `excluido_em` DATETIME NULL,
            `excluido_por` INT UNSIGNED NULL,
            INDEX `idx_contas_status` (`status`),
            INDEX `idx_contas_excluido_em` (`excluido_em`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `contas`;");
    }
};
