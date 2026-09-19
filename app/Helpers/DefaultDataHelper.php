<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Helper de Dados Padrão para Novas Contas de Usuário
 *
 * Centraliza o template de categorias, meios de pagamento e preferências
 * pessoais clonado para cada novo usuário no momento do cadastro (local
 * ou via Google), garantindo que a conta nasça pronta para uso.
 */
class DefaultDataHelper
{
    /**
     * Retorna o template padrão de categorias de receitas e despesas.
     *
     * @return array<int, array{nome: string, tipo: string, icone: string, cor: string}>
     */
    public static function categoriasPadrao(): array
    {
        return [
            // Receitas
            ['nome' => 'Salário', 'tipo' => 'receita', 'icone' => 'bi-cash-stack', 'cor' => '#3BD7D7'],
            ['nome' => 'Rendimentos e Investimentos', 'tipo' => 'receita', 'icone' => 'bi-graph-up-arrow', 'cor' => '#3BD7D7'],
            ['nome' => 'Serviços e Freelance', 'tipo' => 'receita', 'icone' => 'bi-briefcase', 'cor' => '#3BD7D7'],
            ['nome' => 'Outras Receitas', 'tipo' => 'receita', 'icone' => 'bi-plus-circle', 'cor' => '#3BD7D7'],
            // Despesas
            ['nome' => 'Alimentação', 'tipo' => 'despesa', 'icone' => 'bi-egg-fried', 'cor' => '#6F02FF'],
            ['nome' => 'Mercado', 'tipo' => 'despesa', 'icone' => 'bi-cart', 'cor' => '#6F02FF'],
            ['nome' => 'Moradia e Contas', 'tipo' => 'despesa', 'icone' => 'bi-house-door', 'cor' => '#6F02FF'],
            ['nome' => 'Transporte e Combustível', 'tipo' => 'despesa', 'icone' => 'bi-car-front', 'cor' => '#6F02FF'],
            ['nome' => 'Saúde e Farmácia', 'tipo' => 'despesa', 'icone' => 'bi-heart-pulse', 'cor' => '#793CFE'],
            ['nome' => 'Educação e Cursos', 'tipo' => 'despesa', 'icone' => 'bi-book', 'cor' => '#793CFE'],
            ['nome' => 'Lazer', 'tipo' => 'despesa', 'icone' => 'bi-controller', 'cor' => '#793CFE'],
            ['nome' => 'Viagens', 'tipo' => 'despesa', 'icone' => 'bi-airplane', 'cor' => '#793CFE'],
            ['nome' => 'Equipamentos e Tecnologia', 'tipo' => 'despesa', 'icone' => 'bi-laptop', 'cor' => '#793CFE'],
            ['nome' => 'Assinaturas e Serviços', 'tipo' => 'despesa', 'icone' => 'bi-credit-card-2-front', 'cor' => '#793CFE'],
            ['nome' => 'Outras Despesas', 'tipo' => 'despesa', 'icone' => 'bi-dash-circle', 'cor' => '#6F02FF'],
        ];
    }

    /**
     * Retorna o template padrão de meios de pagamento.
     *
     * @return array<int, array{nome: string, eh_credito: int}>
     */
    public static function meiosPagamentoPadrao(): array
    {
        return [
            ['nome' => 'Dinheiro', 'eh_credito' => 0],
            ['nome' => 'Pix', 'eh_credito' => 0],
            ['nome' => 'Cartão de Débito', 'eh_credito' => 0],
            ['nome' => 'Cartão de Crédito', 'eh_credito' => 1],
            ['nome' => 'Boleto Bancário', 'eh_credito' => 0],
            ['nome' => 'Transferência Bancária', 'eh_credito' => 0],
        ];
    }

    /**
     * Retorna as preferências pessoais padrão gravadas em `configuracoes_usuario`.
     *
     * @return array<int, array{chave: string, valor: string, tipo: string}>
     */
    public static function preferenciasPadrao(): array
    {
        return [
            ['chave' => 'simbolo_moeda', 'valor' => 'R$', 'tipo' => 'string'],
            ['chave' => 'separador_decimal', 'valor' => ',', 'tipo' => 'string'],
            ['chave' => 'separador_milhar', 'valor' => '.', 'tipo' => 'string'],
            ['chave' => 'registros_por_pagina', 'valor' => '25', 'tipo' => 'integer'],
        ];
    }

    /**
     * Clona o conjunto completo de dados padrão (categorias, meios de pagamento
     * e preferências pessoais) para um usuário recém-cadastrado.
     *
     * @param PDO $pdo
     * @param int $usuarioId
     * @return void
     */
    public static function popularDadosPadrao(PDO $pdo, int $usuarioId): void
    {
        $insCat = $pdo->prepare("INSERT INTO `categorias` (usuario_id, nome, tipo, icone, cor, status) VALUES (:usuario_id, :nome, :tipo, :icone, :cor, 'ativa')");
        foreach (self::categoriasPadrao() as $categoria) {
            try {
                $insCat->execute([
                    'usuario_id' => $usuarioId,
                    'nome'       => $categoria['nome'],
                    'tipo'       => $categoria['tipo'],
                    'icone'      => $categoria['icone'],
                    'cor'        => $categoria['cor'],
                ]);
            } catch (Throwable $e) {
                error_log('[DefaultDataHelper] Falha ao inserir categoria "' . $categoria['nome'] . '": ' . $e->getMessage());
                throw $e;
            }
        }

        $insPm = $pdo->prepare("INSERT INTO `meios_pagamento` (usuario_id, nome, eh_credito, status) VALUES (:usuario_id, :nome, :eh_credito, 'ativo')");
        foreach (self::meiosPagamentoPadrao() as $meio) {
            try {
                $insPm->execute([
                    'usuario_id'  => $usuarioId,
                    'nome'        => $meio['nome'],
                    'eh_credito'  => $meio['eh_credito'],
                ]);
            } catch (Throwable $e) {
                error_log('[DefaultDataHelper] Falha ao inserir meio de pagamento "' . $meio['nome'] . '": ' . $e->getMessage());
                throw $e;
            }
        }

        $insPref = $pdo->prepare("INSERT INTO `configuracoes_usuario` (usuario_id, chave, valor, tipo) VALUES (:usuario_id, :chave, :valor, :tipo)");
        foreach (self::preferenciasPadrao() as $preferencia) {
            try {
                $insPref->execute([
                    'usuario_id' => $usuarioId,
                    'chave'      => $preferencia['chave'],
                    'valor'      => $preferencia['valor'],
                    'tipo'       => $preferencia['tipo'],
                ]);
            } catch (Throwable $e) {
                error_log('[DefaultDataHelper] Falha ao inserir preferência "' . $preferencia['chave'] . '": ' . $e->getMessage());
                throw $e;
            }
        }
    }
}
