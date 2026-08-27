# DOCUMENTO DE ESPECIFICAÇÃO FUNCIONAL (FSD)

---

## 1. Visão Geral

* **Nome do Sistema:** Symo — Gestão Financeira Pessoal e Compartilhada.
* **Objetivo Principal:** Fornecer uma plataforma web intuitiva, colaborativa e de alta usabilidade para controlo financeiro centralizado, permitindo que pessoas físicas, famílias e pequenos negócios realizem a gestão completa de receitas, despesas, transferências bancárias e compras parceladas no cartão de crédito em tempo real, com clareza absoluta sobre saldos realizados e projeções futuras.
* **Resumo do Funcionamento:** O sistema opera como um painel financeiro compartilhado e multiusuário baseado no conceito de conta única colaborativa (orçamento familiar ou empresarial comum). Os utilizadores autenticam-se com perfis distintos (Administrador e Operador), registam transações à vista ou parceladas, acompanham saldos consolidados por conta financeira, gerem o ciclo de faturas de cartão de crédito e visualizam dashboards com indicadores gráficos e alertas visuais de vencimento. Todas as ações críticas contam com rastreabilidade por auditoria, proteção por exclusão lógica (*soft delete*) com lixeira recuperável e relatórios tabulares exportáveis para CSV.
* **Público Usuário:**
* Pessoas físicas que buscam organizar o orçamento pessoal e planeamento financeiro;
* Famílias que necessitam de um ambiente conjunto para acompanhar despesas domésticas e receitas partilhadas;
* Microempreendedores e pequenas empresas que demandam um controlo de fluxo de caixa operacional simples, sem complexidades burocráticas ou termos contábeis avançados.


* **Contexto de Uso:** Aplicação web responsiva, otimizada para navegação em computadores, notebooks, tablets e smartphones. Os acessos ocorrem em rotinas diárias e semanais de lançamento e conferência de saldos, bem como no fecho mensal de faturas e análise de relatórios.
* **Observações Relevantes para Implementação:**
* A aplicação adota o modelo de ambiente único compartilhado (*single-tenant* compartilhado), onde todos os utilizadores autenticados operam sobre a mesma base de contas e movimentações, sendo o acesso a funções administrativas restringido pelo perfil de acesso.
* A interface segue estritamente o tema *Symo* definido no `docs/DESIGN.md`, utilizando a tipografia *Inter* com numerais tabulares (`tabular-nums`) para perfeito alinhamento financeiro e paleta cromática corporativa moderna (Electric Violet `#6F02FF`, Deep Lavender `#793CFE`, Bright Teal `#3BD7D7` e tons neutros balanceados).
* O código-fonte é estruturado segundo o padrão arquitetural MVC em PHP 8 nativo, com isolamento total de responsabilidades, independência de caminho de instalação e proteção estrita de arquivos internos.



---

## 2. Documentos do Projeto para Implementação

A IA codificadora e a equipa de desenvolvimento devem utilizar exclusivamente os seguintes documentos para a implementação integral do sistema:

1. **`docs/FSD.md`:** Este Documento de Especificação Funcional e Técnica, que consolida de forma autossuficiente todas as regras de negócio, arquitetura, fluxos, entidades, modelo relacional, permissões, validações e critérios de aceitação.
2. **`docs/DESIGN.md`:** Guia de Estilo e Design System (*Symo*), contendo as diretrizes visuais, paleta de cores, tipografia, elevações, espaçamentos, grids responsivos e padrões de componentes de interface.

O presente FSD consolida e substitui todas as discussões e documentos prévios de especificação funcional e técnica. Não é necessária a consulta a documentos externos anteriores para a codificação da aplicação.

---

## 3. Stack Definida

* **Linguagem de Back-end:** PHP 8.1+ (utilizando tipagem estrita `declare(strict_types=1);`, recursos modernos da linguagem e execução nativa sem dependência de frameworks pesados).
* **Banco de Dados Relacional:** MySQL 8.0+ ou MariaDB 10.4+ com motor de armazenamento obrigatório `InnoDB`, conjunto de caracteres `utf8mb4` e collation `utf8mb4_unicode_ci`.
* **Tecnologias de Front-end:**
* HTML5 semântico e estruturado;
* CSS3 moderno com variáveis CSS (Design Tokens);
* JavaScript Vanilla (ES6+) para interações assíncronas, manipulação de DOM e modais;
* Framework CSS: Bootstrap 5.3.x (armazenado e servido localmente no projeto);
* Biblioteca de Ícones: Bootstrap Icons (armazenada e servida localmente);
* Biblioteca de Gráficos: Chart.js 4.x (armazenada e servida localmente).


* **Dependências e Bibliotecas Externas:**
* Nenhuma dependência externa via CDN em tempo de execução. Todos os assets (CSS, JS, WebFonts e Ícones) devem residir localmente no diretório de assets públicos do projeto, garantindo funcionamento autônomo e seguro offline/intranet.


* **Padrão Arquitetural:** MVC (*Model-View-Controller*) desacoplado, com roteamento centralizado (*Front Controller*), injeção de dependência simplificada e camada de acesso a dados via PDO (*PHP Data Objects*).
* **Restrições Técnicas:**
* Proibido o uso de arquivo `.env` para credenciais sensíveis (utilizar arquivo PHP dedicado em `config/config.php`);
* Proibida a execução de comandos SQL diretos sem *Prepared Statements*;
* Proibido o acesso direto via URL a qualquer arquivo ou pasta que não seja o ponto de entrada da aplicação ou assets públicos permitidos;
* Proibida a instanciação de conexões MySQL desprotegidas ou uso de extensões legadas (`mysql_*` ou `mysqli_*` procedural).



---

## 4. Ambientes do Projeto

### 4.1. Desenvolvimento Local

* **Ambiente Padronizado:** XAMPP (Apache + PHP 8.x + MySQL/MariaDB) ou ambiente local similar (WampServer, Laragon, Docker LAMP básico).
* **Estrutura de Caminho Local:** O projeto será hospedado tipicamente em uma subpasta dentro de `htdocs/`, por exemplo: `http://localhost/Sistema_Financeiro/` ou `http://localhost/symo/`.
* **Configuração de Banco Local:** Host `localhost`, porta `3306`, usuário `root`, senha vazia (ou definida localmente), base de dados `symo_db`.

### 4.2. Testes e Homologação

* **Ambiente:** Servidor web Apache com suporte a mod_rewrite e PHP 8.x, simulando fielmente o ambiente de produção.
* **Objetivo:** Validação de migrations, testes de regressão de permissões (RBAC), auditoria de logs e validação cruzada de relatórios.

### 4.3. Produção

* **Ambiente Padronizado:** Hospedagem Compartilhada cPanel / Hostnet ou Servidor VPS Linux (Debian/Ubuntu) rodando Apache 2.4+ e PHP 8.x.
* **Estrutura de Caminho em Produção:** O projeto poderá ser instalado na raiz pública (ex: `www/` ou `public_html/`) ou dentro de um subdiretório específico (ex: `www/financas/` ou `public_html/app/`).
* **Configuração de Banco em Produção:** Credenciais fornecidas pelo painel da hospedagem, com usuário dedicado e privilégios estritos.

### 4.4. Diretrizes de Deploy e Portabilidade

* A aplicação não deve conter URLs absolutas fixas em código (*hardcoded*). Todas as rotas internas, links de assets e redirecionamentos devem utilizar uma constante base (`BASE_URL` ou `APP_URL`) calculada dinamicamente pelo sistema a partir do ambiente de execução ou definida em `config/config.php`.
* A estrutura deve ser 100% agnóstica quanto ao nome da subpasta de instalação, permitindo coexistir múltiplos sistemas no mesmo servidor.

---

## 5. Arquitetura do Sistema

### 5.1. Conceito do Diretório Raiz

Toda a arquitetura do sistema toma como referência estrutural o:
`[Diretório do Projeto - Repositório]`

Este diretório representa a pasta raiz versionada da aplicação, a qual pode ser alocada dentro de `htdocs/nome-do-projeto/` (XAMPP), `www/nome-do-projeto/` (Hostnet) ou `public_html/nome-do-projeto/`.

### 5.2. Estrutura de Diretórios Proposta

