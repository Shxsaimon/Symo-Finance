# Status do Projeto — Symo

Este documento é o arquivo vivo de acompanhamento do progresso de implementação do **Symo**.

---

## 1. Informações Gerais

- **Data da Última Atualização:** 03/09/2026
- **Fase Atual:** Fase 10 — Concluída com Sucesso
- **Próximo Passo Recomendado:** Fase 11 — Relatórios Analíticos e Exportação Estruturada em CSV

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
| **Fase 11** | Relatórios Analíticos e Exportação Estruturada em CSV | Pendente |
| **Fase 12** | Módulos Administrativos (Usuários, Auditoria, Lixeira e Configurações) | Pendente |
| **Fase 13** | Revisão de Segurança, Validações Integradas e Homologação Final | Pendente |

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
- [x] Bateria de 40 testes automatizados executada e 100% aprovada.

### Fase 9 — Módulo de Transferências entre Contas
- [x] Implementação de `app/Models/Transfer.php` com suporte a transferências internas, estorno de saldos, validação de saldo suficiente e transação atômica.
- [x] Implementação de `app/Controllers/TransferController.php` com controle RBAC e validações de contas distintas.
- [x] Criação da view `app/Views/transfers/index.php` com cards de resumo, filtros, histórico e modal de transferência.
- [x] Bateria de 30 testes automatizados executada e 100% aprovada.

### Fase 10 — Dashboard Financeiro e Indicadores Gráficos (Chart.js)
- [x] Implementação de `DashboardController.php` com agregação de KPIs em tempo real, evolução financeira e categorias.
- [x] Integração de gráficos interativos com Chart.js local (Evolução Mensal e Rosca de Despesas por Categoria).
- [x] Criação de widgets de compromissos pendentes nos próximos 7 dias e resumo visual de cartões/limites.
- [x] Criação da view `app/Views/dashboard/index.php` completa e responsiva.
- [x] Bateria de 34 testes automatizados executada e 100% aprovada.

### Fase 11 — Relatórios Analíticos e Exportação Estruturada em CSV
- [ ] Implementação de consultas consolidadas e exportação em CSV com UTF-8 BOM.
- [ ] Rotina de fechamento de fatura e pagamento com transação atômica.
- [ ] Criação das views de cartões e faturas.

### Fase 8 — Módulo de Transações Financeiras (Receitas, Despesas e Parcelamento)
- [ ] Implementação de `TransactionController.php` e model `Transaction.php`.
- [ ] Rotina de parcelamento com agrupamento e cálculo de dízima residual.
- [ ] Atualização automática de saldos de contas e faturas.
- [ ] Criação das views de lançamentos com filtros avançados e visualização de parcelas.

### Fase 9 — Módulo de Transferências entre Contas
- [ ] Implementação de `TransferController.php` e model `Transfer.php`.
- [ ] Execução atômica no banco de dados com `beginTransaction`, `commit` e `rollBack`.
- [ ] Criação de views e histórico de transferências.

### Fase 10 — Dashboard Financeiro e Indicadores Gráficos
- [ ] Implementação de `DashboardController.php` com agregação de KPIs.
- [ ] Renderização dos gráficos de evolução e categorias com Chart.js local.
- [ ] Widgets de contas a vencer e faturas abertas.

### Fase 11 — Relatórios Analíticos e Exportação Estruturada em CSV
- [ ] Implementação de `ReportController.php`.
- [ ] Geração de exportação CSV nativa com UTF-8 BOM e delimitador `;`.
- [ ] Criação das views de relatórios filtrados.

### Fase 12 — Módulos Administrativos (Usuários, Auditoria, Lixeira e Configurações)
- [ ] Implementação de `UserController.php` (gestão de membros e inativação de sessão).
- [ ] Implementação de `AuditController.php` com visualizador de JSON Diff.
- [ ] Implementação de `TrashController.php` com restauração e recálculo de saldos.
- [ ] Implementação de `SettingsController.php` e rotina de upload seguro de avatar e logotipo.

### Fase 13 — Revisão de Segurança, Validações Integradas e Homologação Final
- [ ] Testes de penetração básicos e validação de todas as proteções (SQLi, XSS, CSRF, RBAC).
- [ ] Validação dos critérios de aceitação da seção 26 do `docs/FSD.md`.
