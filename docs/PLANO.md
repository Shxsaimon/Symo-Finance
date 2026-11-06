# Plano de Construção Incremental — Symo

Este documento detalha o planejamento de implementação fase a fase do sistema **Symo (Gestão Financeira Pessoal e Compartilhada)**, com base estrita no `docs/FSD.md` e `docs/DESIGN.md`.

---

## Visão Geral das Fases

- **Fase 1:** Infraestrutura, Estrutura Base e Segurança de Acesso aos Arquivos
- **Fase 2:** Banco de Dados, Migrations e Carga Inicial (Seeds)
- **Fase 3:** Núcleo da Aplicação (Core MVC, Helpers, Roteamento e Manipulação de Erros/Logs)
- **Fase 4:** Autenticação, Sessão e Controle de Acesso Base (RBAC)
- **Fase 5:** Layout Mestre, Design System (Symo) e Componentes Visuais
- **Fase 6:** Cadastros Básicos (Contas Bancárias, Categorias e Meios de Pagamento)
- **Fase 7:** Módulo de Cartões de Crédito e Faturas
- **Fase 8:** Módulo de Transações Financeiras (Receitas, Despesas e Compras Parceladas)
- **Fase 9:** Módulo de Transferências entre Contas
- **Fase 10:** Dashboard Financeiro e Indicadores Gráficos (Chart.js)
- **Fase 11:** Relatórios Analíticos e Exportação Estruturada em CSV
- **Fase 12:** Módulos Administrativos (Gestão de Usuários, Auditoria, Lixeira e Configurações Globais com Upload Seguro)
- **Fase 13:** Revisão de Segurança, Validações Integradas e Homologação Final
- **Fase 14:** Multiusuário e Autenticação Social (etapas já concluídas e pendentes registradas abaixo)
- **Fase 15:** Consulta administrativa de feedbacks recebidos
- **Fase 16:** Correção dos achados prioritários de isolamento e integridade financeira
- **Fase 17:** Catálogo global de meios de pagamento
- **Fase 18:** Compartilhamento por conta (roadmap)
- **Fase 19:** Investimentos
- **Fase 20:** Perfil do usuário e acesso pela navegação
- **Fase 21:** Correção do fluxo de despesas e ciclos de faturas de cartão
- **Fase 22:** Modal de lançamento unificado no Dashboard e padronização visual de receitas
- **Fase 23:** Prioridade de fatura fechada nos cards de cartões e parcelas futuras na abertura de fatura

---

## Detalhamento das Fases

### Fase 1 — Infraestrutura, Estrutura Base e Segurança de Acesso aos Arquivos
- **Objetivo:** Criar a árvore completa de diretórios, configurações de ambiente, ponto de entrada único e proteções HTTP nos diretórios internos.
- **Checklist de Tarefas:**
  - [x] Criar estrutura de diretórios (`app/`, `config/`, `database/`, `public/`, `storage/`).
  - [x] Criar `.htaccess` raiz com regras de reescrita (*Front Controller*) para `index.php`.
  - [x] Criar arquivos `.htaccess` de bloqueio direto em `config/`, `app/`, `database/` e `storage/logs/` (`Require all denied`).
  - [x] Criar `.htaccess` em `storage/uploads/` bloqueando a execução de scripts PHP.
  - [x] Criar `config/config.php` com configurações padrão, caminhos e fallback.
  - [x] Criar `public/css/custom.css` com as variáveis CSS / Design Tokens do tema *Symo* (`docs/DESIGN.md`).
  - [x] Criar `index.php` inicial com cabeçalhos de segurança e checagem de constante `APP_RUNNING`.
  - [x] Criar `README.md` com instruções de requisitos e ambiente.
- **Critérios de Pronto:**
  - Tentativas de acesso direto a arquivos em `config/`, `app/`, `database/` ou `storage/` retornam erro 403 Forbidden.
  - O acesso à raiz do projeto é direcionado ao `index.php`.
  - Configurações e tokens de design estão definidos sem dependência de `.env`.
- **Arquivos/Pastas Envolvidos:** `.htaccess`, `index.php`, `config/`, `app/`, `database/`, `storage/`, `public/`, `README.md`.
- **Dependências:** Nenhuma (fase inicial).

---

### Fase 2 — Banco de Dados, Migrations e Carga Inicial (Seeds)
- **Objetivo:** Implementar a fábrica de conexão PDO Singleton, o sistema versionado de migrations com controle anti-duplicação e a carga inicial de dados.
- **Checklist de Tarefas:**
  - [x] Criar `config/database.php` com padrão Singleton via PDO, UTF-8 (`utf8mb4`) e tratamento de erros.
  - [x] Criar tabela de controle `migracoes`.
  - [x] Criar as migrations estruturais em `database/migrations/`:
    - `001_criar_tabela_usuarios.php`
    - `002_criar_tabela_contas.php`
    - `003_criar_tabela_categorias.php`
    - `004_criar_tabela_meios_pagamento.php`
    - `005_criar_tabela_transacoes.php`
    - `006_criar_tabela_transferencias.php`
    - `007_criar_tabelas_cartoes_e_faturas.php`
    - `008_criar_tabela_auditoria_logs.php`
    - `009_criar_tabelas_seguranca_e_erros_logs.php`
    - `010_criar_tabela_configuracoes.php`
    - `011_criar_tabela_migracoes.php`
  - [x] Criar script CLI `migrate.php` para execução segura de migrations no terminal.
  - [x] Criar seed inicial em `database/seeds/initial_data.php` (usuário admin padrão e categorias/meios iniciais).
- **Critérios de Pronto:**
  - Execução de `php migrate.php` cria todas as 11 tabelas com chaves estrangeiras e índices sem erros.
  - Segunda execução consecutiva do script detecta migrations já aplicadas e não as duplica.
