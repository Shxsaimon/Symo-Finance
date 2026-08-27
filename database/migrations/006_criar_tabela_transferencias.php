<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 006_criar_tabela_transferencias
 * Cria a tabela de transferências financeiras entre contas.
 */
return new class {
    public function up(PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `transferencias` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `usuario_id` INT UNSIGNED NOT NULL,
            `conta_origem_id` INT UNSIGNED NOT NULL,
            `conta_destino_id` INT UNSIGNED NOT NULL,
            `valor` DECIMAL(15,2) NOT NULL,
            `data_transferencia` DATE NOT NULL,
            `observacoes` TEXT NULL,
            `transacao_origem_id` INT UNSIGNED NULL,
            `transacao_destino_id` INT UNSIGNED NULL,
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `excluido_em` DATETIME NULL,
            `excluido_por` INT UNSIGNED NULL,
            INDEX `idx_transf_data_transferencia` (`data_transferencia`),
            INDEX `idx_transf_excluido_em` (`excluido_em`),
            CONSTRAINT `fk_transf_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_transf_origem` FOREIGN KEY (`conta_origem_id`) REFERENCES `contas` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_transf_destino` FOREIGN KEY (`conta_destino_id`) REFERENCES `contas` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `transferencias`;");
    }
};
