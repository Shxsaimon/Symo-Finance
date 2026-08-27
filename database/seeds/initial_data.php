<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Seed de Carga de Dados Iniciais
 *
 * Popula o banco de dados com usuário administrador inicial,
 * categorias essenciais, meios de pagamento e configurações do sistema.
 */
return new class {
    public function run(PDO $pdo): void
    {
        // 1. Usuários Padrão
        $stmtUser = $pdo->prepare("SELECT id FROM `usuarios` WHERE `email` = :email");
        
        // Admin
        $stmtUser->execute(['email' => 'admin@symo.local']);
        if (!$stmtUser->fetch()) {
            $passAdmin = password_hash('Admin@123456', PASSWORD_BCRYPT, ['cost' => 12]);
            $insUser = $pdo->prepare("INSERT INTO `usuarios` (`nome`, `email`, `senha`, `perfil`, `status`) VALUES (:nome, :email, :senha, :perfil, :status)");
            $insUser->execute([
                'nome'    => 'Administrador Symo',
                'email'   => 'admin@symo.local',
                'senha'   => $passAdmin,
                'perfil'  => 'admin',
                'status'  => 'ativo'
            ]);
            echo "  [+] Usuário Administrador criado: admin@symo.local (Senha: Admin@123456)\n";
        }

        // Operador para testes de RBAC
        $stmtUser->execute(['email' => 'operador@symo.local']);
        if (!$stmtUser->fetch()) {
            $passOperador = password_hash('Operador@123456', PASSWORD_BCRYPT, ['cost' => 12]);
            $insUser = $pdo->prepare("INSERT INTO `usuarios` (`nome`, `email`, `senha`, `perfil`, `status`) VALUES (:nome, :email, :senha, :perfil, :status)");
            $insUser->execute([
                'nome'    => 'Operador Symo',
                'email'   => 'operador@symo.local',
                'senha'   => $passOperador,
                'perfil'  => 'operador',
                'status'  => 'ativo'
            ]);
            echo "  [+] Usuário Operador criado: operador@symo.local (Senha: Operador@123456)\n";
        }

        // 2. Categorias Padrão
        $categories = [
            // Receitas
            ['nome' => 'Salário', 'tipo' => 'receita', 'icone' => 'bi-cash-stack', 'cor' => '#3BD7D7'],
            ['nome' => 'Rendimentos e Investimentos', 'tipo' => 'receita', 'icone' => 'bi-graph-up-arrow', 'cor' => '#3BD7D7'],
            ['nome' => 'Serviços e Freelance', 'tipo' => 'receita', 'icone' => 'bi-briefcase', 'cor' => '#3BD7D7'],
            ['nome' => 'Outras Receitas', 'tipo' => 'receita', 'icone' => 'bi-plus-circle', 'cor' => '#3BD7D7'],
            // Despesas
            ['nome' => 'Alimentação e Mercado', 'tipo' => 'despesa', 'icone' => 'bi-cart', 'cor' => '#6F02FF'],
            ['nome' => 'Moradia e Contas', 'tipo' => 'despesa', 'icone' => 'bi-house-door', 'cor' => '#6F02FF'],
            ['nome' => 'Transporte e Combustível', 'tipo' => 'despesa', 'icone' => 'bi-car-front', 'cor' => '#6F02FF'],
            ['nome' => 'Saúde e Farmácia', 'tipo' => 'despesa', 'icone' => 'bi-heart-pulse', 'cor' => '#793CFE'],
            ['nome' => 'Educação e Cursos', 'tipo' => 'despesa', 'icone' => 'bi-book', 'cor' => '#793CFE'],
            ['nome' => 'Lazer e Viagens', 'tipo' => 'despesa', 'icone' => 'bi-controller', 'cor' => '#793CFE'],
            ['nome' => 'Equipamentos e Tecnologia', 'tipo' => 'despesa', 'icone' => 'bi-laptop', 'cor' => '#793CFE'],
            ['nome' => 'Assinaturas e Serviços', 'tipo' => 'despesa', 'icone' => 'bi-credit-card-2-front', 'cor' => '#793CFE'],
            ['nome' => 'Outras Despesas', 'tipo' => 'despesa', 'icone' => 'bi-dash-circle', 'cor' => '#6F02FF'],
        ];

        $stmtCat = $pdo->prepare("SELECT id FROM `categorias` WHERE `nome` = :nome AND `tipo` = :tipo");
        $insCat = $pdo->prepare("INSERT INTO `categorias` (`nome`, `tipo`, `icone`, `cor`, `status`) VALUES (:nome, :tipo, :icone, :cor, 'ativa')");
        
        foreach ($categories as $cat) {
            $stmtCat->execute(['nome' => $cat['nome'], 'tipo' => $cat['tipo']]);
            if (!$stmtCat->fetch()) {
                $insCat->execute($cat);
            }
        }
        echo "  [+] Categorias padrão cadastradas com sucesso.\n";

        // 3. Meios de Pagamento Padrão
        $paymentMethods = [
            ['nome' => 'Dinheiro', 'eh_credito' => 0],
            ['nome' => 'Pix', 'eh_credito' => 0],
            ['nome' => 'Cartão de Débito', 'eh_credito' => 0],
            ['nome' => 'Cartão de Crédito', 'eh_credito' => 1],
            ['nome' => 'Boleto Bancário', 'eh_credito' => 0],
            ['nome' => 'Transferência Bancária', 'eh_credito' => 0],
        ];

        $stmtPm = $pdo->prepare("SELECT id FROM `meios_pagamento` WHERE `nome` = :nome");
        $insPm = $pdo->prepare("INSERT INTO `meios_pagamento` (`nome`, `eh_credito`, `status`) VALUES (:nome, :eh_credito, 'ativo')");

        foreach ($paymentMethods as $pm) {
            $stmtPm->execute(['nome' => $pm['nome']]);
            if (!$stmtPm->fetch()) {
                $insPm->execute($pm);
            }
        }
        echo "  [+] Meios de pagamento padrão cadastrados com sucesso.\n";

        // 4. Configurações Globais Padrão (configuracoes)
        $settings = [
            ['chave' => 'nome_aplicacao', 'valor' => 'Symo', 'tipo' => 'string', 'descricao' => 'Nome da Aplicação ou Empresa'],
            ['chave' => 'simbolo_moeda', 'valor' => 'R$', 'tipo' => 'string', 'descricao' => 'Símbolo Monetário'],
            ['chave' => 'separador_decimal', 'valor' => ',', 'tipo' => 'string', 'descricao' => 'Separador Decimal'],
            ['chave' => 'separador_milhar', 'valor' => '.', 'tipo' => 'string', 'descricao' => 'Separador de Milhar'],
            ['chave' => 'registros_por_pagina', 'valor' => '25', 'tipo' => 'integer', 'descricao' => 'Quantidade padrão de registros por página'],
            ['chave' => 'logotipo_aplicacao', 'valor' => null, 'tipo' => 'string', 'descricao' => 'Caminho do Logotipo da Aplicação'],
        ];

        $stmtSet = $pdo->prepare("SELECT id FROM `configuracoes` WHERE `chave` = :chave");
        $insSet = $pdo->prepare("INSERT INTO `configuracoes` (`chave`, `valor`, `tipo`, `descricao`) VALUES (:chave, :valor, :tipo, :descricao)");

        foreach ($settings as $st) {
            $stmtSet->execute(['chave' => $st['chave']]);
            if (!$stmtSet->fetch()) {
                $insSet->execute($st);
            }
        }
        echo "  [+] Configurações globais padrão registradas com sucesso.\n";
    }
};