- **Arquivos/Pastas Envolvidos:** `config/database.php`, `database/migrations/`, `database/seeds/`, `migrate.php`.
- **Dependências:** Fase 1 concluída.

---

### Fase 3 — Núcleo da Aplicação (Core MVC, Helpers, Roteamento e Logs)
- **Objetivo:** Estabelecer a infraestrutura de código backend: roteador HTTP, controllers/models base, helpers utilitários e sistema de log com contingência em arquivo.
- **Checklist de Tarefas:**
  - [x] Implementar classe base `Controller` e `Model`.
  - [x] Implementar roteador com suporte a rotas GET/POST, middlewares de autenticação e RBAC.
  - [x] Criar `app/Helpers/LoggerHelper.php` com suporte a logs em BD (`erros_logs`/`seguranca_logs`) e fallback automático para `storage/logs/error.log` e `storage/logs/security.log`.
  - [x] Criar `app/Helpers/CsrfHelper.php` para geração e validação de tokens anti-CSRF.
  - [x] Criar `app/Helpers/FlashHelper.php` para mensagens de feedback temporárias na sessão.
  - [x] Criar `app/Helpers/FormatHelper.php` para moeda (`R$`), datas brasileiras e numerais tabulares.
  - [x] Criar páginas de erro amigáveis em `app/Views/errors/` (403, 404, 500) e layout base `main.php`.
- **Critérios de Pronto:**
  - Rotas desconhecidas acionam a tela 404.
  - Exceções não tratadas acionam a tela 500 sem vazar dados sensíveis, gravando em banco ou no arquivo de contingência `storage/logs/error.log`.
- **Arquivos/Pastas Envolvidos:** `app/Helpers/`, `app/Controllers/`, `app/Models/`, `app/Views/errors/`, `index.php`.
- **Dependências:** Fases 1 e 2 concluídas.

---

### Fase 4 — Autenticação, Sessão e Controle de Acesso Base (RBAC)
- **Objetivo:** Implementar o fluxo seguro de login, logout, bloqueio contra força bruta, expiração de 60 minutos e verificação de perfil de acesso.
- **Checklist de Tarefas:**
  - [x] Criar `app/Helpers/AuthHelper.php` (login, logout, `requireLogin`, `requireRole`, timeout de 60 min, regeneração de ID de sessão e mitigação de brute force).
  - [x] Criar `app/Models/User.php` com métodos de autenticação, verificação de hash BCRYPT e atualização de último acesso.
  - [x] Criar `app/Controllers/AuthController.php` (`showLogin`, `authenticate`, `logout`, `showForgotPassword`).
  - [x] Criar telas de autenticação em `app/Views/auth/login.php` e `app/Views/auth/forgot-password.php` e layout `app/Views/layouts/auth.php` seguindo o design do Symo.
  - [x] Implementar proteção contra ataques de força bruta (5 tentativas falhas = bloqueio temporário de 15 minutos por IP/E-mail registrado no `seguranca_logs`).
- **Critérios de Pronto:**
  - Login funcional com credenciais válidas; mensagens genéricas para credenciais inválidas.
  - Bloqueio automático após 5 tentativas incorretas registrado no `seguranca_logs`.
  - Expiração automática após 60 minutos de inatividade com redirecionamento para login.
- **Arquivos/Pastas Envolvidos:** `app/Helpers/AuthHelper.php`, `app/Models/User.php`, `app/Controllers/AuthController.php`, `app/Views/auth/`, `app/Views/layouts/`, `index.php`.
- **Dependências:** Fases 1, 2 e 3 concluídas.

---

### Fase 5 — Layout Mestre, Design System (Symo) e Componentes Visuais
- **Objetivo:** Estruturar a camada visual mestre com Bootstrap 5 local, ícones, responsividade e componentes do tema *Symo* (`docs/DESIGN.md`).
- **Checklist de Tarefas:**
  - [x] Disponibilizar os assets locais em `public/css/`, `public/js/`, `public/fonts/` e `public/img/`.
  - [x] Montar `app/Views/layouts/main.php` (estrutura responsiva com container max 1440px).
  - [x] Montar `app/Views/layouts/navbar.php` (perfil do usuário, logo, alertas e ações rápidas).
  - [x] Montar `app/Views/layouts/sidebar.php` (links de navegação, indicação ativa em pílula Electric Violet de 4px, restrição visual por perfil RBAC).
  - [x] Montar `app/Views/layouts/auth.php` (layout limpo e centralizado para telas de autenticação).
  - [x] Integrar exibição de mensagens flash de sucesso, alerta e erro.
- **Critérios de Pronto:**
  - Layout funcional e responsivo em desktop, tablet e smartphone.
  - Paleta de cores, tipografia *Inter*, sombras tonais e arredondamentos de 8px e 16px conformes ao `docs/DESIGN.md`.
- **Arquivos/Pastas Envolvidos:** `app/Views/layouts/`, `public/css/`, `public/js/`, `public/fonts/`, `public/img/`, `app/Controllers/DashboardController.php`, `app/Views/dashboard/`.
- **Dependências:** Fases 1 a 4 concluídas.

---

### Fase 6 — Cadastros Básicos (Contas Bancárias, Categorias e Meios de Pagamento)
- **Objetivo:** Implementar o gerenciamento cadastral de contas, categorias e meios de pagamento com controle de permissão RBAC e proteção contra exclusão física de itens com movimentações.
- **Checklist de Tarefas:**
  - [x] Implementar `app/Models/Account.php`, `app/Models/Category.php`, `app/Models/PaymentMethod.php`.
  - [x] Implementar `app/Controllers/AccountController.php` (CRUD de contas, cálculo de saldo atual, inativação e soft delete).
  - [x] Implementar `app/Controllers/CategoryController.php` (CRUD de categorias com tipo receita/despesa, ícones e cores).
  - [x] Implementar `app/Controllers/PaymentMethodController.php` (CRUD de meios de pagamento).
  - [x] Criar views em `app/Views/accounts/`, `app/Views/categories/`, `app/Views/payment-methods/`.
  - [x] Aplicar restrição RBAC: Operador apenas consulta; Administrador cria/edita/inativa.
