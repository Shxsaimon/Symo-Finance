# Guia de Manutenção e Evolução — Symo Finance

Este documento é a referência técnica oficial para desenvolvedores e agentes de Inteligência Artificial responsáveis por manter, corrigir, testar e evoluir o **Symo Finance**.

---

## 1. Visão Geral do Sistema

### 1.1. O que o sistema faz
O **Symo Finance** é uma aplicação web completa de gestão financeira pessoal e empresarial leve (para pessoas físicas, casais, famílias, freelancers e MEIs). O sistema permite planejar o fluxo de caixa, monitorar saldos bancários, gerenciar cartões de crédito e seus ciclos de faturas, acompanhar parcelamentos com precisão de centavos, controlar posições de investimentos, auditar todas as operações mutatórias e gerar relatórios analíticos com exportação estruturada.

### 1.2. Para quem foi criado
- **Pessoas Físicas:** Organização de contas, controle de despesas do dia a dia e reservas de emergência.
- **Famílias e Casais:** Acompanhamento do orçamento familiar individual (com previsão futura de compartilhamento seletivo de contas específicas).
- **Freelancers e MEIs:** Separação entre contas pessoais e operacionais, acompanhamento de recebíveis e fluxo de caixa simplificado, sem jargões contábeis complexos.

### 1.3. Problemas que resolve
- **Falta de previsibilidade financeira:** Distinção nítida entre saldos já realizados (dinheiro em conta) e compromissos futuros (faturas e parcelas a vencer).
- **Descontrole de faturas de cartão de crédito:** Automação de fechamento de faturas com fronteira exclusiva de datas, cálculo de limite disponível e vencimentos ajustados para dias úteis bancários.
- **Distorção no fluxo de caixa por transferências:** Transferências internas entre contas bancárias do mesmo proprietário movimentam saldos de forma atômica sem inflar artificialmente receitas ou despesas.
- **Mistura indevida de investimentos no saldo operacional:** Separação estrita entre contas bancárias líquidas (Saldo Atual) e posições de investimentos (resumo de patrimônio investido).
- **Perda de histórico e erros operacionais:** Trilha de auditoria completa com comparativo JSON Diff e lixeira (*Soft Delete*) com recálculo atômico de saldos na restauração.

### 1.4. Principais módulos existentes
1. **Autenticação e Sessão:** Autocadastro aberto, login com proteção contra força bruta, expiração de 60 minutos por inatividade e persistência de sessão em banco (`sessoes`).
2. **Dashboard Financeiro:** Indicadores consolidados em tempo real (Saldo Atual, Receitas, Despesas, Balanço), gráficos locais (Chart.js), compromissos dos próximos 7 dias e resumo de faturas.
3. **Contas Bancárias e Carteiras:** Gestão de contas ativas, preferência de inclusão no Saldo Atual e bloqueio de exclusão quando vinculada a transações.
4. **Categorias de Lançamentos:** Categorização de receitas e despesas com identificadores visuais (ícones e cores).
5. **Catálogo de Meios de Pagamento:** Catálogo global gerenciado pelo perfil `dev` com ativação/inativação individual por usuário para novos lançamentos, preservando o histórico.
6. **Cartões de Crédito e Faturas:** Limite disponível/utilizado, ciclo de faturas mensais (aberta, fechada, paga), ancoragem correta de parcelas e quitação com débito em conta.
7. **Lançamentos Financeiros:** Receitas e despesas à vista ou parceladas ($N$ vezes), com tratamento de resíduo de centavos na 1ª parcela e associação automática a ciclos de fatura para despesas no crédito.
8. **Transferências entre Contas:** Movimentação atômica par-a-par entre contas do mesmo usuário com trava contra saldo insuficiente.
9. **Investimentos:** Posições (aplicações e reservas) associadas a contas bancárias, com movimentos de aporte, resgate, rendimento e atualização de valor patrimonial.
10. **Relatórios e Exportação CSV:** Extratos detalhados com filtros avançados, visão sintética por categoria e download de CSV formatado em UTF-8 com BOM e proteção contra CSV Injection.
11. **Administração da Plataforma (`dev`):** Gestão global de usuários, visualização de trilha de auditoria com JSON Diff, catálogo global de meios de pagamento, painel de status e disparo de migrations remotas via GitHub Actions, e consulta de feedbacks recebidos.
12. **Auto-Gestão e Utilidades:** Perfil de usuário com edição do próprio e-mail e avatar privado, configurações pessoais de exibição e Lixeira com restauração e expurgo (*Hard Delete*).

