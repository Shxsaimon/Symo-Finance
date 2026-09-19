<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Symo — Seed de Carga de Dados Iniciais
 *
 * Cria o usuário administrador do sistema (perfil 'dev', bootstrap único e
 * exclusivo das telas administrativas globais) e as configurações globais
 * padrão. Categorias, meios de pagamento e preferências pessoais deixaram de
 * ser globais: agora são clonados por usuário no momento do autocadastro,
 * via DefaultDataHelper::popularDadosPadrao().
 */
return new class {
    public function run(PDO $pdo): void
    {
        // 1. Usuário administrador do sistema (perfil 'dev')
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $devEmail = trim((string) ($config['dev']['email'] ?? ''));
        $devSenha = (string) ($config['dev']['senha'] ?? '');
        $devNome  = (string) ($config['dev']['nome'] ?? 'Administrador do Sistema');

        if ($devEmail === '' || $devSenha === '') {
            echo "  [!] Variáveis SYSTEM_DEV_EMAIL/SYSTEM_DEV_PASSWORD não definidas: usuário 'dev' não foi criado.\n";
        } else {
            $stmtUser = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmtUser->execute(['email' => $devEmail]);
            if (!$stmtUser->fetch()) {
                $hash = password_hash($devSenha, PASSWORD_BCRYPT, ['cost' => 12]);
                $insUser = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, perfil, status) VALUES (:nome, :email, :senha, 'dev', 'ativo')");
                $insUser->execute([
                    'nome'  => $devNome,
                    'email' => $devEmail,
                    'senha' => $hash,
                ]);
                echo "  [+] Usuário administrador do sistema (dev) criado: {$devEmail}\n";
            }
        }

        // 2. Configurações Globais Padrão (exclusivas do perfil 'dev')
        $settings = [
            ['chave' => 'nome_aplicacao', 'valor' => 'Symo', 'tipo' => 'string', 'descricao' => 'Nome da Aplicação ou Empresa'],
            ['chave' => 'logotipo_aplicacao', 'valor' => null, 'tipo' => 'string', 'descricao' => 'Caminho do Logotipo da Aplicação'],
        ];

        $stmtSet = $pdo->prepare("SELECT id FROM configuracoes WHERE chave = :chave");
        $insSet = $pdo->prepare("INSERT INTO configuracoes (chave, valor, tipo, descricao) VALUES (:chave, :valor, :tipo, :descricao)");

        foreach ($settings as $st) {
            $stmtSet->execute(['chave' => $st['chave']]);
            if (!$stmtSet->fetch()) {
                $insSet->execute($st);
            }
        }
        echo "  [+] Configurações globais padrão registradas com sucesso.\n";
    }
};