```text
[Diretório do Projeto - Repositório]/
│
├── config/
│   ├── .htaccess                # Bloqueio total de acesso HTTP direto (Require all denied)
│   ├── config.php               # Credenciais de BD, parâmetros técnicos e fallback
│   └── database.php             # Fábrica de conexão Singleton via PDO
│
├── app/
│   ├── .htaccess                # Bloqueio total de acesso HTTP direto
│   ├── Controllers/             # Controladores da aplicação (MVC)
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── TransactionController.php
│   │   ├── AccountController.php
│   │   ├── CategoryController.php
│   │   ├── PaymentMethodController.php
│   │   ├── CreditCardController.php
│   │   ├── TransferController.php
│   │   ├── ReportController.php
│   │   ├── UserController.php
│   │   ├── TrashController.php
│   │   ├── AuditController.php
│   │   ├── SettingsController.php
│   │   └── MigrationController.php # Rotina interna protegida de execução
│   │
│   ├── Models/                  # Camada de Dados e Regras de Negócio (PDO)
│   │   ├── User.php
│   │   ├── Account.php
│   │   ├── Category.php
│   │   ├── PaymentMethod.php
│   │   ├── Transaction.php
│   │   ├── CreditCardInvoice.php
│   │   ├── Transfer.php
│   │   ├── AuditLog.php
│   │   ├── SecurityLog.php
│   │   ├── Setting.php
│   │   └── Migration.php
│   │
│   ├── Views/                   # Telas e Templates da Aplicação
│   │   ├── layouts/             # Layouts mestres (header, sidebar, footer, alerts)
│   │   │   ├── main.php
│   │   │   ├── auth.php
│   │   │   ├── navbar.php
│   │   │   └── sidebar.php
│   │   ├── auth/                # Telas de login e recuperação
│   │   ├── dashboard/           # Painel principal e cartões de indicadores
│   │   ├── transactions/        # Listagem, modais e formulários de lançamentos
│   │   ├── accounts/            # Gestão de contas bancárias e carteiras
│   │   ├── categories/          # Gestão de categorias de receitas/despesas
│   │   ├── payment-methods/     # Gestão de meios de pagamento
│   │   ├── credit-cards/        # Gestão de cartões e faturas
│   │   ├── transfers/           # Formulários e extratos de transferências
│   │   ├── reports/             # Telas de relatórios analíticos e exportação
│   │   ├── users/               # Gestão de utilizadores (Admin)
│   │   ├── trash/               # Lixeira do sistema e restauração (Admin)
│   │   ├── audit/               # Visualizador de auditoria e histórico (Admin)
│   │   ├── settings/            # Configurações globais do sistema (Admin)
│   │   └── errors/              # Telas de erro amigáveis (403, 404, 500)
│   │
│   └── Helpers/                 # Funções auxiliares (Auth, CSRF, Session, Formatters)
│       ├── AuthHelper.php
│       ├── CsrfHelper.php
│       ├── FlashHelper.php
│       ├── FormatHelper.php
│       └── LoggerHelper.php
│
├── database/
│   ├── .htaccess                # Bloqueio total de acesso HTTP direto
│   ├── migrations/              # Arquivos versionados de migração do banco
│   │   ├── 001_create_users_table.php
│   │   ├── 002_create_accounts_table.php
│   │   ├── 003_create_categories_table.php
│   │   ├── 004_create_payment_methods_table.php
│   │   ├── 005_create_transactions_table.php
│   │   ├── 006_create_transfers_table.php
│   │   ├── 007_create_credit_cards_and_invoices_table.php
│   │   ├── 008_create_audit_logs_table.php
│   │   ├── 009_create_security_logs_table.php
│   │   ├── 010_create_settings_table.php
│   │   └── 011_create_migrations_table.php
│   └── seeds/                   # Carga de dados iniciais essenciais
│       └── initial_data.php
│
├── storage/
│   ├── .htaccess                # Proteção e bloqueio de execução de scripts
│   ├── logs/                    # Arquivos de texto de logs de contingência
│   │   ├── error.log
│   │   └── security.log
│   └── uploads/                 # Arquivos enviados pelo utilizador (Avatares e Logos)
│       ├── avatars/
│       └── logos/
│
├── public/                      # Assets públicos (acessíveis diretamente pelo browser)
│   ├── css/
│   │   ├── bootstrap.min.css    # Bootstrap 5.3 local
│   │   ├── bootstrap-icons.css  # Ícones locais
│   │   └── custom.css           # Tokens do Design System (Symo)
│   ├── js/
│   │   ├── bootstrap.bundle.min.js
│   │   ├── chart.umd.min.js     # Chart.js local
│   │   └── app.js               # Validações, máscaras e scripts assíncronos
│   ├── fonts/                   # WebFonts locais (Inter e Bootstrap Icons)
│   └── img/                     # Favicons e imagens estáticas de interface
│
├── .htaccess                    # Roteador principal Apache (Reescrita para index.php)
├── index.php                    # Ponto de entrada único (Front Controller)
├── migrate.php                  # Script CLI para execução segura de migrations
└── README.md                    # Instruções de instalação e ambiente
```

### 5.3. Fluxo de Requisição no Padrão MVC

1. **Entrada (*Front Controller*):** Todas as requisições HTTP são capturadas pelo arquivo `index.php` na raiz do projeto através de regras de reescrita no `.htaccess`.
2. **Inicialização do Core:** O `index.php` inicializa a sessão segura, carrega as configurações de `config/config.php`, inicializa o manipulador de erros global e instancia o roteador.
3. **Roteamento e Autenticação:** O roteador identifica a URI solicitada, verifica a autenticação do utilizador (`AuthHelper`) e valida se o perfil possui autorização para a rota (`RBAC`).
4. **Acionamento do Controller:** O Controller correspondente é instanciado. O método da ação valida tokens CSRF em requisições `POST`, sanitiza os parâmetros de entrada e solicita os dados à camada Model.
5. **Camada Model e Dados:** Os Models executam as regras de negócio de persistência, efetuam cálculos financeiros e interagem com o MySQL exclusivamente via PDO com *Prepared Statements*.
6. **Auditoria e Logs:** Se a operação for uma mutação (`INSERT`, `UPDATE`, `DELETE`), o model aciona a gravação automática na tabela de auditoria (`audit_logs`) e atualiza o histórico.
7. **Renderização da View:** O Controller passa os dados tratados para a View correspondente, que monta a interface HTML com os componentes do *Symo* e a entrega ao navegador.

### 5.4. Proteção de Diretórios Internos

* Todas as pastas sensíveis (`config/`, `app/`, `database/`, `storage/logs/`) contêm arquivos `.htaccess` dedicados com a instrução `Require all denied` (Apache 2.4+) e `Deny from all` (Apache 2.2).
* Em nível de código, todos os arquivos PHP internos possuem uma guarda inicial que impede execução isolada:
```php
<?php
defined('APP_RUNNING') or die('Acesso direto proibido.');

```


* O diretório `storage/uploads/` possui regras no `.htaccess` que desabilitam completamente a execução de scripts (`php_flag engine off` e `RemoveHandler .php .phtml`), servindo arquivos estritamente como imagens estáticas.

---

## 6. Escopo Funcional da Primeira Versão

### 6.1. Módulo de Autenticação e Gestão de Sessão

* **Objetivo:** Garantir acesso seguro e controlado aos utilizadores da conta compartilhada.
* **Usuários Envolvidos:** Administradores e Operadores.
* **Ações Permitidas:** Login com e-mail e senha, logout seguro, alteração de senha própria no perfil e bloqueio automático após sucessivas tentativas inválidas.
* **Resultado Esperado:** Estabelecimento de sessão criptografada com isolamento de privilégios e encerramento após 60 minutos de inatividade.

### 6.2. Módulo de Dashboard Financeiro

* **Objetivo:** Apresentar uma visão panorâmica e imediata da saúde financeira do ambiente compartilhado.
* **Usuários Envolvidos:** Administradores e Operadores.
* **Ações Permitidas:** Filtragem por mês/ano corrente e seleção de contas financeiras; visualização rápida de cards de indicadores (Saldo Atual Consolidado, Total de Receitas do Mês, Total de Despesas do Mês, Balanço Mensal Líquido); gráficos interativos de evolução mensal e distribuição de gastos por categoria; widgets de contas pendentes e faturas de cartão próximas do vencimento.
* **Resultado Esperado:** Resposta instantânea sobre saldos realizados e compromissos futuros com atualização em tempo real.

### 6.3. Módulo de Transações Financeiras (Receitas e Despesas)

* **Objetivo:** Registar, consultar, editar e gerir todas as entradas e saídas financeiras.
* **Usuários Envolvidos:** Administradores (acesso total) e Operadores (inserção, edição e exclusão de lançamentos próprios).
* **Ações Permitidas:**
* Registo de Receita (à vista, com data de recebimento, conta bancária, categoria e status 'recebido' ou 'pendente');
* Registo de Despesa (à vista, com data de vencimento/pagamento, conta ou cartão de crédito, categoria, status 'pago' ou 'pendente');
* Registo de Compras Parceladas (geração automática de $N$ lançamentos vinculados com sufixo numérico `[1/N]`, calculando datas de vencimento mensais e valores com tratamento de dízima residual na primeira parcela);
* Filtros avançados por período, tipo, conta, categoria, meio de pagamento, status e usuário autor;
* Alteração e exclusão lógica de transações.


* **Resultado Esperado:** Manutenção fidedigna do fluxo de caixa e histórico financeiro compartilhado.

### 6.4. Módulo de Transferências entre Contas

* **Objetivo:** Registar movimentações internas de valores entre contas bancárias e carteiras do mesmo ambiente sem distorcer as receitas e despesas operacionais.
* **Usuários Envolvidos:** Administradores e Operadores.
* **Ações Permitidas:** Seleção de conta de origem, conta de destino, valor, data e observação.
* **Resultado Esperado:** Criação de transação pareada em banco de dados com dedução no saldo da conta de origem e crédito correspondente na conta de destino sob transação atômica (`DB Transaction`).

### 6.5. Módulo de Cartões de Crédito e Fechamento de Faturas

* **Objetivo:** Controlar os limites de crédito, despesas agrupadas por fatura e pagamento consolidado mensal.
* **Usuários Envolvidos:** Administradores e Operadores.
* **Ações Permitidas:** Cadastro de cartões com limite total, dia de fecho e dia de vencimento; lançamento de despesas no cartão; agrupamento de faturas (Aberta, Fechada, Paga); liquidação da fatura gerando a dedução em uma conta bancária de escolha.
* **Resultado Esperado:** Previsibilidade do montante da fatura do mês sem duplicar débitos em conta corrente antes do pagamento efetivo.

### 6.6. Módulo de Cadastros Básicos (Contas, Categorias e Meios de Pagamento)

* **Objetivo:** Estruturar a base cadastral de suporte aos lançamentos financeiros.
* **Usuários Envolvidos:**
* Contas Financeiras: Administradores e Operadores (visualização de saldos; criação e edição restritas ao Administrador);
* Categorias e Meios de Pagamento: Gestão exclusiva do Administrador; visualização por Operadores.


* **Ações Permitidas:** Criação, edição, inativação e reativação de itens cadastrais.
* **Resultado Esperado:** Padronização dos lançamentos financeiros sem risco de redundância ou criação desordenada de dados por operadores.

### 6.7. Módulo de Relatórios e Exportação CSV

* **Objetivo:** Disponibilizar extratos consolidados, relatórios analíticos de fluxo de caixa e exportação de dados para planilhas.
* **Usuários Envolvidos:** Administradores e Operadores.
* **Ações Permitidas:** Consulta a relatórios por período, categoria, conta e status; download instantâneo de arquivo estruturado em padrão CSV estruturado com separador `;` e codificação UTF-8 com BOM.
* **Resultado Esperado:** Extração fidedigna de relatórios com consistência total entre a tabela visualizada na tela e os dados exportados.

### 6.8. Módulo de Gestão de Usuários (Administrativo)

* **Objetivo:** Administrar os acessos dos membros que compartilham o painel financeiro.
* **Usuários Envolvidos:** Administrador.
* **Ações Permitidas:** Cadastro de novos utilizadores, atribuição de perfil (Administrador ou Operador), inativação de contas de acesso (com revogação imediata de sessão ativa) e redefinição administrativa de senhas.
* **Resultado Esperado:** Controlo rigoroso de credenciais e membros autorizados.

