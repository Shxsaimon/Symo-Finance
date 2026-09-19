<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 013_ajustar_perfil_google_usuarios
 * Introduz o perfil 'dev' (admin do sistema), permite senha nula (contas via Google)
 * e adiciona a coluna de vínculo com a identidade do Google (google_id).
 */
return new class {
    public function up(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            $pdo->exec("ALTER TABLE usuarios ALTER COLUMN senha DROP NOT NULL");
            $pdo->exec("ALTER TABLE usuarios DROP CONSTRAINT IF EXISTS usuarios_perfil_check");
            $pdo->exec("ALTER TABLE usuarios ADD CONSTRAINT usuarios_perfil_check CHECK (perfil IN ('admin', 'operador', 'dev'))");
            $pdo->exec("ALTER TABLE usuarios ALTER COLUMN perfil SET DEFAULT 'admin'");

            $columnExists = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'usuarios' AND column_name = 'google_id'")->fetchColumn();
            if ($columnExists === false) {
                $pdo->exec("ALTER TABLE usuarios ADD COLUMN google_id VARCHAR(255) UNIQUE");
            }
            return;
        }

        $pdo->exec("ALTER TABLE `usuarios` MODIFY `senha` VARCHAR(255) NULL");
        $pdo->exec("ALTER TABLE `usuarios` MODIFY `perfil` ENUM('admin', 'operador', 'dev') NOT NULL DEFAULT 'admin'");

        $columnExists = $pdo->query("SHOW COLUMNS FROM `usuarios` LIKE 'google_id'")->fetchColumn();
        if ($columnExists === false) {
            $pdo->exec("ALTER TABLE `usuarios` ADD COLUMN `google_id` VARCHAR(255) NULL UNIQUE AFTER `senha`, ADD INDEX `idx_usuarios_google_id` (`google_id`)");
        }
    }

    public function down(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            $pdo->exec("ALTER TABLE usuarios DROP COLUMN IF EXISTS google_id");
            $pdo->exec("ALTER TABLE usuarios DROP CONSTRAINT IF EXISTS usuarios_perfil_check");
            $pdo->exec("ALTER TABLE usuarios ADD CONSTRAINT usuarios_perfil_check CHECK (perfil IN ('admin', 'operador'))");
            $pdo->exec("ALTER TABLE usuarios ALTER COLUMN perfil SET DEFAULT 'operador'");
            $pdo->exec("ALTER TABLE usuarios ALTER COLUMN senha SET NOT NULL");
            return;
        }

        $pdo->exec("ALTER TABLE `usuarios` DROP INDEX `idx_usuarios_google_id`, DROP COLUMN `google_id`");
        $pdo->exec("ALTER TABLE `usuarios` MODIFY `perfil` ENUM('admin', 'operador') NOT NULL DEFAULT 'operador'");
        $pdo->exec("ALTER TABLE `usuarios` MODIFY `senha` VARCHAR(255) NOT NULL");
    }
};
