# Status do Projeto — Symo

Este documento é o arquivo vivo de acompanhamento do progresso de implementação do **Symo**.

---

## 1. Informações Gerais

- **Data da Última Atualização:** 27/08/2026
- **Fase Atual:** Fase 2 — Concluída com Sucesso
- **Próximo Passo Recomendado:** Fase 3 — Núcleo da Aplicação (Core MVC, Helpers, Roteamento e Logs)

---

## 2. Visão Geral do Progresso

| Fase | Descrição | Status |
| :--- | :--- | :--- |
| **Fase 1** | Infraestrutura, Estrutura Base e Segurança de Acesso aos Arquivos | Concluída |
| **Fase 2** | Banco de Dados, Migrations e Carga Inicial (Seeds) | Concluída |
| **Fase 3** | Núcleo da Aplicação (Core MVC, Helpers, Roteamento e Logs) | Pendente |
| **Fase 4** | Autenticação, Sessão e Controle de Acesso Base (RBAC) | Pendente |
| **Fase 5** | Layout Mestre, Design System (Symo) e Componentes Visuais | Pendente |
| **Fase 6** | Cadastros Básicos (Contas Bancárias, Categorias e Meios de Pagamento) | Pendente |
| **Fase 7** | Módulo de Cartões de Crédito e Faturas | Pendente |
| **Fase 8** | Módulo de Transações Financeiras (Receitas, Despesas e Parcelamento) | Pendente |
| **Fase 9** | Módulo de Transferências entre Contas | Pendente |
| **Fase 10** | Dashboard Financeiro e Indicadores Gráficos (Chart.js) | Pendente |
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
- [x] Criação de migrations estruturais de 001 a 011 em `database/migrations/`:
  - `001_create_users_table.php` (tabela de usuários com índices e roles)
  - `002_create_accounts_table.php` (contas financeiras e saldos)
  - `003_create_categories_table.php` (categorias de receitas/despesas)
  - `004_create_payment_methods_table.php` (meios de pagamento)
  - `005_create_transactions_table.php` (transações e índices de performance)
  - `006_create_transfers_table.php` (transferências entre contas)
  - `007_create_credit_cards_and_invoices_table.php` (cartões e faturas)
  - `008_create_audit_logs_table.php` (trilha de auditoria JSON)
  - `009_create_security_logs_table.php` (logs de segurança e error_logs)
  - `010_create_settings_table.php` (configurações do sistema)
  - `011_create_migrations_table.php` (controle de histórico de migrações)
- [x] Implementação de `migrate.php` CLI com suporte a `--seed`, `--status`, `--fresh` e lotes (`batch`).
- [x] Implementação do seed de dados essenciais em `database/seeds/initial_data.php` (Admin, Operador, Categorias padrão, Meios de pagamento e Configurações globais).
- [x] Testes de execução: criação de tabelas, integridade de FKs, anti-duplicação e seeds validados.

### Fase 3 — Núcleo da Aplicação (Core MVC, Helpers, Roteamento e Logs)
- [ ] Implementação de classes base de Controller e Model.
- [ ] Implementação do roteador HTTP e manipulador de rotas.
- [ ] Implementação de `LoggerHelper.php` com suporte a fallback em `storage/logs/error.log`.
- [ ] Implementação de `CsrfHelper.php`, `FlashHelper.php` e `FormatHelper.php`.
- [ ] Criação das views de erro amigáveis (403, 404, 500).

### Fase 4 — Autenticação, Sessão e Controle de Acesso Base (RBAC)
- [ ] Implementação de `AuthHelper.php` e modelo `User.php`.
- [ ] Implementação de `AuthController.php`.
- [ ] Criação de views de login e recuperação de senha.
- [ ] Implementação do bloqueio temporário por brute force.

### Fase 5 — Layout Mestre, Design System (Symo) e Componentes Visuais
- [ ] Configuração de assets locais (Bootstrap 5, Icons, Chart.js).
- [ ] Criação de layouts mestres (`main.php`, `navbar.php`, `sidebar.php`, `auth.php`).
- [ ] Testes de responsividade e alinhamento visual com `docs/DESIGN.md`.

### Fase 6 — Cadastros Básicos (Contas Bancárias, Categorias e Meios de Pagamento)
- [ ] Implementação de `AccountController.php` e model `Account.php`.
- [ ] Implementação de `CategoryController.php` e model `Category.php`.
- [ ] Implementação de `PaymentMethodController.php` e model `PaymentMethod.php`.
- [ ] Criação das views e modais de cadastros básicos com regras de RBAC.

### Fase 7 — Módulo de Cartões de Crédito e Faturas
- [ ] Implementação de `CreditCardController.php` e models associados.
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