### 6.9. Módulo de Lixeira e Recuperação (*Soft Delete*)

* **Objetivo:** Proteger os dados contra exclusões acidentais e permitir restauração controlada.
* **Usuários Envolvidos:** Administrador (acesso exclusivo à tela de lixeira).
* **Ações Permitidas:** Consulta de itens excluídos logicamente (transações, categorias, contas), restauração individual com restauração de saldos, e expurgo/exclusão definitiva.
* **Resultado Esperado:** Segurança operacional e recuperação de dados sem necessidade de intervenção direta no banco de dados.

### 6.10. Módulo de Auditoria e Histórico

* **Objetivo:** Rastrear quem criou, alterou ou excluiu cada registo financeiro.
* **Usuários Envolvidos:** Administrador (visualização dos logs de auditoria e do histórico de modificações no modal de detalhes).
* **Ações Permitidas:** Consulta a trilha de auditoria contendo dados anteriores e novos em formato JSON, data/hora exata, endereço IP e utilizador autor.
* **Resultado Esperado:** Transparência completa no uso colaborativo da plataforma.

### 6.11. Módulo de Configurações Globais do Sistema

* **Objetivo:** Customizar parâmetros de funcionamento do sistema.
* **Usuários Envolvidos:** Administrador.
* **Ações Permitidas:** Definição do nome do sistema/empresa, moeda padrão (símbolo e decimais), itens por página nas tabelas e upload do logotipo da aplicação.
* **Resultado Esperado:** Personalização do ambiente visual e operacional.

---

## 7. Fora de Escopo

Os seguintes itens estão expressamente confirmados como **fora de escopo** desta primeira versão da aplicação:

1. **Anexo e Upload de Comprovantes em Lançamentos:** Não haverá armazenamento de PDFs, fotos de recibos ou notas fiscais anexadas a receitas e despesas.
2. **Metas Orçamentárias e Limites de Gastos por Categoria:** Não haverá definição de tetos de gastos com travas automáticas ou alertas de estouro de orçamento.
3. **Recorrência Contínua e Infinita:** Não haverá lançamentos com regras de repetição perpétua sem prazo determinado (o sistema suporta compras parceladas automáticas com número fechado de $N$ parcelas).
4. **Conciliação Bancária via OFX / Extratos:** Não haverá leitura, parsing ou importação automática de arquivos bancários nos formatos OFX, CSV bancário ou PDF.
5. **Integração Direta com APIs Bancárias (Open Finance):** Não haverá sincronização automática de saldos ou extratos bancários via webhooks ou APIs.
6. **Emissão de Boletos, Cobranças ou Gateways de Pagamento:** Não haverá emissão de cobranças, links de pagamento, integração com Stripe, Asaas, Mercado Pago ou geração de boletos.
7. **Conversão Multi-Moeda em Tempo Real:** O sistema operará em moeda única selecionada nas configurações globais (padrão BRL `R$`), sem conversão cambial dinâmica no mesmo painel.
8. **Arquitetura SaaS Multi-Tenant com Isolamento de Bases:** O sistema é concebido como ambiente compartilhado único (*single-tenant* multiusuário para uma família ou empresa).
9. **Exportação Nativa em PDF com Layout Gráfico:** Não haverá gerador interno de relatórios em formato PDF (apenas exportação em formato tabular CSV).
10. **Envio Ativo de Notificações por E-mail (Lembretes de Vencimento):** Não haverá rotinas agendadas (Cron Jobs) para disparo de e-mails diários de contas a vencer.

---

## 8. Perfis de Usuário e Permissões

### 8.1. Perfil Administrador (`admin`)

* **Descrição:** Gestor financeiro titular, proprietário da empresa ou responsável principal pelo orçamento familiar.
* **Permissões Globais:** Acesso irrestrito a todas as telas, funcionalidades e configurações da aplicação.
* **Ações Exclusivas:**
* Gestão completa de utilizadores (criar, inativar, alterar senhas e perfis);
* Gestão de cadastros básicos (criar, editar e inativar categorias, contas bancárias e meios de pagamento);
* Acesso à tela de Lixeira e execução de restaurações ou exclusões definitivas de registros;
* Visualização da Trilha de Auditoria completa e logs de segurança;
* Alteração das Configurações Globais do sistema e upload de logotipo;
* Edição e exclusão de transações criadas por qualquer utilizador;
* Execução controlada de migrations do banco de dados.



### 8.2. Perfil Operador (`operador`)

* **Descrição:** Membro da família, funcionário operacional ou colaborador autorizado a movimentar o painel compartilhado.
* **Permissões:**
* Lançamento de receitas, despesas, transferências e compras parceladas;
* Visualização do Dashboard com indicadores financeiros consolidados;
* Visualização e consulta de extratos de contas e faturas de cartão;
* Visualização das categorias e meios de pagamento disponíveis nos formulários;
* Edição e exclusão lógica (*soft delete*) **exclusivamente de transações financeiras criadas pelo próprio operador**;
* Emissão de relatórios e exportação de dados para CSV;
* Atualização do próprio perfil de acesso (nome, avatar e senha pessoal).


* **Restrições Estritas:**
* Bloqueio total de acesso ao módulo de Gestão de Usuários;
* Bloqueio total de acesso à Lixeira do sistema e impossibilidade de restaurar itens excluídos;
* Bloqueio total de acesso à tela de Auditoria, Logs de Erros e Logs de Segurança;
* Bloqueio de edição e exclusão de categorias, meios de pagamento e contas bancárias;
* Bloqueio de alteração ou exclusão de transações criadas por outros utilizadores;
* Bloqueio de acesso às Configurações Globais do sistema.



### 8.3. Matriz de Permissões (RBAC)

| Módulo / Ação | Administrador (`admin`) | Operador (`operador`) |
| --- | --- | --- |
| Visualizar Dashboard e Saldos | Sim | Sim |
| Criar Receitas / Despesas / Transferências | Sim | Sim |
| Editar / Excluir Transações Próprias | Sim | Sim |
| Editar / Excluir Transações de Outros Usuários | Sim | **Não** |
| Cadastrar / Editar Contas Bancárias | Sim | **Não** (Apenas consulta) |
| Cadastrar / Editar Categorias e Meios | Sim | **Não** (Apenas consulta) |
| Gerenciar Cartões e Faturas | Sim | Sim |
| Emitir Relatórios e Exportar CSV | Sim | Sim |
| Acessar Lixeira e Restaurar Registros | Sim | **Não** |
| Exclusão Definitiva de Registros (*Hard Delete*) | Sim | **Não** |
| Visualizar Histórico de Auditoria | Sim | **Não** |
| Visualizar Logs do Sistema | Sim | **Não** |
| Gerenciar Usuários do Sistema | Sim | **Não** |
| Alterar Configurações Globais e Logotipo | Sim | **Não** |
| Alterar Própria Senha e Avatar | Sim | Sim |

---

## 9. Recursos Estruturais do Sistema

### 9.1. Autenticação e Gestão de Sessão

* Autenticação com e-mail único e senha criptografada via algoritmo nativo BCRYPT (`password_hash` com *cost* 12).
* Destruição e invalidação imediata de sessão no logout.
* Tempo limite de inatividade de sessão de **60 minutos**, com expiração automática e redirecionamento para a tela de login.
* Proteção contra ataques de força bruta com bloqueio temporário de 15 minutos após 5 tentativas consecutivas de senha inválida por IP/E-mail.
* Revogação instantânea de acesso em caso de inativação de conta de usuário pelo Administrador.

### 9.2. Controle de Acesso Baseado em Papéis (RBAC)

* Validação estrita de autorização em duas camadas:
1. *Camada de Visualização (View):* Ocultação condicional de menus, botões e ações no front-end conforme o perfil do utilizador logado;
2. *Camada de Controle (Controller/Backend):* Verificação obrigatória em todas as rotas protegidas. Qualquer tentativa de requisição direta via GET/POST sem autorização resulta em erro `HTTP 403 Forbidden` e gravação imediata no log de segurança.



### 9.3. Auditoria e Rastreabilidade de Dados

* Todas as operações de criação, modificação de valores/datas/status e exclusão lógica de registros financeiros geram automaticamente uma entrada na tabela `audit_logs`.
* Gravação do estado anterior (*old_values*) e do estado posterior (*new_values*) no formato `JSON`, além do identificador do usuário autor, endereço IP, ação executada (`INSERT`, `UPDATE`, `SOFT_DELETE`, `RESTORE`, `PURGE`) e timestamp exato.

### 9.4. Exclusão Lógica (*Soft Delete*) e Lixeira

* Nenhuma movimentação financeira, conta ou categoria é apagada definitivamente do banco de dados na exclusão inicial.
* A aplicação utiliza as colunas `deleted_at` (timestamp da exclusão) e `deleted_by` (ID do usuário autor da exclusão).
* As consultas padrão do sistema incluem obrigatoriamente a cláusula `WHERE deleted_at IS NULL`.
* Uma interface administrativa de Lixeira permite ao Administrador auditar registros excluídos, restaurá-los (recalculando saldos) ou efetuar o expurgo definitivo (*Hard Delete*).

### 9.5. Sistema de Logs e Contingência em Arquivo

* Registro detalhado de exceções e erros de execução em banco de dados (`error_logs`).
* **Mecanismo de Contingência:** Em caso de indisponibilidade ou falha de conexão com o banco MySQL, a camada de log redireciona automaticamente a gravação para o arquivo físico local `storage/logs/error.log`.
* Tratamento de falhas de segurança (`security_logs`) para tentativas de invasão, acessos negados (403), falhas repetidas de login e adulteração de tokens CSRF.

### 9.6. Configurações Globais Dinâmicas

* Armazenamento de parâmetros gerais na tabela `settings` com chave-valor e tipagem associada.
* Mecanismo de fallback em código (`config/config.php`) garantindo o funcionamento do sistema caso a tabela esteja vazia ou inacessível.

### 9.7. Upload Seguro de Imagens