---

## 2. Stack e Ambientes

### 2.1. Tecnologias utilizadas
- **Backend:** PHP 8.1+ nativo (desenvolvimento e CI em PHP 8.2), utilizando `declare(strict_types=1);` em todos os arquivos PHP. Arquitetura MVC clássica sem frameworks pesados (sem Laravel, Symfony ou Slim).
- **Acesso a Dados:** PDO nativo com *Prepared Statements* obrigatórios.
- **Banco de Dados Oficial:** **PostgreSQL** hospedado no **Neon**, conectado via `DATABASE_URL` com `sslmode=require`.
  - *Compatibilidade Legada:* Suporte secundário a MySQL/MariaDB quando `DATABASE_URL` não for fornecida. A classe `SymoPostgresConnection` em `config/database.php` normaliza dialetos SQL conhecidos (como `NOW()`, `INTERVAL`, `EXTRACT`).
- **Frontend:** HTML5 semântico, CSS3 com variáveis / Design Tokens do tema *Symo* (`public/css/custom.css`), JavaScript Vanilla moderno (ES6+).
  - *Bibliotecas Locais:* Bootstrap 5.3.x, Bootstrap Icons e Chart.js 4.x armazenados em `public/`. **Proibido o uso de CDNs externas em runtime**.
- **Arquivos e Uploads Privados:** **Vercel Blob** acessado via Serverless Function `api/blob.js` utilizando o SDK `@vercel/blob`. O PHP conecta-se à Function via proxy autenticado por segredo compartilhado (`BLOB_PROXY_SECRET`) e entrega mídias por rotas autenticadas (`/media/avatar/{id}`, `/media/logo`, `/feedback/imagem/{id}/{index}`).

### 2.2. Ambientes do projeto
| Recurso | Desenvolvimento Local | Produção |
| :--- | :--- | :--- |
| **Servidor HTTP** | Servidor embutido do PHP (`php -S localhost:8000 index.php`) | Vercel (`vercel-php` runtime) |
| **Ponto de Entrada** | `index.php` raiz | `api/index.php` (adaptador que entrega assets e chama `index.php`) |
| **Banco de Dados** | Branch de desenvolvimento no Neon PostgreSQL | Endpoint principal no Neon PostgreSQL com SSL |
| **Armazenamento Mídia** | Proxy Vercel Blob já implantado ou fallback `storage/uploads/` | Vercel Blob privado (`@vercel/blob`) via `api/blob.js` |
| **Migrations** | CLI local: `php migrate.php` | GitHub Actions: workflow manual `.github/workflows/database-migrations.yml` |
| **Variáveis de Ambiente** | Script PowerShell local `load-env.ps1` (ignorado pelo Git) | Environment Variables na Vercel e Secrets no GitHub Actions |

### 2.3. Comandos principais do projeto
Todos os comandos devem ser executados no terminal PowerShell a partir da raiz do projeto:

