<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 005_criar_tabela_transacoes
 * Cria a tabela de transações financeiras (receitas e despesas).
 */
return new class {
    public function up(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `transacoes` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `usuario_id` INT UNSIGNED NOT NULL,
            `tipo` ENUM('receita', 'despesa') NOT NULL,
            `descricao` VARCHAR(255) NOT NULL,
            `valor` DECIMAL(15,2) NOT NULL,
            `data_transacao` DATE NOT NULL,
            `data_vencimento` DATE NOT NULL,
            `data_pagamento` DATE NULL,
            `status` ENUM('pendente', 'pago', 'recebido') NOT NULL DEFAULT 'pendente',
            `conta_id` INT UNSIGNED NULL,
            `categoria_id` INT UNSIGNED NOT NULL,
            `meio_pagamento_id` INT UNSIGNED NOT NULL,
            `cartao_credito_id` INT UNSIGNED NULL,
            `fatura_id` INT UNSIGNED NULL,
            `grupo_parcelamento_id` VARCHAR(64) NULL,
            `numero_parcela` TINYINT UNSIGNED NULL DEFAULT 1,
            `total_parcelas` TINYINT UNSIGNED NULL DEFAULT 1,
            `observacoes` TEXT NULL,
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `excluido_em` DATETIME NULL,
            `excluido_por` INT UNSIGNED NULL,
            INDEX `idx_trans_data_transacao` (`data_transacao`),
            INDEX `idx_trans_data_vencimento` (`data_vencimento`),
            INDEX `idx_trans_status` (`status`),
            INDEX `idx_trans_tipo` (`tipo`),
            INDEX `idx_trans_conta_id` (`conta_id`),
            INDEX `idx_trans_categoria_id` (`categoria_id`),
            INDEX `idx_trans_cartao_credito_id` (`cartao_credito_id`),
            INDEX `idx_trans_fatura_id` (`fatura_id`),
            INDEX `idx_trans_grupo_parcelamento` (`grupo_parcelamento_id`),
            INDEX `idx_trans_excluido_em` (`excluido_em`),
            INDEX `idx_trans_dashboard_composto` (`excluido_em`, `status`, `data_vencimento`, `tipo`),
            CONSTRAINT `fk_trans_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_trans_conta` FOREIGN KEY (`conta_id`) REFERENCES `contas` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_trans_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_trans_meio_pagamento` FOREIGN KEY (`meio_pagamento_id`) REFERENCES `meios_pagamento` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `transacoes`;");
    }
};
