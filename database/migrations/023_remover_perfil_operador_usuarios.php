<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 023_remover_perfil_operador_usuarios
 * Remove definitivamente o perfil legado 'operador' da tabela de usuários,
 * mantendo apenas os perfis 'admin' (padrão) e 'dev' (operação técnica da plataforma).
 */
return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("UPDATE usuarios SET perfil = 'admin' WHERE perfil = 'operador'");

        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            $pdo->exec("ALTER TABLE usuarios DROP CONSTRAINT IF EXISTS usuarios_perfil_check");
            $pdo->exec("ALTER TABLE usuarios ADD CONSTRAINT usuarios_perfil_check CHECK (perfil IN ('admin', 'dev'))");
            $pdo->exec("ALTER TABLE usuarios ALTER COLUMN perfil SET DEFAULT 'admin'");
            return;
        }

        $pdo->exec("ALTER TABLE `usuarios` MODIFY `perfil` ENUM('admin', 'dev') NOT NULL DEFAULT 'admin'");
    }

    public function down(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            $pdo->exec("ALTER TABLE usuarios DROP CONSTRAINT IF EXISTS usuarios_perfil_check");
            $pdo->exec("ALTER TABLE usuarios ADD CONSTRAINT usuarios_perfil_check CHECK (perfil IN ('admin', 'operador', 'dev'))");
            $pdo->exec("ALTER TABLE usuarios ALTER COLUMN perfil SET DEFAULT 'admin'");
            return;
        }

        $pdo->exec("ALTER TABLE `usuarios` MODIFY `perfil` ENUM('admin', 'operador', 'dev') NOT NULL DEFAULT 'admin'");
    }
};
