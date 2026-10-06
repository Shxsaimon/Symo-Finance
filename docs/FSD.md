# DOCUMENTO DE ESPECIFICAÇÃO FUNCIONAL E TÉCNICA (FSD)

---

> **Versão 2.3 — 01/10/2026.** Este documento preserva a estrutura integral de 28 seções do FSD original e distingue o estado implantado das evoluções em rollout. Os dados financeiros permanecem isolados por usuário; a Fase 17 migra os meios de pagamento para catálogo global com ativação individual.

## 1. Visão Geral

* **Nome do Sistema:** Symo Finance — Gestão Financeira Pessoal e Compartilhada.
* **Objetivo Principal:** Fornecer uma plataforma web intuitiva para pessoas, casais, famílias, freelancers e MEIs registrarem receitas, despesas, transferências e faturas, com clareza entre saldos realizados e compromissos futuros.
* **Resumo do Funcionamento Atual:** Cada pessoa cria sua própria conta Symo. Contas financeiras, categorias, cartões, transações, transferências, relatórios e lixeira são isolados pelo usuário autenticado. A Fase 17 migra os meios de pagamento para catálogo global, mantendo a ativação/inativação como preferência individual. O sistema oferece lançamentos à vista ou parcelados, acompanhamento de faturas, dashboard, auditoria, exclusão lógica e exportação CSV.
* **Visão de Colaboração:** A evolução planejada permitirá compartilhar seletivamente uma conta financeira por convite e permissões vinculadas à conta, nunca por papel global. Após o aceite, o participante verá somente lançamentos posteriores e do escopo compartilhado; não receberá acesso ao perfil financeiro inteiro.
* **Público Usuário:**
* Pessoas físicas que buscam organizar o orçamento pessoal e planeamento financeiro;
* Casais e famílias que desejam colaborar futuramente apenas em contas selecionadas, como reserva ou investimento;
* Freelancers e MEIs que demandam um controlo de fluxo de caixa operacional simples, sem complexidades burocráticas ou termos contábeis avançados.


* **Contexto de Uso:** Aplicação web responsiva, otimizada para navegação em computadores, notebooks, tablets e smartphones. Os acessos ocorrem em rotinas diárias e semanais de lançamento e conferência de saldos, bem como no fecho mensal de faturas e análise de relatórios.
* **Observações Relevantes para Implementação:**
* A aplicação adota isolamento lógico por usuário nas entidades financeiras. Não existe orçamento global compartilhado no estado atual.
* O perfil técnico `dev` opera funções globais da plataforma, mas mantém dados financeiros próprios; o perfil `admin` também administra somente os próprios dados. O perfil `operador` continua no código apenas por compatibilidade legada.
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

* **Linguagem de Back-end:** PHP 8.1+ com tipagem estrita, execução nativa e PDO. O workflow de migrations usa PHP 8.2 com `pdo_pgsql`.
* **Banco de Dados Relacional Oficial:** PostgreSQL no Neon, configurado por `DATABASE_URL` e SSL obrigatório. MySQL/MariaDB é compatibilidade temporária para instalações legadas quando `DATABASE_URL` não está definida; não é o caminho de novos ambientes.
* **Tecnologias de Front-end:**
* HTML5 semântico e estruturado;
* CSS3 moderno com variáveis CSS (Design Tokens);
* JavaScript Vanilla (ES6+) para interações assíncronas, manipulação de DOM e modais;
* Framework CSS: Bootstrap 5.3.x (armazenado e servido localmente no projeto);
* Biblioteca de Ícones: Bootstrap Icons (armazenada e servida localmente);
* Biblioteca de Gráficos: Chart.js 4.x (armazenada e servida localmente).


* **Dependências e Bibliotecas Externas:**
* Não há CDN de interface em tempo de execução. CSS, JavaScript, fontes e ícones residem em `public/`.
* `@vercel/blob` é usado apenas pela Function `api/blob.js` para armazenamento privado de avatares e logotipo.


* **Padrão Arquitetural:** MVC desacoplado com Front Controller, roteamento centralizado, sessão persistida no banco e PDO. Em produção, `api/index.php` adapta o Front Controller ao runtime `vercel-php`.
* **Restrições Técnicas:**
* Segredos são fornecidos por variáveis de ambiente e lidos em `config/config.php`; nenhum `.env` com credenciais deve ser versionado;
* Proibida a execução de comandos SQL diretos sem *Prepared Statements*;
* Proibido o acesso direto via URL a qualquer arquivo ou pasta que não seja o ponto de entrada da aplicação ou assets públicos permitidos;
* O token do Vercel Blob e o token do GitHub não podem chegar ao navegador;
* Proibida a instanciação de conexões desprotegidas ou uso de extensões legadas (`mysql_*` ou `mysqli_*` procedural).



---

## 4. Ambientes do Projeto

### 4.1. Desenvolvimento Local

* **Ambiente Recomendado:** PHP 8.2+ com `pdo_pgsql`, PostgreSQL acessível por `DATABASE_URL` e servidor embutido com `php -S localhost:8000 index.php`.
* **Configuração:** `APP_BASE_URL` pode apontar para a URL local e `DATABASE_URL` deve apontar para um PostgreSQL de desenvolvimento ou Neon não produtivo.
* **Compatibilidade Legada:** MySQL/MariaDB e variáveis `DB_*` são aceitos apenas sem `DATABASE_URL`. XAMPP não é ambiente de referência.

### 4.2. Testes e Homologação

* **Ambiente:** Preview na Vercel, banco Neon separado de produção e Blob de teste quando uploads forem validados.
* **Objetivo:** Validar migrations, isolamento por usuário, sessão, assets, uploads privados, rotas autenticadas e relatórios no mesmo modelo da produção.

### 4.3. Produção

* **Ambiente Padronizado:** Vercel para execução HTTP, Neon PostgreSQL para persistência, Vercel Blob privado para arquivos e GitHub Actions para migrations remotas.
* **Entrada Serverless:** `api/index.php` entrega assets permitidos e inclui o `index.php` principal; o rewrite exclui `/public/` e `/api/`.
* **Configuração:** Credenciais ficam nas variáveis da Vercel e nos Secrets do GitHub. O banco remoto não é criado por uma requisição web.

### 4.4. Diretrizes de Deploy e Portabilidade

* A aplicação não deve conter URLs absolutas fixas em código (*hardcoded*). Todas as rotas internas, links de assets e redirecionamentos devem utilizar uma constante base (`BASE_URL` ou `APP_URL`) calculada dinamicamente pelo sistema a partir do ambiente de execução ou definida em `config/config.php`.
* `BASE_URL` continua dinâmica ou definida por `APP_BASE_URL`; em Vercel, `ASSET_URL` usa a URL base sem o prefixo local `/public`.
* O filesystem da Function não é persistente: arquivos privados usam Blob e logs usam banco ou `error_log()`.

---

## 5. Arquitetura do Sistema

### 5.1. Conceito do Diretório Raiz

Toda a arquitetura do sistema toma como referência estrutural o:
`[Diretório do Projeto - Repositório]`

Este diretório representa a raiz versionada enviada à Vercel e executável localmente pelo PHP. Ele não depende de `htdocs`, Hostnet, cPanel ou de qualquer painel de hospedagem compartilhada.

### 5.2. Estrutura de Diretórios Proposta

```text
[Diretório do Projeto - Repositório]/
│
├── api/
│   ├── index.php                # Adaptador PHP da Vercel e entrega segura de assets
│   └── blob.js                  # Function privada para Vercel Blob
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
│   │   ├── ProfileController.php
│   │   ├── BlobController.php
│   │   └── FeedbackController.php
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
│   │   ├── Setting.php
│   │   ├── Feedback.php
│   │   └── Trash.php
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
│       ├── LoggerHelper.php
│       ├── DatabaseSessionHandler.php
│       ├── DefaultDataHelper.php
│       └── GitHubActionsHelper.php
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
├── index.php                    # Ponto de entrada único (Front Controller)
├── migrate.php                  # Script CLI para execução segura de migrations
├── vercel.json                  # Runtime, rewrite, headers e bundle Vercel
├── package.json                 # Dependência @vercel/blob
├── .github/workflows/           # Workflow manual de migrations remotas
└── README.md                    # Instruções de instalação e ambiente
```

### 5.3. Fluxo de Requisição no Padrão MVC