- **Critérios de Pronto:**
  - Administrador consegue cadastrar, editar e inativar contas, categorias e meios de pagamento.
  - Operador visualiza os itens nos formulários e listagens sem permissão para modificá-los.
  - Itens vinculados a transações não podem sofrer exclusão física direta.
- **Arquivos/Pastas Envolvidos:** `app/Models/`, `app/Controllers/`, `app/Views/accounts/`, `app/Views/categories/`, `app/Views/payment-methods/`.
- **Dependências:** Fases 1 a 5 concluídas.

---

### Fase 7 — Módulo de Cartões de Crédito e Faturas
- **Objetivo:** Gerenciar cartões de crédito, cálculo de limite disponível, acompanhamento de ciclo de faturas (Aberta, Fechada, Paga) e pagamento consolidado.
- **Checklist de Tarefas:**
  - [x] Implementar `app/Models/CreditCard.php` e `app/Models/CreditCardInvoice.php`.
  - [x] Implementar `app/Controllers/CreditCardController.php` (cadastro de cartões, visualização de limites e faturas).
  - [x] Criar rotina de fechamento de fatura e liquidação com débito em conta corrente via transação atômica PDO.
  - [x] Criar views em `app/Views/credit-cards/` (cards visuais de cartões, barra de limite e histórico de faturas).
- **Critérios de Pronto:**
  - Faturas agrupam despesas do período corretamente.
  - Pagamento de fatura debita a conta bancária escolhida, marca fatura como paga e restabelece o limite disponível no cartão sob transação atômica.
- **Arquivos/Pastas Envolvidos:** `app/Models/CreditCard*.php`, `app/Controllers/CreditCardController.php`, `app/Views/credit-cards/`.
- **Dependências:** Fases 1 a 6 concluídas.

---

### Fase 8 — Módulo de Transações Financeiras (Receitas, Despesas e Parcelamento)
- **Objetivo:** Implementar o núcleo de movimentação financeira: receitas, despesas à vista e parceladas, controle de status (pendente/pago), atualização atômica de saldos e auditoria de lançamentos.
- **Checklist de Tarefas:**
  - [x] Implementar `app/Models/Transaction.php` com suporte a cálculo de saldos, filtros complexos e soft delete.
  - [x] Implementar `app/Controllers/TransactionController.php`.
  - [x] Implementar rotina de compra parcelada com geração de $N$ lançamentos agrupados por `installment_group_id`, ajuste de dízima residual de centavos na 1ª parcela e vinculação de faturas de cartão.
  - [x] Implementar validação de propriedade: alterações e exclusões ficam limitadas ao dono do lançamento, inclusive para `dev`.
  - [x] Criar views e modais em `app/Views/transactions/` com tabela zebrada de alta densidade e numerais tabulares.
- **Critérios de Pronto:**
  - Lançamento de receita/despesa com status pago atualiza imediatamente o saldo da conta vinculada; status pendente não altera saldo.
  - Compra parcelada de $N$ vezes divide valores com exatidão matemática somando centavos na primeira parcela.
  - Tentativa de operador alterar transação de outro usuário é bloqueada com 403 e log de segurança.
- **Arquivos/Pastas Envolvidos:** `app/Models/Transaction.php`, `app/Controllers/TransactionController.php`, `app/Views/transactions/`.
- **Dependências:** Fases 1 a 7 concluídas.

---

### Fase 9 — Módulo de Transferências entre Contas
- **Objetivo:** Permitir transferências de valores entre contas bancárias com garantia de atomicidade no banco de dados e sem distorcer o fluxo de receitas/despesas.
- **Checklist de Tarefas:**
  - [x] Implementar `app/Models/Transfer.php`.
  - [x] Implementar `app/Controllers/TransferController.php` com validação de contas distintas e valor positivo.
  - [x] Executar débito na origem e crédito no destino sob transação atômica PDO (`beginTransaction`, `commit`, `rollBack`).
  - [x] Criar views e formulário em `app/Views/transfers/`.
- **Critérios de Pronto:**
  - Transferência concluída atualiza simultaneamente o saldo de ambas as contas.
  - Qualquer erro de execução reverte integralmente a operação (`rollBack`).
- **Arquivos/Pastas Envolvidos:** `app/Models/Transfer.php`, `app/Controllers/TransferController.php`, `app/Views/transfers/`.
- **Dependências:** Fases 1 a 8 concluídas.

---

### Fase 10 — Dashboard Financeiro e Indicadores Gráficos
- **Objetivo:** Apresentar painel panorâmico com KPIs em tempo real, gráficos de evolução e distribuição de gastos com Chart.js local e widgets de contas a vencer.
- **Checklist de Tarefas:**
  - [x] Implementar `app/Controllers/DashboardController.php` com agregação de métricas do mês selecionado.
  - [x] Implementar cálculo de Saldo Atual, Receitas do Mês, Despesas do Mês e Balanço Líquido.
  - [x] Integrar Chart.js local com gráficos:
    - Evolução Financeira Mensal (barras/linhas em Electric Violet e Deep Lavender).
    - Despesas por Categoria (gráfico de rosca/donut com paleta do tema Symo).
  - [x] Criar widgets de contas a vencer nos próximos 7 dias e faturas de cartão.
  - [x] Criar view `app/Views/dashboard/index.php`.
- **Critérios de Pronto:**
  - Dashboard carrega dados agregados consistentes com as transações cadastradas.
  - Gráficos renderizam localmente sem requisições a CDNs externas.
- **Arquivos/Pastas Envolvidos:** `app/Controllers/DashboardController.php`, `app/Views/dashboard/`, `public/js/`.
- **Dependências:** Fases 1 a 9 concluídas.

