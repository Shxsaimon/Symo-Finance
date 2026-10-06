# Status do Projeto — Symo

Este documento é o arquivo vivo de acompanhamento do progresso de implementação do **Symo**.

---

## 1. Informações Gerais

- **Data da Última Atualização:** 05/10/2026
- **Fase Prioritária Atual:** Fase 17 — Catálogo global de meios de pagamento (concluída)



- **Próximo Passo Recomendado:** Executar a migration da Fase 17 pelo workflow oficial e validar os fluxos integrados. OAuth Google e testes manuais da Fase 14 continuam pendentes; compartilhamento por conta permanece na Fase 18.
- **Próximo Passo Recomendado:** Publicar o ajuste de reativação e validar o fluxo integrado. OAuth Google e testes manuais da Fase 14 continuam pendentes; compartilhamento por conta permanece na Fase 18.
- **Iniciativa Multiusuário:** O Symo deixa de ser single-tenant colaborativo e passa a isolar dados por usuário (autocadastro aberto + login social via Google). Alinhamento de regras de negócio concluído; plano técnico em 6 etapas incrementais em execução.
- **Ambiente local de testes:** `load-env.ps1` (ignorado pelo Git, modelo em `load-env.example.ps1`) centraliza as variáveis e sobe o PHP embutido com `-Run`, usando uma branch do Neon e o proxy Blob já implantado, sem XAMPP. O `index.php` serve `public/` no servidor embutido (`cli-server`) para que CSS/JS carreguem localmente.
- **Deploy Vercel:** Configuração inicial adicionada em `vercel.json` usando o runtime comunitário `vercel-php`, com adaptador em `api/index.php`; persistência externa de banco, sessões, uploads e logs ainda deve ser configurada antes da produção.
- **Configuração Vercel:** `config/config.php` aceita as variáveis `APP_*` do deploy e credenciais `DB_*`; o driver atual continua sendo MySQL via PDO.
- **Migração Neon:** `DATABASE_URL` passou a ser aceita pelo PDO como PostgreSQL com SSL obrigatório; as migrations e queries ainda precisam ser convertidas de MySQL para PostgreSQL antes da homologação.
- **Compatibilidade Linux/Vercel:** O autoloader de `index.php` mapeia a classe `Database` para `config/database.php`, preservando o nome atual do arquivo e corrigindo a diferença de maiúsculas/minúsculas.
- **Validação Neon:** Fluxo mínimo validado em produção: login, sessão, redirecionamento para Dashboard, consultas com banco vazio e renderização do gráfico funcionando.
- **Armazenamento serverless:** Logs usam `error_log()` na Vercel; uploads e leituras privadas usam a Function `api/blob.js` com `@vercel/blob` e `BLOB_READ_WRITE_TOKEN`. O PostgreSQL guarda as URLs dos blobs e o PHP entrega as imagens por rotas autenticadas.
- **Integração Blob privada:** Implementados upload, download e exclusão via SDK oficial, proxy PHP autenticado, suporte a SVG sanitizado e migration 012 para campos `TEXT`.
- **Correção Dashboard PostgreSQL:** `MONTH()` e `YEAR()` agora são convertidos para `EXTRACT()` na conexão PostgreSQL.
- **Assets Vercel:** Rewrite global ajustado para não capturar `/public/` e `/api/`.
- **Entrega de assets:** `api/index.php` serve assets públicos permitidos com MIME correto; `public/**` está incluído no bundle PHP.
- **Migrations via GitHub Actions:** Workflow manual criado em `.github/workflows/database-migrations.yml`; requer o Secret `NEON_DATABASE_URL` no repositório.
- **Painel GitHub Actions:** A tela de Configurações dispara `status`, `migrate` e `seed` pelo workflow via API do GitHub, acompanha jobs e disponibiliza os logs para administradores. Requer `GITHUB_ACTIONS_TOKEN` na Vercel.
- **Logs GitHub Actions:** O download dos logs trata o redirecionamento temporário `302` da API e exibe o conteúdo completo em modal dentro da tela de Configurações.
- **Correção do modal de logs:** Inicialização adiada para `DOMContentLoaded`, garantindo que o Bootstrap esteja disponível antes de definir `window.carregarLogGitHub`.
- **Planejamento de evolução:** `AGENTS.md` agora orienta alinhar ideias de desenvolvimento e sugerir seu registro em `docs/PLANO.md`; as Fases 15 (feedbacks) e 16 (achados prioritários) foram adicionadas e atualizadas.
- **Favicon dedicado:** Criado `public/img/favicon.svg` apenas com o ícone da marca (sem o texto "Symo"), usado nos layouts `main.php` e `auth.php`; o logo completo com texto continua sendo usado onde a marca é exibida por extenso.
- **Menu mobile por perfil:** O menu lateral mobile passou a exibir Gestão de Usuários e Trilha de Auditoria somente ao `dev`, alinhado à sidebar desktop e ao bloqueio 403 do backend.
- **Perfil dev na gestão de usuários:** A tabela agora identifica explicitamente o perfil `dev` como `DEV`, em vez de exibi-lo incorretamente como `OPERADOR`.
- **Alinhamento de Produto e Documentação:** README e FSD reescritos para posicionar o Symo como plataforma individual por padrão, com contas compartilhadas seletivamente como visão futura. Vercel, Neon/PostgreSQL, Blob e GitHub Actions passam a ser o caminho oficial; MySQL/MariaDB permanece apenas como compatibilidade legado.
- **Revisão Ortográfica do README:** `README.md` totalmente revisado quanto à grafia, acentuação e ortografia em português do Brasil (pt-BR), preservando o vocabulário, clareza técnica e estrutura originais.
- **Atualização do Contexto para Agentes:** `AGENTS.md` revisado para refletir o FSD aprovado, com Vercel/Neon/Blob/GitHub Actions como stack oficial, PostgreSQL como banco principal, `admin` e `dev` com escopo financeiro próprio, `dev` com funções globais da plataforma, `operador` legado, isolamento por `usuario_id` e segredos por variáveis de ambiente.