1. **Entrada (*Front Controller*):** Na Vercel, o rewrite encaminha a aplicação a `api/index.php`; localmente, o servidor PHP pode usar diretamente o `index.php`.
2. **Inicialização do Core:** O `index.php` inicializa a sessão segura, carrega as configurações de `config/config.php`, inicializa o manipulador de erros global e instancia o roteador.
3. **Roteamento e Autenticação:** O roteador identifica a URI solicitada, verifica a autenticação do utilizador (`AuthHelper`) e valida se o perfil possui autorização para a rota (`RBAC`).
4. **Acionamento do Controller:** O Controller correspondente é instanciado. O método da ação valida tokens CSRF em requisições `POST`, sanitiza os parâmetros de entrada e solicita os dados à camada Model.
5. **Camada Model e Dados:** Os Models executam regras de persistência, cálculos financeiros e escopo por `usuario_id` via PDO e *Prepared Statements*. PostgreSQL é o driver oficial; MySQL é compatibilidade legado.
6. **Auditoria e Logs:** Se a operação for uma mutação (`INSERT`, `UPDATE`, `DELETE`), o model aciona a gravação automática na tabela de auditoria (`audit_logs`) e atualiza o histórico.
7. **Renderização da View:** O Controller passa os dados tratados para a View correspondente, que monta a interface HTML com os componentes do *Symo* e a entrega ao navegador.

### 5.4. Proteção de Diretórios Internos

* Em Apache legado, diretórios sensíveis podem manter `.htaccess`; na Vercel, eles não são expostos como arquivos estáticos públicos.
* Em nível de código, todos os arquivos PHP internos possuem uma guarda inicial que impede execução isolada:
```php
<?php
defined('APP_RUNNING') or die('Acesso direto proibido.');

```


* Uploads não são entregues pelo disco da Function: o Vercel Blob é privado e as imagens passam pelas rotas autenticadas `/media/avatar/{id}` e `/media/logo`.

---

## 6. Escopo Funcional Atual e Evolução Planejada

### 6.1. Módulo de Autenticação e Gestão de Sessão

* **Objetivo:** Garantir acesso individual seguro, criação autônoma de conta e isolamento financeiro.
* **Usuários Envolvidos:** Visitantes, `admin`, `operador` legado e `dev` técnico.
* **Ações Entregues:** Autocadastro, login e logout, perfil, senha, avatar, sessão persistida no banco e bloqueio por tentativas inválidas.
* **Planejado:** OAuth Google, verificação de e-mail e recuperação de senha por e-mail. A tela de esqueci a senha ainda não conclui uma redefinição.

### 6.2. Módulo de Dashboard Financeiro

* **Objetivo:** Apresentar a saúde financeira do escopo permitido ao usuário autenticado, sem expor dados de terceiros.
* **Usuários Envolvidos:** Todos os usuários autenticados.
* **Ações Permitidas:** Filtragem por mês/ano corrente e seleção de contas financeiras; visualização rápida de cards de indicadores (Saldo Atual, Total de Receitas do Mês, Total de Despesas do Mês, Balanço Mensal Líquido); gráficos interativos de evolução mensal e distribuição de gastos por categoria; widgets de contas pendentes e faturas de cartão próximas do vencimento.
* **Saldo Atual:** Soma os saldos das contas ativas marcadas pelo usuário para inclusão; contas do tipo `investimento` são sempre excluídas, mesmo que vinculadas a uma instituição bancária comum. A quantidade exibida junto ao saldo conta somente essas contas elegíveis. A preferência não altera o saldo individual nem o uso da conta em lançamentos e transferências.
* **Resultado Esperado:** Resposta instantânea sobre saldos realizados e compromissos futuros com atualização em tempo real.

### 6.3. Módulo de Transações Financeiras (Receitas e Despesas)

* **Objetivo:** Registar, consultar, editar e gerir todas as entradas e saídas financeiras.
* **Usuários Envolvidos:** `admin` e `dev` administram somente os próprios dados financeiros. O papel `operador` é legado e não representa compartilhamento ativo no estado atual.
* **Ações Permitidas:**
* Registo de Receita (à vista, com data de recebimento, conta bancária, categoria e status 'recebido' ou 'pendente');
* Registo de Despesa (à vista, com data de vencimento/pagamento, conta ou cartão de crédito, categoria, status 'pago' ou 'pendente');
* Registo de Compras Parceladas (geração automática de $N$ lançamentos vinculados com sufixo numérico `[1/N]`, calculando datas de vencimento mensais e valores com tratamento de dízima residual na primeira parcela);
* Filtros por período, tipo, conta, categoria, meio de pagamento, cartão, status, texto e autor quando permitido;
* Alteração e exclusão lógica de transações.
* A edição preserva a data de lançamento (`data_transacao`); somente a data de vencimento pode ser alterada pelo campo correspondente.


* **Resultado Esperado:** Manutenção fidedigna do fluxo de caixa individual, preservando a autoria necessária à futura colaboração por conta.

### 6.4. Módulo de Transferências entre Contas

* **Objetivo:** Registar movimentações entre contas do mesmo usuário sem distorcer receitas e despesas operacionais.
* **Usuários Envolvidos:** Proprietário das duas contas. Transferências só podem ocorrer entre contas do mesmo proprietário; o compartilhamento futuro não permitirá transferências entre proprietários diferentes.
* **Ações Permitidas:** Seleção de conta de origem, conta de destino, valor, data e observação.
* **Resultado Esperado:** Criação de transação pareada em banco de dados com dedução no saldo da conta de origem e crédito correspondente na conta de destino sob transação atômica (`DB Transaction`).

### 6.5. Módulo de Cartões de Crédito e Fechamento de Faturas

* **Objetivo:** Controlar os limites de crédito, despesas agrupadas por fatura e pagamento consolidado mensal.
* **Usuários Envolvidos:** Proprietário do cartão; cartões não são compartilhados na primeira fase colaborativa.
* **Ações Permitidas:** Cadastro de cartões com limite total, dia de fecho e dia de vencimento; lançamento de despesas no cartão; agrupamento de faturas (Aberta, Fechada, Paga); liquidação da fatura gerando a dedução em uma conta bancária de escolha.
* **Resultado Esperado:** Previsibilidade do montante da fatura do mês sem duplicar débitos em conta corrente antes do pagamento efetivo.

### 6.6. Módulo de Cadastros Básicos (Contas, Categorias e Meios de Pagamento)

* **Objetivo:** Estruturar a base cadastral de suporte aos lançamentos financeiros.
* **Usuários Envolvidos:** Contas, categorias e cartões pertencem ao usuário autenticado; `admin` e `dev` administram os próprios itens. Meios de pagamento seguem o catálogo global da Fase 17, com disponibilidade individual.


* **Ações Permitidas:** O `dev` cria, edita e altera o status global das opções. Cada usuário autenticado ativa/inativa opções para si. Contas, categorias e cartões continuam sob administração do próprio usuário; em cada conta, o usuário pode escolher se o saldo entra no Saldo Atual. Contas do tipo `investimento` ficam sempre fora desse valor.
* **Resultado Esperado:** Padronização dos lançamentos financeiros sem risco de redundância ou criação desordenada de dados por operadores.

### 6.7. Módulo de Relatórios e Exportação CSV

* **Objetivo:** Disponibilizar extratos consolidados, relatórios analíticos de fluxo de caixa e exportação de dados para planilhas.
* **Usuários Envolvidos:** `admin` e `dev`, cada um no próprio escopo financeiro; `operador` é legado até o compartilhamento por conta.
* **Ações Permitidas:** Consulta a relatórios por período, categoria, conta e status; download instantâneo de arquivo estruturado em padrão CSV estruturado com separador `;` e codificação UTF-8 com BOM.
* **Resultado Esperado:** Extração fidedigna de relatórios com consistência total entre a tabela visualizada na tela e os dados exportados.

### 6.8. Módulo de Gestão de Usuários (Administrativo)

* **Objetivo:** Administrar contas de acesso da plataforma.
* **Usuários Envolvidos:** Exclusivamente `dev`.
* **Ações Permitidas:** Consultar, criar, alterar, inativar, redefinir senha e excluir usuários. O autocadastro é o caminho normal para usuários finais.
* **Resultado Esperado:** Operação global da plataforma sem ampliar o escopo financeiro individual do `dev`.

### 6.9. Módulo de Lixeira e Recuperação (*Soft Delete*)

* **Objetivo:** Proteger os dados contra exclusões acidentais e permitir restauração controlada.
* **Usuários Envolvidos:** Cada usuário autenticado consulta, restaura e expurga apenas os próprios registros, inclusive o perfil `dev`.
* **Ações Permitidas:** Consulta de itens excluídos logicamente (transações, categorias, contas), restauração individual com restauração de saldos, e expurgo/exclusão definitiva.
* **Resultado Esperado:** Segurança operacional e recuperação de dados sem necessidade de intervenção direta no banco de dados.

### 6.10. Módulo de Auditoria e Histórico