---

### Fase 11 — Relatórios Analíticos e Exportação Estruturada em CSV
- **Objetivo:** Disponibilizar consultas consolidadas por período, categoria, conta e meio de pagamento com exportação nativa para arquivo CSV com UTF-8 BOM.
- **Checklist de Tarefas:**
  - [x] Implementar `app/Models/Report.php` e `app/Controllers/ReportController.php`.
  - [x] Implementar filtros de período (mês/ano ou datas customizadas), categoria, conta, meio de pagamento e status.
  - [x] Implementar rotina de exportação CSV com delimitador `;`, valores formatados e BOM UTF-8 (`\xEF\xBB\xBF`) para compatibilidade perfeita com Excel.
  - [x] Criar views em `app/Views/reports/`.
- **Critérios de Pronto:**
  - Relatórios refletem exatamente os filtros selecionados.
  - Arquivo CSV baixado abre com acentuação e colunas corretas no Excel e LibreOffice.
- **Arquivos/Pastas Envolvidos:** `app/Models/Report.php`, `app/Controllers/ReportController.php`, `app/Views/reports/`.
- **Dependências:** Fases 1 a 10 concluídas.

---

### Fase 12 — Módulos Administrativos (Usuários, Auditoria, Lixeira, Configurações e Upload)
- **Objetivo:** Implementar os recursos exclusivos do Administrador: gerenciamento de usuários, trilha de auditoria com JSON Diff, lixeira com restauração de saldos, configurações globais e upload seguro de logotipo/avatar.
- **Checklist de Tarefas:**
  - [x] Implementar `app/Controllers/UserController.php` (CRUD de usuários, inativação imediata de sessão, redefinição de senha).
  - [x] Implementar `app/Controllers/AuditController.php` e visualizador de JSON Diff na view `app/Views/audit/`.
  - [x] Implementar `app/Controllers/TrashController.php` (listagem de transações, contas e categorias em soft delete, ações de restauração e expurgo definitivo).
  - [x] Implementar `app/Controllers/SettingsController.php` e `app/Models/Setting.php` (parâmetros do sistema e disparador de verificação de migrations).
  - [x] Implementar rotina de upload seguro (máx 2MB, validação de MIME-type real com `finfo`, renomeação com hash randômico e exclusão do arquivo antigo) em `storage/uploads/avatars/` e `storage/uploads/logos/`.
- **Critérios de Pronto:**
  - Apenas administradores conseguem acessar `/usuarios`, `/auditoria`, `/lixeira` e `/configuracoes`.
  - Restauração de transação na lixeira reprocessa o saldo da conta com integridade.
  - Upload aceita apenas imagens válidas de até 2MB e salva nomes seguros no disco.
- **Arquivos/Pastas Envolvidos:** `app/Controllers/` (`UserController.php`, `AuditController.php`, `TrashController.php`, `SettingsController.php`, `ProfileController.php`), `app/Models/Setting.php`, `app/Models/AuditLog.php`, `app/Models/Trash.php`, `app/Models/User.php`, `app/Helpers/UploadHelper.php`, `app/Views/` (`users/`, `audit/`, `trash/`, `settings/`), `storage/uploads/`.
- **Dependências:** Fases 1 a 11 concluídas.

---

### Fase 13 — Revisão de Segurança, Validações Integradas e Homologação Final
- **Objetivo:** Conduzir validações finais de segurança em todas as rotas e checagem de todos os critérios de aceitação do `docs/FSD.md`.
- **Checklist de Tarefas:**
  - [x] Auditar rotas públicas, protegidas e inexistentes, incluindo redirecionamento de sessão e respostas 403/404; permanece pendente o teste manual com perfil Operador.
  - [x] Validar por inspeção as saídas com `htmlspecialchars` e a presença de tokens anti-CSRF nos formulários; logout validado por POST protegido.
  - [x] Verificar ausência de consultas SQL diretas na aplicação, no health check e no CLI; `exec()` permanece somente nas migrations versionadas e na criação inicial do banco.
  - [x] Testar contingência de logs simulando falha no banco de dados.
  - [x] Verificar ausência de dependências de CDNs externas em tempo de execução.
  - [x] Validar integralmente a conformidade de todos os itens da seção 26 do `docs/FSD.md`.
- **Validações executadas:** Lint PHP, verificação do estado das migrations, testes HTTP de autenticação/autorização, proteção de arquivos internos e análise estática.
- **Critérios de Pronto:**
  - 100% dos critérios de aceitação do FSD atendidos.
  - Sistema homologado e pronto para uso em produção.
- **Arquivos/Pastas Envolvidos:** Todo o projeto.
- **Dependências:** Fases 1 a 12 concluídas.

---

## Evoluções em Andamento e Próximas

### Fase 14 — Multiusuário e Autenticação Social
- **Checklist:**
  - [x] Criar autocadastro local com perfil `admin` e dados padrão do próprio usuário.
  - [x] Isolar contas, categorias, meios de pagamento, cartões, transações, transferências, relatórios e lixeira por proprietário.
  - [x] Reservar gestão de usuários, auditoria e configurações globais ao perfil `dev`, sem ampliar seu escopo financeiro próprio.
  - [x] Registrar separadamente o catálogo global de meios de pagamento (Fase 17) e o compartilhamento por conta (Fase 18).
  - [ ] Implementar login Google via OAuth 2.0.
  - [ ] Executar testes manuais completos dos fluxos de autenticação, isolamento e RBAC.
- **Estado:** Em andamento; OAuth Google e testes manuais permanecem pendentes.