---

## 2. Visão Geral do Progresso

| Fase | Descrição | Status |
| :--- | :--- | :--- |
| **Fase 1** | Infraestrutura, Estrutura Base e Segurança de Acesso aos Arquivos | Concluída |
| **Fase 2** | Banco de Dados, Migrations e Carga Inicial (Seeds) | Concluída |
| **Fase 3** | Núcleo da Aplicação (Core MVC, Helpers, Roteamento e Logs) | Concluída |
| **Fase 4** | Autenticação, Sessão e Controle de Acesso Base (RBAC) | Concluída |
| **Fase 5** | Layout Mestre, Design System (Symo) e Componentes Visuais | Concluída |
| **Fase 6** | Cadastros Básicos (Contas Bancárias, Categorias e Meios de Pagamento) | Concluída |
| **Fase 7** | Módulo de Cartões de Crédito e Faturas | Concluída |
| **Fase 8** | Módulo de Transações Financeiras (Receitas, Despesas e Parcelamento) | Concluída |
| **Fase 9** | Módulo de Transferências entre Contas | Concluída |
| **Fase 10** | Dashboard Financeiro e Indicadores Gráficos (Chart.js) | Concluída |
| **Fase 11** | Relatórios Analíticos e Exportação Estruturada em CSV | Concluída |
| **Fase 12** | Módulos Administrativos (Usuários, Auditoria, Lixeira, Configurações e Upload) | Concluída |
| **Fase 13** | Revisão de Segurança, Validações Integradas e Homologação Final | Concluída |
| **Fase 14** | Multiusuário e Login Google | Em andamento; OAuth Google e testes manuais pendentes |
| **Fase 15** | Consulta administrativa de feedbacks recebidos | Concluída; upload e visualização de imagens validados manualmente |
| **Fase 16** | Correção dos achados prioritários de isolamento e integridade | Concluída; isolamento e concorrência end-to-end aprovados |
| **Fase 17** | Catálogo global de meios de pagamento | Código preparado; migration remota e validação integrada pendentes |
| **Fase 17** | Catálogo global de meios de pagamento | Migration aplicada; ajuste de reativação aguarda publicação e validação integrada |
| **Fase 18** | Compartilhamento por conta | Roadmap; detalhamento técnico pendente |

---

## 3. Checklist Detalhado por Fase

