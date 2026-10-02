# Contexto do Projeto para Agentes de IA — Symo Finance

Este arquivo define o contexto técnico, as regras de segurança, os limites de produto e o protocolo de trabalho para agentes que atuem neste repositório. O documento deve permanecer coerente com o `docs/FSD.md`; quando uma alteração no sistema mudar alguma regra descrita aqui, o próprio `AGENTS.md` deve ser atualizado na mesma entrega.

---

## 1. Idioma e Comunicação

- **Idioma obrigatório:** responder e documentar sempre em português do Brasil.
- Código, variáveis, nomes de funções e mensagens de negócio devem seguir o idioma e o padrão já adotados no código. Preserve nomes técnicos e nomes físicos de tabelas existentes quando a compatibilidade exigir.
- Não apresentar como entregue um recurso descrito no FSD como roadmap. Em especial, OAuth Google, recuperação/verificação de e-mail e contas compartilhadas ainda não estão implementados.

---

## 2. Visão Atual do Produto

O **Symo Finance** é uma plataforma web de gestão financeira pessoal para pessoas, casais, famílias, freelancers e MEIs.

- Cada usuário possui uma conta financeira individual e privada por padrão.
- Contas, categorias, cartões, transações, transferências, relatórios, preferências e lixeira permanecem isolados por usuário. Meios de pagamento estão em transição do cadastro por usuário para catálogo global; a aplicação da migration 018 deve preceder a ativação da nova versão em produção.
- O cadastro público cria um usuário `admin` da própria conta, com categorias e preferências padrão; meios de pagamento vêm do catálogo global e a disponibilidade inicial é ativa.
- O perfil `dev` é um usuário técnico criado pelo seed, responsável pela operação global da plataforma. Como usuário financeiro, mantém escopo próprio por `usuario_id`, igual ao `admin`.
- O perfil `operador` permanece no código por compatibilidade, não é usado no produto atual e não será um papel global no modelo futuro.
- A visão futura contempla compartilhar contas específicas por vínculo de permissões à conta. O participante verá apenas lançamentos posteriores ao aceite; esse recurso não existe no estado atual.

O sistema não é um orçamento global compartilhado por padrão, não é um banco, não é um sistema contábil formal e não possui integração bancária automática.

O documento funcional completo e vinculante é o [docs/FSD.md](docs/FSD.md). O design visual oficial está em [docs/DESIGN.md](docs/DESIGN.md).

---

## 3. Stack Oficial e Compatibilidade

### 3.1. Aplicação

- PHP 8.1+ nativo, com `declare(strict_types=1);` e sem framework pesado.
- MVC desacoplado com Front Controller em `index.php`.
- Roteamento centralizado por `app/Helpers/Router.php`.
- PDO com statements preparados.
- HTML5, CSS3, JavaScript Vanilla, Bootstrap 5.3, Bootstrap Icons e Chart.js, todos os assets de interface armazenados em `public/`.

### 3.2. Ambiente oficial

- **Execução HTTP:** Vercel com runtime `vercel-php`.
- **Banco:** Neon PostgreSQL, acessado por `DATABASE_URL` com SSL obrigatório.
- **Arquivos privados:** Vercel Blob, acessado pelo proxy `api/blob.js` usando `@vercel/blob`.
- **Migrations remotas:** GitHub Actions por meio de `.github/workflows/database-migrations.yml`.
- **Assets:** `api/index.php` entrega assets permitidos quando necessário; o rewrite da Vercel não deve capturar `/public/` e `/api/` indevidamente.

### 3.3. Compatibilidade legada

- MySQL/MariaDB e variáveis `DB_*` são suportados apenas para instalações antigas quando `DATABASE_URL` não estiver definida.
- XAMPP, Apache, Hostnet, cPanel e filesystem local não são o caminho oficial de produção nem o ambiente recomendado para novas funcionalidades.
- Toda nova alteração de persistência deve ser pensada e validada primeiro para PostgreSQL/Neon. Se mantiver compatibilidade MySQL, implemente explicitamente os dois dialetos.

---

## 4. Configuração e Segredos

