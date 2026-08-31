<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Model de Usuário (User)
 *
 * Responsável pelo acesso e persistência de dados de usuários,
 * verificação de senhas com BCRYPT, controle de status e auditoria.
 */
class User extends Model
{
    protected string $tabela = 'usuarios';
    protected string $chavePrimaria = 'id';

    /**
     * Localiza um usuário pelo seu endereço de e-mail (apenas registros não excluídos).
     *
     * @param string $email
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT id, nome, email, senha, perfil, status, avatar, criado_em, atualizado_em 
                FROM `{$this->tabela}` 
                WHERE email = :email AND excluido_em IS NULL 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':email', trim(strtolower($email)), PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Valida se a senha em texto puro corresponde ao hash criptografado armazenado (BCRYPT).
     *
     * @param string $password Senha informada
     * @param string $hash Hash salvo no banco de dados
     * @return bool
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Atualiza a senha de um usuário gerando hash BCRYPT com cost 12.
     *
     * @param int $userId ID do usuário
     * @param string $newPassword Nova senha em texto puro
     * @return bool
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        $sql = "UPDATE `{$this->tabela}` 
                SET senha = :senha, atualizado_em = NOW() 
                WHERE id = :id AND excluido_em IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':senha', $hash, PDO::PARAM_STR);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $success = $stmt->execute();

        if ($success) {
            $this->logAudit('UPDATE', $userId, ['senha' => '***HASH_ANTERIOR***'], ['senha' => '***NOVO_HASH***'], $userId);
        }

        return $success;
    }

    /**
     * Atualiza o timestamp de última atividade/acesso do usuário.
     *
     * @param int $userId
     * @return void
     */
    public function touch(int $userId): void
    {
        try {
            $sql = "UPDATE `{$this->tabela}` SET atualizado_em = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Throwable $e) {
            // Não interrompe o fluxo caso a atualização falhe
            LoggerHelper::error($e, ['context' => 'User::touch', 'user_id' => $userId]);
        }
    }

    /**
     * Verifica se o usuário possui status 'ativo'.
     *
     * @param array<string, mixed> $user
     * @return bool
     */
    public static function isActive(array $user): bool
    {
        return ($user['status'] ?? '') === 'ativo';
    }
}
