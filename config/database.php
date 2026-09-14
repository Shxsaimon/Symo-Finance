<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Fábrica de Conexão PDO (Singleton)
 *
 * Gerencia a conexão com o banco de dados MySQL/MariaDB utilizando Prepared Statements,
 * charset UTF-8 completo (utf8mb4) e modo de erro com exceções.
 */
class Database
{
    private static ?PDO $instance = null;

    /**
     * Construtor privado para impedir instanciação direta (Singleton).
     */
    private function __construct()
    {
    }

    /**
     * Impede a clonagem da instância.
     */
    private function __clone()
    {
    }

    /**
     * Retorna a instância única da conexão PDO.
     *
     * @param bool $createDbIfNotExists Cria o banco de dados se não existir (usado em migrations/setup).
     * @return PDO
     * @throws PDOException
     */
    public static function getConnection(bool $createDbIfNotExists = false): PDO
    {
        if (self::$instance === null) {
            $configFile = dirname(__DIR__) . '/config/config.php';
            if (!file_exists($configFile)) {
                throw new RuntimeException('Arquivo de configuração config/config.php não encontrado.');
            }

            $config = require $configFile;
            $dbConfig = $config['database'] ?? [];

            $databaseUrl = trim((string) ($dbConfig['url'] ?? ''));
            $driver = strtolower((string) ($dbConfig['driver'] ?? 'mysql'));
            $host     = $dbConfig['host'] ?? 'localhost';
            $port     = (int) ($dbConfig['port'] ?? 3306);
            $dbname   = $dbConfig['dbname'] ?? 'symo_db';
            $username = $dbConfig['username'] ?? 'root';
            $password = $dbConfig['password'] ?? '';
            $charset  = $dbConfig['charset'] ?? 'utf8mb4';

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            if ($databaseUrl !== '') {
                $parts = parse_url($databaseUrl);
                if ($parts === false || ($parts['scheme'] ?? '') !== 'postgresql') {
                    throw new InvalidArgumentException('DATABASE_URL deve ser uma URL PostgreSQL válida.');
                }

                $dsn = 'pgsql:host=' . ($parts['host'] ?? '')
                    . ';port=' . ($parts['port'] ?? 5432)
                    . ';dbname=' . ltrim((string) ($parts['path'] ?? ''), '/')
                    . ';sslmode=require';
                $username = rawurldecode((string) ($parts['user'] ?? ''));
                $password = rawurldecode((string) ($parts['pass'] ?? ''));
                self::$instance = new PDO($dsn, $username, $password, $options);
            } else {
                if ($driver !== 'mysql') {
                    throw new InvalidArgumentException('DB_DRIVER deve ser mysql quando DATABASE_URL não estiver definida.');
                }

                $mysqlOptions = $options;
                $mysqlOptions[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES {$charset} COLLATE utf8mb4_unicode_ci";

                if ($createDbIfNotExists) {
                    $dsnServer = "mysql:host={$host};port={$port};charset={$charset}";
                    $tempPdo = new PDO($dsnServer, $username, $password, $mysqlOptions);
                    $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET {$charset} COLLATE utf8mb4_unicode_ci");
                    unset($tempPdo);
                }

                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
                self::$instance = new PDO($dsn, $username, $password, $mysqlOptions);
            }
        }

        return self::$instance;
    }

    /**
     * Reseta a conexão atual (útil em testes ou reconfigurações).
     */
    public static function resetConnection(): void
    {
        self::$instance = null;
    }
}