* **Objetivo:** Rastrear quem criou, alterou ou excluiu cada registo financeiro.
* **Usuários Envolvidos:** Exclusivamente `dev` para a trilha global.
* **Ações Permitidas:** Consulta a trilha de auditoria contendo dados anteriores e novos em formato JSON, data/hora exata, endereço IP e utilizador autor.
* **Resultado Esperado:** Transparência completa no uso colaborativo da plataforma.

### 6.11. Módulo de Configurações Globais do Sistema

* **Objetivo:** Separar preferências pessoais de operação global.
* **Usuários Envolvidos:** Todos para moeda, separadores e paginação; `dev` para identidade visual, migrations e GitHub Actions.
* **Ações Permitidas:** Preferências em `configuracoes_usuario`; parâmetros globais em `configuracoes` exclusivamente para `dev`.
* **Resultado Esperado:** Personalização individual sem vazar dados ou dar privilégio global ao `admin` comum.

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
8. **Compartilhamento amplo de todo o perfil financeiro:** O estado atual é individual por usuário. A futura colaboração será restrita a contas financeiras selecionadas, por convite e permissão; não haverá compartilhamento automático de todas as entidades.
9. **Exportação Nativa em PDF com Layout Gráfico:** Não haverá gerador interno de relatórios em formato PDF (apenas exportação em formato tabular CSV).
10. **Envio Ativo de Notificações por E-mail (Lembretes de Vencimento):** Não haverá rotinas agendadas (Cron Jobs) para disparo de e-mails diários de contas a vencer.

---

## 8. Perfis de Usuário e Permissões

### 8.1. Perfil Administrador (`admin`)

* **Descrição:** Proprietário da própria conta financeira individual.
* **Permissões:** Acesso integral apenas aos seus dados financeiros e preferências pessoais. Não é administrador global da plataforma.
* **Ações Exclusivas:**
* Gestão de contas, categorias, cartões, transações, transferências, relatórios e lixeira próprios; ativação/inativação individual de meios de pagamento;
* Atualização do próprio perfil, senha e avatar;
* Alteração de preferências individuais de moeda, separadores e paginação;
* Futuramente, gestão de convites e permissões das contas financeiras de sua propriedade.



### 8.2. Perfil Operador (`operador`)

* **Descrição:** Perfil legado, sem uso previsto no produto atual e não concedido pelo autocadastro. Não será a unidade de compartilhamento futuro.
* **Evolução acordada:** O acesso futuro de outra pessoa será uma permissão vinculada a cada conta financeira compartilhada, não um papel global no usuário. A implementação desse vínculo permanece no roadmap.



### 8.3. Perfil Técnico (`dev`) e Matriz de Permissões

* **Descrição:** Usuário técnico interno, criado pelo seed com `SYSTEM_DEV_*`. Além de operar a plataforma, possui seus próprios dados financeiros como qualquer usuário `admin`.
* **Permissões Globais:** Gestão de usuários, trilha de auditoria, identidade visual, status/migrations e GitHub Actions. Essas permissões administrativas não ampliam o escopo de dados financeiros: transações, transferências, contas, dashboard, relatórios e lixeira do `dev` são próprios.

| Módulo / Ação | `admin` individual | `operador` legado | `dev` |
| --- | --- | --- |
| Visualizar Dashboard e Saldos | Próprios | Legado; sem compartilhamento ativo | Próprios |
| Criar Receitas / Despesas / Transferências | Próprios | Legado; sem compartilhamento ativo | Próprios |
| Editar / Excluir Transações Próprias | Sim | Legado; sem compartilhamento ativo | Sim |
| Editar / Excluir Transações de Outros Usuários | Não | Não | Não |
| Cadastrar / Editar Contas e Categorias | Próprios | Legado; sem compartilhamento ativo | Próprios |
| Ativar / Inativar Meios de Pagamento | Individual | Legado; sem compartilhamento ativo | Individual |
| Criar / Editar / Alterar Status Global de Meios | Não | Não | Sim |
| Gerenciar Cartões e Faturas | Próprios | Legado; sem compartilhamento ativo | Próprios |
| Emitir Relatórios e Exportar CSV | Próprios | Legado; sem compartilhamento ativo | Próprios |
| Lixeira | Própria | Legado; sem compartilhamento ativo | Própria |
| Auditoria e Gestão de Usuários | Não | Não | Sim |
| Configurações globais, logotipo e migrations | Não | Não | Sim |
| Alterar Própria Senha e Avatar | Sim | Sim | Sim |

---

## 9. Recursos Estruturais do Sistema

### 9.1. Autenticação e Gestão de Sessão

* Autenticação com e-mail único e senha criptografada via algoritmo nativo BCRYPT (`password_hash` com *cost* 12).
* Destruição e invalidação imediata de sessão no logout.
* Tempo limite de inatividade de sessão de **60 minutos**, com expiração automática e redirecionamento para a tela de login.
* Proteção contra ataques de força bruta com bloqueio temporário de 15 minutos após 5 tentativas consecutivas de senha inválida por IP/E-mail.
* Revogação instantânea de acesso em caso de inativação de conta pelo `dev`.

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
* A lixeira filtra registros pelo proprietário para todos os perfis, inclusive `dev`; restauração e expurgo também ficam limitados aos registros próprios.

### 9.5. Sistema de Logs e Contingência em Arquivo

* Registro detalhado de exceções e erros em `erros_logs` quando o banco estiver disponível.
* **Mecanismo de Contingência:** Em runtime Vercel, a camada usa `error_log()`; em execução local/legada, pode gravar em `storage/logs/error.log`.
* Tratamento de falhas de segurança (`security_logs`) para tentativas de invasão, acessos negados (403), falhas repetidas de login e adulteração de tokens CSRF.

### 9.6. Configurações Globais Dinâmicas

* Preferências individuais ficam em `configuracoes_usuario`; configurações globais de plataforma ficam em `configuracoes` e são restritas ao `dev`.
* Mecanismo de fallback em código (`config/config.php`) garantindo o funcionamento do sistema caso a tabela esteja vazia ou inacessível.

### 9.7. Upload Seguro de Imagens

* Upload restrito a **Avatares de Usuários** e **Logotipo da Aplicação**.
* Limite máximo de arquivo: **2 MB**.
* Tipos aceitos incluem imagens validadas pelo servidor; SVG sanitizado é suportado no fluxo Blob quando aplicável.
* Validação rigorosa via `finfo`, renomeação segura e remoção do objeto anterior. O arquivo é gravado no Vercel Blob privado, não no disco persistente da Function.

### 9.8. Exportação Estruturada em CSV

* Geração nativa de arquivos CSV estruturados para extratos financeiros e relatórios de fluxo de caixa.
* Utilização de codificação UTF-8 com inclusão de BOM (`\xEF\xBB\xBF`) para compatibilidade perfeita com Microsoft Excel, LibreOffice Calc e Google Sheets, utilizando ponto-e-vírgula (`;`) como delimitador padrão de colunas.

---

## 10. Entidades do Sistema

### 10.1. Usuário (`User`)

* **Finalidade:** Representa os membros com acesso ao sistema.
* **Informações:** Nome, e-mail, hash de senha anulável para OAuth futuro, `google_id` único anulável, perfil (`admin`, `operador` ou `dev`), status, avatar Blob e timestamps.
* **Regras:** E-mail único; autocadastro cria `admin` ativo e dados padrão; inativação revoga acesso; o usuário não pode excluir/inativar a si próprio.

### 10.2. Conta Financeira (`Account`)

* **Finalidade:** Representa contas correntes, poupanças, contas de investimento ou carteiras de dinheiro físico.
* **Informações:** `usuario_id` proprietário, nome, instituição, tipo, saldos, preferência `incluir_saldo_atual`, cor, status, timestamps e soft delete.
* **Regras:** Saldo individual é atualizado por transação confirmada. A preferência `incluir_saldo_atual` controla somente a inclusão no Saldo Atual; contas do tipo `investimento` são sempre excluídas desse valor, sem perder saldo próprio nem poder ser usadas em lançamentos e transferências. No roadmap a conta poderá ter participantes e permissões sem alterar o proprietário.

### 10.3. Categoria (`Category`)

* **Finalidade:** Classificação orçamentária das transações.
* **Informações:** Nome da categoria, tipo (`receita` ou `despesa`), ícone visual, cor hexadecimal, status (`ativa` ou `inativa`), timestamps e soft delete.
* **Regras:** Categorias não podem ter seu tipo alterado após o primeiro lançamento; exclusão lógica preserva integridade histórica dos relatórios.

### 10.4. Meio de Pagamento (`PaymentMethod`)

