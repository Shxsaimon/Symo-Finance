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
        $stmtUser = $pdo->prepare("SELECT id FROM `users` WHERE `email` = :email");
        
        // Admin
        $stmtUser->execute(['email' => 'admin@symo.local']);
        if (!$stmtUser->fetch()) {
            $passAdmin = password_hash('Admin@123456', PASSWORD_BCRYPT, ['cost' => 12]);
            $insUser = $pdo->prepare("INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`) VALUES (:name, :email, :password, :role, :status)");
            $insUser->execute([
                'name'     => 'Administrador Symo',
                'email'    => 'admin@symo.local',
                'password' => $passAdmin,
                'role'     => 'admin',
                'status'   => 'ativo'
            ]);
            echo "  [+] Usuário Administrador criado: admin@symo.local (Senha: Admin@123456)\n";
        }

        // Operador para testes de RBAC
        $stmtUser->execute(['email' => 'operador@symo.local']);
        if (!$stmtUser->fetch()) {
            $passOperador = password_hash('Operador@123456', PASSWORD_BCRYPT, ['cost' => 12]);
            $insUser = $pdo->prepare("INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`) VALUES (:name, :email, :password, :role, :status)");
            $insUser->execute([
                'name'     => 'Operador Symo',
                'email'    => 'operador@symo.local',
                'password' => $passOperador,
                'role'     => 'operador',
                'status'   => 'ativo'
            ]);
            echo "  [+] Usuário Operador criado: operador@symo.local (Senha: Operador@123456)\n";
        }

        // 2. Categorias Padrão
        $categories = [
            // Receitas
            ['name' => 'Salário', 'type' => 'receita', 'icon' => 'bi-cash-stack', 'color' => '#3BD7D7'],
            ['name' => 'Rendimentos e Investimentos', 'type' => 'receita', 'icon' => 'bi-graph-up-arrow', 'color' => '#3BD7D7'],
            ['name' => 'Serviços e Freelance', 'type' => 'receita', 'icon' => 'bi-briefcase', 'color' => '#3BD7D7'],
            ['name' => 'Outras Receitas', 'type' => 'receita', 'icon' => 'bi-plus-circle', 'color' => '#3BD7D7'],
            // Despesas
            ['name' => 'Alimentação e Mercado', 'type' => 'despesa', 'icon' => 'bi-cart', 'color' => '#6F02FF'],
            ['name' => 'Moradia e Contas', 'type' => 'despesa', 'icon' => 'bi-house-door', 'color' => '#6F02FF'],
            ['name' => 'Transporte e Combustível', 'type' => 'despesa', 'icon' => 'bi-car-front', 'color' => '#6F02FF'],
            ['name' => 'Saúde e Farmácia', 'type' => 'despesa', 'icon' => 'bi-heart-pulse', 'color' => '#793CFE'],
            ['name' => 'Educação e Cursos', 'type' => 'despesa', 'icon' => 'bi-book', 'color' => '#793CFE'],
            ['name' => 'Lazer e Viagens', 'type' => 'despesa', 'icon' => 'bi-controller', 'color' => '#793CFE'],
            ['name' => 'Equipamentos e Tecnologia', 'type' => 'despesa', 'icon' => 'bi-laptop', 'color' => '#793CFE'],
            ['name' => 'Assinaturas e Serviços', 'type' => 'despesa', 'icon' => 'bi-credit-card-2-front', 'color' => '#793CFE'],
            ['name' => 'Outras Despesas', 'type' => 'despesa', 'icon' => 'bi-dash-circle', 'color' => '#6F02FF'],
        ];

        $stmtCat = $pdo->prepare("SELECT id FROM `categories` WHERE `name` = :name AND `type` = :type");
        $insCat = $pdo->prepare("INSERT INTO `categories` (`name`, `type`, `icon`, `color`, `status`) VALUES (:name, :type, :icon, :color, 'ativa')");
        
        foreach ($categories as $cat) {
            $stmtCat->execute(['name' => $cat['name'], 'type' => $cat['type']]);
            if (!$stmtCat->fetch()) {
                $insCat->execute($cat);
            }
        }
        echo "  [+] Categorias padrão cadastradas com sucesso.\n";

        // 3. Meios de Pagamento Padrão
        $paymentMethods = [
            ['name' => 'Dinheiro', 'is_credit' => 0],
            ['name' => 'Pix', 'is_credit' => 0],
            ['name' => 'Cartão de Débito', 'is_credit' => 0],
            ['name' => 'Cartão de Crédito', 'is_credit' => 1],
            ['name' => 'Boleto Bancário', 'is_credit' => 0],
            ['name' => 'Transferência Bancária', 'is_credit' => 0],
        ];

        $stmtPm = $pdo->prepare("SELECT id FROM `payment_methods` WHERE `name` = :name");
        $insPm = $pdo->prepare("INSERT INTO `payment_methods` (`name`, `is_credit`, `status`) VALUES (:name, :is_credit, 'ativo')");

        foreach ($paymentMethods as $pm) {
            $stmtPm->execute(['name' => $pm['name']]);
            if (!$stmtPm->fetch()) {
                $insPm->execute($pm);
            }
        }
        echo "  [+] Meios de pagamento padrão cadastrados com sucesso.\n";

        // 4. Configurações Globais Padrão (Settings)
        $settings = [
            ['setting_key' => 'app_name', 'setting_value' => 'Symo', 'type' => 'string', 'description' => 'Nome da Aplicação ou Empresa'],
            ['setting_key' => 'currency_symbol', 'setting_value' => 'R$', 'type' => 'string', 'description' => 'Símbolo Monetário'],
            ['setting_key' => 'decimal_separator', 'setting_value' => ',', 'type' => 'string', 'description' => 'Separador Decimal'],
            ['setting_key' => 'thousands_separator', 'setting_value' => '.', 'type' => 'string', 'description' => 'Separador de Milhar'],
            ['setting_key' => 'records_per_page', 'setting_value' => '25', 'type' => 'integer', 'description' => 'Quantidade padrão de registros por página'],
            ['setting_key' => 'app_logo', 'setting_value' => null, 'type' => 'string', 'description' => 'Caminho do Logotipo da Aplicação'],
        ];

        $stmtSet = $pdo->prepare("SELECT id FROM `settings` WHERE `setting_key` = :setting_key");
        $insSet = $pdo->prepare("INSERT INTO `settings` (`setting_key`, `setting_value`, `type`, `description`) VALUES (:setting_key, :setting_value, :type, :description)");

        foreach ($settings as $st) {
            $stmtSet->execute(['setting_key' => $st['setting_key']]);
            if (!$stmtSet->fetch()) {
                $insSet->execute($st);
            }
        }
        echo "  [+] Configurações globais padrão registradas com sucesso.\n";
    }
};
