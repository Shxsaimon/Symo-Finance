<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Helper de Autenticação Social Google OAuth 2.0
 *
 * Centraliza a geração de URLs de autorização, troca do authorization code por token
 * e recuperação segura dos dados do perfil do usuário junto às APIs oficiais do Google.
 */
class GoogleAuthHelper
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

    /**
     * Retorna os parâmetros de configuração do Google OAuth lidos do ambiente.
     *
     * @return array{client_id: string, client_secret: string, redirect_uri: string}
     */
    public static function configuration(): array
    {
        $clientId = trim((string) (getenv('GOOGLE_CLIENT_ID') ?: ''));
        $clientSecret = trim((string) (getenv('GOOGLE_CLIENT_SECRET') ?: ''));
        $redirectUri = trim((string) (getenv('GOOGLE_REDIRECT_URI') ?: ''));

        if ($redirectUri === '' && defined('BASE_URL')) {
            $redirectUri = rtrim(BASE_URL, '/') . '/auth/google/callback';
        }

        return [
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirectUri,
        ];
    }

    /**
     * Verifica se o Google OAuth possui as credenciais necessárias configuradas no ambiente.
     *
     * @return bool
     */
    public static function isConfigured(): bool
    {
        $config = self::configuration();
        return $config['client_id'] !== '' && $config['client_secret'] !== '';
    }

    /**
     * Monta e retorna a URL oficial de consentimento do Google OAuth 2.0.
     *
     * @param string $state Token criptográfico anti-CSRF
     * @return string
     */
    public static function getAuthUrl(string $state): string
    {
        $config = self::configuration();
        $params = [
            'client_id'              => $config['client_id'],
            'redirect_uri'           => $config['redirect_uri'],
            'response_type'          => 'code',
            'scope'                  => 'openid email profile',
            'state'                  => $state,
            'access_type'            => 'online',
            'prompt'                 => 'select_account',
            'include_granted_scopes' => 'true',
        ];

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Troca o código de autorização recebido pelo access_token do Google.
     *
     * @param string $code Código de autorização retornado no callback
     * @return array<string, mixed>|null
     */
    public static function exchangeCode(string $code): ?array
    {
        $config = self::configuration();
        if (!self::isConfigured() || $code === '') {
            return null;
        }

        $postFields = [
            'code'          => $code,
            'client_id'     => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri'  => $config['redirect_uri'],
            'grant_type'    => 'authorization_code',
        ];

        $ch = curl_init(self::TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($postFields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
                'User-Agent: Symo-Finance',
            ],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            LoggerHelper::error(
                new RuntimeException("Falha ao trocar código do Google por token (HTTP {$httpCode}): {$error}"),
                [
                    'context'  => 'GoogleAuthHelper::exchangeCode',
                    'response' => is_string($response) ? substr($response, 0, 500) : null
                ]
            );
            return null;
        }

        $data = json_decode((string) $response, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Consulta as informações do perfil do usuário autenticado no Google UserInfo endpoint.
     *
     * @param string $accessToken Token de acesso obtido
     * @return array{sub: string, email: string, email_verified: bool, name: string, picture: ?string}|null
     */
    public static function getUserInfo(string $accessToken): ?array
    {
        if ($accessToken === '') {
            return null;
        }

        $ch = curl_init(self::USERINFO_URL);
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET        => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
                'User-Agent: Symo-Finance',
            ],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            LoggerHelper::error(
                new RuntimeException("Falha ao consultar perfil no Google (HTTP {$httpCode}): {$error}"),
                [
                    'context'  => 'GoogleAuthHelper::getUserInfo',
                    'response' => is_string($response) ? substr($response, 0, 500) : null
                ]
            );
            return null;
        }

        $data = json_decode((string) $response, true);
        if (!is_array($data) || empty($data['sub']) || empty($data['email'])) {
            return null;
        }

        return [
            'sub'            => (string) $data['sub'],
            'email'          => (string) $data['email'],
            'email_verified' => filter_var($data['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'name'           => (string) ($data['name'] ?? explode('@', (string) $data['email'])[0]),
            'picture'        => !empty($data['picture']) ? (string) $data['picture'] : null,
        ];
    }
}