### Fase 1 — Infraestrutura, Estrutura Base e Segurança de Acesso aos Arquivos
- [x] Leitura completa de `docs/FSD.md`, `docs/DESIGN.md` e `docs/INSUMOS.md`.
- [x] Criação de `docs/PLANO.md` com as 13 etapas incrementais.
- [x] Criação de `AGENTS.md` com diretrizes de arquitetura, segurança e protocolo dos arquivos vivos.
- [x] Criação de `docs/STATUS.md` e `docs/ERROS.md`.
- [x] Criação da estrutura completa de pastas (`app/`, `config/`, `database/`, `public/`, `storage/`).
- [x] Criação dos arquivos de proteção `.htaccess` nos diretórios internos.
- [x] Criação do roteador `.htaccess` raiz para o *Front Controller*.
- [x] Criação de `config/config.php` base e `index.php` inicial.
- [x] Criação de `public/css/custom.css` com Design Tokens do tema *Symo*.
- [x] Criação de `README.md` com instruções de requisitos e ambiente.

### Fase 2 — Banco de Dados, Migrations e Carga Inicial (Seeds)
- [x] Implementação de `config/database.php` (PDO Singleton com auto-criação de schema `utf8mb4_unicode_ci`).
- [x] Criação de migrations estruturais de 001 a 011 em `database/migrations/` em português.
- [x] Implementação de `migrate.php` CLI com suporte a `--seed`, `--status`, `--fresh` e lotes (`lote`).
- [x] Implementação do seed de dados essenciais em `database/seeds/initial_data.php`.
- [x] Testes de execução: criação de tabelas, integridade de FKs, anti-duplicação e seeds validados.

### Fase 3 — Núcleo da Aplicação (Core MVC, Helpers, Roteamento e Logs)
- [x] Implementação de classes base `Controller.php` e `Model.php`.
- [x] Implementação do roteador HTTP `Router.php` com suporte a GET/POST, parâmetros de URL e 404.
- [x] Implementação de `LoggerHelper.php` com gravação nas tabelas `erros_logs`/`seguranca_logs` e fallback automático.
- [x] Implementação de `CsrfHelper.php`, `FlashHelper.php` e `FormatHelper.php`.
- [x] Criação das views de erro amigáveis (`403.php`, `404.php`, `500.php`) e layout base `main.php`.
- [x] Atualização do `index.php` com autoloader PSR-like, inicialização de sessão segura, captura global de exceções e endpoint de diagnóstico `/api/health`.
- [x] Bateria de 21 testes automatizados executada e 100% aprovada.

### Fase 4 — Autenticação, Sessão e Controle de Acesso Base (RBAC)
- [x] Implementação de `AuthHelper.php` com login, logout, `requireLogin`, `requireRole`, expiração de inatividade (60 min), regeneração de ID de sessão e mitigação de força bruta.
- [x] Implementação de `User.php` com busca segura por e-mail, verificação de BCRYPT e auditoria.
- [x] Implementação de `AuthController.php` (`showLogin`, `authenticate`, `logout`, `showForgotPassword`).
- [x] Criação do layout `app/Views/layouts/auth.php` e views `app/Views/auth/login.php` e `app/Views/auth/forgot-password.php`.
- [x] Implementação da proteção contra força bruta (5 tentativas consecutivas em 15 minutos registradas no `seguranca_logs`).
- [x] Registro das rotas de autenticação, rota protegida `/dashboard` e rota administrativa de demonstração RBAC `/admin/teste-rbac`.
- [x] Bateria de 26 testes automatizados e testes de integração HTTP executados e aprovados.

### Fase 5 — Layout Mestre, Design System (Symo) e Componentes Visuais
- [x] Disponibilização e autonomia de assets locais (Bootstrap 5.3, Bootstrap Icons, Fontes WOFF2/WOFF, Chart.js 4.4 e app.js em `public/`).
- [x] Implementação do logotipo vetorial `public/img/logo.svg`.
- [x] Atualização dos design tokens e regras de layout corporativo em `public/css/custom.css`.
- [x] Criação de `app/Views/layouts/sidebar.php` e `app/Views/layouts/navbar.php`.
- [x] Implementação de layout mestre `app/Views/layouts/main.php`.
- [x] Criação de `app/Controllers/DashboardController.php` e view `app/Views/dashboard/index.php`.
- [x] Bateria de 39 testes automatizados validada com 100% de aprovação.

