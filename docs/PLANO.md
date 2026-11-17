# Plano de Construção Incremental — Symo

Este documento detalha o planejamento de implementação fase a fase dos grandes marcos estruturais e épicos do sistema **Symo (Gestão Financeira Pessoal e Compartilhada)**, com base estrita no `docs/FSD.md` e `docs/DESIGN.md`.

> **Nota de governança:** Este documento e o `docs/STATUS.md` acompanham exclusivamente os grandes marcos estruturais e épicos de roadmap do Symo Finance. Para o histórico cronológico de melhorias contínuas, polimentos visuais, novos campos pontuais e correções de regras, consulte o [CHANGELOG.md](../CHANGELOG.md).

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
- **Fase 21:** Revisão Geral de UX/UI, Design Tokens e Padronização de Componentes Frontend
- **Fase 22:** Gestão do Sistema, Histórico de Atualizações e Pipeline Seguro de Migrations

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
- **Estado:** Concluída.

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
- **Estado:** Concluída.
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
- **Estado:** Concluída.
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

---

### Fase 21 — Revisão Geral de UX/UI, Design Tokens e Padronização de Componentes Frontend
- **Estado:** Planejada (Roadmap).
- **Objetivo:** Reformulação completa da experiência visual, de feedback e ergonômica do Symo, substituindo interações nativas do navegador e layouts densos de estilo contábil/ERP por uma interface moderna, consistente, acolhedora e fluida tanto no desktop quanto no mobile.
- **Regras já alinhadas:**
  - **Temas e Cores:**
    - Implementar seletor formal de tema: **Claro**, **Escuro** e **Automático (do sistema)**, acessível tanto no menu do usuário (ao lado da foto no cabeçalho) quanto nas preferências em `/configuracoes`.
    - Padronizar cores e hierarquia de botões de ação: perigo destacado para excluir/apagar/expurgar; secundário/neutro para editar; link/discreto para visualização/detalhes.
    - Atualizar a aplicação para a nova identidade visual e logotipo desenvolvidos no Figma, substituindo elementos genéricos.
    - Eliminar personalizações legadas corporativas em Configurações (ex.: "Nome da Empresa"), consolidando a identidade estritamente voltada a finanças pessoais/familiares.
  - **Layout e Navegação:**
    - Sidebar desktop com suporte a recolher/colapsar (exibindo apenas ícones) para ganho de área útil de tela.
    - Remover o sufixo redundante `" — Symo"` dos cabeçalhos internos de cada tela.
    - Navegação mobile mantida via menu lateral offcanvas otimizado para toque, sem inclusão de bottom bar.
  - **Componentes Globais de Feedback e Interação:**
    - Substituir integralmente caixas de diálogo nativas (`window.confirm`) por modais padronizados com o design system do Symo e botões de perigo para ações destrutivas.
    - Substituir alertas estáticos de topo que empurram o conteúdo por um sistema de notificações flutuantes (*Toasts*) com auto-fechamento e ícones por severidade.
    - Adicionar estado de carregamento (*loading*) com spinner e trava anti-duplo clique em botões de formulário durante o processamento.
    - Revisar e enriquecer os estados vazios (*empty states*) de todas as telas com ilustrações/ícones temáticos e botões de ação direta.
  - **Formulários e Entradas:**
    - Validação visual nos campos com destaque suave e texto explicativo abaixo de campos pendentes ou incorretos ao submeter.
    - Máscaras e formatação em tempo real para campos monetários (`R$ 0,00`) e datas (`DD/MM/AAAA`).
  - **Tabelas e Listagens (Fim do estilo ERP/Contábil):**
    - Redesenhar visualmente as tabelas no desktop com tipografia mais leve, badges de status modernos e espaçamento equilibrado.
    - No mobile, eliminar o scroll horizontal desconfortável das tabelas clássicas, transformando as listagens em **cards compactos e empilhados** (estilo feed/extrato bancário moderno) otimizados para a largura útil do smartphone.
  - **Dashboard e Gráficos:**
    - Unificar o seletor de Mês/Ano do Dashboard ao modelo da aba Investimentos: navegação por setas `< Mês/Ano >` com clique central para abrir modal/picker de mês e ano específicos.
    - Redesenhar o gráfico de evolução financeira dos últimos 6 meses para mobile, exibindo **3 meses em tamanho legível** com navegação/arraste para os outros 3 meses.
    - Personalizar tooltips e curvas do Chart.js com as cores da marca e contraste adaptado ao tema claro/escuro.
  - **Metodologia de Execução:**
    - A revisão será executada de forma minuciosa **tela a tela**, validando e adaptando cada módulo do sistema (Dashboard, Lançamentos, Contas, Categorias, Cartões/Faturas, Transferências, Investimentos, Relatórios, Lixeira, Configurações/Perfil).
