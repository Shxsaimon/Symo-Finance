<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration: 019_incluir_contas_no_saldo_atual
 * Adiciona a preferência individual de inclusão de contas no Saldo Atual.
 */
return new class {
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            $columnExists = $pdo->query(
                "SELECT 1
                 FROM information_schema.columns
                 WHERE table_schema = current_schema()
                   AND table_name = 'contas'
                   AND column_name = 'incluir_saldo_atual'"
            )->fetchColumn() !== false;

            if (!$columnExists) {
                $pdo->exec(
                    'ALTER TABLE contas
                     ADD COLUMN incluir_saldo_atual BOOLEAN NOT NULL DEFAULT TRUE'
                );
            }

            $pdo->exec(
                "UPDATE contas SET incluir_saldo_atual = FALSE WHERE tipo = 'investimento'"
            );
            return;
        }

        if ($driver === 'mysql') {
            $columnExists = $pdo->query(
                "SHOW COLUMNS FROM `contas` LIKE 'incluir_saldo_atual'"
            )->fetchColumn() !== false;

            if (!$columnExists) {
                $pdo->exec(
                    'ALTER TABLE `contas`
                     ADD COLUMN `incluir_saldo_atual` TINYINT(1) NOT NULL DEFAULT 1'
                );
            }

            $pdo->exec(
                "UPDATE `contas` SET `incluir_saldo_atual` = 0 WHERE `tipo` = 'investimento'"
            );
            return;
        }

        throw new RuntimeException('Driver de banco não suportado para a migration de inclusão no Saldo Atual.');
    }

    public function down(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            $pdo->exec('ALTER TABLE contas DROP COLUMN IF EXISTS incluir_saldo_atual');
            return;
        }

        if ($driver === 'mysql') {
            $columnExists = $pdo->query(
                "SHOW COLUMNS FROM `contas` LIKE 'incluir_saldo_atual'"
            )->fetchColumn() !== false;

            if ($columnExists) {
                $pdo->exec('ALTER TABLE `contas` DROP COLUMN `incluir_saldo_atual`');
            }
            return;
        }

        throw new RuntimeException('Driver de banco não suportado para a reversão da migration de inclusão no Saldo Atual.');
    }
};
