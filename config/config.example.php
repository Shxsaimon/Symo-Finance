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
        'name'        => 'Symo',
        'env'         => 'development', // 'development' ou 'production'
        'debug'       => true,           // Definir como false em produção
        'base_url'    => '',             // Deixar vazio para detecção automática ou definir URL base (ex: 'http://localhost/Sistema_Financeiro')
        'timezone'    => 'America/Sao_Paulo',
        'session_ttl' => 3600,           // Tempo de sessão inativa em segundos (60 minutos)
    ],
    'database' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'dbname'   => 'symo_db',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
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