* **Finalidade:** Modalidade utilizada para liquidar as transações (ex: Dinheiro, Pix, Débito, Crédito, Boleto, Transferência).
* **Informações:** Nome, tipo de liquidação (imediata ou a prazo/fatura), status (`ativo` ou `inativo`), timestamps e soft delete.
* **Regras:** Catálogo global; o perfil `dev` cria, edita e inativa/reativa opções globalmente pelo status. Cada usuário autenticado ativa/inativa opções somente para si. A inativação global impede novos usos para todos, mas mantém o vínculo histórico e pode ser revertida pelo `dev`.

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
* **Regras:** Transações pendentes não afetam o saldo real da conta; transações efetivadas debitam/creditam o saldo da conta imediatamente; compras parceladas geram registros individuais linkados por grupo. A edição mantém a data de lançamento original e altera o vencimento somente quando informado.

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

## 11. Modelo de Dados Atual e Evolução Proposta

### 11.1. Especificação das Tabelas e Campos

#### Tabela `usuarios`

> A nomenclatura física atual está em português. No PostgreSQL, identificadores usam `INTEGER GENERATED BY DEFAULT AS IDENTITY`, datas usam `TIMESTAMP` e valores monetários usam `NUMERIC(15,2)`; a adaptação MySQL legada preserva os conceitos equivalentes.

* `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `nome`: VARCHAR(120) NOT NULL
* `email`: VARCHAR(150) NOT NULL UNIQUE
* `senha`: VARCHAR(255) NULL, permitindo futura conta exclusivamente OAuth
* `google_id`: VARCHAR(255) UNIQUE NULL
* `perfil`: CHECK em `admin`, `operador` ou `dev`; autocadastro cria `admin`
* `status`: ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo'
* `avatar`: VARCHAR(255) NULL
* `created_at`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* `updated_at`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `deleted_at`: DATETIME NULL
* `deleted_by`: INT UNSIGNED NULL
* *Índices:* `idx_users_email (email)`, `idx_users_status (status)`, `idx_users_deleted_at (deleted_at)`

#### Tabela `contas`

* `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `usuario_id`: INTEGER NOT NULL, FK para `usuarios`, proprietário da conta
* `name`: VARCHAR(100) NOT NULL
* `bank_name`: VARCHAR(100) NULL
* `type`: ENUM('corrente', 'poupanca', 'investimento', 'dinheiro', 'outros') NOT NULL DEFAULT 'corrente'
* `initial_balance`: DECIMAL(15,2) NOT NULL DEFAULT 0.00
* `current_balance`: DECIMAL(15,2) NOT NULL DEFAULT 0.00
* `incluir_saldo_atual`: BOOLEAN NOT NULL DEFAULT TRUE; preferência para incluir a conta no Saldo Atual, ignorada para contas do tipo `investimento`
* `color`: VARCHAR(7) NOT NULL DEFAULT '#6F02FF'
* `status`: ENUM('ativa', 'inativa') NOT NULL DEFAULT 'ativa'
* `created_at`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* `updated_at`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `deleted_at`: DATETIME NULL
* `deleted_by`: INT UNSIGNED NULL
* *Índices:* `idx_accounts_status (status)`, `idx_accounts_deleted_at (deleted_at)`

#### Tabela `categorias`

* `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `usuario_id`: INTEGER NOT NULL, FK para `usuarios`
* `name`: VARCHAR(100) NOT NULL
* `type`: ENUM('receita', 'despesa') NOT NULL
* `icon`: VARCHAR(50) NOT NULL DEFAULT 'bi-tag'
* `color`: VARCHAR(7) NOT NULL DEFAULT '#793CFE'
* `status`: ENUM('ativa', 'inativa') NOT NULL DEFAULT 'ativa'
* `created_at`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* `updated_at`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `deleted_at`: DATETIME NULL
* `deleted_by`: INT UNSIGNED NULL
* *Índices:* `idx_categories_type (type)`, `idx_categories_status (status)`, `idx_categories_deleted_at (deleted_at)`

#### Tabela `meios_pagamento`

* `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `usuario_id`: INTEGER NOT NULL, FK para `usuarios`
* `name`: VARCHAR(80) NOT NULL
* `is_credit`: TINYINT(1) NOT NULL DEFAULT 0
* `status`: ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo'
* `created_at`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* `updated_at`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `deleted_at`: DATETIME NULL
* `deleted_by`: INT UNSIGNED NULL
* *Índices:* `idx_payment_methods_status (status)`, `idx_payment_methods_deleted_at (deleted_at)`

**Transição da Fase 17:** a migration remove `usuario_id` da tabela de catálogo. O `dev` altera o status global para `inativo` ou `ativo`; essa mudança não altera preferências individuais nem referências históricas.

#### Tabela `meios_pagamento_usuario`

* `usuario_id`: INTEGER NOT NULL, FK para `usuarios`.
* `meio_pagamento_id`: INTEGER NOT NULL, FK para `meios_pagamento`.
* `status`: ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo'; preferência individual de disponibilidade.
* *Chave primária:* (`usuario_id`, `meio_pagamento_id`).

Sem preferência registrada, a opção é considerada ativa. A migration consolida referências antes de remover duplicatas e preserva transações e auditoria histórica.

#### Tabela `cartoes_credito`

* `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `usuario_id`: INTEGER NOT NULL, FK para `usuarios`
* `name`: VARCHAR(100) NOT NULL
* `credit_limit`: DECIMAL(15,2) NOT NULL DEFAULT 0.00
* `closing_day`: TINYINT UNSIGNED NOT NULL
* `due_day`: TINYINT UNSIGNED NOT NULL
* `default_account_id`: INT UNSIGNED NULL
* `color`: VARCHAR(7) NOT NULL DEFAULT '#3BD7D7'
* `status`: ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo'
* `created_at`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* `updated_at`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `deleted_at`: DATETIME NULL
* `deleted_by`: INT UNSIGNED NULL
* *Foreign Keys:* `fk_credit_cards_account (default_account_id) REFERENCES accounts(id) ON DELETE SET NULL`
* *Índices:* `idx_credit_cards_status (status)`, `idx_credit_cards_deleted_at (deleted_at)`

#### Tabela `faturas_cartao`

* `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `credit_card_id`: INT UNSIGNED NOT NULL
* `reference_month`: TINYINT UNSIGNED NOT NULL
* `reference_year`: SMALLINT UNSIGNED NOT NULL
* `closing_date`: DATE NOT NULL
* `due_date`: DATE NOT NULL
* `total_amount`: DECIMAL(15,2) NOT NULL DEFAULT 0.00
* `status`: ENUM('aberta', 'fechada', 'paga') NOT NULL DEFAULT 'aberta'
* `paid_at`: DATETIME NULL
* `payment_account_id`: INT UNSIGNED NULL
* `created_at`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* `updated_at`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* *Foreign Keys:*
* `fk_invoices_card (credit_card_id) REFERENCES credit_cards(id) ON DELETE RESTRICT`
* `fk_invoices_account (payment_account_id) REFERENCES accounts(id) ON DELETE SET NULL`


* *Índices:* `idx_invoices_card_period (credit_card_id, reference_year, reference_month)`, `idx_invoices_status (status)`

#### Tabela `transacoes`

* `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `user_id`: INT UNSIGNED NOT NULL
* `type`: ENUM('receita', 'despesa') NOT NULL
* `description`: VARCHAR(255) NOT NULL
* `amount`: DECIMAL(15,2) NOT NULL
* `transaction_date`: DATE NOT NULL
* `due_date`: DATE NOT NULL
* `payment_date`: DATE NULL
* `status`: ENUM('pendente', 'pago', 'recebido') NOT NULL DEFAULT 'pendente'
* `account_id`: INT UNSIGNED NULL
* `category_id`: INT UNSIGNED NOT NULL
* `payment_method_id`: INT UNSIGNED NOT NULL
* `credit_card_id`: INT UNSIGNED NULL
* `invoice_id`: INT UNSIGNED NULL
* `installment_group_id`: VARCHAR(64) NULL
* `installment_number`: TINYINT UNSIGNED NULL DEFAULT 1
* `installment_total`: TINYINT UNSIGNED NULL DEFAULT 1
* `notes`: TEXT NULL
* `created_at`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* `updated_at`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `deleted_at`: DATETIME NULL
* `deleted_by`: INT UNSIGNED NULL
* *Foreign Keys:*
* `fk_trans_user (user_id) REFERENCES users(id) ON DELETE RESTRICT`
* `fk_trans_account (account_id) REFERENCES accounts(id) ON DELETE SET NULL`
* `fk_trans_category (category_id) REFERENCES categories(id) ON DELETE RESTRICT`
* `fk_trans_payment_method (payment_method_id) REFERENCES payment_methods(id) ON DELETE RESTRICT`
* `fk_trans_credit_card (credit_card_id) REFERENCES credit_cards(id) ON DELETE SET NULL`
* `fk_trans_invoice (invoice_id) REFERENCES credit_card_invoices(id) ON DELETE SET NULL`


* *Índices de Alto Desempenho:*
* `idx_trans_date (transaction_date)`
* `idx_trans_due_date (due_date)`
* `idx_trans_status (status)`
* `idx_trans_type (type)`
* `idx_trans_account (account_id)`
* `idx_trans_category (category_id)`
* `idx_trans_installment (installment_group_id)`
* `idx_trans_deleted_at (deleted_at)`
* `idx_trans_dashboard_composite (deleted_at, status, due_date, type)`



#### Tabela `transferencias`

* `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `user_id`: INT UNSIGNED NOT NULL
* `source_account_id`: INT UNSIGNED NOT NULL
* `destination_account_id`: INT UNSIGNED NOT NULL
* `amount`: DECIMAL(15,2) NOT NULL
* `transfer_date`: DATE NOT NULL
* `notes`: TEXT NULL
* `source_transaction_id`: INT UNSIGNED NULL
* `destination_transaction_id`: INT UNSIGNED NULL
* `created_at`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* `updated_at`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `deleted_at`: DATETIME NULL
* `deleted_by`: INT UNSIGNED NULL
* *Foreign Keys:*
* `fk_transf_user (user_id) REFERENCES users(id) ON DELETE RESTRICT`
* `fk_transf_src (source_account_id) REFERENCES accounts(id) ON DELETE RESTRICT`
* `fk_transf_dst (destination_account_id) REFERENCES accounts(id) ON DELETE RESTRICT`