```powershell
# 1. Iniciar ambiente local com variáveis de desenvolvimento (recomendado)
.\load-env.ps1 -Run

# 2. Iniciar servidor local em porta customizada
.\load-env.ps1 -Run -Port 8080

# 3. Verificar o status das migrações do banco
php migrate.php --status
# ou:
.\load-env.ps1 -Status

# 4. Executar migrações pendentes no banco conectado
php migrate.php
# ou:
.\load-env.ps1 -Migrate

# 5. Executar seeds essenciais (cria usuário 'dev' se configurado)
php migrate.php --seed
# ou:
.\load-env.ps1 -Seed

# 6. Validar sintaxe PHP de um arquivo específico (Lint)
php -l app/Controllers/TransactionController.php

# 7. Verificar espaços em branco e conflitos de merge antes de commitar
git diff --check
```

---

## 3. Como Rodar Localmente

### 3.1. Pré-requisitos
1. PHP 8.2 ou superior instalado e disponível no `PATH` do sistema.
   - Extensões obrigatórias ativas no `php.ini`: `pdo`, `pdo_pgsql`, `mbstring`, `fileinfo`, `json`, `session`, `curl`.
2. Git instalado.
3. Acesso a uma branch de desenvolvimento do Neon PostgreSQL.

### 3.2. Passo a passo para inicialização
1. **Clone o repositório:**
   ```bash
   git clone <URL_DO_REPOSITORIO>
   cd Symo_Finance
   ```
2. **Configure as variáveis de ambiente locais:**
   - Copie o arquivo modelo `load-env.example.ps1` para `load-env.ps1`:
     ```powershell
     Copy-Item load-env.example.ps1 load-env.ps1
     ```
   - Abra `load-env.ps1` e preencha:
     - `$env:DATABASE_URL`: URL de conexão da sua branch de desenvolvimento do Neon (ex: `postgresql://user:pass@ep-branch.neon.tech/symo_db?sslmode=require`).
     - `$env:BLOB_PROXY_URL` e `$env:BLOB_PROXY_SECRET`: credenciais do proxy Vercel se for testar mídias.
     - `$env:SYSTEM_DEV_EMAIL`, `$env:SYSTEM_DEV_PASSWORD`, `$env:SYSTEM_DEV_NAME`: credenciais para geração do usuário técnico `dev` local.
3. **Execute as migrações e o seed inicial:**
   ```powershell
   .\load-env.ps1 -Migrate
   .\load-env.ps1 -Seed
   ```
4. **Inicie o servidor de desenvolvimento:**
   ```powershell
   .\load-env.ps1 -Run
   ```
5. **Acesse no navegador:**
   - Acesse `http://localhost:8000`.
   - Crie uma conta pelo botão "Criar conta" (`/registrar`) ou autentique-se com o usuário `dev` criado pelo seed.

> [!NOTE]
> O `index.php` possui suporte nativo para entregar assets da pasta `public/` quando executado sob o servidor embutido do PHP (`PHP_SAPI === 'cli-server'`). Não é necessário configurar Apache, Nginx ou XAMPP.

---

## 4. Mapa de Pastas e Arquivos