* Upload restrito a **Avatares de Usuários** e **Logotipo da Aplicação**.
* Limite máximo de arquivo: **2 MB**.
* Extensões aceitas: `.jpg`, `.jpeg`, `.png`, `.webp`.
* Validação rigorosa no servidor via extensão `finfo` (verificação real de MIME-Type / *Magic Bytes*), renomeação com hash randômico (ex: `avatar_a1b2c3d4e5.webp`) e remoção de scripts ou metadados maliciosos.

### 9.8. Exportação Estruturada em CSV

* Geração nativa de arquivos CSV estruturados para extratos financeiros e relatórios de fluxo de caixa.
* Utilização de codificação UTF-8 com inclusão de BOM (`\xEF\xBB\xBF`) para compatibilidade perfeita com Microsoft Excel, LibreOffice Calc e Google Sheets, utilizando ponto-e-vírgula (`;`) como delimitador padrão de colunas.

---

## 10. Entidades do Sistema

### 10.1. Usuário (`User`)

* **Finalidade:** Representa os membros com acesso ao sistema.
* **Informações:** Nome completo, e-mail, hash da senha, perfil (`admin` ou `operador`), status (`ativo` ou `inativo`), avatar e timestamps de criação/atualização.
* **Regras:** E-mail único; inativação imediata revoga acesso; não pode excluir a si próprio.

### 10.2. Conta Financeira (`Account`)

* **Finalidade:** Representa contas correntes, poupanças, contas de investimento ou carteiras de dinheiro físico.
* **Informações:** Nome da conta, instituição financeira, tipo de conta, saldo inicial, saldo atual consolidado, cor/identificador visual, status (`ativa` ou `inativa`), timestamps e soft delete.
* **Regras:** Saldo atual é atualizado a cada transação confirmada; contas com transações ativas não podem sofrer exclusão física direta.

### 10.3. Categoria (`Category`)

* **Finalidade:** Classificação orçamentária das transações.
* **Informações:** Nome da categoria, tipo (`receita` ou `despesa`), ícone visual, cor hexadecimal, status (`ativa` ou `inativa`), timestamps e soft delete.
* **Regras:** Categorias não podem ter seu tipo alterado após o primeiro lançamento; exclusão lógica preserva integridade histórica dos relatórios.

### 10.4. Meio de Pagamento (`PaymentMethod`)

* **Finalidade:** Modalidade utilizada para liquidar as transações (ex: Dinheiro, Pix, Débito, Crédito, Boleto, Transferência).
* **Informações:** Nome, tipo de liquidação (imediata ou a prazo/fatura), status (`ativo` ou `inativo`), timestamps e soft delete.
* **Regras:** Gerenciado exclusivamente pelo Administrador.

### 10.5. Cartão de Crédito (`CreditCard`)

* **Finalidade:** Representa um cartão de crédito com limite, fecho de fatura e vencimento.
* **Informações:** Nome do cartão, limite total de crédito, dia de fechamento da fatura, dia de vencimento da fatura, conta bancária padrão de débito vinculada, status, timestamps e soft delete.
* **Regras:** Calcula limite disponível com base nas faturas abertas e parcelas futuras.

### 10.6. Fatura de Cartão (`CreditCardInvoice`)

* **Finalidade:** Agrupamento mensal das despesas realizadas em determinado cartão de crédito.
* **Informações:** Cartão vinculado, mês/ano de competência, data de fechamento, data de vencimento, valor total acumulado, status (`aberta`, `fechada`, `paga`), data de pagamento e conta de liquidação.
* **Regras:** A liquidação da fatura gera automaticamente o débito do montante na conta corrente escolhida.

### 10.7. Transação Financeira (`Transaction`)

* **Finalidade:** Registos de movimentações financeiras de receitas e despesas.
* **Informações:** Tipo (`receita` ou `despesa`), descrição, valor monetário, data de competência/lançamento, data de vencimento, data de liquidação/pagamento, status (`pendente` ou `pago/recebido`), conta bancária vinculada, cartão de crédito vinculado (opcional), fatura vinculada (opcional), categoria, meio de pagamento, ID da compra parcelada agrupada (`installment_group_id`), número da parcela (`installment_number`), total de parcelas (`installment_total`), usuário autor do lançamento, observações, timestamps e soft delete.
* **Regras:** Transações pendentes não afetam o saldo real da conta; transações efetivadas debitam/creditam o saldo da conta imediatamente; compras parceladas geram registros individuais linkados por grupo.

### 10.8. Transferência (`Transfer`)

* **Finalidade:** Registos de transferências de valores entre contas bancárias internas.
* **Informações:** Conta de origem, conta de destino, valor transferido, data da transferência, observação, usuário autor, IDs das transações de débito e crédito geradas, timestamps e soft delete.
* **Regras:** Execução atômica no banco (débito na origem + crédito no destino); não gera impacto em categorias de receitas ou despesas.

### 10.9. Auditoria (`AuditLog`)

* **Finalidade:** Rastreabilidade de alterações cadastrais e financeiras.
* **Informações:** Tabela auditada, ID do registro afetado, ação executada, valores anteriores (JSON), valores novos (JSON), ID do usuário autor, IP e timestamp.

### 10.10. Log de Segurança (`SecurityLog`)

* **Finalidade:** Rastreabilidade de incidentes de segurança e tentativas de acesso.
* **Informações:** Evento (ex: login falho, acesso 403, bloqueio de IP, CSRF inválido), e-mail informado, endereço IP, cabeçalho User-Agent, dados contextuais (JSON) e timestamp.

### 10.11. Configuração Global (`Setting`)

* **Finalidade:** Parâmetros operacionais e visuais do sistema.
* **Informações:** Chave única, valor textual, tipo de dado (`string`, `integer`, `boolean`, `json`), descrição e timestamp de atualização.

---

## 11. Modelo de Dados Proposto

### 11.1. Especificação das Tabelas e Campos

#### Tabela `usuarios`

* `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `nome`: VARCHAR(120) NOT NULL
* `email`: VARCHAR(150) NOT NULL UNIQUE
* `senha`: VARCHAR(255) NOT NULL
* `perfil`: ENUM('admin', 'operador') NOT NULL DEFAULT 'operador'
* `status`: ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo'
* `avatar`: VARCHAR(255) NULL
* `criado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* `atualizado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `excluido_em`: DATETIME NULL
* `excluido_por`: INT UNSIGNED NULL
* *Índices:* `idx_usuarios_email (email)`, `idx_usuarios_status (status)`, `idx_usuarios_excluido_em (excluido_em)`

#### Tabela `contas`

* `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `nome`: VARCHAR(100) NOT NULL
* `instituicao`: VARCHAR(100) NULL
* `tipo`: ENUM('corrente', 'poupanca', 'investimento', 'dinheiro', 'outros') NOT NULL DEFAULT 'corrente'
* `saldo_inicial`: DECIMAL(15,2) NOT NULL DEFAULT 0.00
* `saldo_atual`: DECIMAL(15,2) NOT NULL DEFAULT 0.00
* `cor`: VARCHAR(7) NOT NULL DEFAULT '#6F02FF'
* `status`: ENUM('ativa', 'inativa') NOT NULL DEFAULT 'ativa'
* `criado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* `atualizado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `excluido_em`: DATETIME NULL
* `excluido_por`: INT UNSIGNED NULL
* *Índices:* `idx_contas_status (status)`, `idx_contas_excluido_em (excluido_em)`

#### Tabela `categorias`

* `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `nome`: VARCHAR(100) NOT NULL
* `tipo`: ENUM('receita', 'despesa') NOT NULL
* `icone`: VARCHAR(50) NOT NULL DEFAULT 'bi-tag'
* `cor`: VARCHAR(7) NOT NULL DEFAULT '#793CFE'
* `status`: ENUM('ativa', 'inativa') NOT NULL DEFAULT 'ativa'
* `criado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* `atualizado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `excluido_em`: DATETIME NULL
* `excluido_por`: INT UNSIGNED NULL
* *Índices:* `idx_categorias_tipo (tipo)`, `idx_categorias_status (status)`, `idx_categorias_excluido_em (excluido_em)`

#### Tabela `meios_pagamento`

* `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `nome`: VARCHAR(80) NOT NULL
* `eh_credito`: TINYINT(1) NOT NULL DEFAULT 0
* `status`: ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo'
* `criado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* `atualizado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `excluido_em`: DATETIME NULL
* `excluido_por`: INT UNSIGNED NULL
* *Índices:* `idx_meios_pagamento_status (status)`, `idx_meios_pagamento_excluido_em (excluido_em)`

#### Tabela `cartoes_credito`

* `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `nome`: VARCHAR(100) NOT NULL
* `limite_credito`: DECIMAL(15,2) NOT NULL DEFAULT 0.00
* `dia_fechamento`: TINYINT UNSIGNED NOT NULL
* `dia_vencimento`: TINYINT UNSIGNED NOT NULL
* `conta_padrao_id`: INT UNSIGNED NULL
* `cor`: VARCHAR(7) NOT NULL DEFAULT '#3BD7D7'
* `status`: ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo'
* `criado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* `atualizado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `excluido_em`: DATETIME NULL
* `excluido_por`: INT UNSIGNED NULL
* *Foreign Keys:* `fk_cartoes_conta_padrao (conta_padrao_id) REFERENCES contas(id) ON DELETE SET NULL`
* *Índices:* `idx_cartoes_status (status)`, `idx_cartoes_excluido_em (excluido_em)`

#### Tabela `faturas_cartao`

* `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `cartao_credito_id`: INT UNSIGNED NOT NULL
* `mes_referencia`: TINYINT UNSIGNED NOT NULL
* `ano_referencia`: SMALLINT UNSIGNED NOT NULL
* `data_fechamento`: DATE NOT NULL
* `data_vencimento`: DATE NOT NULL
* `valor_total`: DECIMAL(15,2) NOT NULL DEFAULT 0.00
* `status`: ENUM('aberta', 'fechada', 'paga') NOT NULL DEFAULT 'aberta'
* `pago_em`: DATETIME NULL
* `conta_pagamento_id`: INT UNSIGNED NULL
* `criado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* `atualizado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* *Foreign Keys:*
* `fk_faturas_cartao (cartao_credito_id) REFERENCES cartoes_credito(id) ON DELETE RESTRICT`
* `fk_faturas_conta_pagamento (conta_pagamento_id) REFERENCES contas(id) ON DELETE SET NULL`
* *Índices:* `idx_faturas_periodo (cartao_credito_id, ano_referencia, mes_referencia)`, `idx_faturas_status (status)`

