<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 009_criar_tabelas_seguranca_e_erros_logs
 * Cria as tabelas de logs de segurança e logs de erros de sistema.
 */
return new class {
    public function up(PDO $pdo): void
    {
        // Tabela seguranca_logs
        $sqlSec = "CREATE TABLE IF NOT EXISTS `seguranca_logs` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `evento` VARCHAR(80) NOT NULL,
            `email` VARCHAR(150) NULL,
            `endereco_ip` VARCHAR(45) NOT NULL,
            `agente_usuario` VARCHAR(255) NULL,
            `contexto` JSON NULL,
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_seg_evento` (`evento`),
            INDEX `idx_seg_ip` (`endereco_ip`),
            INDEX `idx_seg_criado_em` (`criado_em`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $pdo->exec($sqlSec);

        // Tabela erros_logs
        $sqlErr = "CREATE TABLE IF NOT EXISTS `erros_logs` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `mensagem` TEXT NOT NULL,
            `codigo` VARCHAR(50) NULL,
            `arquivo` VARCHAR(255) NULL,
            `linha` INT UNSIGNED NULL,
            `rastreamento` TEXT NULL,
            `url` VARCHAR(255) NULL,
            `metodo` VARCHAR(10) NULL,
            `endereco_ip` VARCHAR(45) NOT NULL,
            `usuario_id` INT UNSIGNED NULL,
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_err_criado_em` (`criado_em`),
            INDEX `idx_err_usuario_id` (`usuario_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $pdo->exec($sqlErr);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `erros_logs`;");
        $pdo->exec("DROP TABLE IF EXISTS `seguranca_logs`;");
    }
};
