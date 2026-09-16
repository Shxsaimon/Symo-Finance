<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

return new class {
    public function up(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            $pdo->exec('ALTER TABLE usuarios ALTER COLUMN avatar TYPE TEXT');
            $pdo->exec('ALTER TABLE configuracoes ALTER COLUMN valor TYPE TEXT');
            return;
        }

        $pdo->exec('ALTER TABLE `usuarios` MODIFY `avatar` TEXT NULL');
        $pdo->exec('ALTER TABLE `configuracoes` MODIFY `valor` TEXT NULL');
    }

    public function down(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            $pdo->exec('ALTER TABLE usuarios ALTER COLUMN avatar TYPE VARCHAR(255)');
            $pdo->exec('ALTER TABLE configuracoes ALTER COLUMN valor TYPE VARCHAR(255)');
            return;
        }

        $pdo->exec('ALTER TABLE `usuarios` MODIFY `avatar` VARCHAR(255) NULL');
        $pdo->exec('ALTER TABLE `configuracoes` MODIFY `valor` VARCHAR(255) NULL');
    }
};