### Fase 15 — Consulta administrativa de feedbacks recebidos
- **Checklist:**
  - [x] Restringir a rota de consulta ao perfil `dev` também no backend.
  - [x] Exibir feedbacks paginados com autor, tipo, tela, status, data e texto.
  - [x] Exibir miniaturas e ampliar imagens por rota autenticada, sem tornar o Blob público.
  - [x] Manter `novo` como status inicial e permitir ao `dev` alterar para `em_analise` ou `concluido` (exibido como Concluído), com CSRF e auditoria.
  - [x] Ordenar colunas como mensagem, imagens, status e ações; aplicar o botão de leitura do tema Symo.
  - [x] Exibir ações de status como dois botões compactos, lado a lado, com ícones e indicação acessível.
  - [x] Adicionar a tela à navegação administrativa desktop e mobile.
  - [x] Validar sintaxe PHP e diagnósticos estáticos dos arquivos alterados.
  - [x] Validar listagem e leitura das imagens em ambiente integrado com banco e Vercel Blob.
- **Estado:** Implementada; validação integrada depende do ambiente configurado.

### Fase 16 — Correção dos achados prioritários de isolamento e integridade
- **Checklist:**
  - [x] Limitar transações, consultas por ID, alterações, exclusões e agregados ao proprietário, inclusive para `dev`.
  - [x] Limitar transferências, resumos e estornos ao proprietário; impedir que `admin` ou `dev` operem transferências de terceiros.
  - [x] Validar saldo no `UPDATE` atômico da conta de origem para impedir saldo negativo por concorrência.
  - [x] Limitar listagem, restauração e expurgo da lixeira ao proprietário, inclusive para `dev`.
  - [x] Revalidar o status do usuário na sessão para encerrar o acesso após inativação.
  - [x] Confirmar com outro usuário que o resumo de transferências não exibe dados alheios.
  - [x] Confirmar que o Dashboard e a listagem de transações apresentam dados separados por usuário.
  - [x] Confirmar que a lixeira e o estorno não afetam dados de outro usuário.
  - [x] Confirmar que uma transferência acima do saldo é bloqueada com aviso.
  - [x] Confirmar que uma transferência dentro do saldo debita a origem e credita o destino corretamente.
  - [x] Atualizar FSD, AGENTS, README, PLANO e STATUS para documentar o escopo financeiro próprio de `admin` e `dev`.
  - [x] Executar lint PHP, diagnósticos estáticos e `git diff --check`.
  - [x] Testar acesso direto por ID com outro usuário: GET de transação alheia retorna 404; edição, mudança de status, exclusão e estorno não alteram registros nem saldos do proprietário.
  - [x] Testar concorrência do débito condicional no PostgreSQL: uma operação concorrente é aceita, a outra é recusada e o saldo não fica negativo.
  - [x] Testar concorrência end-to-end por `Transfer::createTransfer`: uma transferência foi criada, a concorrente recusada/rollback, origem debitada uma vez e destino creditado uma vez.
  - [x] Inativar um usuário com sessão ativa e confirmar que a próxima requisição bloqueia o acesso.
- **Estado:** Concluída; testes de isolamento e transferência concorrente end-to-end aprovados no PostgreSQL.

### Fase 17 — Catálogo global de meios de pagamento
- **Prioridade:** Em execução; esta fase não inclui compartilhamento por conta.
- **Regras alinhadas:**
  - O catálogo será global e começará com Dinheiro, Pix, Cartão de Débito, Cartão de Crédito, Boleto Bancário e Transferência Bancária.
  - Somente o perfil `dev` poderá criar opções, editá-las e alterar sua disponibilidade global entre ativa e inativa.
  - O `dev` inativa/reativa globalmente pelo campo de status. Inativar remove a opção dos novos lançamentos para todos; reativar pela mesma tela restaura a disponibilidade global, sem alterar preferências pessoais nem vínculos históricos.
  - Cada usuário, inclusive `dev`, poderá ativar/inativar opções apenas para si. A preferência individual altera as opções oferecidas em novos lançamentos e não afeta outros usuários.
  - Usuários comuns não poderão criar, editar dados globais nem alterar o status global das opções.
  - **Reconciliação:** a migration deverá derivar internamente a correspondência entre opções existentes e canônicas por atributos aprovados. Não registrar em documentação valores, IDs, volumes nem resultados do ambiente.
  - **Sequência obrigatória da reconciliação, dentro da implantação da Fase 17:**
    1. Validar que cada opção existente possui correspondência canônica única; interromper a migration e solicitar análise se houver ausência ou ambiguidade.
    2. Validar que o remapeamento preserva todas as referências antes de remover duplicatas.
    3. Remapear todos os lançamentos para as opções canônicas, incluindo registros logicamente excluídos.
    4. Confirmar que nenhum vínculo foi perdido e que não restam referências às opções duplicadas.
    5. Remover duplicatas somente após as verificações. A migration deve ser atômica e abortar integralmente diante de qualquer divergência.
    6. Preservar registros de auditoria e seus snapshots históricos, sem reescrita ou exclusão.
  - A reconciliação deve ocorrer somente como parte da migration que transforma o modelo em catálogo global.
  - A aplicação deve ser publicada somente após a migration concluir pelo workflow oficial.
- **Checklist de implementação:**
  - [x] Criar migration para PostgreSQL e MySQL legado com catálogo global, status global reversível e preferência individual.
  - [x] Remapear lançamentos para opções canônicas, incluindo excluídos logicamente, validar integridade e abortar diante de divergências.
  - [x] Atualizar cadastro de usuários e seed demo para não clonar meios de pagamento.
  - [x] Atualizar model, controller, rotas e interface com inativação/reativação global exclusiva do `dev` e preferência individual autenticada.
  - [x] Limitar novos lançamentos a opções disponíveis e preservar a opção original na edição histórica.
  - [x] Preservar auditoria histórica e validar CSRF, autorização e isolamento no backend.
  - [x] Executar a migration no banco alvo pelo workflow oficial e confirmar a conclusão sem divergências.
  - [x] Executar a migration do catálogo pelo workflow oficial.
  - [x] Publicar o ajuste de inativação/reativação global e validar o fluxo pela tela.