- **Checklist de implementação:**
  - [ ] Implementar suporte a tema Claro, Escuro e Automático com seletor no menu do usuário e em `/configuracoes`.
  - [ ] Padronizar cores de botões e ações de tabela (excluir, editar, visualizar) em todas as views.
  - [ ] Integrar novo logotipo e identidade visual do Figma e remover configurações corporativas legadas.
  - [ ] Implementar recolhimento/colapso da sidebar no desktop e limpar títulos internos das telas.
  - [ ] Criar utilitário global de modais de confirmação customizados do Symo e substituir todos os `confirm()`.
  - [ ] Implementar contêiner de Toasts flutuantes com integração ao `FlashHelper` e auto-dismiss.
  - [ ] Implementar proteção anti-duplo clique e spinners de loading nos botões de submissão de formulários.
  - [ ] Revisar e implementar empty states ilustrados com CTAs diretos em todas as listagens.
  - [ ] Implementar validação visual com mensagens sob os campos e máscaras monetárias/datas fluidas.
  - [ ] Redesenhar tabelas desktop com estilo moderno e implementar visualização em cards empilhados no mobile.
  - [ ] Padronizar o seletor de Mês/Ano do Dashboard com setas e picker central.
  - [ ] Adaptar o gráfico de evolução financeira de 6 meses para visualização em 3 meses paginados no mobile.
  - [ ] Executar revisão e refinamento tela a tela em todo o sistema.
- **Critérios de aceite:** 100% das telas harmonizadas; nenhum `confirm()` nativo em uso; alternância de tema fluida; mobile sem rolagem horizontal em tabelas; gráficos legíveis em telas pequenas; formulários com validação clara e proteção contra duplo clique.

---

