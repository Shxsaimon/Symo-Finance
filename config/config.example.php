<?php
defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Arquivo de Referência de Configurações Gerais
 *
 * O arquivo 'config/config.php' lê variáveis de ambiente via getenv() e é
 * versionado sem segredos para execução na Vercel e no ambiente local.
 * Para desenvolvimento local, copie 'load-env.example.ps1' para 'load-env.ps1'
 * (ignorado pelo Git) e defina nele suas credenciais de teste.
 * Nunca insira credenciais reais diretamente em arquivos versionados.
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
    ],
    'storage' => [
        'blob_token'  => getenv('BLOB_READ_WRITE_TOKEN') ?: '',
        'store_id'    => getenv('symo_finance_STORE_ID') ?: (getenv('BLOB_STORE_ID') ?: ''),
        'proxy_secret' => getenv('BLOB_PROXY_SECRET') ?: '',
        'proxy_url'   => rtrim((string) (getenv('BLOB_PROXY_URL') ?: ''), '/'),
    ],
    // Usuário administrador do sistema (perfil 'dev'), criado via seed. Não confundir com admin de conta de usuário comum.
    'dev' => [
        'nome'  => getenv('SYSTEM_DEV_NAME') ?: 'Administrador do Sistema',
        'email' => getenv('SYSTEM_DEV_EMAIL') ?: '',
        'senha' => getenv('SYSTEM_DEV_PASSWORD') ?: '',
    ],
    'google_oauth' => [
        'client_id'     => getenv('GOOGLE_CLIENT_ID') ?: '',
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
        'redirect_uri'  => getenv('GOOGLE_REDIRECT_URI') ?: '',
    ],
];