- **Estado:** Migration aplicada; correção de reativação preparada, publicação e validação integrada validados.
- **Critérios de aceite:** A inativação individual não altera as opções dos demais; inativar globalmente impede novos usos para todos e reativar restaura a disponibilidade sem mudar preferências pessoais; nenhum vínculo histórico é perdido; somente o `dev` altera o status global.

### Fase 18 — Compartilhamento por conta
- **Estado:** Roadmap; implementação posterior à Fase 17 e sujeita à especificação técnica detalhada.
- **Regras já alinhadas:**
  - Compartilhamento será concedido por vínculo de permissão à conta, nunca por papel global `operador`.
  - Participantes verão somente lançamentos da conta compartilhada criados após o aceite.
  - Transferências continuarão restritas a contas do mesmo proprietário.
  - Categorias, cartões, faturas e preferências permanecerão pessoais.
- **Checklist de detalhamento e implementação:**
  - [ ] Definir ciclo do convite, aceite/recusa, identidade do participante, expiração e revogação.
  - [ ] Definir níveis de visualização, lançamento, edição e exclusão, incluindo o alcance por autoria.
  - [ ] Criar vínculo de permissão por conta sem remover `contas.usuario_id`.
  - [ ] Aplicar o escopo autorizado a consultas, saldos, dashboard, relatórios, exportação, lixeira e auditoria.
  - [ ] Testar IDOR, revogação imediata, autoria, permissões e isolamento em todos os fluxos afetados.
- **Critérios de aceite:** Convites pendentes não concedem acesso; o participante vê apenas lançamentos posteriores ao aceite e da conta autorizada; revogação encerra o acesso; e transferências nunca cruzam proprietários.

### Fase 19 — Investimentos
- **Estado:** Implementada e validada no ambiente de testes; pendente de teste manual e aplicação em produção.
- **Objetivo:** Criar a aba Investimentos para registrar posições (aplicações e reservas/"caixinhas") sem exigir conta bancária do tipo `investimento`.
- **Regras já alinhadas:**
  - Posição: nome livre, conta vinculada obrigatória (ativa e não do tipo legado `investimento`), custodiante opcional em texto livre, classe opcional e finalidade opcional (apenas etiqueta). Saldo inicial zero ou maior, com data editável, sem movimentar contas.
  - Uma conta pode ter várias posições; cada posição tem uma conta, que pode ser trocada apenas para operações futuras, preservando a conta de cada movimento. Conta inativa bloqueia aportes e resgates.
  - Movimentações com data editável: aporte (debita conta, credita posição), resgate (inverso), ambos atômicos e com validação de saldo; rendimento (aumenta a posição, sem afetar conta nem receitas); atualização de valor (ajuste pela diferença, fora do resumo de rendimentos).
  - Edição e exclusão de movimentos com recálculo atômico e auditoria. Aportes e resgates aparecem só no histórico da posição.
  - Resgate total mantém a posição ativa; a desativação é manual e só permitida com valor zerado; a posição desativada mantém histórico e pode ser reativada.
  - Telas: lista com filtros (conta, custodiante, classe, finalidade, status), tela da posição, resumo mensal de rendimentos e card de total investido no Dashboard, separado do Saldo Atual. O card "Investimentos" da aba Contas passa a exibir o total investido.
  - Contas legadas do tipo `investimento` permanecem intactas, sem migração automática; o tipo deixa de ser oferecido em novos cadastros.
  - Dados privados por usuário, sem herdar o compartilhamento da Fase 18.
  - Fora da primeira versão: carência, vencimento, rentabilidade automática, metas e integração externa.
- **Checklist:**
  - [x] Confirmar critérios de aceite e atualizar o FSD.
  - [x] Migration (PostgreSQL e MySQL), models, controller, rotas e views.
  - [x] Auditoria, CSRF, validação de propriedade e testes transacionais.
  - [x] Ajustar aba Contas e Dashboard.
- **Critérios de aceite:** Aporte e resgate são atômicos e respeitam saldos; rendimento e ajuste não alteram contas; o total investido soma apenas posições novas e fica fora do Saldo Atual; nenhum dado vaza entre usuários.
- **Localização na interface:** a aba Investimentos fica no mesmo grupo de movimentação de Lançamentos, Transferências e Cartão de Crédito.

### Fase 20 — Perfil do usuário e acesso pela navegação
- **Estado:** Concluída.
- **Objetivo:** Tornar o perfil acessível pela navegação principal e permitir que cada usuário atualize o próprio e-mail sem exigir verificação por e-mail.
- **Regras já alinhadas:**
  - O perfil permanece na rota existente `/perfil`, separado de `/configuracoes`.
  - A navegação principal terá o item **Perfil** dentro da seção **Administração**, imediatamente abaixo de **Configurações**, disponível a todos os usuários autenticados; não haverá seção de navegação própria para Perfil.
  - Cada usuário poderá alterar somente os dados do próprio perfil.
  - O e-mail deve passar pela validação de formato existente, respeitar o limite de 150 caracteres e ser único após remoção de espaços externos e normalização para minúsculas.
  - Não haverá confirmação nem verificação de posse do e-mail.
  - A tela `/configuracoes` mantém preferências pessoais de exibição e os controles globais exclusivos do `dev`; moeda continua sendo apenas formatação de exibição.
  - Fuso horário não será editável no painel. `APP_TIMEZONE` permanece uma configuração técnica do runtime.
  - O painel do `dev` poderá disparar e exibir status e logs do GitHub Actions para migrations, sem expor segredos ou credenciais.
