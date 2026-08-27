<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 004_criar_tabela_meios_pagamento
 * Cria a tabela de meios de pagamento.
 */
return new class {
    public function up(PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `meios_pagamento` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nome` VARCHAR(80) NOT NULL,
            `eh_credito` TINYINT(1) NOT NULL DEFAULT 0,
            `status` ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `excluido_em` DATETIME NULL,
            `excluido_por` INT UNSIGNED NULL,
            INDEX `idx_meios_pagamento_status` (`status`),
            INDEX `idx_meios_pagamento_excluido_em` (`excluido_em`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `meios_pagamento`;");
    }
};