* *Índices:* `idx_transfers_date (transfer_date)`, `idx_transfers_deleted_at (deleted_at)`

#### Tabela `auditoria_logs`

* `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `user_id`: INT UNSIGNED NULL
* `table_name`: VARCHAR(64) NOT NULL
* `record_id`: INT UNSIGNED NOT NULL
* `action`: ENUM('INSERT', 'UPDATE', 'SOFT_DELETE', 'RESTORE', 'PURGE') NOT NULL
* `old_values`: JSON NULL
* `new_values`: JSON NULL
* `ip_address`: VARCHAR(45) NOT NULL
* `created_at`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* *Foreign Keys:* `fk_audit_user (user_id) REFERENCES users(id) ON DELETE SET NULL`
* *Índices:* `idx_audit_table_record (table_name, record_id)`, `idx_audit_created_at (created_at)`

#### Tabela `seguranca_logs`

* `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `event`: VARCHAR(80) NOT NULL
* `email`: VARCHAR(150) NULL
* `ip_address`: VARCHAR(45) NOT NULL
* `user_agent`: VARCHAR(255) NULL
* `context`: JSON NULL
* `created_at`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
* *Índices:* `idx_sec_event (event)`, `idx_sec_ip (ip_address)`, `idx_sec_created_at (created_at)`

#### Tabela `configuracoes` e `configuracoes_usuario`

* `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `configuracoes`: dados globais da plataforma, acessíveis ao `dev`.
* `configuracoes_usuario`: `usuario_id`, chave, valor e tipo, com unicidade por usuário e chave; armazena moeda, separadores e paginação.
* `type`: ENUM('string', 'integer', 'boolean', 'json') NOT NULL DEFAULT 'string'
* `description`: VARCHAR(255) NULL
* `updated_at`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* *Índice:* `idx_settings_key (setting_key)`

#### Tabela `migrations`

* `id`: INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `migration`: VARCHAR(255) NOT NULL UNIQUE
* `batch`: INT UNSIGNED NOT NULL
* `executed_at`: DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP

#### Tabela `feedback`

* `usuario_id`: usuário autenticado que enviou o item.
* `texto`: conteúdo entre 10 e 3.000 caracteres.
* `tela`: tela de origem validada e `tipo`: `correcao`, `melhoria` ou `duvida`.
* `imagens`: URLs privadas do Vercel Blob em JSON/JSONB, com até 5 imagens por feedback.
* `status`: `novo`, `em_analise`, `concluido` ou `descartado`, com timestamps de criação e atualização.

#### Evolução Planejada: Tabela de Participantes de Conta

Antes do compartilhamento, deverá ser criada uma tabela como `conta_participantes`, sem remover `contas.usuario_id`. Ela deverá vincular conta e usuário, registrar permissão de visualização, lançamento e alcance de edição, além dos timestamps de convite, aceite e revogação. A visibilidade começa no aceite; não haverá opção para liberar histórico anterior. Consultas e validações contra IDOR deverão aceitar proprietário ou participante ativo com permissão para aquela conta.

---

### 11.2. Estratégia de Migrations do Projeto

1. **Objetivo:** Automatizar tabelas, índices e restrições sem intervenção manual no banco remoto.
2. **Localização Segura:** Todos os scripts de migration residem em `database/migrations/`, bloqueados para acesso web via `.htaccess`.
3. **Mecanismo de Controle de Execução:**
* A tabela `migrations` rastreia o histórico de cada arquivo executado com sucesso.
* Antes de executar qualquer migração, o sistema verifica se o nome do arquivo já consta na tabela `migrations`. Se já tiver sido executado, o arquivo é ignorado, evitando duplicações ou erros de integridade.


4. **Execução Controlada:**
* **Via CLI:** `php migrate.php`, `--status`, `--seed` e `--fresh --confirmar` em ambiente com `pdo_pgsql`.
* **Via GitHub Actions:** Workflow manual `database-migrations.yml`, com Secret `NEON_DATABASE_URL`. O painel do `dev` só dispara `status`, `migrate` e `seed`; `fresh` exige confirmação `RESETAR` diretamente no workflow.



---

## 12. Módulos e Telas

### 12.1. Tela de Login, Cadastro e Recuperação (`/login`, `/registrar`, `/esqueci-senha`)

* **Objetivo:** Ponto de entrada do sistema para identificação de credenciais.
* **Usuários:** Todos (público não autenticado).
* **Campos/Informações:** Login por e-mail e senha, link de autocadastro e link de esqueci a senha. Não existe "lembrar de mim" nem envio funcional de recuperação no estado atual.
* **Estados:** Formulário inicial, estado de carregamento do botão com indicador visual (*spinner*), alerta de credenciais inválidas ou conta inativada, e aviso de bloqueio temporário por excesso de tentativas.
* **Aderência ao Design System:** Layout limpo com fundo `Surface` (`#FAF9F9`), card centralizado em elevação tonal `Surface-Container-Lowest` (`#FFFFFF`), borda sutil de 1px `Neutral 300` e cantos arredondados de 16px.

### 12.2. Dashboard Principal (`/dashboard`)

* **Objetivo:** Central de comando com métricas em tempo real, Saldo Atual e previsibilidade financeira.
* **Usuários:** Usuários autenticados, com dados filtrados por `usuario_id`.
* **Informações e Componentes:**
* Seletor de Período (Mês e Ano corrente) e filtro rápido de conta;
* 4 Cards de Indicadores Principais (KPIs com numerais tabulares):
1. Saldo Atual (somatório das contas ativas incluídas pelo usuário, exceto as do tipo `investimento`);
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
* **Usuários:** `admin` e `dev`, cada um no próprio escopo financeiro. O perfil `operador` é legado até existir compartilhamento por conta.
* **Filtros e Buscas:** Período de datas (de/até), Tipo (Receita/Despesa), Categoria, Conta Financeira, Meio de Pagamento, Status (Pendente/Pago/Recebido) e Busca textual por descrição.
* **Tabela de Dados:** Alta densidade com linhas zebradas sutis (`Surface-Container-Low`), colunas para Data, Tipo (badge semântico), Descrição (com identificação de parcela `[1/12]`), Categoria, Conta/Cartão, Valor (numerais tabulares), Status e Botões de Ação (Visualizar Detalhes, Editar, Excluir).
* **Modais Interativos:**
* Modal de Lançamento de Receita;
* Modal de Lançamento de Despesa (com opção de compra parcelada e seleção de cartão);
* Modal de Detalhes da Transação (exibindo autor, histórico de edições e logs de auditoria para Administrador).



### 12.4. Módulo de Contas Bancárias (`/contas`)

* **Objetivo:** Gestão de instituições financeiras, caixas e saldos.
* **Usuários:** Proprietário da conta; compartilhamento de conta ainda não está implementado.
* **Informações:** Cards de Saldo Atual e de Investimentos com textos explicativos sobre o valor exibido, seguidos da relação de contas com tipo, saldo individual e badge de status. O Saldo Atual exclui contas de investimento e contas desmarcadas; o card de Investimentos soma os saldos das contas ativas desse tipo. Modal de criação e edição com seletor de cor personalizada.

### 12.5. Módulo de Cartões de Crédito e Faturas (`/cartoes`)