- A fonte de configuração da aplicação é `config/config.php`, que lê variáveis de ambiente. Não criar nem versionar arquivo `.env`.
- Em produção, segredos ficam nas Environment Variables da Vercel ou nos Secrets do GitHub Actions.
- Nunca gravar, imprimir, retornar em JSON ou inserir em HTML:
  - `DATABASE_URL` e credenciais `DB_*`;
  - `BLOB_READ_WRITE_TOKEN` e `BLOB_PROXY_SECRET`;
  - `GITHUB_ACTIONS_TOKEN`;
  - `GOOGLE_CLIENT_SECRET`;
  - `SYSTEM_DEV_PASSWORD`.
- Variáveis principais:
  - `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_BASE_URL`, `APP_TIMEZONE`, `APP_SESSION_TTL`;
  - `DATABASE_URL` para Neon/PostgreSQL;
  - `SYSTEM_DEV_NAME`, `SYSTEM_DEV_EMAIL`, `SYSTEM_DEV_PASSWORD` para o seed do `dev`;
  - `BLOB_READ_WRITE_TOKEN`, `BLOB_PROXY_SECRET`, `BLOB_PROXY_URL` e `BLOB_STORE_ID`/`symo_finance_STORE_ID` para Blob;
  - `GITHUB_ACTIONS_TOKEN` para o painel operacional do `dev`;
  - `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` reservadas para OAuth futuro.
- Não incluir valores reais dessas variáveis em documentação, testes, logs ou mensagens de erro.

---

## 5. Ambientes e Comandos

### 5.1. Desenvolvimento recomendado

Use PHP 8.2+ com `pdo_pgsql`, PostgreSQL acessível por `DATABASE_URL` e o servidor embutido:

```bash
php -S localhost:8000 index.php
```

Extensões esperadas: `pdo`, `pdo_pgsql`, `mbstring`, `fileinfo`, `json`, `session` e `curl`.

### 5.2. Migrations

```bash
php migrate.php --status
php migrate.php
php migrate.php --seed
```

- Produção e Neon devem usar o workflow manual do GitHub Actions.
- `php migrate.php --fresh --confirmar` apaga o schema PostgreSQL conectado e é reservado para ambientes descartáveis.
- No workflow, `fresh` exige a confirmação exata `RESETAR`.
- Nunca executar `fresh` em produção sem autorização explícita do usuário.

### 5.3. Validações úteis

```bash
php -l caminho/para/arquivo.php
git diff --check
```

Quando o PHP não estiver no `PATH` do Windows, use o executável configurado no ambiente local. Não alterar o código apenas para contornar uma ausência local do executável.

---

## 6. Estrutura Atual do Repositório

```text
├── api/
│   ├── index.php                # Adaptador Vercel PHP e entrega de assets
│   └── blob.js                  # Proxy privado do Vercel Blob
├── app/
│   ├── Controllers/             # Fluxos HTTP e autorização de ações
│   ├── Models/                  # Persistência e regras financeiras
│   ├── Helpers/                 # Auth, CSRF, sessão, logs, Blob e GitHub
│   └── Views/                   # Telas MVC
├── config/
│   ├── config.php               # Leitura das configurações
│   └── database.php             # Conexão PDO PostgreSQL/MySQL legado
├── database/
│   ├── migrations/              # Migrations 001 a 016
│   └── seeds/                   # Seed do dev e configurações globais
├── public/                      # CSS, JS, fontes e imagens públicas
├── storage/                     # Fallback legado de logs/uploads locais
├── .github/workflows/           # Migrations remotas
├── index.php                    # Front Controller
├── migrate.php                  # CLI de migrations e seeds
├── vercel.json                  # Runtime, rewrites e headers
├── package.json                 # @vercel/blob
├── README.md
├── docs/
└── AGENTS.md
```

---

## 7. Regras de Produto e Escopo