```text
Symo_Finance/
├── api/
│   ├── index.php                # Adaptador para runtime vercel-php; entrega assets e invoca Front Controller
│   └── blob.js                  # Function Node.js para proxy seguro de upload/download/exclusão no Vercel Blob
├── app/
│   ├── Controllers/             # Controladores MVC (tratamento HTTP, autorização, validação e resposta)
│   ├── Helpers/                 # Utilitários (Auth, CSRF, Formatação, Logs, Sessão, Calendário, GitHub API)
│   ├── Models/                  # Camada de persistência PDO, escopo por usuário e regras financeiras
│   ├── Views/                   # Telas HTML/PHP organizadas por módulo funcional
│   └── .htaccess                # Proteção contra acesso HTTP direto em servidores Apache legados
├── config/
│   ├── config.php               # Leitura e consolidação das variáveis de ambiente
│   ├── database.php             # Fábrica Singleton de conexão PDO (SymoPostgresConnection)
│   └── .htaccess                # Bloqueio total de acesso direto
├── database/
│   ├── migrations/              # Migrações versionadas numeradas de 001 a 020+
│   ├── seeds/                   # Carga inicial (seed do dev e parâmetros globais)
│   └── .htaccess                # Bloqueio total de acesso direto
├── docs/                        # Documentação técnica viva e especificações do sistema
│   ├── FSD.md                   # Especificação Funcional e Técnica (fonte primária de verdade)
│   ├── DESIGN.md                # Diretrizes visuais, tokens e design system (Symo)
│   ├── MANUTENCAO.md            # Este guia completo de manutenção
│   ├── COMO-PEDIR-MUDANCAS.md   # Guia de interação e prompts para usuários
│   ├── PLANO.md                 # Histórico e planejamento de fases
│   ├── STATUS.md                # Arquivo vivo de acompanhamento do progresso
│   └── ERROS.md                 # Registro histórico de erros e soluções adotadas
├── public/                      # Assets estáticos servidos diretamente ao navegador
│   ├── css/                     # Bootstrap 5 local, ícones e custom.css (Design Tokens)
│   ├── js/                      # Bootstrap bundle, Chart.js local e app.js
│   ├── fonts/                   # Fontes locais Inter e Bootstrap Icons (WOFF2/WOFF)
│   └── img/                     # Favicons e logotipos SVG
├── storage/                     # Diretórios locais legados de contingência
│   ├── logs/                    # Arquivos error.log e security.log (fallback)
│   └── uploads/                 # Fallback de uploads locais para avatars e logos
├── .github/workflows/
│   └── database-migrations.yml  # Workflow oficial para status/migrate/seed/fresh no Neon
├── index.php                    # Front Controller central da aplicação
├── migrate.php                  # Executável CLI de migrações e seeds
├── vercel.json                  # Configuração do deploy na Vercel (rewrites e headers)
├── package.json                 # Dependência @vercel/blob para api/blob.js
└── AGENTS.md                    # Diretrizes técnicas e regras de trabalho para IAs
```

### 4.1. Diretrizes por pasta
- **`app/Controllers/`:** Mexa ao adicionar rotas ou mudar comportamento HTTP. *Cuidado:* Sempre valide `AuthHelper::requireLogin()` ou `AuthHelper::requireDev()`, verifique tokens CSRF em requisições POST e use `redirect()` apenas com caminhos internos.
- **`app/Models/`:** Mexa ao alterar regras de cálculo financeiro ou persistência. *Cuidado:* Sempre aplique escopo por `usuario_id`, utilize transações atômicas (`beginTransaction`, `commit`, `rollBack`) e registre mutações via `logAudit()`.
- **`app/Views/`:** Mexa ao alterar telas ou formulários. *Cuidado:* Toda saída de dados variáveis deve passar por `htmlspecialchars($dado, ENT_QUOTES, 'UTF-8')`. Use numerais tabulares (`tabular-nums`) para valores numéricos.
- **`config/`:** Mexa apenas para adicionar novas variáveis globais em `config.php`. *Cuidado:* Nunca versione credenciais nem crie arquivos `.env`.
- **`database/migrations/`:** Mexa ao alterar ou criar tabelas. *Cuidado:* Crie sempre uma nova migração sequencial. Nunca altere uma migração já executada em produção. Escreva queries compatíveis com PostgreSQL.

---

## 5. Banco de Dados e Persistência

### 5.1. Estrutura de Migrations
As migrações residem em `database/migrations/` com prefixo numérico de três dígitos (ex: `001_criar_tabela_usuarios.php` até `020_criar_tabelas_investimentos.php`).
O controle de execução é feito pela tabela `migracoes`, que armazena o nome do arquivo, o lote de execução e o timestamp.

Cada migração é um script PHP executado sob a conexão PDO do `migrate.php`. Exemplo de estrutura padrão de migration:

