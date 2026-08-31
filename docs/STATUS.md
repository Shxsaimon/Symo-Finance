# Status do Projeto — Symo

Este documento é o arquivo vivo de acompanhamento do progresso de implementação do **Symo**.

---

## 1. Informações Gerais

- **Data da Última Atualização:** 31/08/2026
- **Fase Atual:** Fase 4 — Concluída com Sucesso
- **Próximo Passo Recomendado:** Fase 5 — Layout Mestre, Design System (Symo) e Componentes Visuais

---

## 2. Visão Geral do Progresso

| Fase | Descrição | Status |
| :--- | :--- | :--- |
| **Fase 1** | Infraestrutura, Estrutura Base e Segurança de Acesso aos Arquivos | Concluída |
| **Fase 2** | Banco de Dados, Migrations e Carga Inicial (Seeds) | Concluída |
| **Fase 3** | Núcleo da Aplicação (Core MVC, Helpers, Roteamento e Logs) | Concluída |
| **Fase 4** | Autenticação, Sessão e Controle de Acesso Base (RBAC) | Concluída |
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
- [x] Criação de migrations estruturais de 001 a 011 em `database/migrations/` em português:
  - `001_criar_tabela_usuarios.php` (tabela de usuários com índices e perfis)
  - `002_criar_tabela_contas.php` (contas financeiras e saldos)
  - `003_criar_tabela_categorias.php` (categorias de receitas/despesas)
  - `004_criar_tabela_meios_pagamento.php` (meios de pagamento)
  - `005_criar_tabela_transacoes.php` (transações e índices de performance)
  - `006_criar_tabela_transferencias.php` (transferências entre contas)
  - `007_criar_tabelas_cartoes_e_faturas.php` (cartões e faturas)
  - `008_criar_tabela_auditoria_logs.php` (trilha de auditoria JSON)
  - `009_criar_tabelas_seguranca_e_erros_logs.php` (logs de segurança e erros)
  - `010_criar_tabela_configuracoes.php` (configurações do sistema)
  - `011_criar_tabela_migracoes.php` (controle de histórico de migrações)
- [x] Implementação de `migrate.php` CLI com suporte a `--seed`, `--status`, `--fresh` e lotes (`lote`).
- [x] Implementação do seed de dados essenciais em `database/seeds/initial_data.php` (Admin, Operador, Categorias padrão, Meios de pagamento e Configurações globais).
- [x] Testes de execução: criação de tabelas, integridade de FKs, anti-duplicação e seeds validados.

### Fase 3 — Núcleo da Aplicação (Core MVC, Helpers, Roteamento e Logs)
- [x] Implementação de classes base `Controller.php` e `Model.php`.
- [x] Implementação do roteador HTTP `Router.php` com suporte a GET/POST, parâmetros de URL e 404.
- [x] Implementação de `LoggerHelper.php` com gravação nas tabelas `erros_logs`/`seguranca_logs` e fallback automático para `storage/logs/error.log` e `storage/logs/security.log`.
- [x] Implementação de `CsrfHelper.php`, `FlashHelper.php` e `FormatHelper.php`.
- [x] Criação das views de erro amigáveis (`403.php`, `404.php`, `500.php`) e layout base `main.php`.
- [x] Atualização do `index.php` com autoloader PSR-like, inicialização de sessão segura, captura global de exceções e endpoint de diagnóstico `/api/health`.
- [x] Bateria de 21 testes automatizados executada e 100% aprovada.

### Fase 4 — Autenticação, Sessão e Controle de Acesso Base (RBAC)
- [x] Implementação de `AuthHelper.php` com login, logout, `requireLogin`, `requireRole`, expiração de inatividade (60 min), regeneração de ID de sessão e mitigação de força bruta.
- [x] Implementação de `User.php` com busca segura por e-mail, verificação de BCRYPT e auditoria.
- [x] Implementação de `AuthController.php` (`showLogin`, `authenticate`, `logout`, `showForgotPassword`).
- [x] Criação do layout `app/Views/layouts/auth.php` e views `app/Views/auth/login.php` e `app/Views/auth/forgot-password.php` com suporte ao Design System Symo.
- [x] Implementação da proteção contra força bruta (5 tentativas consecutivas em 15 minutos registradas no `seguranca_logs`).
- [x] Registro das rotas de autenticação, rota protegida `/dashboard` e rota administrativa de demonstração RBAC `/admin/teste-rbac`.
- [x] Bateria de 26 testes automatizados e testes de integração HTTP executados e aprovados.

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