* **Objetivo:** Gestão de cartões, limites e fechamento/pagamento de faturas mensais.
* **Usuários:** `admin` e `dev`, cada um no próprio escopo financeiro.
* **Informações:** Card visual do cartão com limite total, limite disponível (barra de progresso), dia de fecho e dia de vencimento. Tabela de faturas históricas com status (Aberta, Fechada, Paga) e botão de ação para "Pagar Fatura" (dispara liquidação em conta corrente).

### 12.6. Módulo de Transferências (`/transferencias`)

* **Objetivo:** Registro e extrato de transferências internas entre contas.
* **Usuários:** `admin` e `dev`, cada um no próprio escopo financeiro.
* **Informações:** Formulário simples com seleção de Conta de Origem, Conta de Destino, Valor, Data e Observação. Tabela histórica das transferências com identificação das contas envolvidas e autor da movimentação.

### 12.7. Módulo de Categorias e Meios de Pagamento (`/categorias`, `/meios-pagamento`)

* **Objetivo:** Cadastro e parametrização das classificações financeiras.
* **Estado atual:** `admin` e `dev` administram os próprios cadastros; `operador` é legado e não tem compartilhamento ativo.
* **Evolução dos meios de pagamento (Fase 17):** catálogo global; criação e alteração de status global exclusivas do `dev`, incluindo reativação pela listagem. Cada usuário, inclusive `dev`, controla apenas a própria disponibilidade individual. A inativação global ou individual impede novos lançamentos no escopo correspondente, preservando os históricos.
* **Informações:** Listagens tabulares com badges de cores, ícones associados, tipo e estado de disponibilidade.

### 12.8. Módulo de Relatórios e Exportação (`/relatorios`)

* **Objetivo:** Emissão de extratos consolidados, análise de fluxo de caixa e exportação para planilhas.
* **Usuários:** `admin` e `dev`, cada um no próprio escopo financeiro.
* **Informações:** Painel de filtros obrigatórios de período (Mês/Ano ou Intervalo Personalizado), agrupamentos opcionais por categoria, conta ou centro de custo, tabela analítica consolidada e botão destacado de "Exportar para CSV".

### 12.9. Módulo de Usuários (`/usuarios`)

* **Objetivo:** Administração de contas de acesso ao sistema compartilhado.
* **Usuários:** Exclusivo `dev`.
* **Informações:** Listagem de usuários com avatar, nome, e-mail, perfil (badge roxo para Admin, badge azul para Operador), status e ações (Editar Perfil, Inativar/Reativar, Redefinir Senha).

### 12.10. Módulo de Lixeira do Sistema (`/lixeira`)

* **Objetivo:** Auditoria e recuperação de itens excluídos logicamente.
* **Usuários:** Registros próprios para todos os perfis, inclusive `dev`.
* **Informações:** Abas separadas para Transações Excluídas, Categorias Excluídas e Contas Excluídas. Tabela detalhada informando data da exclusão, usuário que excluiu, dados originais e botões de ação: "Restaurar" e "Excluir Definitivamente".

### 12.11. Módulo de Auditoria (`/auditoria`)

* **Objetivo:** Inspeção de segurança e conformidade das ações executadas.
* **Usuários:** Exclusivo `dev`.
* **Informações:** Tabela com busca avançada por tabela, ação (`INSERT`, `UPDATE`, `SOFT_DELETE`, `RESTORE`), usuário e período. Modal com visualizador JSON lado a lado (*Diff* entre dados antigos e novos).

### 12.12. Módulo de Configurações Globais (`/configuracoes`)

* **Objetivo:** Ajuste de preferências do sistema e logotipo.
* **Usuários:** Todos para preferências pessoais; `dev` para funções globais.
* **Informações:** Formulário de configurações gerais (Nome do Sistema, Moeda, Itens por Página, Fuso Horário), área de upload de logotipo com pré-visualização instantânea e botão de verificação de migrations do banco de dados.

### 12.13. Consulta de Feedbacks Recebidos (`/feedbacks`)

* **Objetivo:** Permitir a triagem operacional das sugestões, dúvidas e correções enviadas pelos usuários.
* **Usuários:** Exclusivamente o perfil `dev`, com autorização também validada no Controller.
* **Informações:** Tabela paginada em ordem cronológica decrescente com autor, data, tela de origem, tipo, mensagem, imagens, status e ações. Feedback novo recebe status `novo`; `concluido` é exibido como **Concluído**.
* **Ações:** O `dev` pode mudar o status para `em_analise` (Em análise) ou `concluido` (Concluído) usando botões compactos lado a lado. A alteração exige CSRF e gera evento de auditoria. O texto e as imagens podem ser ampliados para leitura.
* **Segurança:** A leitura das imagens passa por rota autenticada exclusiva do `dev`; URLs privadas e credenciais do Vercel Blob não são expostas à interface.

---

## 13. Fluxos Funcionais

### 13.1. Fluxo de Lançamento de Despesa Parcelada

1. **Perfil:** Usuário autorizado no próprio escopo; regras futuras de conta compartilhada deverão validar a permissão de lançamento.
2. **Pré-condições:** Estar autenticado; existirem categorias de despesa e contas/cartões cadastrados e ativos.
3. **Passo a Passo:**
* O utilizador clica em "+ Nova Despesa" no Dashboard ou na tela de Transações;
* Seleciona a opção "Compra Parcelada" e informa a quantidade desejada de parcelas;
* Preenche descrição, valor total, categoria, meio de pagamento, cartão vinculado e data da primeira parcela;
* O sistema calcula o valor base de cada parcela. Eventual diferença de centavos é ajustada na primeira parcela;
* O backend valida o CSRF e os campos, gera um identificador único de grupo e cria atomicamente os lançamentos correspondentes às parcelas, calculando as datas de vencimento subsequentes;
* O usuário submete o formulário;
* Se a transação for em Cartão de Crédito, cada parcela é vinculada à sua respetiva fatura mensal (`credit_card_invoices`).


4. **Resultado:** As parcelas são criadas e visíveis no extrato futuro e nas faturas correspondentes.
5. **Auditoria:** A operação gera o registro de auditoria correspondente.

### 13.2. Fluxo de Transferência entre Contas

1. **Perfil:** Proprietário das duas contas no estado atual.
2. **Pré-condições:** Haver pelo menos duas contas bancárias ativas cadastradas.
3. **Passo a Passo:**
* O utilizador acessa o modal de "+ Transferência";
* Seleciona as contas de origem e destino;
* Informa o valor, a data e uma observação opcional;
* O sistema valida contas distintas, valor positivo, propriedade das duas contas e saldo suficiente na origem;
* O backend inicia uma transação PDO (`beginTransaction`);
* Cria o registro mestre na tabela `transfers`;
* Debita a conta de origem e credita a conta de destino pelo mesmo valor, sob a mesma transação atômica;
* Executa o `commit` da transação no banco de dados.


4. **Resultado:** Os saldos das contas são atualizados instantaneamente e o histórico de transferência fica registrado.
5. **Erros Possíveis:** Falha de banco (executa `rollBack`), contas idênticas (retorna erro de validação).

### 13.3. Fluxo de Fechamento e Pagamento de Fatura de Cartão

1. **Perfil:** Proprietário do cartão e da conta de pagamento.
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

1. **Perfil de Exclusão:** Cada usuário, inclusive `dev`, opera somente no próprio escopo.
2. **Perfil de Restauração:** Cada usuário restaura somente seus próprios registros.
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

* **Valores Monetários:** Devem ser estritamente positivos ($> 0.00$), com precisão de 2 casas e armazenamento em `NUMERIC(15,2)` no PostgreSQL.
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
6. **Isolamento de Dados:** Conta, categoria, meio de pagamento e cartão usados em um lançamento precisam pertencer ao usuário autenticado. A futura conta compartilhada deverá substituir essa regra por uma checagem explícita de participação e permissão.

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
* `AuthHelper::requireRole(...)`: valida perfis permitidos, com `dev` herdando permissões operacionais de `admin`.
* `AuthHelper::requireDev()`: protege usuários, auditoria, operações globais, migrations e GitHub Actions.

### 16.2. Regra de Isolamento por Usuário e Edição de Operador

* Ao tentar editar ou excluir uma transação financeira, o Controller e o Model restringem a ação ao proprietário autenticado. O perfil `dev` não é exceção para dados financeiros:
```php
if ($transaction->usuario_id !== $userId) {
    FlashHelper::set('error', 'Acesso negado: Você só pode alterar lançamentos criados por você.');
    LoggerHelper::security('UNAUTHORIZED_TRANSACTION_EDIT_ATTEMPT', ['trans_id' => $id]);
    $this->redirect('/transacoes');
}

```



---

