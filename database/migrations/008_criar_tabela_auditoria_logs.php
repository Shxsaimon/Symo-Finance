<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 008_criar_tabela_auditoria_logs
 * Cria a tabela de trilha de auditoria do sistema.
 */
return new class {
    public function up(PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `auditoria_logs` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `usuario_id` INT UNSIGNED NULL,
            `tabela` VARCHAR(64) NOT NULL,
            `registro_id` INT UNSIGNED NOT NULL,
            `acao` ENUM('INSERT', 'UPDATE', 'SOFT_DELETE', 'RESTORE', 'PURGE') NOT NULL,
            `valores_antigos` JSON NULL,
            `valores_novos` JSON NULL,
            `endereco_ip` VARCHAR(45) NOT NULL,
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_auditoria_tabela_registro` (`tabela`, `registro_id`),
            INDEX `idx_auditoria_criado_em` (`criado_em`),
            CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `auditoria_logs`;");
    }
};
