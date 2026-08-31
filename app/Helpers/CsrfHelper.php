<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Helper de Proteção Anti-CSRF
 *
 * Gera e valida tokens criptograficamente seguros para mitigar ataques
 * de Cross-Site Request Forgery em formulários e requisições HTTP mutantes.
 */
class CsrfHelper
{
    private const SESSION_KEY = 'csrf_token';

    /**
     * Gera um novo token anti-CSRF e salva na sessão ativa.
     *
     * @return string
     */
    public static function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::SESSION_KEY] = $token;

        return $token;
    }

    /**
     * Retorna o token CSRF existente na sessão ou gera um novo se não houver.
     *
     * @return string
     */
    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (empty($_SESSION[self::SESSION_KEY])) {
            return self::generateToken();
        }

        return (string) $_SESSION[self::SESSION_KEY];
    }

    /**
     * Valida se o token recebido é idêntico ao token armazenado na sessão.
     *
     * @param string|null $token
     * @return bool
     */
    public static function validateToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (empty($token) || empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    /**
     * Gera o elemento HTML <input type="hidden"> com o token CSRF pronto para formulários.
     *
     * @return string
     */
    public static function inputField(): string
    {
        $token = htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}
