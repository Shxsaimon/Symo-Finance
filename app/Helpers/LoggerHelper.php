<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Helper de Logs e Contingência
 *
 * Gerencia a gravação de erros de sistema e eventos de segurança no banco de dados (tabelas erros_logs e seguranca_logs)
 * com fallback automático para arquivos de texto em storage/logs/ em caso de indisponibilidade de banco.
 */
class LoggerHelper
{
    /**
     * Registra um erro ou exceção no sistema.
     *
     * @param string|Throwable $error Objeto de exceção ou mensagem de erro
     * @param array<string, mixed> $context Dados adicionais de contexto
     * @return void
     */
    public static function error(string|Throwable $error, array $context = []): void
    {
        $mensagem     = $error instanceof Throwable ? $error->getMessage() : (string) $error;
        $codigo       = $error instanceof Throwable ? (string) $error->getCode() : '0';
        $arquivo      = $error instanceof Throwable ? $error->getFile() : ($_SERVER['SCRIPT_FILENAME'] ?? 'desconhecido');
        $linha        = $error instanceof Throwable ? $error->getLine() : 0;
        $rastreamento = $error instanceof Throwable ? $error->getTraceAsString() : null;

        $usuarioId  = $_SESSION['user_id'] ?? null;
        $url        = $_SERVER['REQUEST_URI'] ?? '/';
        $metodo     = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $enderecoIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // Tenta gravar na tabela erros_logs do banco de dados
        try {
            $db = Database::getConnection();
            $sql = "INSERT INTO erros_logs (
                        mensagem, codigo, arquivo, linha, rastreamento, 
                        url, metodo, endereco_ip, usuario_id, criado_em
                    ) VALUES (
                        :mensagem, :codigo, :arquivo, :linha, :rastreamento, 
                        :url, :metodo, :endereco_ip, :usuario_id, NOW()
                    )";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':mensagem', $mensagem, PDO::PARAM_STR);
            $stmt->bindValue(':codigo', $codigo, PDO::PARAM_STR);
            $stmt->bindValue(':arquivo', $arquivo, PDO::PARAM_STR);
            $stmt->bindValue(':linha', $linha, PDO::PARAM_INT);
            $stmt->bindValue(':rastreamento', $rastreamento, $rastreamento !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':url', $url, PDO::PARAM_STR);
            $stmt->bindValue(':metodo', $metodo, PDO::PARAM_STR);
            $stmt->bindValue(':endereco_ip', $enderecoIp, PDO::PARAM_STR);
            $stmt->bindValue(':usuario_id', $usuarioId, $usuarioId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->execute();
        } catch (Throwable $e) {
            // Em caso de falha de banco, executa fallback imediato para o arquivo físico error.log
            self::fallbackLogToFile('error', $mensagem, [
                'codigo'     => $codigo,
                'arquivo'    => $arquivo,
                'linha'      => $linha,
                'ip'         => $enderecoIp,
                'url'        => $url,
                'metodo'     => $metodo,
                'usuario_id' => $usuarioId,
                'contexto'   => $context,
                'erro_banco' => $e->getMessage()
            ]);
        }
    }

    /**
     * Registra um evento de segurança (tentativas de login, acessos 403, CSRF inválido, etc.).
     *
     * @param string $evento Tipo de evento (ex: 'LOGIN_FAILED', 'FORBIDDEN_ACCESS', 'CSRF_INVALID')
     * @param array<string, mixed> $contexto Dados contextuais adicionais
     * @param string|null $email E-mail tentado (se aplicável)
     * @return void
     */
    public static function security(string $evento, array $contexto = [], ?string $email = null): void
    {
        $enderecoIp    = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $agenteUsuario = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/Desconhecido';
        $contextoJson  = !empty($contexto) ? json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

        // Tenta gravar na tabela seguranca_logs
        try {
            $db = Database::getConnection();
            $sql = "INSERT INTO seguranca_logs (
                        evento, email, endereco_ip, agente_usuario, contexto, criado_em
                    ) VALUES (
                        :evento, :email, :endereco_ip, :agente_usuario, :contexto, NOW()
                    )";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':evento', $evento, PDO::PARAM_STR);
            $stmt->bindValue(':email', $email, $email !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':endereco_ip', $enderecoIp, PDO::PARAM_STR);
            $stmt->bindValue(':agente_usuario', $agenteUsuario, PDO::PARAM_STR);
            $stmt->bindValue(':contexto', $contextoJson, $contextoJson !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->execute();
        } catch (Throwable $e) {
            // Em caso de falha de banco, executa fallback para o arquivo security.log
            self::fallbackLogToFile('security', $evento, [
                'email'      => $email,
                'ip'         => $enderecoIp,
                'contexto'   => $contexto,
                'erro_banco' => $e->getMessage()
            ]);
        }
    }

    /**
     * Gravação física de contingência em arquivo de log de texto.
     *
     * @param string $tipo 'error' ou 'security'
     * @param string $mensagem
     * @param array<string, mixed> $contexto
     * @return void
     */
    private static function fallbackLogToFile(string $tipo, string $mensagem, array $contexto = []): void
    {
        $logDir = APP_ROOT . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $fileName = $tipo === 'security' ? 'security.log' : 'error.log';
        $logFile  = $logDir . '/' . $fileName;

        $timestamp = date('Y-m-d H:i:s');
        $ip = $contexto['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $fileInfo = isset($contexto['arquivo'], $contexto['linha']) ? " em {$contexto['arquivo']}:{$contexto['linha']}" : '';
        $contextStr = !empty($contexto) ? ' | Contexto: ' . json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';

        $logEntry = "[{$timestamp}] [" . strtoupper($tipo) . "] [IP: {$ip}] Mensagem: {$mensagem}{$fileInfo}{$contextStr}" . PHP_EOL;

        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
