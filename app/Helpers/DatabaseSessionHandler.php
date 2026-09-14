<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

final class DatabaseSessionHandler implements SessionHandlerInterface
{
    private PDO $db;
    private int $ttl;

    public function __construct(PDO $db, int $ttl)
    {
        $this->db = $db;
        $this->ttl = max(60, $ttl);
        $this->db->exec("CREATE TABLE IF NOT EXISTS sessoes (
            id VARCHAR(128) PRIMARY KEY,
            dados TEXT NOT NULL,
            atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $cutoff = date('Y-m-d H:i:s', time() - $this->ttl);
        $statement = $this->db->prepare('SELECT dados FROM sessoes WHERE id = :id AND atualizado_em >= :cutoff');
        $statement->execute(['id' => $id, 'cutoff' => $cutoff]);
        $data = $statement->fetchColumn();
        return $data === false ? '' : (string) $data;
    }

    public function write(string $id, string $data): bool
    {
        $update = $this->db->prepare('UPDATE sessoes SET dados = :dados, atualizado_em = CURRENT_TIMESTAMP WHERE id = :id');
        $update->execute(['id' => $id, 'dados' => $data]);

        if ($update->rowCount() === 0) {
            $insert = $this->db->prepare('INSERT INTO sessoes (id, dados, atualizado_em) VALUES (:id, :dados, CURRENT_TIMESTAMP)');
            try {
                $insert->execute(['id' => $id, 'dados' => $data]);
            } catch (PDOException $exception) {
                $update->execute(['id' => $id, 'dados' => $data]);
            }
        }

        return true;
    }

    public function destroy(string $id): bool
    {
        $statement = $this->db->prepare('DELETE FROM sessoes WHERE id = :id');
        return $statement->execute(['id' => $id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $cutoff = date('Y-m-d H:i:s', time() - $max_lifetime);
        $statement = $this->db->prepare('DELETE FROM sessoes WHERE atualizado_em < :cutoff');
        $statement->execute(['cutoff' => $cutoff]);
        return $statement->rowCount();
    }
}