### Fase 6 — Cadastros Básicos (Contas Bancárias, Categorias e Meios de Pagamento)
- [x] Implementação de `app/Models/Account.php` com gestão de saldos, integridade contra exclusão de conta vinculada e auditoria.
- [x] Adicionar preferência por conta para inclusão no Saldo Atual e excluir automaticamente contas do tipo `investimento`; migration 019 aplicada no ambiente de testes e validação transacional do model aprovada.
- [x] Exibir os cards de Saldo Atual e Investimentos na tela de contas, com textos explicativos sobre os valores.
- [x] Implementação de `app/Models/Category.php` com gestão de receitas e despesas, ícones visuais e auditoria.
- [x] Implementação de `app/Models/PaymentMethod.php` com controle de modalidade de crédito e auditoria.
- [x] Implementação de `AccountController.php`, `CategoryController.php` e `PaymentMethodController.php` com controle estrito de RBAC e validação anti-CSRF.
- [x] Criação das views `app/Views/accounts/index.php`, `app/Views/categories/index.php` e `app/Views/payment-methods/index.php`.
- [x] Bateria de 40 testes automatizados executada e 100% aprovada.

### Fase 7 — Módulo de Cartões de Crédito e Faturas
- [x] Implementação de `app/Models/CreditCard.php` com cálculo de limite utilizado/disponível, dias de ciclo e auditoria.
- [x] Implementação de `app/Models/CreditCardInvoice.php` com cálculo seguro de datas de fechamento/vencimento, controle de status (aberta/fechada/paga) e quitação atômica com débito em conta.
- [x] Implementação de `CreditCardController.php` com controle de permissões e ações de faturas.
- [x] Criação das views `app/Views/credit-cards/index.php`, `app/Views/credit-cards/show.php` e `app/Views/credit-cards/invoice.php`.
- [x] Bateria de 44 testes automatizados executada e 100% aprovada.

### Fase 8 — Módulo de Transações Financeiras (Receitas, Despesas e Parcelamento)
- [x] Implementação de `app/Models/Transaction.php` com suporte a receitas, despesas à vista e parcelamento em N vezes com tratamento de resíduo.
- [x] Implementação de `app/Controllers/TransactionController.php` com ações de criação, edição, alternância de status e exclusão respeitando RBAC.
- [x] Criação da view `app/Views/transactions/index.php` com tabela financeira, filtros avançados, cards de KPIs e modais.
- [x] Corrigir edição para preservar a data de lançamento e alterar o vencimento somente quando informado no modal.
- [x] Bateria de 40 testes automatizados executada e 100% aprovada.

### Fase 9 — Módulo de Transferências entre Contas
- [x] Implementação de `app/Models/Transfer.php` com suporte a transferências internas, estorno de saldos, validação de saldo suficiente e transação atômica.
- [x] Implementação de `app/Controllers/TransferController.php` com controle RBAC e validações de contas distintas.
- [x] Criação da view `app/Views/transfers/index.php` com cards de resumo, filtros, histórico e modal de transferência.
- [x] Bateria de 30 testes automatizados executada e 100% aprovada.

### Fase 10 — Dashboard Financeiro e Indicadores Gráficos (Chart.js)
- [x] Exibir abaixo do Saldo Atual somente a quantidade de contas ativas incluídas nesse saldo.
- [x] Implementação de `DashboardController.php` com agregação de KPIs em tempo real, evolução financeira e categorias.
- [x] Integração de gráficos interativos com Chart.js local (Evolução Mensal e Rosca de Despesas por Categoria).
- [x] Criação de widgets de compromissos pendentes nos próximos 7 dias e resumo visual de cartões/limites.
- [x] Criação da view `app/Views/dashboard/index.php` completa e responsiva.
- [x] Bateria de 34 testes automatizados executada e 100% aprovada.

### Fase 11 — Relatórios Analíticos e Exportação Estruturada em CSV
- [x] Implementação de `app/Models/Report.php` com suporte a filtros combinados (mês/ano, período personalizado, tipo, status, conta, cartão, meio de pagamento, busca textual), totalizadores e visão agrupada por categoria.
- [x] Implementação de `app/Controllers/ReportController.php` com listagem analítica/sintética e exportação estruturada em CSV com delimitador `;`, valores numéricos padrão PT-BR e BOM UTF-8 (`\xEF\xBB\xBF`) para compatibilidade nativa com Microsoft Excel.
- [x] Criação da view `app/Views/reports/index.php` com alternador dinâmico de tipo de período, extrato analítico detalhado com badges e totalizadores, e visão sintética de participação por categoria com barras de progresso.
- [x] Bateria de 33 testes automatizados executada e 100% aprovada.