#### Tabela `transacoes`

* `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `usuario_id`: INT UNSIGNED NOT NULL
* `tipo`: ENUM('receita', 'despesa') NOT NULL
* `descricao`: VARCHAR(255) NOT NULL
* `valor`: DECIMAL(15,2) NOT NULL
* `data_transacao`: DATE NOT NULL
* `data_vencimento`: DATE NOT NULL
* `data_pagamento`: DATE NULL
* `status`: ENUM('pendente', 'pago', 'recebido') NOT NULL DEFAULT 'pendente'
* `conta_id`: INT UNSIGNED NULL
* `categoria_id`: INT UNSIGNED NOT NULL
* `meio_pagamento_id`: INT UNSIGNED NOT NULL
* `cartao_credito_id`: INT UNSIGNED NULL
* `fatura_id`: INT UNSIGNED NULL
* `grupo_parcelamento_id`: VARCHAR(64) NULL
* `numero_parcela`: TINYINT UNSIGNED NULL DEFAULT 1
* `total_parcelas`: TINYINT UNSIGNED NULL DEFAULT 1
* `observacoes`: TEXT NULL
* `criado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* `atualizado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `excluido_em`: DATETIME NULL
* `excluido_por`: INT UNSIGNED NULL
* *Foreign Keys:*
* `fk_trans_usuario (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT`
* `fk_trans_conta (conta_id) REFERENCES contas(id) ON DELETE SET NULL`
* `fk_trans_categoria (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT`
* `fk_trans_meio_pagamento (meio_pagamento_id) REFERENCES meios_pagamento(id) ON DELETE RESTRICT`
* `fk_trans_cartao_credito (cartao_credito_id) REFERENCES cartoes_credito(id) ON DELETE SET NULL`
* `fk_trans_fatura (fatura_id) REFERENCES faturas_cartao(id) ON DELETE SET NULL`
* *Índices de Alto Desempenho:*
* `idx_trans_data_transacao (data_transacao)`
* `idx_trans_data_vencimento (data_vencimento)`
* `idx_trans_status (status)`
* `idx_trans_tipo (tipo)`
* `idx_trans_conta_id (conta_id)`
* `idx_trans_categoria_id (categoria_id)`
* `idx_trans_cartao_credito_id (cartao_credito_id)`
* `idx_trans_fatura_id (fatura_id)`
* `idx_trans_grupo_parcelamento (grupo_parcelamento_id)`
* `idx_trans_excluido_em (excluido_em)`
* `idx_trans_dashboard_composto (excluido_em, status, data_vencimento, tipo)`

#### Tabela `transferencias`

* `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `usuario_id`: INT UNSIGNED NOT NULL
* `conta_origem_id`: INT UNSIGNED NOT NULL
* `conta_destino_id`: INT UNSIGNED NOT NULL
* `valor`: DECIMAL(15,2) NOT NULL
* `data_transferencia`: DATE NOT NULL
* `observacoes`: TEXT NULL
* `transacao_origem_id`: INT UNSIGNED NULL
* `transacao_destino_id`: INT UNSIGNED NULL
* `criado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* `atualizado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `excluido_em`: DATETIME NULL
* `excluido_por`: INT UNSIGNED NULL
* *Foreign Keys:*
* `fk_transf_usuario (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT`
* `fk_transf_origem (conta_origem_id) REFERENCES contas(id) ON DELETE RESTRICT`
* `fk_transf_destino (conta_destino_id) REFERENCES contas(id) ON DELETE RESTRICT`
* *Índices:* `idx_transf_data_transferencia (data_transferencia)`, `idx_transf_excluido_em (excluido_em)`

#### Tabela `auditoria_logs`

* `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `usuario_id`: INT UNSIGNED NULL
* `tabela`: VARCHAR(64) NOT NULL
* `registro_id`: INT UNSIGNED NOT NULL
* `acao`: ENUM('INSERT', 'UPDATE', 'SOFT_DELETE', 'RESTORE', 'PURGE') NOT NULL
* `valores_antigos`: JSON NULL
* `valores_novos`: JSON NULL
* `endereco_ip`: VARCHAR(45) NOT NULL
* `criado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* *Foreign Keys:* `fk_auditoria_usuario (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL`
* *Índices:* `idx_auditoria_tabela_registro (tabela, registro_id)`, `idx_auditoria_criado_em (criado_em)`

#### Tabela `seguranca_logs`

* `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `evento`: VARCHAR(80) NOT NULL
* `email`: VARCHAR(150) NULL
* `endereco_ip`: VARCHAR(45) NOT NULL
* `agente_usuario`: VARCHAR(255) NULL
* `contexto`: JSON NULL
* `criado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* *Índices:* `idx_seg_evento (evento)`, `idx_seg_ip (endereco_ip)`, `idx_seg_criado_em (criado_em)`

#### Tabela `erros_logs`

* `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `mensagem`: TEXT NOT NULL
* `codigo`: VARCHAR(50) NULL
* `arquivo`: VARCHAR(255) NULL
* `linha`: INT UNSIGNED NULL
* `rastreamento`: TEXT NULL
* `url`: VARCHAR(255) NULL
* `metodo`: VARCHAR(10) NULL
* `endereco_ip`: VARCHAR(45) NOT NULL
* `usuario_id`: INT UNSIGNED NULL
* `criado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* *Índices:* `idx_err_criado_em (criado_em)`, `idx_err_usuario_id (usuario_id)`

#### Tabela `configuracoes`

* `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `chave`: VARCHAR(64) NOT NULL UNIQUE
* `valor`: TEXT NULL
* `tipo`: ENUM('string', 'integer', 'boolean', 'json') NOT NULL DEFAULT 'string'
* `descricao`: VARCHAR(255) NULL
* `atualizado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* *Índice:* `idx_configuracoes_chave (chave)`

#### Tabela `migracoes`

* `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `migracao`: VARCHAR(255) NOT NULL UNIQUE
* `lote`: INT UNSIGNED NOT NULL
* `executado_em`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP

---

### 11.2. Estratégia de Migrations do Projeto

1. **Objetivo:** Automatizar a criação e modificação de tabelas, índices e restrições de integridade sem demandar intervenção manual no phpMyAdmin ou no terminal do servidor.
2. **Localização Segura:** Todos os scripts de migration residem em `database/migrations/`, bloqueados para acesso web via `.htaccess`.
3. **Mecanismo de Controle de Execução:**
* A tabela `migrations` rastreia o histórico de cada arquivo executado com sucesso.
* Antes de executar qualquer migração, o sistema verifica se o nome do arquivo já consta na tabela `migrations`. Se já tiver sido executado, o arquivo é ignorado, evitando duplicações ou erros de integridade.


4. **Execução Controlada:**
* **Via Linha de Comando (CLI):** Executando `php migrate.php` a partir do diretório raiz da aplicação.
* **Via Rota Administrativa Protegida:** Apenas utilizadores autenticados com perfil `admin` podem disparar a verificação de migrations pelo painel de controle (ex: botão restrito em Configurações), sendo expressamente proibida qualquer rota pública desprotegida para esse fim.



---

## 12. Módulos e Telas

### 12.1. Tela de Login e Recuperação (`/login`, `/recuperar-senha`)

* **Objetivo:** Ponto de entrada do sistema para identificação de credenciais.
* **Usuários:** Todos (público não autenticado).
* **Campos/Informações:** Campos de E-mail, Senha, Opção "Lembrar de mim", Botão "Entrar" (Electric Violet), link "Esqueci minha senha" e mensagens de erro contextuais.
* **Estados:** Formulário inicial, estado de carregamento do botão com indicador visual (*spinner*), alerta de credenciais inválidas ou conta inativada, e aviso de bloqueio temporário por excesso de tentativas.
* **Aderência ao Design System:** Layout limpo com fundo `Surface` (`#FAF9F9`), card centralizado em elevação tonal `Surface-Container-Lowest` (`#FFFFFF`), borda sutil de 1px `Neutral 300` e cantos arredondados de 16px.

### 12.2. Dashboard Principal (`/dashboard`)

* **Objetivo:** Central de comando com métricas em tempo real, saldo consolidado e previsibilidade financeira.
* **Usuários:** Administrador e Operador.
* **Informações e Componentes:**
* Seletor de Período (Mês e Ano corrente) e filtro rápido de conta;
* 4 Cards de Indicadores Principais (KPIs com numerais tabulares):
1. Saldo Real Consolidado (somatório das contas ativas);
2. Total de Receitas Realizadas no Mês;
3. Total de Despesas Pagas no Mês;
4. Balanço Líquido Mensal (com badge semântico verde para positivo ou vermelho para negativo).


* Gráfico 1: Evolução Financeira Mensal (Chart.js - Barras/Linhas em Electric Violet e Deep Lavender);
* Gráfico 2: Despesas por Categoria (Chart.js - Rosca/Donut com paleta customizada);
* Tabela/Widget de Contas a Vencer nos Próximos 7 Dias (com destaque visual de urgência);
* Widget de Faturas de Cartão de Crédito (Limite utilizado vs. Disponível).


* **Ações:** Botões de ação rápida no cabeçalho (+ Nova Receita, + Nova Despesa, + Transferência).

### 12.3. Módulo de Transações (`/transacoes`)

* **Objetivo:** Listagem geral, busca filtrada, auditoria e manutenção de receitas e despesas.
* **Usuários:** Administrador (gestão total) e Operador (gestão dos lançamentos próprios).
* **Filtros e Buscas:** Período de datas (de/até), Tipo (Receita/Despesa), Categoria, Conta Financeira, Meio de Pagamento, Status (Pendente/Pago/Recebido) e Busca textual por descrição.
* **Tabela de Dados:** Alta densidade com linhas zebradas sutis (`Surface-Container-Low`), colunas para Data, Tipo (badge semântico), Descrição (com identificação de parcela `[1/12]`), Categoria, Conta/Cartão, Valor (numerais tabulares), Status e Botões de Ação (Visualizar Detalhes, Editar, Excluir).
* **Modais Interativos:**
* Modal de Lançamento de Receita;
* Modal de Lançamento de Despesa (com opção de compra parcelada e seleção de cartão);
* Modal de Detalhes da Transação (exibindo autor, histórico de edições e logs de auditoria para Administrador).



### 12.4. Módulo de Contas Bancárias (`/contas`)

* **Objetivo:** Gestão de instituições financeiras, caixas e saldos.
* **Usuários:** Administrador (criar, editar, inativar) e Operador (visualização de saldos).
* **Informações:** Grid de Cards de Contas com logotipo/ícone do banco, tipo, saldo atual e badge de status. Modal de criação e edição com seletor de cor personalizada.

### 12.5. Módulo de Cartões de Crédito e Faturas (`/cartoes`)

* **Objetivo:** Gestão de cartões, limites e fechamento/pagamento de faturas mensais.
* **Usuários:** Administrador e Operador.
* **Informações:** Card visual do cartão com limite total, limite disponível (barra de progresso), dia de fecho e dia de vencimento. Tabela de faturas históricas com status (Aberta, Fechada, Paga) e botão de ação para "Pagar Fatura" (dispara liquidação em conta corrente).

### 12.6. Módulo de Transferências (`/transferencias`)

* **Objetivo:** Registro e extrato de transferências internas entre contas.
* **Usuários:** Administrador e Operador.
* **Informações:** Formulário simples com seleção de Conta de Origem, Conta de Destino, Valor, Data e Observação. Tabela histórica das transferências com identificação das contas envolvidas e autor da movimentação.

### 12.7. Módulo de Categorias e Meios de Pagamento (`/categorias`, `/meios-pagamento`)

* **Objetivo:** Cadastro e parametrização das classificações financeiras.
* **Usuários:** Administrador (gestão total) e Operador (somente leitura).
* **Informações:** Listagens tabulares com badges de cores, ícones associados, tipo e status (ativo/inativo).

### 12.8. Módulo de Relatórios e Exportação (`/relatorios`)

* **Objetivo:** Emissão de extratos consolidados, análise de fluxo de caixa e exportação para planilhas.
* **Usuários:** Administrador e Operador.
* **Informações:** Painel de filtros obrigatórios de período (Mês/Ano ou Intervalo Personalizado), agrupamentos opcionais por categoria, conta ou centro de custo, tabela analítica consolidada e botão destacado de "Exportar para CSV".

### 12.9. Módulo de Usuários (`/usuarios`)

* **Objetivo:** Administração de contas de acesso ao sistema compartilhado.
* **Usuários:** Exclusivo Administrador.
* **Informações:** Listagem de usuários com avatar, nome, e-mail, perfil (badge roxo para Admin, badge azul para Operador), status e ações (Editar Perfil, Inativar/Reativar, Redefinir Senha).

### 12.10. Módulo de Lixeira do Sistema (`/lixeira`)

* **Objetivo:** Auditoria e recuperação de itens excluídos logicamente.
* **Usuários:** Exclusivo Administrador.
* **Informações:** Abas separadas para Transações Excluídas, Categorias Excluídas e Contas Excluídas. Tabela detalhada informando data da exclusão, usuário que excluiu, dados originais e botões de ação: "Restaurar" e "Excluir Definitivamente".

### 12.11. Módulo de Auditoria (`/auditoria`)

* **Objetivo:** Inspeção de segurança e conformidade das ações executadas.
* **Usuários:** Exclusivo Administrador.
* **Informações:** Tabela com busca avançada por tabela, ação (`INSERT`, `UPDATE`, `SOFT_DELETE`, `RESTORE`), usuário e período. Modal com visualizador JSON lado a lado (*Diff* entre dados antigos e novos).

### 12.12. Módulo de Configurações Globais (`/configuracoes`)

* **Objetivo:** Ajuste de preferências do sistema e logotipo.
* **Usuários:** Exclusivo Administrador.
* **Informações:** Formulário de configurações gerais (Nome do Sistema, Moeda, Itens por Página, Fuso Horário), área de upload de logotipo com pré-visualização instantânea e botão de verificação de migrations do banco de dados.

---

## 13. Fluxos Funcionais

### 13.1. Fluxo de Lançamento de Despesa Parcelada

1. **Perfil:** Administrador ou Operador.
2. **Pré-condições:** Estar autenticado; existirem categorias de despesa e contas/cartões cadastrados e ativos.
3. **Passo a Passo:**
* O utilizador clica em "+ Nova Despesa" no Dashboard ou na tela de Transações;
* Seleciona a opção "Compra Parcelada" e informa o número total de parcelas (ex: 10x);
* Preenche a Descrição (ex: "Notebook Dell"), Valor Total (ex: `R$ 3.500,00`), Categoria ("Equipamentos"), Meio de Pagamento ("Cartão de Crédito"), Cartão vinculado e Data da 1ª Parcela;
* O sistema calcula o valor base da parcela (`3500.00 / 10 = 350.00`). Caso haja dízima residual, a diferença de centavos é alocada na 1ª parcela;
* O usuário submete o formulário;
* O backend valida o CSRF e os campos, gera um identificador único de grupo (`installment_group_id = UUID/Hash`) e insere em transação atômica 10 registros na tabela `transactions` com sufixos `[1/10]`, `[2/10]`, ..., calculando as datas de vencimento mensais subsequentes;
* Se a transação for em Cartão de Crédito, cada parcela é vinculada à sua respetiva fatura mensal (`credit_card_invoices`).


4. **Resultado:** As 10 parcelas são criadas e visíveis no extrato futuro e nas faturas correspondentes.
5. **Auditoria:** Gravação de registro de auditoria tipo `INSERT` contendo a lista de IDs criados.

### 13.2. Fluxo de Transferência entre Contas

1. **Perfil:** Administrador ou Operador.
2. **Pré-condições:** Haver pelo menos duas contas bancárias ativas cadastradas.
3. **Passo a Passo:**
* O utilizador acessa o modal de "+ Transferência";
* Seleciona a Conta de Origem (ex: "Conta Corrente Itaú") e a Conta de Destino (ex: "Reserva Nubank");
* Informa o Valor (ex: `R$ 1.000,00`), a Data e uma Observação opcional;
* O sistema valida se Conta de Origem e Destino são distintas e se o valor é estritamente positivo (> 0);
* O backend inicia uma transação PDO (`beginTransaction`);
* Cria o registro mestre na tabela `transfers`;
* Atualiza o saldo da Conta de Origem deduzindo o valor (`current_balance = current_balance - 1000.00`);
* Atualiza o saldo da Conta de Destino somando o valor (`current_balance = current_balance + 1000.00`);
* Executa o `commit` da transação no banco de dados.


4. **Resultado:** Os saldos das contas são atualizados instantaneamente e o histórico de transferência fica registrado.
5. **Erros Possíveis:** Falha de banco (executa `rollBack`), contas idênticas (retorna erro de validação).

### 13.3. Fluxo de Fechamento e Pagamento de Fatura de Cartão

1. **Perfil:** Administrador ou Operador.
2. **Pré-condições:** Existirem faturas fechadas com valor consolidado > 0 e conta corrente ativa com saldo disponível.
3. **Passo a Passo:**
* O utilizador acessa o módulo de Cartões e visualiza a fatura fechada do mês;
* Clica no botão "Liquidar / Pagar Fatura";
* Seleciona a Conta Bancária de débito para liquidação e confirma a data de pagamento;
* O backend abre uma transação PDO;
* Atualiza o status da fatura para `paga` na tabela `credit_card_invoices`;
* Atualiza o status de todas as transações vinculadas àquela fatura para `pago`;
* Deduz o valor total da fatura do saldo atual da conta bancária selecionada;
* Restabelece o limite de crédito disponível no cartão;
* Comita a operação e gera log de auditoria.


4. **Resultado:** Fatura liquidada, saldo bancário debitado e limite do cartão liberado.

### 13.4. Fluxo de Exclusão Lógica e Restauração via Lixeira

1. **Perfil de Exclusão:** Operador (apenas transações próprias) ou Administrador (qualquer registro).
2. **Perfil de Restauração:** Exclusivo Administrador.
3. **Passo a Passo:**
* O utilizador solicita a exclusão de uma transação confirmada;
* O sistema executa o *Soft Delete*, gravando `deleted_at = NOW()` e `deleted_by = ID_USER`;
* Se a transação excluída estava com status `pago`/`recebido`, o sistema reverte o impacto financeiro no saldo da conta correspondente;
* O Administrador acessa a tela `/lixeira`, localiza a transação e clica em "Restaurar";
* O sistema limpa as colunas `deleted_at` e `deleted_by`, reprocessa o impacto no saldo da conta e grava evento `RESTORE` na auditoria.


4. **Resultado:** Registro restaurado com consistência financeira total.

---

## 14. Validações e Regras de Negócio

### 14.1. Validações Gerais de Dados

* **Valores Monetários:** Devem ser sempre estritamente positivos ($> 0.00$), com validação de precisão em 2 casas decimais e armazenamento em colunas `DECIMAL(15,2)`.
* **Datas:** Não é permitido o lançamento de transações com formatos inválidos. Datas de vencimento devem ser coerentes com o ciclo da movimentação.
* **Sanitização de Strings:** Toda entrada textual é sanitizada contra espaços excessivos e tags HTML. Toda saída renderizada nas Views passa obrigatoriamente por `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.
* **Proteção contra CSRF:** Todo formulário HTML deve incluir um token anti-CSRF oculto (`<input type="hidden" name="csrf_token" value="...">`), validado obrigatoriamente pelo Controller antes do processamento.

### 14.2. Regras de Negócio Específicas

1. **Transações Pendentes vs. Realizadas:**
* Transações com status `pendente` (a pagar ou a receber) não afetam a coluna `current_balance` da conta bancária.
* Transações com status `pago` ou `recebido` afetam imediatamente o `current_balance` da conta vinculada.


2. **Edição de Transações Realizadas:** Caso o usuário altere o valor ou a conta de uma transação já efetivada, o sistema reverte o saldo antigo na conta anterior e aplica o novo saldo na conta selecionada em transação atômica.
3. **Regra de Parcelamento:** O somatório de todas as $N$ parcelas geradas deve ser matematicamente idêntico ao valor total informado no formulário. Quaisquer frações residuais de centavos geradas pela divisão devem ser somadas à primeira parcela.
4. **Exclusão de Contas e Categorias com Histórico:** Não é permitida a exclusão física (*Hard Delete*) de contas bancárias ou categorias que possuam transações financeiras vinculadas. O sistema aplica inativação ou exclusão lógica, preservando a integridade dos relatórios históricos.
5. **Transferência entre Mesma Conta:** É terminantemente proibido selecionar a mesma conta como Origem e Destino em uma transferência.

---

## 15. Autenticação e Sessão

### 15.1. Mecanismo de Autenticação

* **Algoritmo de Hash:** PHP nativo `password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12])` e validação via `password_verify($senha, $hash)`.
* **Regeneração de ID de Sessão:** A cada autenticação bem-sucedida, o sistema executa obrigatoriamente `session_regenerate_id(true)` para mitigar ataques de fixação de sessão (*Session Fixation*).
* **Parâmetros Seguros de Cookie de Sessão:**
* `session.cookie_httponly = 1` (impede acesso via JavaScript contra XSS);
* `session.cookie_samesite = 'Lax'` ou `'Strict'` (proteção nativa contra CSRF);
* `session.use_only_cookies = 1`.



### 15.2. Gestão de Inatividade e Expiração

* A sessão do utilizador expira automaticamente após **60 minutos** (3600 segundos) de inatividade.
* A cada requisição, o sistema compara `$_SESSION['last_activity']` com o tempo atual. Se o intervalo for superior a 3600 segundos, a sessão é destruída, os dados são limpos e o usuário é redirecionado para `/login?expired=1` com mensagem amigável.

### 15.3. Política de Bloqueio por Tentativas Inválidas (Brute Force)

* Se houver **5 tentativas consecutivas** de login inválido para a mesma combinação de e-mail e endereço IP em um intervalo de 15 minutos, o sistema bloqueia temporariamente as tentativas para aquele IP/e-mail por 15 minutos.
* O evento de bloqueio é registrado na tabela `security_logs`.

---

## 16. Controle de Acesso

### 16.1. Verificação em Nível de Rota e Controlador

O controle de acesso é aplicado de forma centralizada através de métodos auxiliares no `AuthHelper` e em classes base de controle:

* `AuthHelper::requireLogin()`: Interrompe a execução e redireciona para o login caso não haja sessão ativa.
* `AuthHelper::requireRole('admin')`: Interrompe a execução com código `HTTP 403 Forbidden` se o perfil logado não for Administrador.

### 16.2. Regra de Isolamento de Edição para Operador

* Ao tentar editar ou excluir uma transação financeira, o `TransactionController` executa a seguinte validação:
```php
if ($user->role !== 'admin' && $transaction->user_id !== $user->id) {
    FlashHelper::set('error', 'Acesso negado: Você só pode alterar lançamentos criados por você.');
    LoggerHelper::security('UNAUTHORIZED_TRANSACTION_EDIT_ATTEMPT', ['trans_id' => $id]);
    ResponseHelper::redirect('/transacoes');
}