### Fase 22 — Gestão do Sistema, Histórico de Atualizações e Pipeline Seguro de Migrations
- **Estado:** Planejada (Roadmap).
- **Objetivo:** Estabelecer um ciclo de atualização seguro e controlado internamente pelo perfil `dev`, integrando autorização de commits da branch `main`, aplicação atômica de migrations no banco antes do deploy na Vercel, histórico detalhado de releases e contingência em tempo de execução para prevenir indisponibilidade por descompasso de schema.
- **Regras já alinhadas:**
  - **Fluxo de Deploy e Git:**
    - Branch única de desenvolvimento: `main`, sem dependência de branches intermediárias.
    - O deploy automático de produção acionado por push na `main` será desativado nas configurações da Vercel.
    - O deploy de produção na Vercel será disparado exclusivamente por Deploy Hook chamado pelo GitHub Actions após confirmação de sucesso na execução das migrations.
  - **Nova Tela "Sistema" (`/sistema`):**
    - Tela dedicada e exclusiva do perfil `dev`, protegida por `requireDev()` e adicionada ao menu de Administração.
    - A gestão técnica de migrations e GitHub Actions é completamente transferida de `/configuracoes` para `/sistema`, deixando `/configuracoes` restrita a preferências do usuário.
    - **Painel Unificado de Atualizações e Migrations:**
      - Exibição da versão atual do sistema e SHA do commit ativo.
      - Comparativo com a branch `main` via API do GitHub: listagem dos commits pendentes contendo mensagem, autor, data/hora e a versão correspondente a cada commit (extraída do `CHANGELOG.md` no momento do commit).
      - Indicador de migrations pendentes no código em relação ao banco Neon.
      - Botão principal unificado: **"Autorizar e Atualizar Sistema"**.
      - Ao autorizar a atualização, o Symo dispara um workflow dedicado no GitHub Actions que:
        1. Executa `php migrate.php` contra o Neon de produção. Em caso de falha, o workflow aborta imediatamente, mantendo o ambiente de produção na versão estável anterior sem publicar o código novo;
        2. Se as migrations concluírem com 100% de sucesso, aciona o Deploy Hook da Vercel para publicar a versão;
        3. Registra os metadados do lote de atualização no histórico do sistema.
    - **Histórico Consolidado de Atualizações:**
      - Substitui a listagem isolada de migrations por um histórico detalhado de atualizações do sistema: data/hora, versão alcançada, usuário que autorizou, lista de commits integrados, migrations executadas no lote e logs de execução.
    - **Aba de Diagnóstico e Ambiente:**
      - Monitoramento do status operacional e latência dos serviços integrados:
        - *PostgreSQL (Neon):* teste de conectividade ativa (`SELECT 1`), medição de latência em milissegundos e status SSL;
        - *Vercel Blob:* validação da presença e formato das credenciais e integridade do proxy autenticado;
        - *GitHub Actions API:* validação do token (`GITHUB_ACTIONS_TOKEN`) e conectividade com a API de repositórios;
        - *Runtime PHP:* versão do PHP, fuso horário ativo, limites de memória/execução e presença de extensões essenciais (`pdo_pgsql`, `openssl`, `mbstring`, `fileinfo`).
  - **Mecanismo de Proteção em Tempo de Execução (Modo Manutenção / Gatekeeper no PHP):**
    - Verificação no bootstrap (`index.php`): se o código em execução contiver migrations superiores às aplicadas no banco:
      - Usuários comuns: redirecionamento para página amigável HTTP 503 ("O Symo está sendo atualizado. Voltamos em alguns instantes."), prevenindo erros 500 ou quebras de tela;
      - Usuário `dev`: acesso mantido ou tela de contingência para execução emergencial direta das migrations pendentes.
- **Checklist de implementação:**
  - [ ] Desativar o auto-deploy de produção na `main` na Vercel e configurar o Deploy Hook como segredo do repositório.
  - [ ] Criar workflow dedicado no GitHub Actions (`system-update.yml`) orquestrando: migrations no Neon ➔ validação de sucesso ➔ chamada do Deploy Hook da Vercel.
  - [ ] Criar controller, rota `/sistema`, view dedicada e navegação administrativa exclusiva para o perfil `dev`.
  - [ ] Migrar controles e visualizador de logs de migrations de `/configuracoes` para `/sistema`.
  - [ ] Implementar integração com a API do GitHub para buscar commits pendentes na `main` e parsear a versão associada no `CHANGELOG.md`.
  - [ ] Criar tabela/modelo para registrar o histórico consolidado de atualizações (versão, commits, migrations, data e autor).
  - [ ] Desenvolver a aba de Diagnóstico e Ambiente com testes de conectividade ativa do Neon, Blob, GitHub API e PHP.
  - [ ] Implementar o guardião de contingência no `index.php` com tela amigável 503 para usuários comuns quando houver descompasso de migrations.
  - [ ] Testar o ciclo completo de ponta a ponta: commit na `main` ➔ detecção na tela `/sistema` ➔ autorização ➔ migration atômica ➔ deploy na Vercel ➔ registro no histórico.
- **Critérios de aceite:** Pushes na `main` não atualizam a produção automaticamente; atualizações ocorrem somente por autorização do `dev` no Symo; o código novo nunca entra no ar antes da migration ter sido aplicada com sucesso; falhas em migrations abortam o deploy sem quebrar o sistema; usuários nunca visualizam erros de schema/SQL; a tela `/sistema` centraliza atualizações, histórico e diagnóstico do ambiente.