```php
<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

/**
 * Migration 021: Exemplo de nova tabela
 */
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

if ($driver === 'pgsql') {
    $sql = "CREATE TABLE IF NOT EXISTS exemplo (
        id INTEGER GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
        usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
        nome VARCHAR(100) NOT NULL,
        criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );
    CREATE INDEX IF NOT EXISTS idx_exemplo_usuario ON exemplo(usuario_id);";
} else {
    // Compatibilidade MySQL legado
    $sql = "CREATE TABLE IF NOT EXISTS `exemplo` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `usuario_id` INT UNSIGNED NOT NULL,
        `nome` VARCHAR(100) NOT NULL,
        `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_exemplo_usuario` (`usuario_id`),
        CONSTRAINT `fk_exemplo_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
}

$pdo->exec($sql);
```

### 5.2. Execução de migrações
- **Localmente:** `php migrate.php` aplica migrations pendentes; `php migrate.php --status` lista o estado atual.
- **Produção:** Acionado manualmente via GitHub Actions no workflow `Symo Database Migrations` (`.github/workflows/database-migrations.yml`), ou disparado pelo perfil `dev` através do painel `/configuracoes`.
- **Comando `--fresh`:** O comando `php migrate.php --fresh --confirmar` apaga todo o schema `public` do banco conectado (`DROP SCHEMA public CASCADE`). **Nunca execute este comando contra o banco de produção.**

### 5.3. Seeds de dados
- `database/seeds/initial_data.php`: Executa a criação do usuário `dev` (lendo variáveis `SYSTEM_DEV_*`) e popula configurações globais iniciais.
- Dados financeiros e categorias padrão de novos usuários comuns são populados via código em `app/Helpers/DefaultDataHelper.php` durante o autocadastro (`/registrar`).

---

## 6. Autenticação, Autorização e Usuários

### 6.1. Como funciona a autenticação
1. O usuário submete formulário POST para `/login`.
2. `AuthController::authenticate()` valida o token CSRF e checa bloqueio por força bruta via `AuthHelper::isBruteForceLocked($email, $ip)`.
3. `User::findByEmail()` busca o usuário ativo no banco.
4. `password_verify($senha, $hash)` valida a senha com BCRYPT.
5. Em caso de sucesso, `AuthHelper::login($usuario)` regenera o ID de sessão (`session_regenerate_id(true)`), grava dados essenciais na sessão e emite log de segurança.
6. A sessão é persistida no banco pela classe `DatabaseSessionHandler` e expira automaticamente após 60 minutos de inatividade (`session_ttl`).

### 6.2. Perfis de usuários (RBAC)
- **`admin`:** Perfil padrão de todo usuário que se cadastra pelo formulário público. É o administrador exclusivo dos seus próprios dados financeiros (contas, categorias, cartões, despesas, transferências, relatórios). Não tem acesso a dados de outros usuários nem a controles globais da plataforma.
- **`dev`:** Perfil técnico operacional criado via seed inicial. Possui acesso exclusivo às telas operacionais da plataforma: Gestão de Usuários (`/usuarios`), Auditoria Global (`/auditoria`), Configurações do Sistema e Migrations (`/configuracoes`), Consulta de Feedbacks (`/feedbacks`) e Catálogo Global de Meios de Pagamento. Como usuário financeiro, seus lançamentos e contas permanecem estritamente isolados pelo seu próprio `usuario_id`, idêntico a um `admin`.
- **`operador`:** Perfil legado mantido no código apenas por compatibilidade com versões antigas. Não é utilizado no produto atual.

### 6.3. Onde e como as permissões são verificadas
Toda rota protegida deve conter verificação de autorização no início do método do Controller correspondente:

```php
// Para qualquer usuário autenticado (admin ou dev):
AuthHelper::requireLogin();

// Para funcionalidades exclusivas do administrador da plataforma (dev):
AuthHelper::requireDev();
// ou:
AuthHelper::requireRole('dev');
```

Além da verificação de perfil, **é obrigatório aplicar isolamento financeiro (IDOR)**:
Toda busca, edição ou exclusão deve garantir que o registro pertence ao usuário logado:
```php
$usuarioId = AuthHelper::id();
$registro = $this->model->find($id);

