<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Classe Base Controller
 *
 * Fornece métodos utilitários para renderização de views com layout,
 * respostas JSON estruturadas, redirecionamento e tratamento seguro de requisições.
 */
abstract class Controller
{
    /**
     * Renderiza uma view com dados e envolve em layout caso especificado.
     *
     * @param string $view Caminho relativo da view dentro de app/Views (ex: 'auth/login' ou 'errors/404')
     * @param array<string, mixed> $data Dados passados para a view
     * @param string|null $layout Layout a ser utilizado dentro de app/Views (ex: 'layouts/main') ou null para renderização sem layout
     * @return void
     */
    protected function render(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        $viewFile = APP_ROOT . '/app/Views/' . ltrim($view, '/') . '.php';

        if (!file_exists($viewFile)) {
            throw new RuntimeException("Arquivo de view '{$view}' não encontrado em: {$viewFile}");
        }

        // Extrai variáveis para a view
        extract($data, EXTR_SKIP);

        // Se houver layout, captura o conteúdo da view em buffer
        if ($layout !== null) {
            $layoutFile = APP_ROOT . '/app/Views/' . ltrim($layout, '/') . '.php';
            if (!file_exists($layoutFile)) {
                throw new RuntimeException("Arquivo de layout '{$layout}' não encontrado em: {$layoutFile}");
            }

            ob_start();
            require $viewFile;
            $content = ob_get_clean();

            require $layoutFile;
        } else {
            require $viewFile;
        }
    }

    /**
     * Envia uma resposta JSON formatada.
     *
     * @param mixed $data Dados a serem serializados em JSON
     * @param int $statusCode Código de status HTTP
     * @return void
     */
    protected function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Redireciona para uma URL interna de forma segura.
     *
     * @param string $path Caminho relativo (ex: '/login' ou '/dashboard') ou URL interna completa
     * @param string $fallbackPath Caminho relativo padrão caso $path aponte para domínio externo
     * @return void
     */
    protected function redirect(string $path, string $fallbackPath = '/dashboard'): void
    {
        $candidate = trim($path);
        $baseUrl = rtrim(BASE_URL, '/');

        if ($candidate !== '' && !str_contains($candidate, "\r") && !str_contains($candidate, "\n")) {
            if (preg_match('#^https?://#i', $candidate)) {
                if (
                    $baseUrl !== ''
                    && str_starts_with($candidate, $baseUrl)
                    && (strlen($candidate) === strlen($baseUrl) || in_array($candidate[strlen($baseUrl)], ['/', '?'], true))
                ) {
                    header('Location: ' . $candidate);
                    exit;
                }
                $candidate = $fallbackPath;
            } elseif (str_starts_with($candidate, '//') || str_starts_with($candidate, '/\\')) {
                $candidate = $fallbackPath;
            }
        } else {
            $candidate = $fallbackPath;
        }

        $targetUrl = $baseUrl . '/' . ltrim($candidate, '/');
        header('Location: ' . $targetUrl);
        exit;
    }

    /**
     * Valida o token anti-CSRF recebido via POST ou cabeçalho.
     *
     * @return bool
     */
    protected function validateCsrf(): bool
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        return CsrfHelper::validateToken($token);
    }

    /**
     * Retorna os dados de $_POST sanitizados contra tags maliciosas e espaços extras.
     *
     * @return array<string, mixed>
     */
    protected function getPost(): array
    {
        return $this->sanitize($_POST ?? []);
    }

    /**
     * Retorna os dados de $_GET sanitizados.
     *
     * @return array<string, mixed>
     */
    protected function getQuery(): array
    {
        return $this->sanitize($_GET ?? []);
    }

    /**
     * Sanitiza strings, arrays ou valores escalares recursivamente.
     *
     * @param mixed $data
     * @return mixed
     */
    protected function sanitize(mixed $data): mixed
    {
        if (is_array($data)) {
            $clean = [];
            foreach ($data as $key => $value) {
                $cleanKey = is_string($key) ? trim(strip_tags($key)) : $key;
                $clean[$cleanKey] = $this->sanitize($value);
            }
            return $clean;
        }

        if (is_string($data)) {
            return trim($data);
        }

        return $data;
    }
}
