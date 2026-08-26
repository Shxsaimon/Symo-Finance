<?php
declare(strict_types=1);

/**
 * Symo — Script de Execução de Migrations via Linha de Comando (CLI)
 *
 * Uso: php migrate.php
 */

if (php_sapi_name() !== 'cli') {
    die("Este script só pode ser executado via linha de comando (CLI).\n");
}

define('APP_RUNNING', true);
define('APP_ROOT', __DIR__);

echo "========================================\n";
echo " Symo — Gerenciador de Migrations\n";
echo "========================================\n";

$configFile = __DIR__ . '/config/config.php';
if (!file_exists($configFile)) {
    die("Erro: Arquivo de configuração não encontrado em config/config.php\n");
}

$config = require $configFile;
echo "Ambiente: " . ($config['app']['env'] ?? 'development') . "\n";
echo "Banco de Dados: " . ($config['database']['dbname'] ?? 'não configurado') . "\n";
echo "Status: Estrutura base pronta para receber as migrations na Fase 2.\n";