### Fase 12 — Módulos Administrativos (Usuários, Auditoria, Lixeira, Configurações e Upload)
- [x] Implementação de `app/Controllers/UserController.php` e expansão de `app/Models/User.php` com CRUD de usuários, alternância de status com revogação imediata, redefinição de senhas com BCRYPT e bloqueio de auto-exclusão/auto-inativação.
- [x] Implementação de `app/Controllers/AuditController.php` e model `app/Models/AuditLog.php` com filtros por tabela/ação/usuário, métricas consolidadas e modal interativo com comparativo de JSON Diff (valores antigos vs. valores novos).
- [x] Implementação de `app/Controllers/TrashController.php` e model `app/Models/Trash.php` com abas para transações, contas e categorias excluídas, ação de restauração com reprocessamento atômico de saldos e expurgo definitivo (*Hard Delete*).
- [x] Implementação de `app/Controllers/SettingsController.php` e model `app/Models/Setting.php` com preferências gerais de moeda/separadores/paginação, painel de status de migrações e disparador de execução de migrações.
- [x] Implementação de `app/Helpers/UploadHelper.php` com validação de tamanho (máx 2MB), verificação real de MIME-Type / Magic Bytes via `finfo_file`, renomeação com hash randômico e exclusão limpa de arquivos anteriores em `storage/uploads/avatars/` e `storage/uploads/logos/`.
- [x] Implementação de `app/Controllers/ProfileController.php` e view `app/Views/users/profile.php` para auto-gestão de avatar e alteração de senha por qualquer usuário autenticado.
- [x] Bateria de 50 testes automatizados executada e 100% aprovada.

### Fase 13 — Revisão de Segurança, Validações Integradas e Homologação Final
- [x] Corrigido logout mutável por GET; agora exige POST com token CSRF em todos os layouts.
- [x] Removida exposição de erro do banco e dados do usuário no endpoint `/api/health`.
- [x] Aplicado atributo `Secure` ao cookie de sessão quando a requisição usa HTTPS.
- [x] Substituídos acessos SQL diretos por prepared statements na camada `app/`, no health check e no CLI de migrations.
- [x] Executados lint PHP completo, diagnóstico do editor, testes HTTP de 302/403/404, proteção de diretórios e ausência de CDNs externas em runtime.
- [x] Verificado o estado das migrations em ambiente de desenvolvimento.
- [x] Concluídos os testes manuais autenticados de RBAC, XSS/SQLi, uploads e exportação CSV.
- [x] Validada a conformidade dos critérios de aceitação da seção 26 do `docs/FSD.md`.
- [x] Sistema construído, banco de dados operacional e aplicação homologada para uso.

### Fase 14 — Multiusuário (Isolamento por Usuário) e Login Google
- **Contexto:** Symo passa de single-tenant colaborativo para multiusuário real: autocadastro aberto (perfil `admin` da própria conta), login social via Google, e isolamento de dados por `usuario_id`. Novo perfil `dev` (admin do sistema, bootstrap único) substitui o antigo par admin/operador nas telas administrativas globais (Configurações completas, Gestão de Usuários, Auditoria). Alinhamento de negócio completo realizado antes da implementação.
- **Etapa 1/6 — Migrations, seed do `dev` e template de dados padrão (Concluída):**
  - [x] Migration `013_ajustar_perfil_google_usuarios.php`: `perfil` ganha o valor `dev`; `senha` passa a aceitar `NULL` (contas 100% Google); nova coluna `google_id` (única, nullable).
  - [x] Migration `014_adicionar_usuario_id_dados_financeiros.php`: adiciona `usuario_id` (FK obrigatória) em `contas`, `categorias`, `meios_pagamento` e `cartoes_credito` (assume tabelas vazias, banco recriado via `--fresh`).
  - [x] Migration `015_criar_tabela_configuracoes_usuario.php`: nova tabela `configuracoes_usuario` (preferências pessoais por usuário: moeda, separadores, paginação); tabela `configuracoes` original permanece exclusiva do perfil `dev` (identidade visual, status de migrations, GitHub Actions).
  - [x] Criado `app/Helpers/DefaultDataHelper.php`, extraindo o template de categorias/meios de pagamento/preferências (antes fixo no seed) para clonagem por usuário no cadastro.
  - [x] `database/seeds/initial_data.php` reescrito: remove os usuários demo `admin@symo.local`/`operador@symo.local` e as categorias/meios globais; cria apenas o usuário `dev` (via `config.php`/variáveis `SYSTEM_DEV_EMAIL`, `SYSTEM_DEV_PASSWORD`, `SYSTEM_DEV_NAME`) e as configurações globais restantes (`nome_aplicacao`, `logotipo_aplicacao`).
  - [x] Adicionados blocos `dev` e `google_oauth` em `config/config.php` e `config/config.example.php`.
  - **Pendente antes de rodar em qualquer ambiente:** definir as variáveis de ambiente `SYSTEM_DEV_EMAIL`/`SYSTEM_DEV_PASSWORD`/`SYSTEM_DEV_NAME`; executar `php migrate.php` e depois `php migrate.php --seed`.
  - [x] `migrate.php --fresh` agora funciona também no PostgreSQL/Neon: reseta o schema `public` inteiro (`DROP SCHEMA ... CASCADE`) e reaplica todas as migrations do zero. Exige o flag extra `--confirmar` (ex.: `php migrate.php --fresh --confirmar`) para evitar execução acidental — ação destrutiva, apaga todos os dados do banco conectado.
  - [x] Workflow `.github/workflows/database-migrations.yml` ganhou a opção `fresh`, disponível apenas via disparo manual direto no GitHub Actions (não pelo painel de Configurações do Symo, que continua restrito a `status`/`migrate`/`seed`). Exige preencher o campo `confirmacao_fresh` com o texto exato `RESETAR`. Requer os Secrets `SYSTEM_DEV_NAME`, `SYSTEM_DEV_EMAIL` e `SYSTEM_DEV_PASSWORD` no repositório para que o seed do usuário `dev` seja recriado automaticamente após o reset.
