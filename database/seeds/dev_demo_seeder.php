<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Seed de Exemplo para o Perfil 'dev' (ID 1)
 *
 * Gera dados realistas de contas, cartões, categorias, receitas, despesas
 * e transferências no período de Março/2026 a Outubro/2026 (6 meses atrás até 1 mês no futuro).
 *
 * Perfil do Usuário ID 1: Desenvolvedor de Software (Classe média baixa BR)
 * Salário TI R$ 4.250, mora sozinho, entusiasta de tecnologia e cultura nerd/geek.
 */
return new class {
    public function run(PDO $pdo): void
    {
        $usuarioId = 1;

        // 1. Garantir que o usuário ID 1 existe
        $stmtUser = $pdo->prepare("SELECT id FROM usuarios WHERE id = :id");
        $stmtUser->execute(['id' => $usuarioId]);
        if (!$stmtUser->fetch()) {
            echo "  [!] Usuário ID 1 não encontrado. Seed ignorado.\n";
            return;
        }

        echo "  [*] Limpando dados anteriores do usuário ID 1 para recalcular a base de teste...\n";
        
        // Limpa transações, transferências, faturas, cartões e contas do usuário 1 se existirem
        $pdo->exec("DELETE FROM transacoes WHERE usuario_id = {$usuarioId}");
        $pdo->exec("DELETE FROM transferencias WHERE usuario_id = {$usuarioId}");
        
        // Exclui faturas dos cartões do usuário 1
        $pdo->exec("DELETE FROM faturas_cartao WHERE cartao_credito_id IN (SELECT id FROM cartoes_credito WHERE usuario_id = {$usuarioId})");
        $pdo->exec("DELETE FROM cartoes_credito WHERE usuario_id = {$usuarioId}");
        $pdo->exec("DELETE FROM contas WHERE usuario_id = {$usuarioId}");
        $pdo->exec("DELETE FROM categorias WHERE usuario_id = {$usuarioId}");
        $pdo->exec("DELETE FROM meios_pagamento WHERE usuario_id = {$usuarioId}");
        $pdo->exec("DELETE FROM configuracoes_usuario WHERE usuario_id = {$usuarioId}");

        // 2. Popular Categorias, Meios de Pagamento e Preferências Padrão
        echo "  [+] Populando categorias, meios de pagamento e preferências padrão para usuário ID 1...\n";
        require_once dirname(__DIR__, 2) . '/app/Helpers/DefaultDataHelper.php';
        DefaultDataHelper::popularDadosPadrao($pdo, $usuarioId);

        // Mapear Categorias (Nome => ID)
        $stmtCat = $pdo->prepare("SELECT nome, id FROM categorias WHERE usuario_id = :uid");
        $stmtCat->execute(['uid' => $usuarioId]);
        $catMap = $stmtCat->fetchAll(PDO::FETCH_KEY_PAIR);

        // Mapear Meios de Pagamento (Nome => ID)
        $stmtPm = $pdo->prepare("SELECT nome, id FROM meios_pagamento WHERE usuario_id = :uid");
        $stmtPm->execute(['uid' => $usuarioId]);
        $pmMap = $stmtPm->fetchAll(PDO::FETCH_KEY_PAIR);


        // 3. Criar Contas Bancárias
        echo "  [+] Criando contas bancárias para o perfil dev...\n";
        $insConta = $pdo->prepare("INSERT INTO contas (usuario_id, nome, instituicao, tipo, saldo_inicial, saldo_atual, cor, status, criado_em, atualizado_em)
                                   VALUES (:usuario_id, :nome, :instituicao, :tipo, :saldo_inicial, :saldo_atual, :cor, 'ativa', NOW(), NOW())
                                   RETURNING id");
        
        // Conta Nubank
        $insConta->execute([
            'usuario_id' => $usuarioId, 'nome' => 'Nubank Principal', 'instituicao' => 'Nubank',
            'tipo' => 'corrente', 'saldo_inicial' => 1250.00, 'saldo_atual' => 1250.00, 'cor' => '#820AD1'
        ]);
        $contaNubankId = (int) $insConta->fetchColumn();

        // Reserva Inter
        $insConta->execute([
            'usuario_id' => $usuarioId, 'nome' => 'Reserva Inter', 'instituicao' => 'Banco Inter',
            'tipo' => 'poupanca', 'saldo_inicial' => 2500.00, 'saldo_atual' => 2500.00, 'cor' => '#FF7A00'
        ]);
        $contaInterId = (int) $insConta->fetchColumn();

        // Carteira Física
        $insConta->execute([
            'usuario_id' => $usuarioId, 'nome' => 'Carteira', 'instituicao' => 'Espécie',
            'tipo' => 'dinheiro', 'saldo_inicial' => 100.00, 'saldo_atual' => 100.00, 'cor' => '#3BD7D7'
        ]);
        $contaCarteiraId = (int) $insConta->fetchColumn();

        // 4. Criar Cartão de Crédito
        echo "  [+] Criando cartão de crédito para o perfil dev...\n";
        $insCartao = $pdo->prepare("INSERT INTO cartoes_credito (usuario_id, nome, limite_credito, dia_fechamento, dia_vencimento, conta_padrao_id, cor, status, criado_em, atualizado_em)
                                    VALUES (:usuario_id, :nome, :limite, :fechamento, :vencimento, :conta_padrao, :cor, 'ativo', NOW(), NOW())
                                    RETURNING id");
        $insCartao->execute([
            'usuario_id' => $usuarioId, 'nome' => 'Nubank Roxinho', 'limite' => 3500.00,
            'fechamento' => 25, 'vencimento' => 5, 'conta_padrao' => $contaNubankId, 'cor' => '#820AD1'
        ]);
        $cartaoId = (int) $insCartao->fetchColumn();

        // Helper de inserção de transação
        $insTrans = $pdo->prepare("INSERT INTO transacoes (
            usuario_id, tipo, descricao, valor, data_transacao, data_vencimento, data_pagamento, status,
            conta_id, categoria_id, meio_pagamento_id, cartao_credito_id, fatura_id, numero_parcela, total_parcelas,
            observacoes, criado_em, atualizado_em
        ) VALUES (
            :usuario_id, :tipo, :descricao, :valor, :data_transacao, :data_vencimento, :data_pagamento, :status,
            :conta_id, :categoria_id, :meio_pagamento_id, :cartao_credito_id, :fatura_id, :numero_parcela, :total_parcelas,
            :observacoes, NOW(), NOW()
        ) RETURNING id");

        // Helper de transferência
        $insTransf = $pdo->prepare("INSERT INTO transferencias (
            usuario_id, conta_origem_id, conta_destino_id, valor, data_transferencia, observacoes, criado_em, atualizado_em
        ) VALUES (:usuario_id, :origem, :destino, :valor, :data_transf, :obs, NOW(), NOW())");

        // Faturas por Mês Referência (Ano 2026) => ID da Fatura
        $insFatura = $pdo->prepare("INSERT INTO faturas_cartao (
            cartao_credito_id, mes_referencia, ano_referencia, data_fechamento, data_vencimento, valor_total, status, pago_em, conta_pagamento_id, criado_em, atualizado_em
        ) VALUES (:cartao_id, :mes, :ano, :fechamento, :vencimento, :valor_total, :status, :pago_em, :conta_pagamento, NOW(), NOW()) RETURNING id");

        $faturasMap = []; // [ "2026-03" => id_fatura, ... ]

        // Gerar faturas de Março a Outubro de 2026
        $meses = [
            ['mes' => 3, 'ano' => 2026, 'fechamento' => '2026-03-25', 'vencimento' => '2026-04-05', 'status' => 'paga', 'pago_em' => '2026-04-05 10:00:00'],
            ['mes' => 4, 'ano' => 2026, 'fechamento' => '2026-04-25', 'vencimento' => '2026-05-05', 'status' => 'paga', 'pago_em' => '2026-05-05 11:30:00'],
            ['mes' => 5, 'ano' => 2026, 'fechamento' => '2026-05-25', 'vencimento' => '2026-06-05', 'status' => 'paga', 'pago_em' => '2026-06-05 09:15:00'],
            ['mes' => 6, 'ano' => 2026, 'fechamento' => '2026-06-25', 'vencimento' => '2026-07-05', 'status' => 'paga', 'pago_em' => '2026-07-05 14:00:00'],
            ['mes' => 7, 'ano' => 2026, 'fechamento' => '2026-07-25', 'vencimento' => '2026-08-05', 'status' => 'paga', 'pago_em' => '2026-08-05 16:45:00'],
            ['mes' => 8, 'ano' => 2026, 'fechamento' => '2026-08-25', 'vencimento' => '2026-09-05', 'status' => 'paga', 'pago_em' => '2026-09-05 10:20:00'],
            ['mes' => 9, 'ano' => 2026, 'fechamento' => '2026-09-25', 'vencimento' => '2026-10-05', 'status' => 'fechada', 'pago_em' => null],
            ['mes' => 10, 'ano' => 2026, 'fechamento' => '2026-10-25', 'vencimento' => '2026-11-05', 'status' => 'aberta', 'pago_em' => null],
        ];

        foreach ($meses as $m) {
            $insFatura->execute([
                'cartao_id' => $cartaoId,
                'mes' => $m['mes'],
                'ano' => $m['ano'],
                'fechamento' => $m['fechamento'],
                'vencimento' => $m['vencimento'],
                'valor_total' => 0.00,
                'status' => $m['status'],
                'pago_em' => $m['pago_em'],
                'conta_pagamento' => ($m['status'] === 'paga') ? $contaNubankId : null
            ]);
            $key = sprintf('%04d-%02d', $m['ano'], $m['mes']);
            $faturasMap[$key] = (int) $insFatura->fetchColumn();
        }

        echo "  [+] Inserindo lançamentos financeiros de Março/2026 a Outubro/2026...\n";

        // Coleção de dados para gerar a massa de lançamentos
        $dadosMeses = [
            '2026-03' => ['luz' => 175.40, 'freelance' => 0, 'geek' => 'HQ Sandman & Clean Code', 'geek_valor' => 145.00],
            '2026-04' => ['luz' => 192.10, 'freelance' => 0, 'geek' => 'Farmácia & Suplementos Dev', 'geek_valor' => 98.50],
            '2026-05' => ['luz' => 210.00, 'freelance' => 950.00, 'geek' => 'Steam Summer Sale Games', 'geek_valor' => 189.90],
            '2026-06' => ['luz' => 188.30, 'freelance' => 0, 'geek' => 'Setup Deskmat & Cabo Coiled', 'geek_valor' => 110.00],
            '2026-07' => ['luz' => 165.90, 'freelance' => 0, 'geek' => 'Ingresso Anime Friends & Feira Geek', 'geek_valor' => 120.00],
            '2026-08' => ['luz' => 180.00, 'freelance' => 1100.00, 'geek' => 'Caneca & Poster Dev Cyberpunk', 'geek_valor' => 78.00],
            '2026-09' => ['luz' => 195.50, 'freelance' => 0, 'geek' => 'Hamburgueria Temática Star Wars', 'geek_valor' => 115.00],
            '2026-10' => ['luz' => 190.00, 'freelance' => 0, 'geek' => 'Ingressos Cinema IMAX & Popcorn', 'geek_valor' => 65.00],
        ];

        // Relação de compras parceladas no cartão
        // 1) Teclado Mecânico Keychron K2 em 3x de R$ 160.00 (Jun, Jul, Ago)
        // 2) Monitor Secundário Dell 24" em 3x de R$ 220.00 (Ago, Set, Out)

        foreach ($dadosMeses as $anoMes => $info) {
            $isFuturo = ($anoMes === '2026-10');
            $statusPadraoConta = $isFuturo ? 'pendente' : 'pago';
            $statusPadraoReceita = $isFuturo ? 'pendente' : 'recebido';
            
            list($ano, $mesStr) = explode('-', $anoMes);
            $mes = (int) $mesStr;
            $faturaIdAtual = $faturasMap[$anoMes] ?? null;

            // 1. Receita Salário TI (Dia 05)
            $dtSalario = "$anoMes-05";
            $insTrans->execute([
                'usuario_id' => $usuarioId, 'tipo' => 'receita', 'descricao' => 'Salário Dev - TechCorp Solutions',
                'valor' => 4250.00, 'data_transacao' => $dtSalario, 'data_vencimento' => $dtSalario,
                'data_pagamento' => $isFuturo ? null : $dtSalario, 'status' => $statusPadraoReceita,
                'conta_id' => $contaNubankId, 'categoria_id' => $catMap['Salário'],
                'meio_pagamento_id' => $pmMap['Pix'], 'cartao_credito_id' => null, 'fatura_id' => null,
                'numero_parcela' => 1, 'total_parcelas' => 1, 'observacoes' => 'Pagamento mensal CLT Dev Junior/Pleno'
            ]);

            // 2. Receita Rendimento Caixinha (Dia 01)
            $dtRendimento = "$anoMes-01";
            $insTrans->execute([
                'usuario_id' => $usuarioId, 'tipo' => 'receita', 'descricao' => 'Rendimento CDB Caixinha Inter',
                'valor' => rand(1800, 2450) / 100, 'data_transacao' => $dtRendimento, 'data_vencimento' => $dtRendimento,
                'data_pagamento' => $isFuturo ? null : $dtRendimento, 'status' => $statusPadraoReceita,
                'conta_id' => $contaInterId, 'categoria_id' => $catMap['Rendimentos e Investimentos'],
                'meio_pagamento_id' => $pmMap['Transferência Bancária'], 'cartao_credito_id' => null, 'fatura_id' => null,
                'numero_parcela' => 1, 'total_parcelas' => 1, 'observacoes' => 'Rendimento mensal 100% CDI'
            ]);

            // 3. Receita Freelance (Se houver no mês)
            if ($info['freelance'] > 0) {
                $dtFree = "$anoMes-18";
                $insTrans->execute([
                    'usuario_id' => $usuarioId, 'tipo' => 'receita', 'descricao' => 'Freelance TI - Projeto Web / API Python',
                    'valor' => $info['freelance'], 'data_transacao' => $dtFree, 'data_vencimento' => $dtFree,
                    'data_pagamento' => $isFuturo ? null : $dtFree, 'status' => $statusPadraoReceita,
                    'conta_id' => $contaNubankId, 'categoria_id' => $catMap['Serviços e Freelance'],
                    'meio_pagamento_id' => $pmMap['Pix'], 'cartao_credito_id' => null, 'fatura_id' => null,
                    'numero_parcela' => 1, 'total_parcelas' => 1, 'observacoes' => 'Desenvolvimento de funcionalidade sob demanda'
                ]);
            }

            // 4. Despesa Fixa: Aluguel & Condomínio Studio (Dia 10)
            $dtAluguel = "$anoMes-10";
            $insTrans->execute([
                'usuario_id' => $usuarioId, 'tipo' => 'despesa', 'descricao' => 'Aluguel & Condomínio Studio',
                'valor' => 1350.00, 'data_transacao' => $dtAluguel, 'data_vencimento' => $dtAluguel,
                'data_pagamento' => $isFuturo ? null : $dtAluguel, 'status' => $statusPadraoConta,
                'conta_id' => $contaNubankId, 'categoria_id' => $catMap['Moradia e Contas'],
                'meio_pagamento_id' => $pmMap['Pix'], 'cartao_credito_id' => null, 'fatura_id' => null,
                'numero_parcela' => 1, 'total_parcelas' => 1, 'observacoes' => 'Pagamento mensal de moradia'
            ]);

            // 5. Despesa Fixa: Internet Fibra 600MB (Dia 12)
            $dtNet = "$anoMes-12";
            $insTrans->execute([
                'usuario_id' => $usuarioId, 'tipo' => 'despesa', 'descricao' => 'Internet Fibra 600MB Dev',
                'valor' => 129.90, 'data_transacao' => $dtNet, 'data_vencimento' => $dtNet,
                'data_pagamento' => $isFuturo ? null : $dtNet, 'status' => $statusPadraoConta,
                'conta_id' => $contaNubankId, 'categoria_id' => $catMap['Moradia e Contas'],
                'meio_pagamento_id' => $pmMap['Boleto Bancário'], 'cartao_credito_id' => null, 'fatura_id' => null,
                'numero_parcela' => 1, 'total_parcelas' => 1, 'observacoes' => 'Link dedicado para home-office'
            ]);

            // 6. Despesa Fixa: Conta de Luz (Dia 15)
            $dtLuz = "$anoMes-15";
            $insTrans->execute([
                'usuario_id' => $usuarioId, 'tipo' => 'despesa', 'descricao' => 'Conta de Energia Elétrica Enel',
                'valor' => $info['luz'], 'data_transacao' => $dtLuz, 'data_vencimento' => $dtLuz,
                'data_pagamento' => $isFuturo ? null : $dtLuz, 'status' => $statusPadraoConta,
                'conta_id' => $contaNubankId, 'categoria_id' => $catMap['Moradia e Contas'],
                'meio_pagamento_id' => $pmMap['Pix'], 'cartao_credito_id' => null, 'fatura_id' => null,
                'numero_parcela' => 1, 'total_parcelas' => 1, 'observacoes' => 'Consumo mensal computadores & ar condicionado'
            ]);

            // 7. Despesa Fixa: Plano de Saúde Coparticipação (Dia 20)
            $dtSaude = "$anoMes-20";
            $insTrans->execute([
                'usuario_id' => $usuarioId, 'tipo' => 'despesa', 'descricao' => 'Plano de Saúde Coparticipação',
                'valor' => 185.00, 'data_transacao' => $dtSaude, 'data_vencimento' => $dtSaude,
                'data_pagamento' => $isFuturo ? null : $dtSaude, 'status' => $statusPadraoConta,
                'conta_id' => $contaNubankId, 'categoria_id' => $catMap['Saúde e Farmácia'],
                'meio_pagamento_id' => $pmMap['Pix'], 'cartao_credito_id' => null, 'fatura_id' => null,
                'numero_parcela' => 1, 'total_parcelas' => 1, 'observacoes' => 'Mensalidade do plano de saúde empresarial'
            ]);

            // 8. Assinaturas Dev & Lazer no Cartão de Crédito
            $dtCopilot = "$anoMes-02";
            $insTrans->execute([
                'usuario_id' => $usuarioId, 'tipo' => 'despesa', 'descricao' => 'Assinatura GitHub Copilot / Dev AI',
                'valor' => 56.00, 'data_transacao' => $dtCopilot, 'data_vencimento' => $dtCopilot,
                'data_pagamento' => null, 'status' => 'pendente',
                'conta_id' => null, 'categoria_id' => $catMap['Equipamentos e Tecnologia'],
                'meio_pagamento_id' => $pmMap['Cartão de Crédito'], 'cartao_credito_id' => $cartaoId, 'fatura_id' => $faturaIdAtual,
                'numero_parcela' => 1, 'total_parcelas' => 1, 'observacoes' => 'Ferramenta de assistência de código'
            ]);

            $dtSpotify = "$anoMes-08";
            $insTrans->execute([
                'usuario_id' => $usuarioId, 'tipo' => 'despesa', 'descricao' => 'Spotify Premium Family Shared',
                'valor' => 21.90, 'data_transacao' => $dtSpotify, 'data_vencimento' => $dtSpotify,
                'data_pagamento' => null, 'status' => 'pendente',
                'conta_id' => null, 'categoria_id' => $catMap['Lazer'],
                'meio_pagamento_id' => $pmMap['Cartão de Crédito'], 'cartao_credito_id' => $cartaoId, 'fatura_id' => $faturaIdAtual,
                'numero_parcela' => 1, 'total_parcelas' => 1, 'observacoes' => 'Música e Podcasts de tecnologia'
            ]);

            // 9. Compras de Supermercado & Feira (Débito e Pix)
            $dtMercado1 = "$anoMes-07";
            $insTrans->execute([
                'usuario_id' => $usuarioId, 'tipo' => 'despesa', 'descricao' => 'Supermercado - Compras do Mês',
                'valor' => 415.80, 'data_transacao' => $dtMercado1, 'data_vencimento' => $dtMercado1,
                'data_pagamento' => $isFuturo ? null : $dtMercado1, 'status' => $statusPadraoConta,
                'conta_id' => $contaNubankId, 'categoria_id' => $catMap['Mercado'],
                'meio_pagamento_id' => $pmMap['Cartão de Débito'], 'cartao_credito_id' => null, 'fatura_id' => null,
                'numero_parcela' => 1, 'total_parcelas' => 1, 'observacoes' => 'Alimentação e produtos de limpeza'
            ]);

            $dtMercado2 = "$anoMes-21";
            $insTrans->execute([
                'usuario_id' => $usuarioId, 'tipo' => 'despesa', 'descricao' => 'Hortifruti & Reposição de Semana',
                'valor' => 84.50, 'data_transacao' => $dtMercado2, 'data_vencimento' => $dtMercado2,
                'data_pagamento' => $isFuturo ? null : $dtMercado2, 'status' => $statusPadraoConta,
                'conta_id' => $contaNubankId, 'categoria_id' => $catMap['Mercado'],
                'meio_pagamento_id' => $pmMap['Pix'], 'cartao_credito_id' => null, 'fatura_id' => null,
                'numero_parcela' => 1, 'total_parcelas' => 1, 'observacoes' => 'Frutas e vegetais frescos'
            ]);

            // 10. Item Geek / Lazer por Mês (Cartão de Crédito ou Débito)
            $dtGeek = "$anoMes-22";
            $insTrans->execute([
                'usuario_id' => $usuarioId, 'tipo' => 'despesa', 'descricao' => $info['geek'],
                'valor' => $info['geek_valor'], 'data_transacao' => $dtGeek, 'data_vencimento' => $dtGeek,
                'data_pagamento' => null, 'status' => 'pendente',
                'conta_id' => null, 'categoria_id' => $catMap['Lazer'],
                'meio_pagamento_id' => $pmMap['Cartão de Crédito'], 'cartao_credito_id' => $cartaoId, 'fatura_id' => $faturaIdAtual,
                'numero_parcela' => 1, 'total_parcelas' => 1, 'observacoes' => 'Item de lazer, cultura ou compras nerd'
            ]);

            // 11. Transporte Uber (2 viagens por mês)
            $dtUber1 = "$anoMes-11";
            $insTrans->execute([
                'usuario_id' => $usuarioId, 'tipo' => 'despesa', 'descricao' => 'Uber - Ida para Meetup / Trabalho',
                'valor' => 24.50, 'data_transacao' => $dtUber1, 'data_vencimento' => $dtUber1,
                'data_pagamento' => $isFuturo ? null : $dtUber1, 'status' => $statusPadraoConta,
                'conta_id' => $contaNubankId, 'categoria_id' => $catMap['Transporte e Combustível'],
                'meio_pagamento_id' => $pmMap['Pix'], 'cartao_credito_id' => null, 'fatura_id' => null,
                'numero_parcela' => 1, 'total_parcelas' => 1, 'observacoes' => 'Deslocamento urbano'
            ]);

            // 12. Parcelamentos Especiais no Cartão
            // Keychron em Jun, Jul, Ago (Parcelas 1, 2, 3)
            if ($anoMes === '2026-06') {
                $insTrans->execute([
                    'usuario_id' => $usuarioId, 'tipo' => 'despesa', 'descricao' => 'Teclado Mecânico Keychron K2 (1/3)',
                    'valor' => 160.00, 'data_transacao' => "$anoMes-14", 'data_vencimento' => "$anoMes-14",
                    'data_pagamento' => null, 'status' => 'pendente',
                    'conta_id' => null, 'categoria_id' => $catMap['Equipamentos e Tecnologia'],
                    'meio_pagamento_id' => $pmMap['Cartão de Crédito'], 'cartao_credito_id' => $cartaoId, 'fatura_id' => $faturaIdAtual,
                    'numero_parcela' => 1, 'total_parcelas' => 3, 'observacoes' => 'Up no setup de trabalho dev'
                ]);
            } elseif ($anoMes === '2026-07') {
                $insTrans->execute([
                    'usuario_id' => $usuarioId, 'tipo' => 'despesa', 'descricao' => 'Teclado Mecânico Keychron K2 (2/3)',
                    'valor' => 160.00, 'data_transacao' => "$anoMes-14", 'data_vencimento' => "$anoMes-14",
                    'data_pagamento' => null, 'status' => 'pendente',
                    'conta_id' => null, 'categoria_id' => $catMap['Equipamentos e Tecnologia'],
                    'meio_pagamento_id' => $pmMap['Cartão de Crédito'], 'cartao_credito_id' => $cartaoId, 'fatura_id' => $faturaIdAtual,
                    'numero_parcela' => 2, 'total_parcelas' => 3, 'observacoes' => 'Up no setup de trabalho dev'
                ]);
            } elseif ($anoMes === '2026-08') {
                $insTrans->execute([
                    'usuario_id' => $usuarioId, 'tipo' => 'despesa', 'descricao' => 'Teclado Mecânico Keychron K2 (3/3)',
                    'valor' => 160.00, 'data_transacao' => "$anoMes-14", 'data_vencimento' => "$anoMes-14",
                    'data_pagamento' => null, 'status' => 'pendente',
                    'conta_id' => null, 'categoria_id' => $catMap['Equipamentos e Tecnologia'],
                    'meio_pagamento_id' => $pmMap['Cartão de Crédito'], 'cartao_credito_id' => $cartaoId, 'fatura_id' => $faturaIdAtual,
                    'numero_parcela' => 3, 'total_parcelas' => 3, 'observacoes' => 'Up no setup de trabalho dev'
                ]);
            }

            // Monitor Dell em Ago, Set, Out (Parcelas 1, 2, 3)
            if ($anoMes === '2026-08') {
                $insTrans->execute([
                    'usuario_id' => $usuarioId, 'tipo' => 'despesa', 'descricao' => 'Monitor Secundário Dell 24" (1/3)',
                    'valor' => 220.00, 'data_transacao' => "$anoMes-16", 'data_vencimento' => "$anoMes-16",
                    'data_pagamento' => null, 'status' => 'pendente',
                    'conta_id' => null, 'categoria_id' => $catMap['Equipamentos e Tecnologia'],
                    'meio_pagamento_id' => $pmMap['Cartão de Crédito'], 'cartao_credito_id' => $cartaoId, 'fatura_id' => $faturaIdAtual,
                    'numero_parcela' => 1, 'total_parcelas' => 3, 'observacoes' => 'Produtividade de tela dupla'
                ]);
            } elseif ($anoMes === '2026-09') {
                $insTrans->execute([
                    'usuario_id' => $usuarioId, 'tipo' => 'despesa', 'descricao' => 'Monitor Secundário Dell 24" (2/3)',
                    'valor' => 220.00, 'data_transacao' => "$anoMes-16", 'data_vencimento' => "$anoMes-16",
                    'data_pagamento' => null, 'status' => 'pendente',
                    'conta_id' => null, 'categoria_id' => $catMap['Equipamentos e Tecnologia'],
                    'meio_pagamento_id' => $pmMap['Cartão de Crédito'], 'cartao_credito_id' => $cartaoId, 'fatura_id' => $faturaIdAtual,
                    'numero_parcela' => 2, 'total_parcelas' => 3, 'observacoes' => 'Produtividade de tela dupla'
                ]);
            } elseif ($anoMes === '2026-10') {
                $insTrans->execute([
                    'usuario_id' => $usuarioId, 'tipo' => 'despesa', 'descricao' => 'Monitor Secundário Dell 24" (3/3)',
                    'valor' => 220.00, 'data_transacao' => "$anoMes-16", 'data_vencimento' => "$anoMes-16",
                    'data_pagamento' => null, 'status' => 'pendente',
                    'conta_id' => null, 'categoria_id' => $catMap['Equipamentos e Tecnologia'],
                    'meio_pagamento_id' => $pmMap['Cartão de Crédito'], 'cartao_credito_id' => $cartaoId, 'fatura_id' => $faturaIdAtual,
                    'numero_parcela' => 3, 'total_parcelas' => 3, 'observacoes' => 'Produtividade de tela dupla'
                ]);
            }

            // 13. Transferências Mensais
            // Aporte na Reserva de Emergência (Dia 06)
            $dtTransf1 = "$anoMes-06";
            if (!$isFuturo) {
                $insTransf->execute([
                    'usuario_id' => $usuarioId, 'origem' => $contaNubankId, 'destino' => $contaInterId,
                    'valor' => 350.00, 'data_transf' => $dtTransf1, 'obs' => 'Aporte mensal na Reserva de Emergência'
                ]);
            }

            // Saque para Carteira em Espécie (Dia 07)
            $dtTransf2 = "$anoMes-07";
            if (!$isFuturo) {
                $insTransf->execute([
                    'usuario_id' => $usuarioId, 'origem' => $contaNubankId, 'destino' => $contaCarteiraId,
                    'valor' => 50.00, 'data_transf' => $dtTransf2, 'obs' => 'Saque mensal dinheiro físico'
                ]);
            }
        }

        // 5. Atualizar os totais das Faturas do Cartão e alinhar status dos lançamentos pagos
        echo "  [+] Atualizando totais das faturas do cartão e baixando lançamentos de faturas pagas...\n";
        $stmtFaturasTotal = $pdo->prepare("
            UPDATE faturas_cartao f
            SET valor_total = COALESCE((
                SELECT SUM(t.valor) 
                FROM transacoes t 
                WHERE t.fatura_id = f.id AND t.excluido_em IS NULL
            ), 0.00)
            WHERE f.cartao_credito_id = :cartao_id
        ");
        $stmtFaturasTotal->execute(['cartao_id' => $cartaoId]);

        // Para as faturas já pagas (Março a Agosto), atualizar todos os seus lançamentos para 'pago'
        $stmtFaturasPagas = $pdo->prepare("SELECT id, valor_total, data_vencimento, conta_pagamento_id FROM faturas_cartao WHERE cartao_credito_id = :cartao_id AND status = 'paga'");
        $stmtFaturasPagas->execute(['cartao_id' => $cartaoId]);
        $faturasPagas = $stmtFaturasPagas->fetchAll(PDO::FETCH_ASSOC);

        $stmtBaixarItensFatura = $pdo->prepare("
            UPDATE transacoes 
            SET status = 'pago', 
                data_pagamento = :dt_pagto, 
                conta_id = :conta_id,
                atualizado_em = NOW()
            WHERE fatura_id = :fatura_id AND excluido_em IS NULL
        ");

        foreach ($faturasPagas as $fp) {
            $stmtBaixarItensFatura->execute([
                'dt_pagto'  => $fp['data_vencimento'],
                'conta_id'  => $fp['conta_pagamento_id'] ?: $contaNubankId,
                'fatura_id' => $fp['id'],
            ]);
        }


        // 6. Recalcular e atualizar o `saldo_atual` real de cada conta
        echo "  [+] Recalculando saldo atual de cada conta bancária...\n";
        $contas = [$contaNubankId, $contaInterId, $contaCarteiraId];
        
        foreach ($contas as $cId) {
            // Saldo inicial
            $stmtInit = $pdo->prepare("SELECT saldo_inicial FROM contas WHERE id = :id");
            $stmtInit->execute(['id' => $cId]);
            $saldoInicial = (float) $stmtInit->fetchColumn();

            // Total receitas pagas/recebidas nesta conta
            $stmtRec = $pdo->prepare("SELECT COALESCE(SUM(valor), 0) FROM transacoes WHERE conta_id = :id AND tipo = 'receita' AND status = 'recebido' AND excluido_em IS NULL");
            $stmtRec->execute(['id' => $cId]);
            $receitas = (float) $stmtRec->fetchColumn();

            // Total despesas pagas nesta conta
            $stmtDesp = $pdo->prepare("SELECT COALESCE(SUM(valor), 0) FROM transacoes WHERE conta_id = :id AND tipo = 'despesa' AND status = 'pago' AND excluido_em IS NULL");
            $stmtDesp->execute(['id' => $cId]);
            $despesas = (float) $stmtDesp->fetchColumn();

            // Transferências Entradas
            $stmtTransfIn = $pdo->prepare("SELECT COALESCE(SUM(valor), 0) FROM transferencias WHERE conta_destino_id = :id AND excluido_em IS NULL");
            $stmtTransfIn->execute(['id' => $cId]);
            $transfIn = (float) $stmtTransfIn->fetchColumn();

            // Transferências Saídas
            $stmtTransfOut = $pdo->prepare("SELECT COALESCE(SUM(valor), 0) FROM transferencias WHERE conta_origem_id = :id AND excluido_em IS NULL");
            $stmtTransfOut->execute(['id' => $cId]);
            $transfOut = (float) $stmtTransfOut->fetchColumn();

            $saldoFinal = $saldoInicial + $receitas - $despesas + $transfIn - $transfOut;

            $stmtUpdSaldo = $pdo->prepare("UPDATE contas SET saldo_atual = :saldo WHERE id = :id");
            $stmtUpdSaldo->execute(['saldo' => $saldoFinal, 'id' => $cId]);
        }

        echo "  [OK] Massa de dados para o usuário ID 1 criada com sucesso!\n";
    }
};
