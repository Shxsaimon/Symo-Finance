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
- **Fase 17:** Compartilhamento por conta e catálogo global de meios de pagamento (roadmap)

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
  - [x] Implementar cálculo de Saldo Real Consolidado, Receitas do Mês, Despesas do Mês e Balanço Líquido.
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
- **Validações executadas:** Lint PHP dos 76 arquivos, `migrate.php --status` com as 11 migrations aplicadas, testes HTTP de redirecionamento/403/404, proteção de diretórios internos e análise estática de SQL/CDNs.
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
  - [x] Registrar no plano as regras futuras de compartilhamento por conta e catálogo global de meios de pagamento.
  - [ ] Implementar login Google via OAuth 2.0.
  - [ ] Executar testes manuais completos dos fluxos de autenticação, isolamento e RBAC.
- **Estado:** Em andamento; OAuth Google e testes manuais permanecem pendentes.

### Fase 15 — Consulta administrativa de feedbacks recebidos
- **Checklist:**
  - [x] Restringir a rota de consulta ao perfil `dev` também no backend.
  - [x] Exibir feedbacks paginados com autor, tipo, tela, status, data e texto.
  - [x] Exibir miniaturas e ampliar imagens por rota autenticada, sem tornar o Blob público.
  - [x] Adicionar a tela à navegação administrativa desktop e mobile.
  - [x] Validar sintaxe PHP e diagnósticos estáticos dos arquivos alterados.
  - [ ] Validar listagem e leitura das imagens em ambiente integrado com banco e Vercel Blob.
- **Estado:** Implementada; validação integrada depende do ambiente configurado.

### Fase 16 — Correção dos achados prioritários de isolamento e integridade
- **Checklist:**
  - [x] Limitar transações, consultas por ID, alterações, exclusões e agregados ao proprietário, inclusive para `dev`.
  - [x] Limitar transferências, resumos e estornos ao proprietário; impedir que `admin` ou `dev` operem transferências de terceiros.
  - [x] Validar saldo no `UPDATE` atômico da conta de origem para impedir saldo negativo por concorrência.
  - [x] Limitar listagem, restauração e expurgo da lixeira ao proprietário, inclusive para `dev`.
  - [x] Revalidar o status do usuário na sessão para encerrar o acesso após inativação.
  - [x] Atualizar FSD, AGENTS, README, PLANO e STATUS para documentar o escopo financeiro próprio de `admin` e `dev`.
  - [x] Executar lint PHP, diagnósticos estáticos e `git diff --check`.
  - [ ] Validar isolamento contra PostgreSQL com usuários distintos e testar transferências concorrentes.
  - [ ] Testar em ambiente integrado a recusa de sessão de usuário inativado.
- **Estado:** Correções implementadas; validações integradas dependem do ambiente de banco.

### Fase 17 — Compartilhamento por conta e catálogo global de meios de pagamento
- **Estado:** Roadmap; não implementar na demanda atual.
- **Checklist de alinhamento prévio:**
  - [ ] Detalhar o ciclo do convite: envio, aceite/recusa, revogação e identidade do participante.
  - [ ] Confirmar qual perfil poderá ativar/desativar meios de pagamento, pois a alteração afeta o catálogo global.
  - [ ] Definir a correspondência entre os meios de pagamento atuais e os IDs canônicos antes de migrar os vínculos existentes.
  - [ ] Definir estratégia de implantação e reversão da migration de consolidação, preservando o histórico dos lançamentos.
- **Checklist de implementação:**
  - [ ] Criar vínculo de permissão por conta; não criar papel global `operador` para conceder acesso financeiro.
  - [ ] Permitir ao proprietário conceder: somente visualização; lançamento com edição/exclusão dos registros da conta; ou lançamento com edição/exclusão apenas dos registros criados pelo participante.
  - [ ] Restringir a visibilidade do participante aos lançamentos criados após o aceite e pertencentes à conta compartilhada; não mostrar histórico anterior.
  - [ ] Restringir transferências a contas do mesmo proprietário e não oferecer no seletor contas de outro proprietário.
  - [ ] Manter categorias, cartões, faturas e preferências pessoais; aplicar o escopo de contas compartilhadas somente onde autorizado em dashboard, relatórios e lixeira.
  - [ ] Consolidar os meios de pagamento em catálogo global sem criação por usuários; oferecer somente ativação/desativação pela manutenção autorizada.
  - [ ] Migrar transações existentes para os IDs canônicos, validar todos os vínculos e só então remover registros duplicados.
  - [ ] Validar permissões e isolamento para leitura, lançamento, edição, exclusão, exportação, dashboard, relatórios, transferências e lixeira.
- **Critérios de aceite:** Nenhum convite pendente concede acesso; o participante vê somente dados posteriores ao aceite e da conta autorizada; revogação encerra acesso; transferências nunca cruzam proprietários; e lançamentos existentes preservam o meio de pagamento correto após a consolidação.