- Funcionalidades atuais: autocadastro, login local, dashboard, contas, categorias, meios de pagamento, transações, parcelamentos, transferências, cartões, faturas, relatórios CSV, perfil, lixeira, auditoria, configurações, Blob privado e feedback beta.
- OAuth Google possui preparação de configuração e banco, mas não possui fluxo ativo.
- A tela de esqueci a senha existe, mas não há recuperação por e-mail implementada.
- Contas compartilhadas são roadmap. Não criar tabelas, rotas ou telas desse recurso sem especificação complementar e aprovação.
- A Fase 17 implementa o catálogo global de meios de pagamento: somente `dev` cria, edita e arquiva opções globalmente; qualquer usuário autenticado, inclusive `dev`, ativa/inativa opções apenas para si. Arquivamento é lógico, não remove vínculos históricos e impede novos usos. A migration remapeia as transações antes de remover duplicatas, valida a integridade e preserva a auditoria. Executar o workflow oficial da migration antes de ativar o código em produção. A Fase 18, compartilhamento por conta, continua no roadmap; categorias, cartões, faturas e preferências continuam pessoais.
- Fora de escopo: Open Finance, OFX, importação bancária, boletos, gateways de pagamento, anexos de comprovantes, metas orçamentárias, recorrência infinita, conversão cambial em tempo real, PDF nativo e notificações de vencimento por e-mail.

---

## 8. Regras de Segurança e Dados

1. **SQL Injection:** usar PDO com statements preparados e parâmetros vinculados. Não concatenar valores de usuário em SQL. Identificadores estruturais usados em migrations ou `ORDER BY` devem vir de listas internas controladas.
2. **XSS:** toda variável renderizada em View HTML deve passar por `htmlspecialchars($valor, ENT_QUOTES, 'UTF-8')`, salvo conteúdo explicitamente sanitizado e documentado.
3. **CSRF:** todo formulário mutável usa token gerado na sessão e validado no Controller. Logout é `POST` protegido.
4. **Senhas:** usar `password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12])` e `password_verify()`. Nunca registrar senha ou token em texto puro.
5. **Sessão:** cookies `HttpOnly`, `SameSite=Lax`, `use_only_cookies=1` e `Secure` sob HTTPS; executar `session_regenerate_id(true)` após login; expiração padrão de 60 minutos.
6. **Força bruta:** cinco falhas de login em 15 minutos por e-mail/IP geram bloqueio temporário e evento em `seguranca_logs`.
7. **Isolamento:** toda leitura, alteração, exclusão e associação financeira deve validar o `usuario_id` autenticado. Não confiar somente na ocultação de menus ou botões.
8. **RBAC:**
  - `admin` e `dev` administram somente seus próprios dados financeiros e preferências;
  - `operador` é legado, sem uso no produto atual; o compartilhamento futuro será autorizado por conta, não por papel global;
  - `dev` é obrigatório para gestão de usuários, auditoria global, configurações globais, migrations e GitHub Actions, sem ampliar o escopo financeiro próprio.
9. **IDOR:** validar propriedade de contas, categorias, meios, cartões, faturas e transações relacionadas antes de qualquer operação.
10. **Arquivos privados:** Blob é privado; tokens nunca vão para o navegador; avatar e logotipo são entregues por rotas autenticadas.
11. **Mensagens:** telas públicas não exibem stack trace, SQL, credenciais, URLs privadas ou detalhes de infraestrutura.

### Política para Documentos Vivos

Trate `docs/FSD.md`, `docs/PLANO.md`, `docs/STATUS.md` e `docs/ERROS.md` como documentos públicos, mesmo enquanto o repositório for privado.

- Nunca registrar dados reais ou derivados do banco ou de usuários, ainda que pareçam indiretos: identificadores, nomes ou e-mails de pessoas/registros; volumes, contagens, distribuições, mapeamentos, vínculos, estados ou datas de registros; exemplos, resultados de consultas, screenshots ou dados de testes baseados no ambiente. Nomes fixos de funcionalidades e opções padronizadas do produto podem ser documentados como regras, sem associação a registros reais.
- Nunca registrar segredos, valores de configuração, endereços de serviços privados, detalhes operacionais destrutivos, vulnerabilidades exploráveis ou outros detalhes técnicos que possam facilitar acesso, enumeração, alteração ou indisponibilidade do sistema.
- Documentar somente regras funcionais e critérios técnicos abstratos necessários ao produto. Para descrever migrações, usar invariantes genéricas (por exemplo, preservar vínculos, validar integridade e abortar em caso de divergência), sem IDs, volumes, amostras ou resultados de ambiente.
- Antes de concluir qualquer alteração documental, revisar os quatro arquivos por exposição direta e indireta; generalizar ou remover o conteúdo sensível. Não copiar saídas de terminal, consultas, logs ou dados de usuários para eles.

