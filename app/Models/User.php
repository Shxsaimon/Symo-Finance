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
     * Valida nome e e-mail para a edição do próprio perfil.
     *
     * @param int $userId
     * @param array<string, mixed> $data
     * @return array{success: bool, message: string}
     */
    public function validateOwnProfile(int $userId, array $data): array
    {
        $usuarioAntigo = $this->find($userId);
        if (!$usuarioAntigo) {
            return ['success' => false, 'message' => 'Usuário não encontrado.'];
        }

        $nome = trim((string) ($data['nome'] ?? $usuarioAntigo['nome']));
        $email = trim(strtolower((string) ($data['email'] ?? $usuarioAntigo['email'])));

        if ($nome === '' || mb_strlen($nome) < 3) {
            return ['success' => false, 'message' => 'O nome completo deve conter no mínimo 3 caracteres.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'O endereço de e-mail informado é inválido.'];
        }

        if (mb_strlen($email) > 150) {
            return ['success' => false, 'message' => 'O e-mail deve conter no máximo 150 caracteres.'];
        }

        if ($this->emailExists($email, $userId)) {
            return ['success' => false, 'message' => 'Este e-mail já está em uso por outro usuário.'];
        }

        return ['success' => true, 'message' => 'Dados do perfil válidos.'];
    }

    /**
     * Atualiza somente os dados de perfil do próprio usuário.
     *
     * @param int $userId
     * @param array<string, mixed> $data
     * @return array{success: bool, message: string}
     */
    public function updateOwnProfile(int $userId, array $data): array
    {
        $validation = $this->validateOwnProfile($userId, $data);
        if (!$validation['success']) {
            return $validation;
        }

        $usuarioAntigo = $this->find($userId);
        if (!$usuarioAntigo) {
            return ['success' => false, 'message' => 'Usuário não encontrado.'];
        }

        $nome = trim((string) ($data['nome'] ?? $usuarioAntigo['nome']));
        $email = trim(strtolower((string) ($data['email'] ?? $usuarioAntigo['email'])));
        $avatar = array_key_exists('avatar', $data) ? $data['avatar'] : $usuarioAntigo['avatar'];

        $sql = "UPDATE `{$this->tabela}`
                SET nome = :nome, email = :email, avatar = :avatar, atualizado_em = NOW()
                WHERE id = :id AND excluido_em IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':avatar', $avatar, $avatar !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            if (in_array((string) $e->getCode(), ['23000', '23505'], true)) {
                return ['success' => false, 'message' => 'Este e-mail já está em uso por outro usuário.'];
            }
            throw $e;
        }

        $this->logAudit(
            'UPDATE',
            $userId,
            [
                'nome' => $usuarioAntigo['nome'],
                'email' => $usuarioAntigo['email'],
                'avatar' => $usuarioAntigo['avatar'],
            ],
            [
                'nome' => $nome,
                'email' => $email,
                'avatar' => $avatar,
            ],
            $userId
        );

        return ['success' => true, 'message' => 'Perfil atualizado com sucesso.'];
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

    /**
     * Retorna todos os usuários cadastrados não excluídos com ordenação.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllUsers(): array
    {
        $sql = "SELECT id, nome, email, perfil, status, avatar, criado_em, atualizado_em 
                FROM `{$this->tabela}` 
                WHERE excluido_em IS NULL 
                ORDER BY perfil ASC, nome ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Verifica se um e-mail já está em uso por outro usuário.
     *
     * @param string $email
     * @param int|null $excludeId ID a desconsiderar (em edições)
     * @return bool
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM `{$this->tabela}` 
                WHERE email = :email AND excluido_em IS NULL";
        
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':email', trim(strtolower($email)), PDO::PARAM_STR);
        if ($excludeId !== null) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $res = $stmt->fetch();
        return ((int) ($res['total'] ?? 0)) > 0;
    }

    /**
     * Cadastra um novo usuário no sistema.
     *
     * @param array<string, mixed> $data
     * @param int $currentUserId
     * @return array{success: bool, user_id?: int, message?: string}
     */
    public function createUser(array $data, int $currentUserId): array
    {
        $nome = trim((string) ($data['nome'] ?? ''));
        $email = trim(strtolower((string) ($data['email'] ?? '')));
        $senha = (string) ($data['senha'] ?? '');
        $perfil = in_array($data['perfil'] ?? '', ['admin', 'operador'], true) ? $data['perfil'] : 'operador';
        $status = in_array($data['status'] ?? '', ['ativo', 'inativo'], true) ? $data['status'] : 'ativo';
        $avatar = !empty($data['avatar']) ? (string) $data['avatar'] : null;

        if (empty($nome) || mb_strlen($nome) < 3) {
            return ['success' => false, 'message' => 'O nome completo deve conter no mínimo 3 caracteres.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'O endereço de e-mail informado é inválido.'];
        }

        if ($this->emailExists($email)) {
            return ['success' => false, 'message' => 'Este e-mail já está cadastrado para outro usuário.'];
        }

        if (mb_strlen($senha) < 8 || !preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $senha)) {
            return ['success' => false, 'message' => 'A senha deve conter no mínimo 8 caracteres, incluindo letras e números.'];
        }

        $hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

        $sql = "INSERT INTO `{$this->tabela}` (nome, email, senha, perfil, status, avatar, criado_em, atualizado_em)
                VALUES (:nome, :email, :senha, :perfil, :status, :avatar, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':senha', $hash, PDO::PARAM_STR);
        $stmt->bindValue(':perfil', $perfil, PDO::PARAM_STR);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':avatar', $avatar, $avatar !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();

        $newId = (int) $this->db->lastInsertId();

        $this->logAudit('INSERT', $newId, null, [
            'nome'   => $nome,
            'email'  => $email,
            'perfil' => $perfil,
            'status' => $status,
            'avatar' => $avatar,
        ], $currentUserId);

        return ['success' => true, 'user_id' => $newId, 'message' => 'Usuário cadastrado com sucesso.'];
    }

    /**
     * Realiza o autocadastro público de uma nova conta (perfil 'admin' da própria conta).
     * Clona o conjunto padrão de categorias, meios de pagamento e preferências pessoais.
     *
     * @param array<string, mixed> $data
     * @return array{success: bool, user_id?: int, message?: string}
     */
    public function registerSelf(array $data): array
    {
        $nome = trim((string) ($data['nome'] ?? ''));
        $email = trim(strtolower((string) ($data['email'] ?? '')));
        $senha = (string) ($data['senha'] ?? '');

        if (empty($nome) || mb_strlen($nome) < 3) {
            return ['success' => false, 'message' => 'O nome completo deve conter no mínimo 3 caracteres.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'O endereço de e-mail informado é inválido.'];
        }

        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $senha)) {
            return ['success' => false, 'message' => 'A senha deve conter no mínimo 8 caracteres, incluindo letras e números.'];
        }

        // Mensagem genérica: evita confirmar/negar a existência de um e-mail cadastrado (anti-enumeração).
        if ($this->emailExists($email)) {
            return ['success' => false, 'message' => 'Não foi possível concluir o cadastro. Verifique os dados informados.'];
        }

        $hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

        $this->db->beginTransaction();
        try {
            $isPgsql = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';
            $sql = "INSERT INTO `{$this->tabela}` (nome, email, senha, perfil, status, criado_em, atualizado_em)
                    VALUES (:nome, :email, :senha, 'admin', 'ativo', NOW(), NOW())"
                    . ($isPgsql ? " RETURNING id" : "");
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':senha', $hash, PDO::PARAM_STR);
            $stmt->execute();

            // No PostgreSQL, o RETURNING id devolve o identificador exato da linha inserida nesta mesma instrução.
            $newId = $isPgsql ? (int) $stmt->fetchColumn() : (int) $this->db->lastInsertId();

            if ($newId <= 0) {
                throw new RuntimeException('Não foi possível obter o ID do usuário recém-criado.');
            }

            DefaultDataHelper::popularDadosPadrao($this->db, $newId);

            // Gravação de auditoria feita diretamente (sem o catch silencioso de Model::logAudit),
            // para que uma eventual falha real apareça no log em vez de aparecer mascarada
            // pelo erro genérico "current transaction is aborted" do PostgreSQL.
            $enderecoIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmtAudit = $this->db->prepare("INSERT INTO auditoria_logs (usuario_id, tabela, registro_id, acao, valores_antigos, valores_novos, endereco_ip, criado_em)
                    VALUES (:usuario_id, :tabela, :registro_id, 'INSERT', NULL, :valores_novos, :endereco_ip, NOW())");
            $stmtAudit->bindValue(':usuario_id', $newId, PDO::PARAM_INT);
            $stmtAudit->bindValue(':tabela', $this->tabela, PDO::PARAM_STR);
            $stmtAudit->bindValue(':registro_id', $newId, PDO::PARAM_INT);
            $stmtAudit->bindValue(':valores_novos', json_encode(['nome' => $nome, 'email' => $email, 'perfil' => 'admin'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PDO::PARAM_STR);
            $stmtAudit->bindValue(':endereco_ip', $enderecoIp, PDO::PARAM_STR);
            $stmtAudit->execute();

            $stmtUser = $this->db->prepare("SELECT id, nome, email, senha, perfil, status, avatar, criado_em, atualizado_em FROM `{$this->tabela}` WHERE id = :id LIMIT 1");
            $stmtUser->bindValue(':id', $newId, PDO::PARAM_INT);
            $stmtUser->execute();
            $novoUsuario = $stmtUser->fetch();

            if (!$novoUsuario) {
                throw new RuntimeException('Usuário recém-criado não pôde ser recuperado após o cadastro.');
            }

            $this->db->commit();
            return ['success' => true, 'user_id' => $newId, 'user' => $novoUsuario, 'message' => 'Conta criada com sucesso.'];
        } catch (Throwable $e) {
            $this->db->rollBack();
            LoggerHelper::error($e, ['context' => 'User::registerSelf', 'email' => $email]);
            return ['success' => false, 'message' => 'Não foi possível concluir o cadastro. Tente novamente em instantes.'];
        }
    }

    /**
     * Atualiza os dados de um usuário existente.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @param int $currentUserId
     * @return array{success: bool, message: string}
     */
    public function updateUser(int $id, array $data, int $currentUserId): array
    {
        $usuarioAntigo = $this->find($id);
        if (!$usuarioAntigo) {
            return ['success' => false, 'message' => 'Usuário não encontrado.'];
        }

        $nome = trim((string) ($data['nome'] ?? $usuarioAntigo['nome']));
        $email = trim(strtolower((string) ($data['email'] ?? $usuarioAntigo['email'])));
        $perfil = in_array($data['perfil'] ?? '', ['admin', 'operador'], true) ? $data['perfil'] : $usuarioAntigo['perfil'];
        $status = in_array($data['status'] ?? '', ['ativo', 'inativo'], true) ? $data['status'] : $usuarioAntigo['status'];
        $avatar = array_key_exists('avatar', $data) ? $data['avatar'] : $usuarioAntigo['avatar'];

        if (empty($nome) || mb_strlen($nome) < 3) {
            return ['success' => false, 'message' => 'O nome completo deve conter no mínimo 3 caracteres.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'O endereço de e-mail informado é inválido.'];
        }

        if ($this->emailExists($email, $id)) {
            return ['success' => false, 'message' => 'Este e-mail já está em uso por outro usuário.'];
        }

        // Regra de segurança: Não permitir que o usuário atual inative ou altere seu próprio perfil para operador
        if ($id === $currentUserId) {
            $status = 'ativo'; // Força ativo
            $perfil = 'admin'; // Força admin
        }

        $sql = "UPDATE `{$this->tabela}` 
                SET nome = :nome, email = :email, perfil = :perfil, status = :status, avatar = :avatar, atualizado_em = NOW() 
                WHERE id = :id AND excluido_em IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':perfil', $perfil, PDO::PARAM_STR);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':avatar', $avatar, $avatar !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $usuarioNovo = $this->find($id);
        $this->logAudit('UPDATE', $id, $usuarioAntigo, $usuarioNovo, $currentUserId);

        return ['success' => true, 'message' => 'Dados do usuário atualizados com sucesso.'];
    }

    /**
     * Alterna o status do usuário entre ativo e inativo (com bloqueio para auto-desativação).
     *
     * @param int $id
     * @param int $currentUserId
     * @return array{success: bool, message: string}
     */
    public function toggleStatus(int $id, int $currentUserId): array
    {
        if ($id === $currentUserId) {
            return ['success' => false, 'message' => 'Você não pode desativar sua própria conta de usuário.'];
        }

        $user = $this->find($id);
        if (!$user) {
            return ['success' => false, 'message' => 'Usuário não encontrado.'];
        }

        $novoStatus = ($user['status'] === 'ativo') ? 'inativo' : 'ativo';

        $sql = "UPDATE `{$this->tabela}` SET status = :status, atualizado_em = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':status', $novoStatus, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $this->logAudit('UPDATE', $id, ['status' => $user['status']], ['status' => $novoStatus], $currentUserId);

        $msg = ($novoStatus === 'ativo') ? 'Usuário ativado com sucesso.' : 'Usuário inativado. Acesso revogado imediatamente.';
        return ['success' => true, 'message' => $msg, 'novo_status' => $novoStatus];
    }

    /**
     * Redefine a senha de um usuário administrativo.
     *
     * @param int $id
     * @param string $newPassword
     * @param int $currentUserId
     * @return array{success: bool, message: string}
     */
    public function resetPassword(int $id, string $newPassword, int $currentUserId): array
    {
        if (mb_strlen($newPassword) < 8 || !preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $newPassword)) {
            return ['success' => false, 'message' => 'A nova senha deve conter no mínimo 8 caracteres, incluindo letras e números.'];
        }

        $user = $this->find($id);
        if (!$user) {
            return ['success' => false, 'message' => 'Usuário não encontrado.'];
        }

        $success = $this->updatePassword($id, $newPassword);
        if ($success) {
            $this->logAudit('UPDATE', $id, ['senha' => '***ALTERADA_POR_ADMIN***'], ['alterado_por' => $currentUserId], $currentUserId);
            return ['success' => true, 'message' => 'Senha redefinida com sucesso.'];
        }

        return ['success' => false, 'message' => 'Não foi possível redefinir a senha.'];
    }

    /**
     * Exclui logicamente um usuário (com bloqueio para auto-exclusão).
     *
     * @param int $id
     * @param int $currentUserId
     * @return array{success: bool, message: string}
     */
    public function deleteUser(int $id, int $currentUserId): array
    {
        if ($id === $currentUserId) {
            return ['success' => false, 'message' => 'Você não pode excluir sua própria conta de usuário.'];
        }

        $user = $this->find($id);
        if (!$user) {
            return ['success' => false, 'message' => 'Usuário não encontrado.'];
        }

        $deleted = $this->softDelete($id, $currentUserId);
        if ($deleted) {
            return ['success' => true, 'message' => 'Usuário excluído com sucesso.'];
        }

        return ['success' => false, 'message' => 'Falha ao excluir o usuário.'];
    }
}