- **Etapa 2/6 — Autocadastro local (Concluída):**
  - [x] `User::registerSelf()`: valida nome (mín. 3 caracteres), e-mail (formato + duplicidade com mensagem genérica anti-enumeração), senha (mín. 8 caracteres com letra e número); cria o usuário com `perfil = admin`, ativa imediatamente (sem verificação de e-mail) e clona os dados padrão via `DefaultDataHelper::popularDadosPadrao()` dentro de uma transação atômica.
  - [x] `AuthController::showRegister()`/`register()` e rotas `GET`/`POST /registrar` (com proteção CSRF).
  - [x] Nova view `app/Views/auth/register.php` (nome, e-mail, senha, confirmação de senha).
  - [x] Botão "Criar conta" adicionado em `app/Views/auth/login.php`, com link recíproco "Entrar" na tela de cadastro.
  - [x] Login automático após cadastro bem-sucedido, redirecionando para `/dashboard`.
- **Etapa 3/6 — Isolamento de dados e validação contra IDOR (Concluída):**
  - [x] `Model` aplica escopo automático por `usuario_id` em `find`, `all` e `softDelete` para contas, categorias, meios de pagamento e cartões.
  - [x] Cadastros de contas, categorias, meios de pagamento e cartões gravam `usuario_id` e filtram alterações/status pelo proprietário.
  - [x] Cartões validam a propriedade da conta padrão; transações validam conta, categoria, meio de pagamento e cartão antes da escrita.
  - [x] Transferências validam contas do mesmo usuário e restringem os ajustes de saldo ao proprietário.
  - [x] Dashboard, relatórios, histórico de transferências e faturas passaram a filtrar o usuário autenticado.
  - [x] Lixeira filtra contagens/listagens/restauração/expurgo por `usuario_id`.
- **Etapa 4/6 — Segmentação de RBAC, menus e configurações (Concluída):**
  - [x] Perfil `dev` ganhou helpers explícitos (`isDev()`/`requireDev()`); `admin` continua sendo o administrador da própria conta, não administrador global.
  - [x] Gestão de Usuários e Auditoria ficaram restritas ao `dev`, inclusive por URL direta; `dev` herda as permissões operacionais de `admin` nos módulos financeiros.
  - [x] Lixeira disponível a usuários autenticados, sempre limitada aos dados do próprio usuário, inclusive para `dev`.
  - [x] Configurações comuns usam exclusivamente `configuracoes_usuario`; identidade visual, status de migrations e GitHub Actions ficaram exclusivos do `dev`.
  - [x] Sidebar passou a exibir Gestão de Usuários/Auditoria apenas para `dev` e Configurações/Lixeira conforme o perfil.
