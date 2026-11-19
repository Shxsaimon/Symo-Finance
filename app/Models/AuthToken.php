<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Model de Tokens de Autenticação (AuthToken)
 *
 * Gerencia tokens temporários de segurança: códigos de 6 dígitos para verificação de e-mail
 * e tokens criptográficos para recuperação e redefinição de senha.
 */
class AuthToken extends Model
{
    protected string $tabela = 'tokens_verificacao';
    protected string $chavePrimaria = 'id';

    /**
     * Gera e armazena um novo código numérico de 6 dígitos para verificação de e-mail (validade de 15 min).
     * Invalida códigos anteriores não utilizados do mesmo usuário.
     *
     * @param int $userId
     * @return string Código gerado de 6 dígitos
     */
    public function createVerificationCode(int $userId): string
    {
        // 1. Invalida tokens anteriores pendentes do mesmo tipo
        $stmtInvalidate = $this->db->prepare(
            "UPDATE `{$this->tabela}`
             SET usado_em = NOW()
             WHERE usuario_id = :uid AND tipo = 'verificacao_email' AND usado_em IS NULL"
        );
        $stmtInvalidate->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmtInvalidate->execute();

        // 2. Gera código criptograficamente seguro de 6 dígitos
        $now = time();
        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $criadoEm = date('Y-m-d H:i:s', $now);
        $expiraEm = date('Y-m-d H:i:s', $now + 900); // 15 minutos

        $stmt = $this->db->prepare(
            "INSERT INTO `{$this->tabela}` (usuario_id, codigo, tipo, tentativas, expira_em, criado_em)
             VALUES (:uid, :code, 'verificacao_email', 0, :expira_em, :criado_em)"
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':code', $code, PDO::PARAM_STR);
        $stmt->bindValue(':expira_em', $expiraEm, PDO::PARAM_STR);
        $stmt->bindValue(':criado_em', $criadoEm, PDO::PARAM_STR);
        $stmt->execute();

        return $code;
    }

    /**
     * Valida o código de 6 dígitos informado pelo usuário.
     * Conta tentativas falhas (máximo 5) para proteção contra força bruta.
     *
     * @param int $userId
     * @param string $code
     * @return array{success: bool, message: string}
     */
    public function verifyCode(int $userId, string $code): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, codigo, tentativas, expira_em, usado_em, criado_em
             FROM `{$this->tabela}`
             WHERE usuario_id = :uid AND tipo = 'verificacao_email' AND usado_em IS NULL
             ORDER BY criado_em DESC
             LIMIT 1"
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $token = $stmt->fetch();

        if (!$token) {
            return [
                'success' => false,
                'message' => 'Nenhum código de verificação ativo encontrado. Solicite um novo código.'
            ];
        }

        // Bloqueio por excesso de tentativas
        if ((int) $token['tentativas'] >= 5) {
            return [
                'success' => false,
                'message' => 'Número máximo de tentativas incorretas excedido. Solicite um novo código.'
            ];
        }

        // Verificação de expiração resiliente a fuso horário
        $expiraEmStr = (string) $token['expira_em'];
        $criadoEmStr = (string) ($token['criado_em'] ?? '');
        $isExpired = false;

        if ($criadoEmStr !== '' && strtotime($criadoEmStr) > (time() + 300)) {
            // Caso criado_em tenha sido registrado em UTC
            $expiraEmUtc = strtotime($expiraEmStr . ' UTC');
            $expiraEmLocal = strtotime($expiraEmStr);
            if ($expiraEmUtc < time() || $expiraEmLocal < time()) {
                $isExpired = true;
            }
        } elseif (strtotime($expiraEmStr) < time()) {
            $isExpired = true;
        }

        if ($isExpired) {
            return [
                'success' => false,
                'message' => 'O código de verificação expirou após 15 minutos. Solicite um novo código.'
            ];
        }

        // Validação com comparação resistente a timing attack
        $trimmedCode = trim($code);
        if (!hash_equals((string) $token['codigo'], $trimmedCode)) {
            // Incrementa contador de tentativas
            $stmtTentativa = $this->db->prepare(
                "UPDATE `{$this->tabela}` SET tentativas = tentativas + 1 WHERE id = :id"
            );
            $stmtTentativa->bindValue(':id', (int) $token['id'], PDO::PARAM_INT);
            $stmtTentativa->execute();

            return [
                'success' => false,
                'message' => 'Código de verificação incorreto. Verifique os 6 dígitos digitados.'
            ];
        }

        // Marca como utilizado
        $stmtUse = $this->db->prepare("UPDATE `{$this->tabela}` SET usado_em = NOW() WHERE id = :id");
        $stmtUse->bindValue(':id', (int) $token['id'], PDO::PARAM_INT);
        $stmtUse->execute();

