<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Helper de Mensagens Flash
 *
 * Gerencia mensagens temporárias de feedback para o usuário armazenadas na sessão,
 * que são automaticamente consumidas e destruídas na próxima exibição de tela.
 */
class FlashHelper
{
    private const SESSION_KEY = 'flash_messages';

    /**
     * Define uma nova mensagem flash para a sessão.
     *
     * @param string $type 'success', 'error', 'warning', 'info'
     * @param string $message Conteúdo do alerta
     * @return void
     */
    public static function set(string $type, string $message): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }

        $_SESSION[self::SESSION_KEY][$type][] = $message;
    }

    /**
     * Retorna a primeira mensagem do tipo especificado e a remove da sessão.
     *
     * @param string $type
     * @return string|null
     */
    public static function get(string $type): ?string
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (!empty($_SESSION[self::SESSION_KEY][$type])) {
            $message = array_shift($_SESSION[self::SESSION_KEY][$type]);
            if (empty($_SESSION[self::SESSION_KEY][$type])) {
                unset($_SESSION[self::SESSION_KEY][$type]);
            }
            return $message;
        }

        return null;
    }

    /**
     * Verifica se há mensagens cadastradas para um tipo específico.
     *
     * @param string $type
     * @return bool
     */
    public static function has(string $type): bool
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        return !empty($_SESSION[self::SESSION_KEY][$type]);
    }

    /**
     * Retorna todas as mensagens flash e limpa a sessão.
     *
     * @return array<string, array<int, string>>
     */
    public static function all(): array
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $messages = $_SESSION[self::SESSION_KEY] ?? [];
        unset($_SESSION[self::SESSION_KEY]);

        return $messages;
    }

    /**
     * Renderiza o HTML com alertas Bootstrap estruturados para exibição no layout.
     *
     * @return string
     */
    public static function render(): string
    {
        $messages = self::all();
        if (empty($messages)) {
            return '';
        }

        $iconMap = [
            'success' => 'bi-check-circle-fill',
            'error'   => 'bi-exclamation-triangle-fill',
            'warning' => 'bi-exclamation-circle-fill',
            'info'    => 'bi-info-circle-fill',
        ];

        $classMap = [
            'success' => 'alert-success',
            'error'   => 'alert-danger',
            'warning' => 'alert-warning',
            'info'    => 'alert-info',
        ];

        $html = '<div class="flash-messages-container mb-4">';

        foreach ($messages as $type => $list) {
            $alertClass = $classMap[$type] ?? 'alert-secondary';
            $icon = $iconMap[$type] ?? 'bi-bell-fill';

            foreach ($list as $msg) {
                $safeMsg = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
                $html .= "<div class=\"alert {$alertClass} alert-dismissible fade show d-flex align-items-center\" role=\"alert\">";
                $html .= "    <i class=\"bi {$icon} me-2 fs-5\"></i>";
                $html .= "    <div>{$safeMsg}</div>";
                $html .= "    <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Fechar\"></button>";
                $html .= "</div>";
            }
        }

        $html .= '</div>';

        return $html;
    }
}
