<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Roteador HTTP Centralizado
 *
 * Mapeia URIs e verbos HTTP (GET, POST) para controladores e ações específicas,
 * com suporte a rotas parametrizadas (ex: /transacoes/{id}/editar), middlewares e tratamento de 404.
 */
class Router
{
    /** @var array<string, array<int, array{pattern: string, regex: string, handler: callable|array<int, string>, middlewares: array<int, mixed>, params: array<int, string>}>> */
    private static array $routes = [
        'GET'  => [],
        'POST' => [],
    ];

    /**
     * Registra uma rota HTTP GET.
     *
     * @param string $path Caminho da rota (ex: '/dashboard' ou '/usuarios/{id}')
     * @param callable|array<int, string> $handler Ação do controlador [Controller::class, 'method'] ou closure
     * @param array<int, mixed> $middlewares Middlewares ou verificações prévias
     * @return void
     */
    public static function get(string $path, callable|array $handler, array $middlewares = []): void
    {
        self::addRoute('GET', $path, $handler, $middlewares);
    }

    /**
     * Registra uma rota HTTP POST.
     *
     * @param string $path Caminho da rota
     * @param callable|array<int, string> $handler Ação do controlador [Controller::class, 'method'] ou closure
     * @param array<int, mixed> $middlewares Middlewares ou verificações prévias
     * @return void
     */
    public static function post(string $path, callable|array $handler, array $middlewares = []): void
    {
        self::addRoute('POST', $path, $handler, $middlewares);
    }

    /**
     * Adiciona internamente a rota processando parâmetros dinâmicos {param}.
     *
     * @param string $method 'GET' ou 'POST'
     * @param string $path
     * @param callable|array<int, string> $handler
     * @param array<int, mixed> $middlewares
     * @return void
     */
    private static function addRoute(string $method, string $path, callable|array $handler, array $middlewares): void
    {
        $normalizedPath = '/' . trim($path, '/');
        if ($normalizedPath !== '/' && str_ends_with($normalizedPath, '/')) {
            $normalizedPath = rtrim($normalizedPath, '/');
        }

        // Converte {param} para grupos regex nomeados
        $paramNames = [];
        $pattern = preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function ($matches) use (&$paramNames) {
            $paramNames[] = $matches[1];
            return '([^/]+)';
        }, $normalizedPath);

        $regex = '#^' . $pattern . '$#';

        self::$routes[$method][] = [
            'pattern'     => $normalizedPath,
            'regex'       => $regex,
            'handler'     => $handler,
            'middlewares' => $middlewares,
            'params'      => $paramNames,
        ];
    }

    /**
     * Processa a requisição atual e despacha para a rota correspondente.
     *
     * @return void
     */
    public static function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = self::getCurrentUri();

        $routesForMethod = self::$routes[$method] ?? [];

        foreach ($routesForMethod as $route) {
            if (preg_match($route['regex'], $uri, $matches)) {
                array_shift($matches); // Remove o match completo

                // Mapeia parâmetros capturados
                $params = [];
                foreach ($route['params'] as $index => $name) {
                    $params[$name] = isset($matches[$index]) ? urldecode($matches[$index]) : null;
                }

                // Executa middlewares
                foreach ($route['middlewares'] as $middleware) {
                    if (is_callable($middleware)) {
                        $result = call_user_func($middleware);
                        if ($result === false) {
                            return; // Middleware interrompeu a execução
                        }
                    }
                }

                // Executa o handler da rota
                $handler = $route['handler'];

                if (is_callable($handler) && !is_array($handler)) {
                    call_user_func_array($handler, $params);
                    return;
                }

                if (is_array($handler) && count($handler) === 2) {
                    [$controllerClass, $actionMethod] = $handler;

                    if (!class_exists($controllerClass)) {
                        throw new RuntimeException("Classe Controller '{$controllerClass}' não encontrada.");
                    }

                    $controllerInstance = new $controllerClass();

                    if (!method_exists($controllerInstance, $actionMethod)) {
                        throw new RuntimeException("Método '{$actionMethod}' não existe em '{$controllerClass}'.");
                    }

                    call_user_func_array([$controllerInstance, $actionMethod], $params);
                    return;
                }

                throw new RuntimeException("Handler de rota inválido configurado.");
            }
        }

        // Rota não encontrada - Dispara 404
        self::handleNotFound();
    }

    /**
     * Normaliza a URI atual da requisição removendo prefixos de subpastas e query strings.
     *
     * @return string
     */
    public static function getCurrentUri(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        // Remove query string (?foo=bar)
        if (($pos = strpos($requestUri, '?')) !== false) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        // Remove o diretório base da subpasta caso o projeto rode dentro de htdocs/subpasta
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = str_replace('\\', '/', dirname($scriptName));

        if ($baseDir !== '/' && $baseDir !== '.' && str_starts_with($requestUri, $baseDir)) {
            $requestUri = substr($requestUri, strlen($baseDir));
        }

        $uri = '/' . trim($requestUri, '/');
        return $uri === '//' ? '/' : $uri;
    }

    /**
     * Renderiza a tela 404 amigável quando a rota não for encontrada.
     *
     * @return void
     */
    public static function handleNotFound(): void
    {
        http_response_code(404);

        $view404 = APP_ROOT . '/app/Views/errors/404.php';
        if (file_exists($view404)) {
            require $view404;
        } else {
            echo '<h1>404 — Página Não Encontrada</h1>';
            echo '<p>A página que você tentou acessar não existe ou foi movida.</p>';
        }
        exit;
    }
}
