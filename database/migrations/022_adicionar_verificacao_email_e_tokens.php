<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 022_adicionar_verificacao_email_e_tokens
 * Adiciona controle de verificação de e-mail na tabela usuarios e cria a tabela tokens_verificacao
 * para gestão de códigos de 6 dígitos e links seguros de redefinição de senha.
 */
return new class {
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            // 1. Adiciona coluna email_verificado_em em usuarios se não existir
            $columnExists = $pdo->query(
                "SELECT 1
                 FROM information_schema.columns
                 WHERE table_schema = current_schema()
                   AND table_name = 'usuarios'
                   AND column_name = 'email_verificado_em'"
            )->fetchColumn() !== false;

            if (!$columnExists) {
                $pdo->exec('ALTER TABLE usuarios ADD COLUMN email_verificado_em TIMESTAMP NULL');
                // Usuários já existentes no banco mantêm-se verificados para não travar acessos atuais
                $pdo->exec('UPDATE usuarios SET email_verificado_em = criado_em WHERE email_verificado_em IS NULL');
            }

            // 2. Cria a tabela tokens_verificacao
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS tokens_verificacao (
                    id SERIAL PRIMARY KEY,
                    usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
                    codigo VARCHAR(6) NULL,
                    token_hash VARCHAR(64) NULL,
                    tipo VARCHAR(30) NOT NULL,
                    tentativas INTEGER NOT NULL DEFAULT 0,
                    expira_em TIMESTAMP NOT NULL,
                    usado_em TIMESTAMP NULL,
                    criado_em TIMESTAMP NOT NULL
                )"
            );

            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_tokens_usuario_tipo ON tokens_verificacao (usuario_id, tipo)');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_tokens_codigo ON tokens_verificacao (codigo)');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_tokens_token_hash ON tokens_verificacao (token_hash)');
            return;
        }

        if ($driver === 'mysql') {
            // 1. Adiciona coluna email_verificado_em em usuarios se não existir
            $columnExists = $pdo->query(
                "SHOW COLUMNS FROM `usuarios` LIKE 'email_verificado_em'"
            )->fetchColumn() !== false;

            if (!$columnExists) {
                $pdo->exec('ALTER TABLE `usuarios` ADD COLUMN `email_verificado_em` DATETIME NULL AFTER `email`');
                $pdo->exec('UPDATE `usuarios` SET `email_verificado_em` = `criado_em` WHERE `email_verificado_em` IS NULL');
            }

            // 2. Cria a tabela tokens_verificacao
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS `tokens_verificacao` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `usuario_id` INT UNSIGNED NOT NULL,
                    `codigo` VARCHAR(6) NULL,
                    `token_hash` VARCHAR(64) NULL,
                    `tipo` VARCHAR(30) NOT NULL,
                    `tentativas` INT UNSIGNED NOT NULL DEFAULT 0,
                    `expira_em` DATETIME NOT NULL,
                    `usado_em` DATETIME NULL,
                    `criado_em` DATETIME NOT NULL,
                    INDEX `idx_tokens_usuario_tipo` (`usuario_id`, `tipo`),
                    INDEX `idx_tokens_codigo` (`codigo`),
                    INDEX `idx_tokens_token_hash` (`token_hash`),
                    CONSTRAINT `fk_tokens_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            return;
        }

        throw new RuntimeException('Driver de banco não suportado para a migration de verificação de e-mail e tokens.');
    }

    public function down(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            $pdo->exec('DROP TABLE IF EXISTS tokens_verificacao CASCADE');
            $pdo->exec('ALTER TABLE usuarios DROP COLUMN IF EXISTS email_verificado_em');
            return;
        }

        if ($driver === 'mysql') {
            $pdo->exec('DROP TABLE IF EXISTS `tokens_verificacao`');
            $pdo->exec('ALTER TABLE `usuarios` DROP COLUMN `email_verificado_em`');
        }
    }
};
