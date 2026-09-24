<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 017_adicionar_imagens_feedback
 * Adiciona a coluna `imagens` (JSON com as URLs do Vercel Blob) diretamente na tabela `feedback`.
 */
return new class {
    public function up(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            $pdo->exec('ALTER TABLE feedback ADD COLUMN IF NOT EXISTS imagens JSONB');
            return;
        }

        $pdo->exec("ALTER TABLE `feedback` ADD COLUMN `imagens` JSON NULL AFTER `tipo`");
    }

    public function down(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            $pdo->exec('ALTER TABLE feedback DROP COLUMN IF EXISTS imagens');
            return;
        }

        $pdo->exec('ALTER TABLE `feedback` DROP COLUMN `imagens`');
    }
};
