<?php
declare(strict_types=1);

/**
 * Symo — Gerenciador de Migrations e Seeds (CLI)
 *
 * Uso:
 *   php migrate.php          -> Executa migrations pendentes e seeds iniciais se for primeira execução
 *   php migrate.php --seed   -> Executa seeds de dados iniciais
 *   php migrate.php --status -> Exibe status das migrations
 *   php migrate.php --fresh  -> Recria o banco de dados do zero e roda migrations + seeds
 */

if (php_sapi_name() !== 'cli') {
    die("Este script só pode ser executado via linha de comando (CLI).\n");
}

define('APP_RUNNING', true);
define('APP_ROOT', __DIR__);

require_once __DIR__ . '/config/database.php';

echo "\n==================================================\n";
echo "           Symo — Gerenciador de Banco            \n";
echo "==================================================\n\n";

try {
    // Obtém conexão PDO (criando o banco de dados caso não exista)
    $pdo = Database::getConnection(true);
} catch (Throwable $e) {
    echo "[ERRO CRÍTICO] Falha ao conectar ao banco de dados: " . $e->getMessage() . "\n";
    exit(1);
}

// Garante que a tabela de migrations exista
$pdo->exec("CREATE TABLE IF NOT EXISTS `migrations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(255) NOT NULL UNIQUE,
    `batch` INT UNSIGNED NOT NULL,
    `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_migrations_migration` (`migration`),
    INDEX `idx_migrations_batch` (`batch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$args = $argv ?? [];
$isFresh  = in_array('--fresh', $args, true);
$onlySeed = in_array('--seed', $args, true);
$isStatus = in_array('--status', $args, true);

if ($isFresh) {
    echo "[!] ATENÇÃO: Recriando banco de dados completo (--fresh)...\n";
    $config = require __DIR__ . '/config/config.php';
    $dbname = $config['database']['dbname'] ?? 'symo_db';
    $charset = $config['database']['charset'] ?? 'utf8mb4';

    $pdo->exec("DROP DATABASE IF EXISTS `{$dbname}`");
    $pdo->exec("CREATE DATABASE `{$dbname}` CHARACTER SET {$charset} COLLATE utf8mb4_unicode_ci");
    Database::resetConnection();
    $pdo = Database::getConnection(false);

    $pdo->exec("CREATE TABLE IF NOT EXISTS `migrations` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `migration` VARCHAR(255) NOT NULL UNIQUE,
        `batch` INT UNSIGNED NOT NULL,
        `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_migrations_migration` (`migration`),
        INDEX `idx_migrations_batch` (`batch`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "[OK] Banco de dados recriado.\n\n";
}

// Função para rodar seeds
function runSeeds(PDO $pdo): void {
    $seedFile = __DIR__ . '/database/seeds/initial_data.php';
    if (file_exists($seedFile)) {
        echo "\n[*] Executando seeds de dados iniciais...\n";
        $seeder = require $seedFile;
        if (is_object($seeder) && method_exists($seeder, 'run')) {
            $seeder->run($pdo);
            echo "[OK] Seeds executados com sucesso!\n";
        }
    }
}

if ($onlySeed) {
    runSeeds($pdo);
    echo "\nConcluído com sucesso.\n";
    exit(0);
}

// 1. Obter histórico de migrations já executadas
$executed = $pdo->query("SELECT `migration` FROM `migrations`")->fetchAll(PDO::FETCH_COLUMN);

// 2. Localizar arquivos de migration no diretório
$migrationsDir = __DIR__ . '/database/migrations';
$files = glob($migrationsDir . '/*.php');
sort($files);

if ($isStatus) {
    echo "Status das Migrations:\n";
    echo str_repeat('-', 60) . "\n";
    foreach ($files as $file) {
        $name = basename($file, '.php');
        $status = in_array($name, $executed, true) ? "[APLICADA]" : "[PENDENTE]";
        echo sprintf(" %-12s %s\n", $status, $name);
    }
    echo str_repeat('-', 60) . "\n\n";
    exit(0);
}

// Determina o próximo número de batch
$currentBatch = (int) $pdo->query("SELECT MAX(`batch`) FROM `migrations`")->fetchColumn();
$nextBatch = $currentBatch + 1;

$appliedCount = 0;
echo "[*] Verificando migrations pendentes...\n";

foreach ($files as $file) {
    $name = basename($file, '.php');

    if (in_array($name, $executed, true)) {
        continue;
    }

    echo "  -> Aplicando: {$name}... ";
    
    $migration = require $file;
    if (is_object($migration) && method_exists($migration, 'up')) {
        try {
            $migration->up($pdo);

            $stmt = $pdo->prepare("INSERT INTO `migrations` (`migration`, `batch`) VALUES (:migration, :batch)");
            $stmt->execute([
                'migration' => $name,
                'batch'     => $nextBatch
            ]);

            echo "[OK]\n";
            $appliedCount++;
        } catch (Throwable $e) {
            echo "[FALHA]\n";
            echo "\n[ERRO CRÍTICO] Falha ao executar migration '{$name}': " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}

if ($appliedCount === 0) {
    echo "  [i] Nenhuma migration pendente encontrada. Banco de dados está atualizado.\n";
} else {
    echo "\n[OK] {$appliedCount} migration(s) aplicada(s) com sucesso no Lote {$nextBatch}!\n";
}

// Executa seeds automaticamente se for o primeiro lote de migrations ou se solicitado
$totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
if ($totalUsers === 0 || in_array('--seed', $args, true)) {
    runSeeds($pdo);
}

echo "\n==================================================\n";
echo " Operação finalizada com sucesso.\n";
echo "==================================================\n\n";
