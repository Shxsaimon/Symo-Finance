<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 003_criar_tabela_categorias
 * Cria a tabela de categorias de receitas e despesas.
 */
return new class {
    public function up(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `categorias` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nome` VARCHAR(100) NOT NULL,
            `tipo` ENUM('receita', 'despesa') NOT NULL,
            `icone` VARCHAR(50) NOT NULL DEFAULT 'bi-tag',
            `cor` VARCHAR(7) NOT NULL DEFAULT '#793CFE',
            `status` ENUM('ativa', 'inativa') NOT NULL DEFAULT 'ativa',
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `excluido_em` DATETIME NULL,
            `excluido_por` INT UNSIGNED NULL,
            INDEX `idx_categorias_tipo` (`tipo`),
            INDEX `idx_categorias_status` (`status`),
            INDEX `idx_categorias_excluido_em` (`excluido_em`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `categorias`;");
    }
};
