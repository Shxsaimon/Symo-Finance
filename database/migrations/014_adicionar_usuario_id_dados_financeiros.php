<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 014_adicionar_usuario_id_dados_financeiros
 * Vincula contas, categorias, meios de pagamento e cartões de crédito a um usuário
 * proprietário, isolando os dados de cada conta cadastrada no sistema.
 *
 * Atenção: assume-se que as tabelas estão vazias (banco recriado via --fresh)
 * no momento da execução, conforme decisão de reset total dos dados de seed.
 */
return new class {
    private const TABELAS = ['contas', 'categorias', 'meios_pagamento', 'cartoes_credito'];

    public function up(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            foreach (self::TABELAS as $tabela) {
                $columnExists = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = '{$tabela}' AND column_name = 'usuario_id'")->fetchColumn();
                if ($columnExists === false) {
                    $pdo->exec("ALTER TABLE {$tabela} ADD COLUMN usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE RESTRICT");
                    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_{$tabela}_usuario_id ON {$tabela} (usuario_id)");
                }
            }
            return;
        }

        foreach (self::TABELAS as $tabela) {
            $columnExists = $pdo->query("SHOW COLUMNS FROM `{$tabela}` LIKE 'usuario_id'")->fetchColumn();
            if ($columnExists === false) {
                $pdo->exec("ALTER TABLE `{$tabela}` 
                    ADD COLUMN `usuario_id` INT UNSIGNED NOT NULL AFTER `id`,
                    ADD INDEX `idx_{$tabela}_usuario_id` (`usuario_id`),
                    ADD CONSTRAINT `fk_{$tabela}_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT");
            }
        }
    }

    public function down(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            foreach (self::TABELAS as $tabela) {
                $pdo->exec("ALTER TABLE {$tabela} DROP COLUMN IF EXISTS usuario_id");
            }
            return;
        }

        foreach (self::TABELAS as $tabela) {
            try {
                $pdo->exec("ALTER TABLE `{$tabela}` DROP FOREIGN KEY `fk_{$tabela}_usuario`");
            } catch (PDOException $e) {
                // Ignora se a constraint não existir
            }
            $pdo->exec("ALTER TABLE `{$tabela}` DROP INDEX `idx_{$tabela}_usuario_id`, DROP COLUMN `usuario_id`");
        }
    }
};