## 17. Auditoria e Histórico

### 17.1. Escopo da Auditoria

A auditoria abrange entidades financeiras, cadastros, usuários e configurações. A tela global de consulta é exclusiva do `dev`; a autoria é preservada em `usuario_id`.

### 17.2. Estrutura de Armazenamento

Os dados anteriores e novos são serializados em JSON padrão:

* Os snapshots preservam os valores anteriores e posteriores à operação, sem alterar os dados históricos.

### 17.3. Exibição da Trilha de Auditoria

* O `dev` tem acesso à tela `/auditoria` para consultar todos os eventos com filtros por usuário, tabela e período; essa visão global de auditoria não torna globais as telas financeiras.
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

* **Restauração:** Todos os perfis restauram somente itens próprios. A restauração de transações recalcula os saldos associados.
* **Expurgo Definitivo (*Hard Delete*):** Todos os perfis expurgam somente registros próprios; o evento `PURGE` é auditado com cópia dos dados relevantes.

---

## 19. Logs

### 19.1. Log de Erros e Exceções

* **Erros Registrados:** Exceções não tratadas (`PDOException`, `Throwable`), erros de execução fatal, falhas de I/O em upload e falhas em consultas SQL.
* **Informações Gravadas:** Mensagem de erro, código, arquivo, linha, *stack trace* sanitizado, URL requisitada, método HTTP, IP e ID do usuário autenticado.
* **Mensagens Amigáveis:** O utilizador final nunca visualiza mensagens de erro brutas do banco de dados ou detalhes de código. O sistema exibe telas de erro genéricas e elegantes (ex: "Ocorreu um erro temporário ao processar sua solicitação. O incidente foi registrado para análise.").
* **Contingência:** Se a gravação no banco falhar, a Function usa `error_log()` na Vercel; no ambiente local/legado, `LoggerHelper` pode gravar em `storage/logs/error.log`:
`[YYYY-MM-DD HH:II:SS] [CRITICAL] [IP: 127.0.0.1] Mensagem: ... em Arquivo:Linha`

### 19.2. Log de Eventos de Segurança

* **Eventos Rastreados:** Falhas de autenticação (`LOGIN_FAILED`), tentativas de acesso a áreas restritas (`FORBIDDEN_ACCESS_ATTEMPT`), bloqueios por brute force (`IP_LOCKED_OUT`), tokens CSRF inválidos (`INVALID_CSRF_TOKEN`), tentativas de upload com arquivos maliciosos (`MALICIOUS_UPLOAD_ATTEMPT`).
* **Armazenamento:** Tabela `security_logs` e arquivo de contingência `storage/logs/security.log`.

---

## 20. Configurações Globais

### 20.1. Parâmetros Editáveis pelo Painel Administrativo

1. Preferências por usuário em `configuracoes_usuario`: símbolo da moeda, separadores e registros por página.
2. Configurações globais em `configuracoes`, exclusivas do `dev`: nome da aplicação, identidade visual, logotipo, status de migrations e integração com GitHub Actions.

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
        'url'      => getenv('DATABASE_URL') ?: '',
        'driver'   => getenv('DB_DRIVER') ?: 'mysql', // compatibilidade legado
        'host'     => getenv('DB_HOST') ?: 'localhost',
    ],
    'upload' => [
        'max_size'    => 2097152, // 2 MB em bytes
        'allowed_ext' => ['jpg', 'jpeg', 'png', 'webp'],
        'paths'       => [
            'avatars' => dirname(__DIR__) . '/storage/uploads/avatars/',
            'logos'   => dirname(__DIR__) . '/storage/uploads/logos/',
        ],
    ],
    'storage' => [
        'proxy_secret' => getenv('BLOB_PROXY_SECRET') ?: '',
        'proxy_url' => getenv('BLOB_PROXY_URL') ?: '',
    ],
    'dev' => [
        'nome' => getenv('SYSTEM_DEV_NAME') ?: '',
        'email' => getenv('SYSTEM_DEV_EMAIL') ?: '',
        'senha' => getenv('SYSTEM_DEV_PASSWORD') ?: '',
    ],
    'google_oauth' => [
        'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
        'redirect_uri' => getenv('GOOGLE_REDIRECT_URI') ?: '',
    ],
];

