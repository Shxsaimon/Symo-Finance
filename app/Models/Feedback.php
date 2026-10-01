<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

class Feedback extends Model
{
    private const TELAS_PERMITIDAS = [
        'sistema',
        'dashboard',
        'lancamentos',
        'contas',
        'categorias',
        'meios_pagamento',
        'cartoes',
        'transferencias',
        'relatorios',
        'lixeira',
        'configuracoes',
        'perfil',
    ];

    private const TIPOS_PERMITIDOS = ['correcao', 'melhoria', 'duvida'];

    public function __construct()
    {
        parent::__construct();
        $this->tabela = 'feedback';
    }

    /**
     * @param array<int, string> $imagensUrls URLs do Vercel Blob já enviadas, gravadas como JSON.
     */
    public function registrar(int $usuarioId, string $texto, string $tela, string $tipo, array $imagensUrls = []): int
    {
        $isPgsql = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';
        $imagensJson = !empty($imagensUrls) ? json_encode(array_values($imagensUrls), JSON_UNESCAPED_SLASHES) : null;

        $sql = 'INSERT INTO feedback (usuario_id, texto, tela, tipo, imagens, status, criado_em, atualizado_em)
                VALUES (:usuario_id, :texto, :tela, :tipo, :imagens, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
                . ($isPgsql ? ' RETURNING id' : '');

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':texto', $texto, PDO::PARAM_STR);
        $stmt->bindValue(':tela', $tela, PDO::PARAM_STR);
        $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
        $stmt->bindValue(':imagens', $imagensJson, $imagensJson !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':status', 'novo', PDO::PARAM_STR);
        $stmt->execute();

        return $isPgsql ? (int) $stmt->fetchColumn() : (int) $this->db->lastInsertId();
    }

    /**
    * @return array{items: array<int, array<string, mixed>>, total: int, page: int, total_pages: int}
     */
    public function getPaginated(int $page, int $perPage = 25): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        $countStatement = $this->db->prepare('SELECT COUNT(*) FROM feedback');
        $countStatement->execute();
        $total = (int) $countStatement->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);

        $statement = $this->db->prepare('SELECT f.id, f.usuario_id, f.texto, f.tela, f.tipo, f.imagens,
                    f.status, f.criado_em, u.nome AS usuario_nome, u.email AS usuario_email
                FROM feedback f
                LEFT JOIN usuarios u ON u.id = f.usuario_id
                ORDER BY f.criado_em DESC, f.id DESC
                LIMIT :limite OFFSET :deslocamento');
        $statement->bindValue(':limite', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':deslocamento', ($page - 1) * $perPage, PDO::PARAM_INT);
        $statement->execute();

        $items = $statement->fetchAll();
        foreach ($items as &$item) {
            $images = json_decode((string) ($item['imagens'] ?? '[]'), true);
            $item['imagens'] = is_array($images) ? $images : [];
        }
        unset($item);

        return ['items' => $items, 'total' => $total, 'page' => $page, 'total_pages' => $totalPages];
    }

    public function getImageUrl(int $feedbackId, int $imageIndex): ?string
    {
        if ($imageIndex < 0) {
            return null;
        }

        $feedback = $this->find($feedbackId);
        if (!$feedback) {
            return null;
        }

        $images = json_decode((string) ($feedback['imagens'] ?? '[]'), true);
        $url = is_array($images) ? ($images[$imageIndex] ?? null) : null;

        return is_string($url) && filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    public static function telaPermitida(string $tela): bool
    {
        return in_array($tela, self::TELAS_PERMITIDAS, true);
    }

    public static function tipoPermitido(string $tipo): bool
    {
        return in_array($tipo, self::TIPOS_PERMITIDOS, true);
    }
}