if (!$registro || (int) $registro['usuario_id'] !== $usuarioId) {
    FlashHelper::set('danger', 'Registro não encontrado ou acesso não autorizado.');
    $this->redirect('/modulo');
}
```

---

## 7. Como Adicionar uma Nova Tela

Para adicionar uma nova tela ao sistema respeitando a arquitetura MVC, siga este fluxo de 5 etapas:

### Passo 1: Definir a Rota em `index.php`
Abra `index.php` e adicione a rota GET e eventuais rotas POST:
```php
// Rota da nova funcionalidade
Router::get('/minha-funcionalidade', [MinhaController::class, 'index']);
Router::post('/minha-funcionalidade/salvar', [MinhaController::class, 'store']);
```

### Passo 2: Criar o Controller em `app/Controllers/`
Crie o arquivo `app/Controllers/MinhaController.php` herdando de `Controller`:
```php
<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

class MinhaController extends Controller
{
    private MeuModel $model;

    public function __construct()
    {
        $this->model = new MeuModel();
    }

    public function index(): void
    {
        AuthHelper::requireLogin();
        $usuarioId = (int) AuthHelper::id();

        $itens = $this->model->listarPorUsuario($usuarioId);

        $this->render('minha-funcionalidade/index', [
            'pageTitle' => 'Minha Funcionalidade — Symo',
            'itens'     => $itens,
        ]);
    }
}
```

### Passo 3: Criar o Model em `app/Models/` (se houver persistência)
Crie o arquivo `app/Models/MeuModel.php` herdando de `Model`:
```php
<?php
declare(strict_types=1);

defined('APP_RUNNING') or die('Acesso direto proibido.');

class MeuModel extends Model
{
    protected string $tabela = 'minha_tabela';

    public function listarPorUsuario(int $usuarioId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tabela} WHERE usuario_id = :usuario_id AND excluido_em IS NULL ORDER BY id DESC"
        );
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
```

### Passo 4: Criar a View em `app/Views/`
Crie o arquivo `app/Views/minha-funcionalidade/index.php`:
```php
<?php
defined('APP_RUNNING') or die('Acesso direto proibido.');
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <!-- Conteúdo da tela seguindo o DESIGN.md -->
        </div>
    </div>
</div>
```

### Passo 5: Adicionar o Link na Barra Lateral
Edite `app/Views/layouts/sidebar.php` e insira o item no grupo de menu adequado, respeitando eventuais regras de perfil (`AuthHelper::isDev()`).

---

## 8. Como Adicionar um Novo Campo a um Cadastro Existente

Para adicionar um novo campo a uma entidade já existente no sistema, execute rigorosamente os passos abaixo:

1. **Criar a Migration do Banco:**
   - Crie um novo arquivo sequencial em `database/migrations/` (ex: `021_adicionar_campo_x_em_contas.php`).
   - Implemente o comando `ALTER TABLE contas ADD COLUMN campo_x VARCHAR(100) NULL;` para PostgreSQL e MySQL.
   - Aplique localmente: `php migrate.php`.
2. **Atualizar o Model (`app/Models/`):**
   - Inclua o novo campo na listagem de colunas selecionadas ou nos métodos de inserção e atualização (`store`, `update`).
   - Vincule o parâmetro com o tipo adequado via PDO (`PDO::PARAM_STR`, `PDO::PARAM_INT`, etc.).
   - Certifique-se de que o log de auditoria registrará o valor antigo e novo (`logAudit()`).
3. **Atualizar o Controller (`app/Controllers/`):**
   - Sanitizar a entrada no método `store()` e `update()`.
   - Adicionar regras de validação (tamanho máximo, obrigatoriedade, formato esperado).
4. **Atualizar a View do Formulário / Modal:**
   - Adicione o `<input>`, `<select>` ou `<textarea>` com classes padrão do Bootstrap 5.
   - Sempre escape o valor no atributo `value` com `htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8')`.