```



---

## 17. Auditoria e Histórico

### 17.1. Escopo da Auditoria

A auditoria abrange obrigatoriamente as tabelas: `transactions`, `transfers`, `accounts`, `categories`, `credit_cards`, `users` e `settings`.

### 17.2. Estrutura de Armazenamento

Os dados anteriores e novos são serializados em JSON padrão:

* `old_values`: `{"amount": 150.00, "status": "pendente", "category_id": 3}`
* `new_values`: `{"amount": 180.00, "status": "pago", "category_id": 3}`

### 17.3. Exibição da Trilha de Auditoria

* O Administrador tem acesso à tela dedicada `/auditoria` para consultar todos os eventos com filtros por usuário, tabela e período.
* No modal de detalhes de cada transação, é exibida a aba "Histórico de Alterações" listando as modificações ocorridas naquele registro específico.

---

## 18. Soft Delete e Exclusões

### 18.1. Entidades com Soft Delete Ativo

* `transactions` (Transações Financeiras)
* `transfers` (Transferências entre Contas)
* `accounts` (Contas Bancárias)
* `categories` (Categorias)
* `payment_methods` (Meios de Pagamento)
* `credit_cards` (Cartões de Crédito)
* `users` (Usuários)

### 18.2. Regras de Restauração e Expurgo

* **Restauração:** Apenas o Administrador pode restaurar itens na tela `/lixeira`. A restauração de transações recalcula e revalida os saldos de contas associadas.
* **Expurgo Definitivo (*Hard Delete*):** Apenas o Administrador pode acionar a exclusão definitiva na Lixeira. O expurgo remove fisicamente o registro do banco de dados após gravar o evento `PURGE` na auditoria com cópia completa dos dados deletados em JSON.

---

## 19. Logs

### 19.1. Log de Erros e Exceções

* **Erros Registrados:** Exceções não tratadas (`PDOException`, `Throwable`), erros de execução fatal, falhas de I/O em upload e falhas em consultas SQL.
* **Informações Gravadas:** Mensagem de erro, código, arquivo, linha, *stack trace* sanitizado, URL requisitada, método HTTP, IP e ID do usuário autenticado.
* **Mensagens Amigáveis:** O utilizador final nunca visualiza mensagens de erro brutas do banco de dados ou detalhes de código. O sistema exibe telas de erro genéricas e elegantes (ex: "Ocorreu um erro temporário ao processar sua solicitação. O incidente foi registrado para análise.").
* **Contingência em Arquivo:** Se a gravação na tabela `error_logs` falhar (ex: queda da conexão com o banco MySQL), a classe `LoggerHelper` grava automaticamente a ocorrência em `storage/logs/error.log` com formatação padrão:
`[YYYY-MM-DD HH:II:SS] [CRITICAL] [IP: 127.0.0.1] Mensagem: ... em Arquivo:Linha`

### 19.2. Log de Eventos de Segurança

* **Eventos Rastreados:** Falhas de autenticação (`LOGIN_FAILED`), tentativas de acesso a áreas restritas (`FORBIDDEN_ACCESS_ATTEMPT`), bloqueios por brute force (`IP_LOCKED_OUT`), tokens CSRF inválidos (`INVALID_CSRF_TOKEN`), tentativas de upload com arquivos maliciosos (`MALICIOUS_UPLOAD_ATTEMPT`).
* **Armazenamento:** Tabela `security_logs` e arquivo de contingência `storage/logs/security.log`.

---

## 20. Configurações Globais

### 20.1. Parâmetros Editáveis pelo Painel Administrativo

1. `app_name`: Nome da Aplicação / Família / Empresa (Texto, Padrão: "Symo");
2. `currency_symbol`: Símbolo Monetário (Texto, Padrão: "R$");
3. `decimal_separator`: Separador Decimal (Texto, Padrão: ",");
4. `thousands_separator`: Separador de Milhar (Texto, Padrão: ".");
5. `records_per_page`: Quantidade padrão de registros em tabelas (Inteiro, Padrão: 25, Opções: 10, 25, 50, 100);
6. `app_logo`: Caminho do arquivo de logotipo da interface (Texto, Padrão: NULL).

### 20.2. Arquivo de Configuração Técnica em Código (`config/config.php`)

Credenciais e parâmetros técnicos do ambiente **não** são salvos em `.env`. Ficam centralizados no arquivo PHP dedicado `config/config.php`:

```php
<?php
defined('APP_RUNNING') or die('Acesso direto proibido.');