        return [
            'success' => true,
            'message' => 'E-mail verificado com sucesso!'
        ];
    }

    /**
     * Verifica se o usuário pode solicitar um novo código (cooldown de segurança para evitar spam).
     *
     * @param int $userId
     * @param int $cooldownSeconds Tempo mínimo entre disparos em segundos (padrão: 60s)
     * @return bool
     */
    public function canResendCode(int $userId, int $cooldownSeconds = 60): bool
    {
        $stmt = $this->db->prepare(
            "SELECT criado_em
             FROM `{$this->tabela}`
             WHERE usuario_id = :uid AND tipo = 'verificacao_email'
             ORDER BY criado_em DESC
             LIMIT 1"
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $lastToken = $stmt->fetch();

        if (!$lastToken || empty($lastToken['criado_em'])) {
            return true;
        }

        $criadoEmStr = (string) $lastToken['criado_em'];
        $criadoEmTs = strtotime($criadoEmStr);
        $tempoDecorrido = time() - $criadoEmTs;

        // Se o tempo decorrido for negativo (divergência histórica de fuso UTC vs Local),
        // calcula interpretando como UTC
        if ($tempoDecorrido < 0) {
            $criadoEmUtc = strtotime($criadoEmStr . ' UTC');
            $tempoDecorrido = time() - $criadoEmUtc;
        }

        // Se por qualquer anomalia de relógio ainda for negativo, permite o reenvio
        if ($tempoDecorrido < 0) {
            return true;
        }

        return $tempoDecorrido >= $cooldownSeconds;
    }

    /**
     * Gera e armazena um token criptográfico seguro para redefinição de senha (validade de 60 min).
     *
     * @param int $userId
     * @return string Token bruto para envio na URL
     */
    public function createPasswordResetToken(int $userId): string
    {
        // 1. Invalida tokens anteriores não usados
        $stmtInvalidate = $this->db->prepare(
            "UPDATE `{$this->tabela}`
             SET usado_em = NOW()
             WHERE usuario_id = :uid AND tipo = 'recuperacao_senha' AND usado_em IS NULL"
        );
        $stmtInvalidate->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmtInvalidate->execute();

        // 2. Gera token seguro e salva o hash sha256 no banco
        $now = time();
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $criadoEm = date('Y-m-d H:i:s', $now);
        $expiraEm = date('Y-m-d H:i:s', $now + 3600); // 60 minutos

        $stmt = $this->db->prepare(
            "INSERT INTO `{$this->tabela}` (usuario_id, token_hash, tipo, tentativas, expira_em, criado_em)
             VALUES (:uid, :token_hash, 'recuperacao_senha', 0, :expira_em, :criado_em)"
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':token_hash', $tokenHash, PDO::PARAM_STR);
        $stmt->bindValue(':expira_em', $expiraEm, PDO::PARAM_STR);
        $stmt->bindValue(':criado_em', $criadoEm, PDO::PARAM_STR);
        $stmt->execute();

        return $rawToken;
    }

    /**
     * Valida um token de redefinição de senha a partir do token bruto recebido na URL.
     *
     * @param string $rawToken
     * @return array<string, mixed>|null Dados do token ou null se inválido/expirado
     */
    public function verifyPasswordResetToken(string $rawToken): ?array
    {
        if (trim($rawToken) === '' || strlen($rawToken) !== 64) {
            return null;
        }

        $tokenHash = hash('sha256', $rawToken);

        $stmt = $this->db->prepare(
            "SELECT id, usuario_id, expira_em, usado_em, criado_em
             FROM `{$this->tabela}`
             WHERE token_hash = :hash AND tipo = 'recuperacao_senha' AND usado_em IS NULL
             LIMIT 1"
        );
        $stmt->bindValue(':hash', $tokenHash, PDO::PARAM_STR);
        $stmt->execute();
        $token = $stmt->fetch();

        if (!$token) {
            return null;
        }

        $expiraEmStr = (string) $token['expira_em'];
        $criadoEmStr = (string) ($token['criado_em'] ?? '');
        $isExpired = false;

        if ($criadoEmStr !== '' && strtotime($criadoEmStr) > (time() + 300)) {
            $expiraEmUtc = strtotime($expiraEmStr . ' UTC');
            $expiraEmLocal = strtotime($expiraEmStr);
            if ($expiraEmUtc < time() || $expiraEmLocal < time()) {
                $isExpired = true;
            }
        } elseif (strtotime($expiraEmStr) < time()) {
            $isExpired = true;
        }

        if ($isExpired) {
            return null;
        }

        return $token;
    }

    /**
     * Marca o token de recuperação de senha como utilizado.
     *
     * @param int $tokenId
     * @return bool
     */
    public function consumePasswordResetToken(int $tokenId): bool
    {
        $stmt = $this->db->prepare("UPDATE `{$this->tabela}` SET usado_em = NOW() WHERE id = :id");
        $stmt->bindValue(':id', $tokenId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