- **Checklist de implementação:**
  - [x] Incluir o item **Perfil** dentro de **Administração**, abaixo de **Configurações**, na navegação desktop e mobile; incluir também o acesso no menu do usuário.
  - [x] Tornar o campo de e-mail editável no perfil e persistir a alteração somente no registro do usuário autenticado.
  - [x] Validar formato, limite de 150 caracteres e unicidade no backend, normalizando espaços externos e letras para minúsculas; preservar papel e status da conta.
  - [x] Atualizar os dados da sessão após a alteração sem simular um novo login.
  - [x] Preservar CSRF e auditoria; exigir senha atual, confirmação e mínimo de 8 caracteres para troca de senha; manter validações do avatar.
  - [x] Testar em ambiente integrado e autenticado e-mail inválido, duplicado, válido ainda não verificado, tentativa de alterar outro usuário e sessão após atualização.
- **Critérios de aceite:** Todos os usuários autenticados encontram e acessam `/perfil`; alterações afetam somente o próprio usuário; e-mails inválidos ou duplicados são recusados, e um e-mail sintaticamente válido pode ser salvo sem confirmação; `/configuracoes` não oferece edição de perfil nem fuso horário.

### Fase 21 — Correção do fluxo de despesas e ciclos de faturas de cartão
- **Estado:** Concluída; implementada, validada com testes transacionais/concorrentes no Neon e publicada em produção.
- **Objetivo:** Remover a seleção duplicada de destino e meio de pagamento, garantir que compras no cartão sejam associadas ao ciclo de fatura correto e manter coerentes as parcelas, o limite e a quitação da fatura.
- **Regras já alinhadas:**
  - O meio de pagamento define a modalidade: meios marcados como crédito exigem a seleção de um cartão; os demais exigem uma conta bancária e permitem informar o status pago ou pendente.
  - No modal de despesa, substituir o rótulo **Data de Vencimento** por **Data da Compra**. A data informada representa quando a compra ocorreu.
  - Em despesas fora do cartão, `data_transacao` e `data_vencimento` permanecem iguais. Em compras no cartão, `data_transacao` guarda a data da compra e `data_vencimento` acompanha o vencimento da fatura vinculada.
  - O fechamento é exclusivo: compras anteriores ao dia de fechamento pertencem ao ciclo que fecha naquele mês; compras feitas no próprio dia ou depois pertencem ao ciclo seguinte.
  - O vencimento decorre da sequência do ciclo: se o dia de vencimento for posterior ao fechamento, ocorre no mesmo mês; se for igual ou anterior, ocorre no mês seguinte.
  - O vencimento calculado que cair em fim de semana ou feriado bancário nacional será ajustado para o próximo dia útil. O calendário é implementado localmente em helper versionado, sem depender de consulta externa em tempo de execução.
  - As faturas mudam automaticamente de aberta para fechada conforme o ciclo, atualizadas sob demanda nas consultas e operações; pagamentos continuam sendo integrais, em conta bancária ativa, e devem ser recusados se não houver saldo suficiente.
  - O limite excedido deve gerar alerta, mas não bloquear o registro. Cartões inativos não aceitam novas compras, mas seu histórico e suas faturas permanecem acessíveis.
  - Compras parceladas geram uma parcela por ciclo mensal consecutivo. Cada parcela conserva sua própria `data_transacao`, no mesmo dia da parcela inicial; quando o mês não tiver esse dia, usa seu último dia (por exemplo, dia 31 ajustado para 28 ou 29 em fevereiro, depois dia 31 em março e dia 30 em abril).
  - Deve ser possível corrigir manualmente a fatura vinculada a uma compra quando o ciclo calculado não corresponder ao lançamento efetivo do emissor.
- **Checklist de implementação:**
  - [x] Atualizar o FSD com as regras aprovadas para data da compra, ciclos de fatura, parcelas, vencimento em dias não úteis e pagamento integral.
  - [x] Remover **Forma de Saída** e derivar os campos de conta/cartão e status a partir do meio de pagamento; validar compatibilidade também no backend.
  - [x] Alterar o rótulo do campo para **Data da Compra** e preservar a distinção entre data da compra e vencimento da fatura.
  - [x] Associar compras avulsas e parceladas ao ciclo calculado pela data da compra, aplicando a fronteira exclusiva do fechamento.
  - [x] Gerar datas de parcelas mês a mês sem transbordo de calendário em meses curtos.
  - [x] Automatizar o fechamento de faturas e permitir corrigir a fatura de uma compra, preservando os totais das faturas afetadas e a auditoria.
  - [x] Ajustar vencimentos que caiam em fins de semana ou feriados bancários nacionais para o próximo dia útil, com calendário local e versionado.
  - [x] Alertar sobre limite excedido sem bloquear compras; impedir novas compras em cartão inativo sem bloquear o acesso ao histórico.
  - [x] Validar no backend saldo suficiente e atomicidade ao pagar faturas integralmente; preservar CSRF, autorização, isolamento por usuário e auditoria.
  - [x] Criar teste focado para fronteira do fechamento, sequência mensal e vencimentos em fins de semana/feriados.
  - [x] Validar manualmente, em ambiente local conectado ao Neon e sem publicar em produção, a associação de compras parceladas aos ciclos de fatura.
  - [x] Gerar compras parceladas sem sufixo redundante e ocultar a duplicação herdada em lançamentos, relatórios e faturas; padronizar cores de botões e ícone de calendário nas telas de lançamentos, cartões, transferências e investimentos.
  - [x] Corrigir link de visualização da fatura no widget de compromissos pendentes do dashboard para a fatura correta (`/faturas/{fatura_id}`).
  - [x] Corrigir a seleção da fatura atual nos cards de resumo de cartão de crédito para refletir o ciclo corrente de compras baseado no dia de fechamento, sem transbordar para parcelas futuras distantes.
  - [x] Executar validações integradas com PostgreSQL/Neon e MySQL mantido, incluindo operações transacionais e concorrência.