5. **Atualizar a View de Listagem / Detalhes:**
   - Adicione a nova coluna na `<table>` ou no card de visualização. Se for valor financeiro, aplique numerais tabulares e formatação via `FormatHelper::moeda()`.
6. **Validar e Testar:**
   - Execute `php -l` nos arquivos alterados.
   - Teste cadastro com campo preenchido, campo vazio, caracteres especiais e dados de outro usuário (garantindo que o isolamento persiste).
7. **Atualizar Documentos:**
   - Registre a adição no `docs/STATUS.md` e em `docs/FSD.md` se alterar regras do negócio.

---

## 9. Como Adicionar ou Alterar uma Regra de Negócio

Para alterar regras financeiras ou de fluxo de trabalho sem quebrar o sistema:

1. **Consulte o `docs/FSD.md`:** O FSD é o documento soberano de regras funcionais. Verifique se a mudança planejada está alinhada ou se o FSD precisa ser revisado primeiro.
2. **Localize a camada correta:**
   - **Regras financeiras, cálculos e integridade:** Devem residir no **Model** ou em um **Helper especializado** (como `CreditCardCalendar.php`), nunca soltas no Controller ou no HTML.
   - **Fluxos HTTP e autorizações:** Devem residir no **Controller**.
3. **Garanta atomicidade em operações de saldo:**
   - Sempre que uma regra envolver alteração de saldos bancários ou limites de crédito (ex: transferências, estornos, pagamentos de faturas, investimentos), use transações PDO:
     ```php
     $this->beginTransaction();
     try {
         // Débito
         // Crédito
         // Log de auditoria
         $this->commit();
     } catch (Throwable $e) {
         $this->rollBack();
         throw $e;
     }
     ```
4. **Verifique concorrência:** Ao debitar contas bancárias, valide fundos diretamente no `UPDATE` para evitar saldo negativo decorrente de cliques concorrentes:
   ```sql
   UPDATE contas SET saldo_atual = saldo_atual - :valor WHERE id = :id AND saldo_atual >= :valor
   ```
5. **Teste cenários de erro:** Teste o caminho feliz e, fundamentalmente, os casos de erro: saldo insuficiente, data em feriado bancário, conta inativa, tentativa de manipular dados de outro usuário.

---

## 10. Como Testar Alterações

### 10.1. Comandos de teste e validação estática
Antes de commitar qualquer alteração, execute:
```powershell
# Validar sintaxe PHP de todos os arquivos modificados
php -l caminho/do/arquivo.php

# Conferir se há espaços em branco residuais ou marcadores de conflito
git diff --check
```

### 10.2. Testes manuais obrigatórios por fluxo
- **Autenticação e Isolamento:**
  - Crie dois usuários distintos no ambiente local (`usuario_a@local.test` e `usuario_b@local.test`).
  - Cadastre contas, categorias e despesas com o Usuário A.
  - Faça login com o Usuário B e confirme que o Dashboard, a lista de transações, contas e relatórios estão completamente vazios.
  - Tente acessar `/transacoes/obter/{id_do_usuario_a}` logado como Usuário B: o sistema deve retornar erro 404 ou 403 sem vazar informações.
- **Cartão de Crédito e Faturas:**
  - Cadastre uma compra antes do dia de fechamento: deve entrar na fatura do mês.
  - Cadastre uma compra no dia de fechamento ou após: deve entrar na fatura do ciclo seguinte.
  - Teste compra parcelada e valide se a soma das parcelas bate exatamente com o valor total (ajuste de resíduo na 1ª parcela).
- **Transferências:**
  - Teste transferência com valor superior ao saldo: a operação deve ser rejeitada com aviso claro.
  - Teste transferência com valor válido: confirme o débito na origem e o crédito no destino.

