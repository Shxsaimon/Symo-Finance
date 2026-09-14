<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 001_criar_tabela_usuarios
 * Cria a tabela de usuários do sistema.
 */
return new class {
    public function up(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            require_once dirname(__DIR__) . '/PostgresSchema.php';
            PostgresSchema::create($pdo);
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `usuarios` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nome` VARCHAR(120) NOT NULL,
            `email` VARCHAR(150) NOT NULL UNIQUE,
            `senha` VARCHAR(255) NOT NULL,
            `perfil` ENUM('admin', 'operador') NOT NULL DEFAULT 'operador',
            `status` ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
            `avatar` VARCHAR(255) NULL,
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `excluido_em` DATETIME NULL,
            `excluido_por` INT UNSIGNED NULL,
            INDEX `idx_usuarios_email` (`email`),
            INDEX `idx_usuarios_status` (`status`),
            INDEX `idx_usuarios_excluido_em` (`excluido_em`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `usuarios`;");
    }
};
