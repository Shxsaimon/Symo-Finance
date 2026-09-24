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

    public static function telaPermitida(string $tela): bool
    {
        return in_array($tela, self::TELAS_PERMITIDAS, true);
    }

    public static function tipoPermitido(string $tipo): bool
    {
        return in_array($tipo, self::TIPOS_PERMITIDOS, true);
    }
}