return [
    'app' => [
        'name'        => 'Symo',
        'env'         => 'development', // 'development' ou 'production'
        'debug'       => true,           // false em produção
        'base_url'    => '',             // Detectada dinamicamente ou informada
        'timezone'    => 'America/Sao_Paulo',
        'session_ttl' => 3600,           // 60 minutos
    ],
    'database' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'dbname'   => 'symo_db',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
    'upload' => [
        'max_size'    => 2097152, // 2 MB em bytes
        'allowed_ext' => ['jpg', 'jpeg', 'png', 'webp'],
        'paths'       => [
            'avatars' => dirname(__DIR__) . '/storage/uploads/avatars/',
            'logos'   => dirname(__DIR__) . '/storage/uploads/logos/',
        ],
    ],
    'logs' => [
        'file_fallback' => dirname(__DIR__) . '/storage/logs/error.log',
        'security_file' => dirname(__DIR__) . '/storage/logs/security.log',
    ]
];

```

---

## 21. Uploads, Anexos e Arquivos

### 21.1. Escopo de Uploads

* **Permitido:** Avatares de usuários e Logotipo da empresa/sistema.
* **Não Permitido (Fora de Escopo):** Comprovantes de pagamento, notas fiscais, boletos em PDF e anexos em transações financeiras.

### 21.2. Diretrizes de Armazenamento e Segurança

* **Diretório Físico:** Armazenados em `storage/uploads/avatars/` e `storage/uploads/logos/`.
* **Validação de Arquivo:**
1. Verificação do tamanho máximo de **2 MB** (`$_FILES['file']['size'] <= 2097152`);
2. Validação da extensão real (`pathinfo`) e validação rigorosa de MIME-Type através do `finfo_file` (aceitando apenas `image/jpeg`, `image/png`, `image/webp`);
3. Renomeação mandatória com hash randômico seguro:
`$novoNome = bin2hex(random_bytes(16)) . '.' . $extensao;`


* **Exclusão de Arquivos:** Ao atualizar o avatar ou logotipo, o arquivo antigo anterior é fisicamente removido do disco para evitar acúmulo de arquivos órfãos.
* **Proteção:** O diretório de upload possui regras de `.htaccess` impedindo expressamente a interpretação e execução de scripts PHP.

---

## 22. Relatórios, Consultas e Exportações

### 22.1. Relatórios Nativos da Aplicação

1. **Extrato Financeiro Consolidado:** Listagem detalhada de receitas, despesas e transferências em um período selecionado.
2. **Fluxo de Caixa por Categoria:** Consolidado de entradas e saídas totalizado por categorias de receitas e despesas.
3. **Relatório por Meio de Pagamento:** Demonstrativo de gastos por cartão de crédito, débito, dinheiro, Pix e transferências.
4. **Relatório de Despesas Pendentes / Contas a Pagar:** Listagem focada em obrigações financeiras vincendas no mês.

### 22.2. Filtros de Consulta

* **Obrigatórios:** Período de Análise (Data Inicial e Data Final).
* **Opcionais:** Conta Bancária, Categoria, Meio de Pagamento, Status (Pendente/Pago/Recebido), Tipo (Receita/Despesa).

### 22.3. Exportação para Planilhas (CSV)

* A exportação para CSV gera um arquivo correspondente exatamente aos dados filtrados na tela atual.
* O arquivo CSV é gerado com:
* Delimitador de colunas: Ponto-e-vírgula (`;`);
* Formato numérico: Formatação configurada (ex: `1500,50`);
* Cabeçalho de colunas: `Data;Tipo;Descrição;Categoria;Conta/Cartão;Meio de Pagamento;Status;Valor (R$)`;
* Codificação: `UTF-8` com inclusão do byte de ordem de marcação (`BOM`: `\xEF\xBB\xBF`) garantindo compatibilidade direta com Excel sem desconfigurar acentuação.



---

## 23. APIs e Integrações Externas

### 23.1. Definição do Escopo de Integrações

* O sistema é completamente **autônomo e autocontido**.
* Não há consumo nem exposição de endpoints de APIs externas (sem integrações bancárias Open Finance, sem webhooks de gateways de pagamento, sem envio de e-mails via APIs transacionais externas).
* Todas as operações e manipulações de dados ocorrem localmente no banco de dados da aplicação.

---

## 24. Segurança Funcional

### 24.1. Medidas de Proteção Obrigatórias

1. **Prevenção contra SQL Injection:** Uso exclusivo de PDO com *Prepared Statements* e *Bound Parameters* em todas as operações de banco. Proibida concatenação de variáveis em queries SQL.
2. **Prevenção contra Cross-Site Scripting (XSS):** Aplicação de `htmlspecialchars($dado, ENT_QUOTES, 'UTF-8')` em todas as variáveis renderizadas nas Views.
3. **Prevenção contra Cross-Site Request Forgery (CSRF):** Geração de token criptográfico único por sessão (`CsrfHelper::generate()`) e validação mandatória em todas as requisições com métodos `POST`, `PUT` ou `DELETE`.
4. **Proteção de Dados Sensíveis:** As senhas nunca são gravadas ou trafegadas em texto puro, sendo armazenadas exclusivamente sob hash BCRYPT.
5. **Prevenção de Enumeração de Usuários:** Respostas de erro na autenticação são sempre genéricas ("E-mail ou senha inválidos").
6. **Proteção contra Injeção de Cabeçalhos e Cliques (Clickjacking):** Envio dos cabeçalhos de segurança HTTP na resposta principal:
* `X-Frame-Options: SAMEORIGIN`
* `X-Content-Type-Options: nosniff`
* `X-XSS-Protection: 1; mode=block`
* `Referrer-Policy: strict-origin-when-cross-origin`



---

## 25. Organização Sugerida da Implementação

A implementação da aplicação pela IA codificadora deve seguir uma sequência modular, progressiva e testável:

1. **Etapa 1 - Preparação do Ambiente e Estrutura de Pastas:**
* Criação do `[Diretório do Projeto - Repositório]`;
* Montagem da estrutura de pastas (`app/`, `config/`, `database/`, `storage/`, `public/`);
* Implementação dos arquivos de proteção `.htaccess` nos diretórios internos.


2. **Etapa 2 - Configuração Base e Banco de Dados:**
* Criação de `config/config.php` e fábrica de conexão PDO `config/database.php`;
* Criação do manipulador global de erros e classes auxiliares de log (`LoggerHelper`).


3. **Etapa 3 - Sistema de Migrations e Carga Inicial:**
* Criação da tabela de controle `migrations`;
* Desenvolvimento das migrations estruturais das tabelas (conforme seção 11);
* Desenvolvimento do script CLI `migrate.php`;
* Execução das migrations e seed de dados essenciais (usuário Administrador inicial e categorias padrão).


4. **Etapa 4 - Roteamento Base e Autenticação (Auth & Session):**
* Estruturação do Front Controller (`index.php`) e roteador HTTP;
* Desenvolvimento do `AuthController`, helpers de sessão, CSRF e hashing de senhas;
* Criação das Views de Login e bloqueio de tentativas por brute force.


5. **Etapa 5 - Layout Master e Integração do Design System:**
* Montagem do layout mestre (`main.php`, `navbar.php`, `sidebar.php`) com Bootstrap 5 local e tokens do tema *Symo* (`docs/DESIGN.md`).


6. **Etapa 6 - Gestão de Cadastros Básicos (CRUDs):**
* Implementação de Contas Bancárias (`AccountController`), Categorias (`CategoryController`) e Meios de Pagamento (`PaymentMethodController`);
* Validação de regras de permissão (RBAC) e proteção contra exclusão física de itens em uso.


7. **Etapa 7 - Gestão de Cartões de Crédito e Faturas:**
* Implementação do `CreditCardController` e rotinas de abertura, fechamento e pagamento consolidado de faturas.


8. **Etapa 8 - Módulo Central de Transações Financeiras:**
* Desenvolvimento de lançamentos de receitas e despesas à vista;
* Implementação da rotina de compra parcelada com distribuição automática de parcelas e identificador de grupo;
* Implementação das regras de atualização imediata de saldo em contas e faturas.


9. **Etapa 9 - Módulo de Transferências entre Contas:**
* Desenvolvimento do `TransferController` com execução atômica via transação PDO.


10. **Etapa 10 - Dashboard Interativo e Gráficos:**
* Desenvolvimento do `DashboardController`, agregação de saldos, alertas de contas a vencer e renderização de gráficos com Chart.js local.


11. **Etapa 11 - Relatórios e Exportação CSV:**
* Desenvolvimento de extratos filtrados e gerador de CSV nativo com UTF-8 BOM.


12. **Etapa 12 - Módulos Administrativos (Usuários, Auditoria, Lixeira e Configurações):**
* Desenvolvimento da gestão de usuários e controle de inativação imediata;
* Desenvolvimento da visualização de auditoria com JSON Diff;
* Desenvolvimento da Lixeira (*Soft Delete*) com funções de restauração e expurgo;
* Desenvolvimento do painel de Configurações Globais e rotina segura de upload de logo/avatar.


13. **Etapa 13 - Revisão de Segurança, Testes Finais e Homologação:**
* Verificação de proteção de rotas, injeção de parâmetros, validações CSRF/XSS e contingência de logs.



---

## 26. Critérios de Aceitação Técnica e Funcional

Para que o sistema seja considerado homologado e pronto para entrega, os seguintes critérios devem ser integralmente atendidos:

* [ ] **Estrutura e Portabilidade:** O projeto executa a partir do `[Diretório do Projeto - Repositório]`, operando perfeitamente em subpastas locais no XAMPP ou em ambiente de produção (Hostnet/cPanel) sem URLs fixas em código.
* [ ] **Configuração em Código:** Ausência de arquivos `.env`; credenciais e parâmetros residem em `config/config.php` protegido.
* [ ] **Proteção de Pastas Internas:** Arquivos em `config/`, `app/`, `database/` e `storage/logs/` são inacessíveis diretamente via navegador.
* [ ] **Migrations Automatizadas:** Toda a estrutura de banco de dados é criada e atualizada via migrations versionadas com controle anti-duplicação na tabela `migrations`.
* [ ] **Padrão MVC Desacoplado:** Separação estrita entre Models (PDO), Views (HTML/CSS/JS) e Controllers, com roteamento centralizado.
* [ ] **Design System Fiel:** A interface adere rigorosamente aos componentes, cores e tipografia do tema *Symo* definido no `docs/DESIGN.md`.
* [ ] **Autenticação e Segurança de Senhas:** Senhas criptografadas com BCRYPT, proteção de sessão com expiração em 60 minutos, e bloqueio de tentativas de força bruta.
* [ ] **Controle de Acesso RBAC:** Operadores não conseguem acessar menus administrativos, lixeira, auditoria, configurações ou editar transações de outros membros.
* [ ] **Integridade nos Lançamentos e Parcelamentos:** Compras parceladas geram $N$ lançamentos consistentes, com centavos residuais ajustados na primeira parcela.
* [ ] **Atomicidade nas Transferências e Pagamentos:** Transferências entre contas e liquidações de fatura executam sob transações atômicas de banco (`BEGIN ... COMMIT/ROLLBACK`).
* [ ] **Soft Delete e Lixeira Operacional:** Exclusões lógicas alimentam a lixeira; restaurações reprocessam os saldos financeiros corretamente.
* [ ] **Auditoria Completa:** Alterações financeiras e cadastrais geram registros detalhados com snapshots em JSON anterior e posterior.
* [ ] **Logs de Contingência:** Falhas de banco são registradas com sucesso no arquivo físico `storage/logs/error.log`.
* [ ] **Exportação CSV Funcional:** Download de relatórios em CSV compatível com Excel (UTF-8 com BOM e delimitador `;`).
* [ ] **Uploads Seguros:** Avatares e logotipos são validados por tamanho (máx 2MB), extensão e MIME-type real, com nomes hasheados e sem execução de scripts.
* [ ] **Sem Recursos Fora de Escopo:** Ausência de códigos ou telas para itens confirmados fora de escopo (sem anexos de comprovantes, sem metas, sem APIs bancárias, sem OFX).

---

## 27. Pontos Pendentes e Decisões Futuras

Não foram identificadas pendências para iniciar a codificação com base neste FSD.

---

## 28. Conclusão

Este Documento de Especificação Funcional e Técnica (FSD) consolida de forma integral, estruturada e autossuficiente todas as regras de negócio, definições arquiteturais, modelo de dados, fluxos de uso, padrões visuais e critérios de qualidade necessários para a construção do sistema **Symo**.

O documento está **100% pronto** para orientar a IA codificadora e a equipe de desenvolvimento.

### Documentos Oficiais para a Fase de Codificação:

1. **`docs/FSD.md`** (Este documento de especificação funcional e técnica);
2. **`docs/DESIGN.md`** (Design System e guia de estilos visuais da interface).