```

---

## 21. Uploads, Anexos e Arquivos

### 21.1. Escopo de Uploads

* **Permitido:** Avatares de usuários e Logotipo da empresa/sistema.
* **Não Permitido (Fora de Escopo):** Comprovantes de pagamento, notas fiscais, boletos em PDF e anexos em transações financeiras.

### 21.2. Diretrizes de Armazenamento e Segurança

* **Armazenamento Oficial:** Vercel Blob privado, por meio da Function `api/blob.js`, sem exposição do token no navegador. Os diretórios `storage/uploads/` permanecem somente como compatibilidade local.
* **Validação de Arquivo:**
1. Verificação do tamanho máximo de **2 MB** (`$_FILES['file']['size'] <= 2097152`);
2. Validação da extensão real (`pathinfo`) e validação rigorosa de MIME-Type através do `finfo_file` (aceitando apenas `image/jpeg`, `image/png`, `image/webp`);
3. Renomeação mandatória com hash randômico seguro:
`$novoNome = bin2hex(random_bytes(16)) . '.' . $extensao;`


* **Exclusão de Arquivos:** Ao atualizar avatar ou logotipo, o objeto anterior é removido pelo SDK do Blob para evitar arquivos órfãos.
* **Proteção:** URLs do Blob permanecem privadas; o PHP as lê por proxy e as entrega somente em rota autenticada.

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

* O núcleo financeiro é autocontido e não possui Open Finance, gateways de pagamento ou importação bancária.
* A operação usa integrações de infraestrutura: Vercel Blob privado, API GitHub Actions para migrations e Neon PostgreSQL.
* OAuth Google é uma integração planejada; as variáveis e colunas foram preparadas, mas não existem rotas ou fluxo OAuth ativos.

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
7. **Isolamento e IDOR:** Models e controllers devem validar `usuario_id` e propriedade de entidades relacionadas antes de leitura, alteração ou exclusão. A futura participação em conta deverá passar por validação de permissão no servidor.
8. **Segredos Serverless:** `BLOB_READ_WRITE_TOKEN`, `BLOB_PROXY_SECRET`, credenciais do Neon e `GITHUB_ACTIONS_TOKEN` ficam apenas no ambiente de execução ou Secrets do GitHub.



---

## 25. Organização Sugerida da Implementação

A implementação da aplicação pela IA codificadora deve seguir uma sequência modular, progressiva e testável:

1. **Etapa 1 a 13 - Fundação Financeira:**
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

14. **Etapa 14 - Multiusuário, Operação Serverless e Colaboração:**
* Migrations 012 a 016, isolamento por `usuario_id`, perfil `dev`, autocadastro e preferências individuais já foram implementados;
* OAuth Google, recuperação/verificação de e-mail e testes manuais completos permanecem pendentes;
* Antes de implementar contas compartilhadas, criar a especificação de convite, participação, histórico e permissões da seção 27.



---

## 26. Critérios de Aceitação Técnica e Funcional

Para que o sistema seja considerado homologado e pronto para entrega, os seguintes critérios devem ser integralmente atendidos:

* [ ] **Execução Serverless:** O deploy Vercel atende rotas, assets e headers; `api/index.php` não expõe arquivos internos.
* [ ] **Banco Oficial:** `DATABASE_URL` conecta ao Neon com `pdo_pgsql`, SSL e migrations executadas pelo workflow controlado.
* [ ] **Configuração:** Ausência de segredos versionados; variáveis de ambiente e Secrets do GitHub são suficientes para banco, Blob, `dev` e operação.
* [ ] **Proteção de Pastas Internas:** Arquivos em `config/`, `app/`, `database/` e `storage/logs/` são inacessíveis diretamente via navegador.
* [ ] **Migrations Automatizadas:** Toda a estrutura de banco de dados é criada e atualizada via migrations versionadas com controle anti-duplicação na tabela `migrations`.
* [ ] **Padrão MVC Desacoplado:** Separação estrita entre Models (PDO), Views (HTML/CSS/JS) e Controllers, com roteamento centralizado.
* [ ] **Design System Fiel:** A interface adere rigorosamente aos componentes, cores e tipografia do tema *Symo* definido no `docs/DESIGN.md`.
* [ ] **Autenticação e Segurança de Senhas:** Senhas criptografadas com BCRYPT, proteção de sessão com expiração em 60 minutos, e bloqueio de tentativas de força bruta.
* [ ] **Isolamento e RBAC:** `admin` e `dev` não leem ou mutam dados financeiros de terceiros; `dev` é obrigatório em usuários, auditoria e configurações globais; a futura colaboração será vinculada a uma conta e suas permissões.
* [ ] **Integridade nos Lançamentos e Parcelamentos:** Compras parceladas geram $N$ lançamentos consistentes, com centavos residuais ajustados na primeira parcela.
* [ ] **Atomicidade nas Transferências e Pagamentos:** Transferências entre contas e liquidações de fatura executam sob transações atômicas de banco (`BEGIN ... COMMIT/ROLLBACK`).
* [ ] **Soft Delete e Lixeira Operacional:** Exclusões lógicas alimentam a lixeira; restaurações reprocessam os saldos financeiros corretamente.
* [ ] **Auditoria Completa:** Alterações financeiras e cadastrais geram registros detalhados com snapshots em JSON anterior e posterior.
* [ ] **Logs de Contingência:** Falhas de banco são registradas por `error_log()` na Vercel e possuem fallback local quando aplicável.
* [ ] **Exportação CSV Funcional:** Download de relatórios em CSV compatível com Excel (UTF-8 com BOM e delimitador `;`).
* [ ] **Uploads Seguros:** Avatares e logotipos são validados por tamanho e MIME real, armazenados privadamente no Blob e entregues apenas por rotas autenticadas.
* [ ] **Sem Recursos Fora de Escopo:** Ausência de códigos ou telas para itens confirmados fora de escopo (sem anexos de comprovantes, sem metas, sem APIs bancárias, sem OFX).

---

## 27. Pontos Pendentes e Decisões Futuras

1. **OAuth Google:** Implementar autorização, callback, validação de identidade, vínculo por `google_id`, login e criação de conta com dados padrão.
2. **E-mail:** Definir provedor transacional, verificação de endereço e token de recuperação de senha. Nenhum desses fluxos deve ser anunciado como entregue antes de testes completos.
3. **Contas Compartilhadas:** Criar convite por e-mail, aceite/recusa e revogação. O vínculo e as permissões serão por conta, não por papel global do usuário.
4. **Catálogo Global de Meios de Pagamento (Fase 17):** Um catálogo único substituirá os cadastros por usuário. O `dev` poderá criar opções e alterar sua disponibilidade global; usuários autenticados poderão ativar/inativar cada opção apenas para si. Opções globalmente ou individualmente inativas não estarão disponíveis em novos lançamentos, mas as referências e a exibição histórica serão preservadas. O catálogo inicial terá Dinheiro, Pix, Cartão de Débito, Cartão de Crédito, Boleto Bancário e Transferência Bancária. A migration deverá validar equivalências e preservar todos os vínculos.
5. **Regras de Colaboração (Fase 18):** Categorias, cartões, faturas e preferências permanecem pessoais. Transferências permanecem restritas a contas do mesmo proprietário, inclusive após o compartilhamento.
6. **Compatibilidade MySQL:** Manter somente enquanto houver ambientes legados; toda nova funcionalidade deve ser testada primeiro no PostgreSQL/Neon.

### 27.1. Critérios Funcionais para Contas Compartilhadas

1. **Convite:** O proprietário informa o e-mail do participante e seleciona as permissões antes de enviar. O convite não pode conceder acesso a uma conta inexistente, inativa ou excluída. O acesso será um vínculo com a conta, nunca um papel global do usuário.
2. **Identidade do Participante:** O convite deverá vincular um usuário existente ou permanecer pendente até que a pessoa se cadastre com o mesmo e-mail. A aceitação jamais poderá criar acesso para e-mail diferente do convidado.
3. **Aceite e Recusa:** O participante deve aceitar ou recusar explicitamente. Enquanto pendente, não pode visualizar saldo, histórico ou metadados financeiros da conta.
4. **Leitura:** A permissão `visualizar` concede acesso ao saldo, aos lançamentos autorizados e às informações mínimas da conta. Sem ela, nenhuma rota, JSON ou agregação poderá revelar a existência da conta.
5. **Histórico:** O participante verá somente lançamentos criados após o aceite do compartilhamento, sempre no escopo da conta compartilhada. Lançamentos anteriores não ficam visíveis.
6. **Lançamento:** A permissão de lançamento permite criar movimentações na conta compartilhada. O registro conserva o `usuario_id` de quem o criou.
7. **Permissões de escrita:** O proprietário poderá conceder visualização, lançamento com edição/exclusão dos registros da conta, ou lançamento com edição/exclusão somente dos registros criados pelo participante.
8. **Administração:** Somente o proprietário convida, altera permissões e revoga acesso. Participantes não podem delegar acesso nem habilitar leitura de lançamentos anteriores ao aceite.
9. **Revogação:** Ao revogar, o sistema deve encerrar o acesso imediatamente em consultas, dashboard, relatórios e ações mutáveis. Os lançamentos anteriores permanecem com autoria e auditoria preservadas.
10. **Lixeira e Auditoria:** A restauração ou o expurgo de um lançamento compartilhado deve respeitar o proprietário e a autoria. A auditoria deve registrar conta, ator, ação e contexto de participação.

### 27.2. Critérios Técnicos para Contas Compartilhadas

* Toda query que hoje usa `usuario_id = :usuario_id` deverá aceitar o proprietário ou participante ativo somente quando houver vínculo e permissão para a conta específica.
* A política de autorização precisa residir no backend. Ocultar botões na View não substitui a validação em controller/model.
* Consultas agregadas de dashboard, relatórios, faturas, pendências e lixeira devem receber o escopo de visibilidade de forma consistente, para não produzir totais que revelem dados indevidos.
* A migração da tabela de participantes deve possuir índices por `conta_id`, `usuario_id`, estado do convite e `revogado_em`; deve impedir duas participações ativas duplicadas para o mesmo usuário e conta.
* A implementação deve incluir testes de IDOR para leitura, criação, atualização, exclusão, transferência, exportação CSV e restauração de dados compartilhados.
* Cartões de crédito, faturas, categorias e preferências não ganham compartilhamento implícito.

### 27.3. Critérios Funcionais para o Catálogo Global de Meios de Pagamento

1. O catálogo é único para todos os usuários e começa com Dinheiro, Pix, Cartão de Débito, Cartão de Crédito, Boleto Bancário e Transferência Bancária.
2. Somente o perfil `dev` pode criar opções e alterar sua disponibilidade global. Inativar altera `status` para `inativo`; reativar altera para `ativo`, sem exclusão física.
3. Cada usuário autenticado, inclusive `dev`, pode ativar ou inativar opções somente para si; essa preferência não altera a disponibilidade para os demais usuários.
4. Opções inativadas globalmente ou pelo usuário não são oferecidas em novos lançamentos daquele escopo.
5. Lançamentos existentes continuam exibindo o meio associado após inativação global ou individual. Ao editar um lançamento antigo, o vínculo original deve ser preservado mesmo que a opção esteja indisponível para novos lançamentos.
6. Usuários comuns não podem criar opções, alterar atributos globais nem mudar a disponibilidade global.
7. A consolidação deve verificar as opções existentes, preservar todos os vínculos de transações e impedir a exclusão física de qualquer opção referenciada.

### 27.4. Critérios Técnicos para o Catálogo Global de Meios de Pagamento

* O status global da opção e a preferência individual de disponibilidade devem ser armazenados separadamente.
* A autorização para criar e alterar o status global é exclusiva do `dev` e validada no backend; a preferência individual deve ser sempre limitada ao usuário autenticado.
* A migration deve remapear todas as referências de `transacoes.meio_pagamento_id` para opções canônicas equivalentes antes de remover duplicatas; deve validar a integridade referencial e a equivalência de nome/tipo, incluindo transações logicamente excluídas, e abortar integralmente diante de divergências. Implementar primeiro para PostgreSQL/Neon, considerando também o MySQL legado enquanto suportado.
* Logs existentes de `auditoria_logs` são históricos e não devem ser reescritos nem apagados durante a consolidação; snapshots e IDs auditados permanecem como registrados.
* Os dados padrão para novos usuários não devem mais clonar linhas de meios de pagamento por usuário.
* Testes devem cobrir isolamento das preferências entre usuários, autorização do `dev`, seleção para novos lançamentos e exibição/edição de lançamentos históricos.

---

## 28. Conclusão

Este Documento de Especificação Funcional e Técnica consolida regras de negócio, arquitetura, modelo de dados, fluxos, padrões visuais e critérios de qualidade do **Symo Finance** sem confundir o que está entregue com a visão futura.

O documento orienta manutenção e evolução do código atual. Recursos assinalados como planejados exigem especificação complementar e critérios de aceite antes de implementação.

### Documentos Oficiais para a Fase de Codificação:

1. **`docs/FSD.md`** (Este documento de especificação funcional e técnica);
2. **`docs/DESIGN.md`** (Design System e guia de estilos visuais da interface).