### 10.3. Verificação de logs
- Em desenvolvimento local, acompanhe os registros em `seguranca_logs` e `erros_logs` no banco de dados.
- Caso o banco esteja fora do ar, valide os arquivos de contingência em `storage/logs/error.log` e `storage/logs/security.log`.

### 10.4. Quando atualizar `docs/ERROS.md`
Atualize `docs/ERROS.md` **apenas** quando encontrar um erro real, uma falha de ambiente, uma incompatibilidade entre dialetos SQL ou um problema de integração resolvido durante o desenvolvimento. Não crie entradas fictícias para ações rotineiras de codificação.

---

## 11. Cuidados Críticos de Segurança

Qualquer alteração no código deve manter rigorosamente estes padrões de segurança:

1. **SQL Injection:** Uso exclusivo de PDO com *Prepared Statements* e `bindValue()`. Nunca concatene variáveis de entrada diretamente em strings SQL.
2. **Cross-Site Scripting (XSS):** Toda exibição de dados de usuário ou do banco em Views deve usar `htmlspecialchars($valor, ENT_QUOTES, 'UTF-8')`.
3. **Cross-Site Request Forgery (CSRF):** Todo formulário mutável (`POST`) deve conter o campo oculto `csrf_token` gerado por `CsrfHelper::getToken()`. O Controller deve validar via `CsrfHelper::validateToken($_POST['csrf_token'] ?? '')`. O Logout é obrigatoriamente via POST com token CSRF.
4. **Isolamento de Dados (Anti-IDOR):** Nenhuma leitura, atualização ou exclusão financeira pode confiar apenas no ID recebido na rota. A query deve obrigatoriamente incluir `AND usuario_id = :usuario_escopo`.
5. **Gestão de Senhas:** Senhas são salvas exclusivamente via `password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12])` e verificadas com `password_verify()`. Senhas devem ter no mínimo 8 caracteres contendo letras e números.
6. **Segurança de Sessão:** Cookies de sessão são configurados com `HttpOnly`, `SameSite=Lax`, `use_only_cookies=1` e `Secure` (sob HTTPS). O ID da sessão é regenerado no login.
7. **Proteção de Força Bruta:** 5 falhas consecutivas de login em 15 minutos resultam em bloqueio temporário por IP e e-mail.
8. **Arquivos e Uploads Privados:** Mídias nunca são acessadas diretamente pelo filesystem público. Passam pelo proxy autenticado do Vercel Blob e são validadas por MIME-type real (`finfo_file`), extensão e limite de 2 MB. Nomes de arquivos são gerados via `bin2hex(random_bytes(16))`.
9. **Segredos e Credenciais:** Variáveis sensíveis (`DATABASE_URL`, `BLOB_*`, `GITHUB_*`, senhas) nunca devem ser gravadas em código, expostas em telas, impressas em JSON ou versionadas em arquivos `.env`.

---

## 12. Como Registrar o Progresso

Após qualquer alteração concluída com sucesso:
1. Abra `docs/STATUS.md`.
2. Marque as tarefas concluídas no checklist com `[x]`.
3. Atualize o campo `Data da Última Atualização` e o `Estado` da respectiva fase.
4. Caso tenha resolvido um erro técnico importante, registre-o em `docs/ERROS.md`.

---

## 13. O que NÃO Fazer

- **NÃO** reescreva partes do sistema ou adote frameworks (Laravel, Symfony, etc.) sem decisão explícita e alinhada.
- **NÃO** instale dependências desnecessárias ou utilize CDNs externas para componentes visuais.
- **NÃO** remova verificações de CSRF, RBAC ou isolamento de `usuario_id` para "resolver rápido" um problema de permissão.
- **NÃO** crie arquivos `.env` com senhas reais no repositório.
- **NÃO** execute `migrate.php --fresh` no banco de produção.
- **NÃO** faça alterações em vários módulos simultaneamente sem explicar o impacto de cada uma.
- **NÃO** exponha dados reais de usuários, credenciais ou IDs do banco nos arquivos de documentação viva.