- **Critérios de aceite:** O meio de pagamento selecionado determina uma única modalidade de saída e combinações inválidas são rejeitadas no backend; compras são exibidas na fatura correta conforme data e ciclo; parcelas mantêm a progressão mensal e os valores exatos; faturas fecham e são pagas conforme as regras alinhadas; nenhuma operação parcial ou inconsistente altera saldos, vínculos, totais ou dados de outro usuário.
- **Arquivos/Pastas Envolvidos:** `app/Views/transactions/`, `app/Controllers/TransactionController.php`, `app/Models/Transaction.php`, `app/Models/CreditCard.php`, `app/Models/CreditCardInvoice.php`, `app/Controllers/CreditCardController.php`, `app/Views/credit-cards/`, testes relacionados, `database/migrations/` se necessária, `docs/FSD.md`, `docs/STATUS.md` e `AGENTS.md` se as regras para agentes mudarem.
- **Dependências:** Fases 6, 7 e 8 concluídas; requer manter as regras de isolamento e atomicidade estabelecidas na Fase 16.

### Fase 22 — Modal de lançamento unificado no Dashboard e padronização visual de receitas
- **Estado:** Concluída; implementada e validada em desenvolvimento.
- **Objetivo:** Permitir o lançamento de receitas e despesas diretamente a partir do Dashboard por meio de modal interativo com abas no topo, eliminando a navegação intermediária para a tela de lançamentos e padronizando os botões de receita na paleta do design system.
- **Checklist de implementação:**
  - [x] Criar estilo `.btn-symo-revenue` baseado no roxo secundário (`#600de6`, hover `#5300c4`) e estilos de abas `.nav-pills-symo` em `public/css/custom.css`.
  - [x] Disponibilizar categorias ativas de receita e despesa e meios de pagamento no `DashboardController` para alimentar os formulários do modal.
  - [x] Converter o botão "+ Novo Lançamento" no Dashboard em trigger para o modal `#modalNovoLancamento`.
  - [x] Implementar abas no topo do modal para alternância instantânea entre Despesa e Receita com seus respectivos formulários e regras.
  - [x] Garantir comportamento dinâmico de despesa (alternância entre conta bancária e cartão de crédito, alerta de limite de crédito e toggle de parcelamento de 2x a 72x).
  - [x] Configurar `redirect_to` para manter a navegação no próprio Dashboard com o período de mês/ano selecionado preservado após o salvamento.
  - [x] Atualizar o botão de "Nova Receita" na tela de lançamentos para utilizar o padrão visual `.btn-symo-revenue`.
  - [x] Executar validação estática de sintaxe (`php -l`) e verificação de integridade (`git diff --check`).
- **Critérios de aceite:** O botão "+ Novo Lançamento" do Dashboard abre o modal com abas de Despesa e Receita; salvar um registro pelo modal atualiza a base e retorna ao Dashboard com os KPIs e gráficos recalculados; botões de receita seguem o tom roxo secundário sem o uso de verde Bootstrap.
- **Arquivos/Pastas Envolvidos:** `public/css/custom.css`, `app/Controllers/DashboardController.php`, `app/Views/dashboard/index.php`, `app/Views/transactions/index.php`, `docs/PLANO.md`, `docs/STATUS.md`.
- **Dependências:** Fases 5, 8 e 10 concluídas.

### Fase 23 — Prioridade de fatura fechada nos cards de cartões e parcelas futuras na abertura de fatura
- **Estado:** Concluída; implementada e validada em desenvolvimento.
- **Objetivo:** Garantir que o card de cartão de crédito priorize faturas com status `fechada` pendentes de pagamento antes de exibir o ciclo aberto, e alinhar a geração de parcelas futuras de cartão de crédito para serem lançadas no dia de abertura da sua respectiva fatura (dia do fechamento anterior).
- **Regras já alinhadas:**
  - No card de resumo de cartões (`CreditCard::getCurrentOpenInvoice`), verificar primeiro a existência de fatura `fechada` não quitada; se houver, exibi-la imediatamente com a badge Fechada. Somente na ausência de fatura fechada pendente, exibir a fatura do ciclo aberto atual.
  - A 1ª parcela de compras no cartão preserva a data real da transação para determinação do ciclo de início.
  - As parcelas 2 a $N$ são vinculadas aos ciclos mensais consecutivos seguintes e registradas contabilmente no dia de abertura da respectiva fatura (dia do fechamento anterior do cartão).
  - O fechamento e a abertura de fatura permanecem fixos no dia nominal (mesmo em fins de semana e feriados); apenas a data de vencimento é ajustada para dia útil.
- **Checklist de implementação:**
  - [x] Ajustar `CreditCard::getCurrentOpenInvoice` para consultar prioritariamente faturas fechadas pendentes (`status = 'fechada'`) antes da fatura do ciclo aberto.
  - [x] Adicionar métodos `CreditCardCalendar::invoiceOpeningDate` e `CreditCardCalendar::nextInvoiceReference` para cálculo de abertura de ciclo e progressão de referências de fatura.
  - [x] Atualizar `Transaction::createInstallmentTransactions` para registrar parcelas 2 a $N$ no cartão na data de abertura da fatura correspondente.
  - [x] Atualizar a sincronização de parcelas subsequentes em `Transaction::update` para manter a coerência de datas na edição.
  - [x] Atualizar o `docs/FSD.md` com as regras aprovadas.
  - [x] Validar cenários com testes unitários de cálculo de datas e ausência de regressões com `php -l` e `git diff --check`.
- **Critérios de aceite:** Cards de cartões exibem faturas fechadas não pagas com prioridade máxima; compras parceladas no cartão registram parcelas 2 em diante na data de abertura da fatura; compras parceladas fora do cartão mantêm o dia da compra.
- **Arquivos/Pastas Envolvidos:** `app/Models/CreditCard.php`, `app/Helpers/CreditCardCalendar.php`, `app/Models/Transaction.php`, `docs/FSD.md`, `docs/PLANO.md`, `docs/STATUS.md`.
- **Dependências:** Fases 7, 8, 21 e 22 concluídas.