- **Próximas etapas planejadas:** A prioridade atual é a Fase 17, catálogo global de meios de pagamento. OAuth Google e testes manuais completos da Fase 14 continuam pendentes. O compartilhamento por conta foi separado para a Fase 18 e segue no roadmap.

### Fase 15 — Consulta administrativa de feedbacks recebidos
- [x] Criar consulta paginada de feedbacks exclusiva do perfil `dev`.
- [x] Exibir autor, tela, tipo, mensagem, anexos e status; manter `novo` como estado inicial.
- [x] Exibir imagens por rota autenticada, sem tornar os arquivos do Blob públicos.
- [x] Permitir ao `dev` mudar o status para Em análise ou Concluído, com CSRF e auditoria.
- [x] Organizar a tabela com mensagem, imagens, status e ações; oferecer botões de status compactos lado a lado.
- [x] Incluir a tela na navegação administrativa para desktop e mobile.
- [x] Validar lint e diagnósticos estáticos.
- [x] Confirmar manualmente envio e visualização de feedback com imagem em ambiente configurado.
- **Estado:** Concluída.

### Fase 16 — Isolamento financeiro de `admin` e `dev`
- [x] Restringir transações, transferências, agregados e lixeira ao proprietário, inclusive para `dev`.
- [x] Confirmar com outro usuário que o resumo de transferências não expõe dados alheios.
- [x] Confirmar Dashboard, listagem de transações, lixeira/estorno e os cenários de saldo das transferências.
- [x] Testar acesso por identificadores de registros pertencentes a outro usuário; leituras e mutações não autorizadas foram bloqueadas sem alterar dados.
- [x] Testar duas chamadas concorrentes a `Transfer::createTransfer`: uma conclui, a outra sofre rollback; origem é debitada e destino creditado uma única vez.
- [x] Confirmar a remoção dos dados temporários de teste sem alteração dos dados financeiros existentes.
- [x] Revalidar o estado ativo do usuário nas requisições autenticadas.
- [x] Validar lint, diagnósticos estáticos e whitespace.
- **Estado:** Concluída; testes de isolamento e concorrência end-to-end aprovados no PostgreSQL.

### Fase 17 — Catálogo global de meios de pagamento
- [x] Definir o catálogo inicial: Dinheiro, Pix, Cartão de Débito, Cartão de Crédito, Boleto Bancário e Transferência Bancária.
- [x] Definir criação e alteração do status global como ações exclusivas do `dev`.
- [x] Permitir ao `dev` inativar e reativar globalmente pela listagem, atualizando o status sem marcar exclusão lógica.
- [x] Definir ativação/inativação individual por usuário, inclusive para o próprio `dev`, sem efeito sobre outras contas.
- [x] Definir que opções globalmente ou individualmente inativas não aparecem em novos lançamentos, mas permanecem nos vínculos e na exibição histórica.
- [x] Definir que a migration validará as opções existentes, derivará as correspondências canônicas e preservará os vínculos antes de remover duplicatas.
- [x] Definir que a migration abortará em caso de divergência e manterá a auditoria histórica intacta.
- [x] Implementar migration, catálogo global, preferências individuais e atualização do cadastro e do seed demo.
- [x] Adaptar autorização, listagem, seleção de opções e preservação do meio em lançamentos históricos.
- [x] Validar sintaxe dos arquivos PHP alterados e diagnósticos do editor.
- [x] Registrar a migration aplicada pelo workflow oficial; nenhuma escrita adicional foi feita nesta correção.
- [x] Implementar reativação global pelo status sem exclusão lógica e preservar a preferência pessoal.
- [x] Publicar a correção e validar o fluxo integrado de inativação/reativação pela tela.
- **Estado:** Migration aplicada; ajuste de reativação preparado, publicação e validação integrada validados.

### Fase 18 — Compartilhamento por conta
- [x] Alinhar que permissões serão vínculos por conta, não papel global de usuário.
- [x] Alinhar que participantes verão somente lançamentos posteriores ao aceite e da conta compartilhada.
- [x] Alinhar que transferências só podem envolver contas do mesmo proprietário.
- [ ] Definir ciclo do convite, aceite/recusa, identidade do participante e revogação.
- [ ] Definir permissões e implementar o escopo nas telas e operações autorizadas.
- **Estado:** Roadmap; detalhamento técnico e implementação pendentes.
