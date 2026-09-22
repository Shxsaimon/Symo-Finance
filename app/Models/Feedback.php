<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

class Feedback extends Model
{
    private const TELAS_PERMITIDAS = [
        'dashboard',
        'lancamentos',
        'contas',
        'categorias',
        'meios_pagamento',
        'cartoes',
        'transferencias',
        'relatorios',
        'configuracoes',
        'perfil',
    ];

    private const TIPOS_PERMITIDOS = ['correcao', 'melhoria', 'duvida'];

    public function __construct()
    {
        parent::__construct();
        $this->tabela = 'feedback';
    }

    public function registrar(int $usuarioId, string $texto, string $tela, string $tipo): bool
    {
        $sql = 'INSERT INTO feedback (usuario_id, texto, tela, tipo, status, criado_em, atualizado_em)
                VALUES (:usuario_id, :texto, :tela, :tipo, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':texto', $texto, PDO::PARAM_STR);
        $stmt->bindValue(':tela', $tela, PDO::PARAM_STR);
        $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'novo', PDO::PARAM_STR);

        return $stmt->execute();
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