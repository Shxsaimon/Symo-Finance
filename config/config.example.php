<?php
defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Arquivo de Exemplo de Configurações Gerais
 *
 * Copie este arquivo para 'config.php' no mesmo diretório e preencha
 * com as credenciais reais do seu ambiente (desenvolvimento ou produção).
 * NUNCA comite o arquivo 'config.php' com credenciais reais no repositório.
 */

return [
    'app' => [
        'name'        => getenv('APP_NAME') ?: 'Symo',
        'env'         => getenv('APP_ENV') ?: 'development',
        'debug'       => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN),
        'base_url'    => rtrim((string) (getenv('APP_BASE_URL') ?: ''), '/'),
        'timezone'    => getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo',
        'session_ttl' => max(60, (int) (getenv('APP_SESSION_TTL') ?: 3600)),
    ],
    'database' => [
        'url'      => getenv('DATABASE_URL') ?: '',
        'driver'   => getenv('DB_DRIVER') ?: 'mysql',
        'host'     => getenv('DB_HOST') ?: 'localhost',
        'port'     => (int) (getenv('DB_PORT') ?: 3306),
        'dbname'   => getenv('DB_NAME') ?: 'symo_db',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset'  => getenv('DB_CHARSET') ?: 'utf8mb4',
    ],
    'upload' => [
        'max_size'    => 2097152, // 2 MB em bytes
        'allowed_ext' => ['jpg', 'jpeg', 'png', 'webp'],
        'paths'       => [
            'avatars' => dirname(__DIR__) . '/storage/uploads/avatars/',
            'logos'   => dirname(__DIR__) . '/storage/uploads/logos/',
        ],
    ],
    'logs' => [
        'file_fallback' => dirname(__DIR__) . '/storage/logs/error.log',
        'security_file' => dirname(__DIR__) . '/storage/logs/security.log',
    ]
];