---

## 9. Proteção de Arquivos e Assets

- Arquivos PHP internos carregados pelo Front Controller devem manter a guarda:

```php
defined('APP_RUNNING') or die('Acesso direto proibido.');
```

- `api/index.php` é um entrypoint da Vercel e possui fluxo próprio; não aplicar a guarda de forma que impeça o runtime de iniciá-lo.
- Em ambientes Apache legados, `config/`, `app/`, `database/` e `storage/logs/` devem permanecer bloqueados por `.htaccess`.
- O rewrite da Vercel deve excluir `/api/` e assets públicos. `api/index.php` deve aceitar somente extensões e caminhos públicos permitidos, validar `realpath` e MIME antes de `readfile`.
- Não adicionar arquivos executáveis em `public/`. Assets públicos devem ser locais, sem CDN em runtime.
- O filesystem da Vercel Function não é armazenamento persistente. Não depender de gravações locais para dados de negócio, uploads ou logs.

---

## 10. Uploads e Vercel Blob

- Escopo atual: avatar de usuário e logotipo da aplicação. Comprovantes e anexos de transações continuam fora de escopo.
- Limite padrão: 2 MB.
- Validar MIME real com `finfo_file`, extensão coerente e conteúdo permitido. JPEG, PNG e WebP são aceitos; SVG só pode ser aceito quando sanitizado pelo fluxo específico já implementado.
- Gerar nomes com `bin2hex(random_bytes(16))` ou equivalente seguro. Nunca usar nome original como caminho final.
- Upload, download e exclusão passam pelo `api/blob.js` e SDK oficial `@vercel/blob`.
- O PHP guarda a URL retornada pelo Blob no banco, mas não expõe token. Ao substituir arquivo, remover o objeto anterior quando possível.
- `storage/uploads/` é compatibilidade local; não tratá-lo como armazenamento oficial em Vercel.

---

## 11. Banco de Dados e Migrations

- Tabelas atuais: `usuarios`, `contas`, `categorias`, `meios_pagamento`, `transacoes`, `transferencias`, `cartoes_credito`, `faturas_cartao`, `auditoria_logs`, `seguranca_logs`, `erros_logs`, `configuracoes`, `migracoes`, `sessoes`, `configuracoes_usuario` e `feedback`.
- Migrations 013 a 016 introduzem `dev`, `google_id`, senha anulável para OAuth futuro, `usuario_id` nas entidades financeiras, preferências por usuário e feedback. A migration 018 globaliza o catálogo de meios de pagamento e cria disponibilidade individual.
- PostgreSQL é o banco oficial. Ao escrever SQL compatível, verificar diferenças de `NOW()`, `CURRENT_DATE`, `EXTRACT`, tipos identity, `RETURNING`, `INTERVAL`, `ENUM/CHECK`, quoting e `lastInsertId()`.
- A classe `SymoPostgresConnection` normaliza somente compatibilidades conhecidas; não usar essa normalização como justificativa para escrever SQL ambíguo.
- Migrations destrutivas ou que assumam tabelas vazias devem documentar essa condição e exigir fluxo `fresh` autorizado.
- Seeds não devem recriar usuários ou dados padrão duplicados. O usuário `dev` depende de `SYSTEM_DEV_*`.
- Toda nova tabela financeira deve possuir estratégia explícita de isolamento por usuário e índices de escopo.

---

## 12. Logs, Auditoria e Operação

- Erros de aplicação são registrados em `erros_logs` quando o banco está disponível.
- Na Vercel, a contingência principal é `error_log()`; em execução local/legada, pode usar `storage/logs/error.log` e `security.log`.
- Segurança registra falhas de login, bloqueios, CSRF inválido, acesso 403 e tentativas de acesso indevido.
- Auditoria registra ação, tabela, registro, usuário, IP, timestamp e valores anterior/novo quando aplicável.
- Não registrar senhas, tokens, secrets, conteúdo integral de credenciais ou URLs privadas.
- Migrations de produção devem ser executadas pelo workflow do GitHub Actions, não diretamente durante uma requisição web. O painel apenas dispara ações autorizadas e exibe status/logs sem expor o token.

