<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Model de Configurações Globais e Parâmetros do Sistema (Setting)
 *
 * Gerencia preferências gerais (moeda, separadores, nome da aplicação, logo),
 * auditoria de modificações e verificação do status de migrações do banco.
 */
class Setting extends Model
{
    protected string $tabela = 'configuracoes';
    protected string $chavePrimaria = 'id';

    /**
     * Retorna todas as configurações como um array associativo [chave => valor_convertido].
     *
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        $sql = "SELECT chave, valor, tipo FROM `{$this->tabela}`";
        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll();

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['chave']] = $this->castValue($row['valor'], (string)$row['tipo']);
        }

        return $settings;
    }

    /**
     * Retorna o valor de uma configuração específica pelo nome da chave.
     *
     * @param string $chave
     * @param mixed $default
     * @return mixed
     */
    public function get(string $chave, mixed $default = null): mixed
    {
        $sql = "SELECT valor, tipo FROM `{$this->tabela}` WHERE chave = :chave LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':chave', $chave, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row) {
            return $this->castValue($row['valor'], (string)$row['tipo']);
        }

        return $default;
    }

    /**
     * Define ou atualiza o valor de uma configuração.
     *
     * @param string $chave
     * @param mixed $valor
     * @param string $tipo
     * @param string|null $descricao
     * @param int $userId
     * @return bool
     */
    public function set(string $chave, mixed $valor, string $tipo = 'string', ?string $descricao = null, int $userId = 1): bool
    {
        $valorString = is_array($valor) ? json_encode($valor, JSON_UNESCAPED_UNICODE) : (string)$valor;
        $antigo = $this->get($chave);

        $sql = "INSERT INTO `{$this->tabela}` (chave, valor, tipo, descricao, atualizado_em)
                VALUES (:chave, :valor, :tipo, :descricao, NOW())
                ON DUPLICATE KEY UPDATE valor = :valor_up, tipo = :tipo_up, atualizado_em = NOW()";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':chave', $chave, PDO::PARAM_STR);
        $stmt->bindValue(':valor', $valorString, PDO::PARAM_STR);
        $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
        $stmt->bindValue(':descricao', $descricao, $descricao !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':valor_up', $valorString, PDO::PARAM_STR);
        $stmt->bindValue(':tipo_up', $tipo, PDO::PARAM_STR);
        $success = $stmt->execute();

        if ($success) {
            $this->logAudit('UPDATE', 0, ['chave' => $chave, 'valor' => $antigo], ['chave' => $chave, 'valor' => $valor], $userId);
        }

        return $success;
    }

    /**
     * Atualiza um lote de configurações simultaneamente.
     *
     * @param array<string, mixed> $settings
     * @param int $userId
     * @return bool
     */
    public function updateBatch(array $settings, int $userId): bool
    {
        $atuais = $this->getAll();
        $modificadosAntigos = [];
        $modificadosNovos = [];

        foreach ($settings as $chave => $valor) {
            $tipo = match (true) {
                is_int($valor) => 'integer',
                is_bool($valor) => 'boolean',
                is_array($valor) => 'json',
                default => 'string',
            };

            $valorStr = is_bool($valor) ? ($valor ? '1' : '0') : (string)$valor;

            $sql = "UPDATE `{$this->tabela}` SET valor = :valor, tipo = :tipo, atualizado_em = NOW() WHERE chave = :chave";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':valor', $valorStr, PDO::PARAM_STR);
            $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->bindValue(':chave', $chave, PDO::PARAM_STR);
            $stmt->execute();

            $modificadosAntigos[$chave] = $atuais[$chave] ?? null;
            $modificadosNovos[$chave] = $valor;
        }

        $this->logAudit('UPDATE', 0, $modificadosAntigos, $modificadosNovos, $userId);
        return true;
    }

    /**
     * Verifica o status das migrações do banco de dados inspecionando a pasta database/migrations
     * e confrontando com os registros na tabela migracoes.
     *
     * @return array<int, array{arquivo: string, versao: string, executada: bool, lote?: int, executada_em?: string}>
     */
    public function checkMigrationsStatus(): array
    {
        $migrationsPath = APP_ROOT . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        $files = glob($migrationsPath . DIRECTORY_SEPARATOR . '*.php');
        if ($files === false) {
            $files = [];
        }

        // Busca histórico de migrações executadas
        $sql = "SELECT migracao, lote, executado_em FROM `migracoes` ORDER BY migracao ASC";
        $stmt = $this->db->query($sql);
        $executed = [];
        foreach ($stmt->fetchAll() as $row) {
            $executed[$row['migracao']] = $row;
        }

        $statusList = [];
        foreach ($files as $file) {
            $basename = basename($file);
            $versao = pathinfo($basename, PATHINFO_FILENAME);
            $isExecuted = isset($executed[$versao]);

            $statusList[] = [
                'arquivo'      => $basename,
                'versao'       => $versao,
                'executada'    => $isExecuted,
                'lote'         => $isExecuted ? (int)$executed[$versao]['lote'] : null,
                'executada_em' => $isExecuted ? (string)$executed[$versao]['executado_em'] : null,
            ];
        }

        usort($statusList, fn($a, $b) => strcmp($a['versao'], $b['versao']));
        return $statusList;
    }

    /**
     * Converte o valor de string armazenado para a tipagem apropriada.
     *
     * @param string|null $value
     * @param string $type
     * @return mixed
     */
    private function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer' => (int) $value,
            'boolean' => ($value === '1' || strtolower($value) === 'true'),
            'json'    => json_decode($value, true),
            default   => $value,
        };
    }
}