---

## 13. Design e Frontend

- Seguir `docs/DESIGN.md` e os tokens já presentes em `public/css/custom.css`.
- Usar Inter, numerais tabulares em valores financeiros, Electric Violet, Deep Lavender, Bright Teal e neutros definidos no design system.
- Manter responsividade para desktop, tablet e smartphone.
- Usar Bootstrap e Bootstrap Icons locais; Chart.js local para gráficos.
- Preservar a distinção visual entre dados pessoais, estados de sucesso/erro, pendências, faturas e ações administrativas.
- Não introduzir CDN, biblioteca frontend adicional ou mudança de tokens sem necessidade e sem atualizar a documentação de design.

---

## 14. Protocolo dos Arquivos Vivos

### Alinhamento de ideias de desenvolvimento

- Quando o usuário desenvolvedor trouxer ideias ou necessidades em uma conversa de alinhamento, primeiro esclarecer objetivo, escopo e critérios de aceite antes de iniciar a implementação.
- Depois que a necessidade estiver alinhada, sugerir registrá-la em `docs/PLANO.md`, que continua sendo a referência para organizar as próximas evoluções mesmo após as fases iniciais.
- Incluir a funcionalidade no plano quando o usuário confirmar ou já solicitar explicitamente seu registro/execução; preservar prioridades e dependências acordadas.
- Ao iniciar uma demanda planejada, consultar o plano junto aos demais arquivos vivos e atualizar seu estado real ao concluir.

### Antes de qualquer trabalho

1. Ler `docs/FSD.md`.
2. Ler `docs/DESIGN.md`.
3. Ler `docs/INSUMOS.md`.
4. Ler `docs/PLANO.md`.
5. Ler `docs/STATUS.md`.
6. Ler `docs/ERROS.md`.
7. Ler este `AGENTS.md` e verificar se o escopo do trabalho exige atualizá-lo.

Use caminhos relativos à raiz do projeto nas documentações. Não usar links `file:///`, caminhos absolutos da máquina ou credenciais reais.

### Durante o trabalho

- Começar pelo arquivo, símbolo, rota, teste ou erro mais próximo do comportamento solicitado.
- Formular uma hipótese local verificável antes da primeira edição.
- Fazer a menor alteração coerente com os padrões existentes.
- Não reverter alterações do usuário nem corrigir problemas não relacionados.
- Depois da primeira edição substantiva, executar imediatamente a validação mais estreita disponível.
- Para mudanças de banco, validar tanto o dialeto oficial PostgreSQL quanto a compatibilidade MySQL quando ela for mantida.

### Ao finalizar

1. Atualizar `docs/STATUS.md` com o estado real e tarefas concluídas.
2. Atualizar este `AGENTS.md` se a alteração mudou stack, arquitetura, segurança, comandos, escopo, estrutura ou convenções para agentes.
3. Registrar em `docs/ERROS.md` somente erros, incompatibilidades ou bloqueios encontrados e resolvidos.
4. Executar validações adequadas e informar exatamente o que foi executado.
5. Responder ao usuário em português do Brasil, com resumo objetivo, arquivos afetados e limitações restantes.

---

## 15. Checklist de Entrega para Agentes

- [ ] Mudança alinhada ao FSD aprovado.
- [ ] Dados financeiros continuam isolados por usuário.
- [ ] RBAC validado no backend, não apenas na View.
- [ ] CSRF, escape HTML e prepared statements preservados.
- [ ] Nenhum segredo ou token exposto.
- [ ] Compatibilidade PostgreSQL/Neon considerada.
- [ ] Assets continuam locais e uploads continuam privados.
- [ ] Teste ou validação executado após a edição.
- [ ] `STATUS.md` atualizado.
- [ ] `AGENTS.md` atualizado caso suas próprias regras tenham mudado.
- [ ] `ERROS.md` atualizado somente se houve incidente relevante.