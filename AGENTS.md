# Contexto do Projeto para Agentes de IA — Symo Finance (Modo Manutenção)

Este arquivo define o contexto técnico, as regras de segurança, os limites de produto e o protocolo de trabalho obrigatório para agentes de Inteligência Artificial que atuem neste repositório em **modo de manutenção e evolução contínua**.

O documento deve permanecer rigorosamente coerente com o `docs/FSD.md` e o `docs/MANUTENCAO.md`. Quando uma alteração no sistema mudar alguma regra ou diretriz descrita aqui, o próprio `AGENTS.md` deve ser atualizado na mesma entrega.

---

## 1. Idioma e Comunicação

- **Idioma obrigatório:** responder e documentar sempre em **português do Brasil**.
- Código, variáveis, nomes de funções, comentários e mensagens de negócio devem seguir o idioma e os padrões já adotados no código. Preserve nomes técnicos e nomes físicos de tabelas existentes quando a compatibilidade exigir.
- Não apresentar como entregue um recurso descrito no FSD como roadmap. Em especial, OAuth Google (Fase 14), recuperação/verificação de e-mail e contas compartilhadas (Fase 18) são itens de roadmap e ainda não estão implementados.

---

## 2. Visão Atual do Produto

O **Symo Finance** é uma plataforma web de gestão financeira pessoal para pessoas, casais, famílias, freelancers e MEIs.

- Cada usuário possui uma conta financeira individual e privada por padrão.
- Contas, categorias, cartões, transações, transferências, relatórios, preferências e lixeira permanecem isolados por usuário.
- O Saldo Atual inclui contas ativas marcadas para inclusão, exceto contas do tipo `investimento`, que ficam sempre fora e são exibidas separadamente no resumo de Contas. A aba Investimentos (menu Movimentações) registra posições (aplicações/reservas) que não são contas bancárias operacionais e ficam fora do Saldo Atual.
- Em despesas, o meio de pagamento define a modalidade: crédito exige cartão ativo e associa a compra à fatura do ciclo; outros meios exigem conta bancária. A data informada é a data da compra; parcelas preservam seu dia mensal quando possível; faturas fecham automaticamente após a fronteira de fechamento; e seu pagamento integral debita a conta escolhida uma única vez. Exceder o limite gera alerta, mas não bloqueia a compra.
- O cadastro público cria um usuário `admin` da própria conta, com categorias e preferências padrão clonadas; meios de pagamento vêm do catálogo global e a disponibilidade inicial é ativa.
- O perfil `dev` é um usuário técnico criado pelo seed, responsável pela operação global da plataforma (gestão de usuários, auditoria global, status/migrations via GitHub Actions, catálogo global de meios de pagamento e feedbacks). Como usuário financeiro, mantém escopo próprio por `usuario_id`, idêntico ao `admin`.
- O perfil `operador` permanece no código por compatibilidade legada, sem uso no produto atual.
- A visão futura contempla compartilhar contas específicas por vínculo de permissões à conta (Fase 18 — roadmap). O participante verá apenas lançamentos posteriores ao aceite; esse recurso não existe no estado atual.

O documento funcional completo e vinculante é o [docs/FSD.md](docs/FSD.md). O guia de manutenção é o [docs/MANUTENCAO.md](docs/MANUTENCAO.md). O design visual oficial está em [docs/DESIGN.md](docs/DESIGN.md).

---

## 3. Stack Oficial e Arquitetura

### 3.1. Aplicação
- **PHP 8.1+ nativo** (desenvolvimento e CI em PHP 8.2), com `declare(strict_types=1);` e sem framework pesado (sem Laravel, Symfony, etc.).
- **Padrão arquitetural:** MVC desacoplado com Front Controller em `index.php`.
- **Roteamento:** Centralizado por `app/Helpers/Router.php`.
- **Persistência:** PDO com *Prepared Statements* obrigatórios.
- **Frontend:** HTML5, CSS3, JavaScript Vanilla (ES6+), Bootstrap 5.3 local, Bootstrap Icons local e Chart.js 4.x local, todos armazenados em `public/`. Sem CDNs externas em runtime.

### 3.2. Ambiente Oficial
- **Execução HTTP:** Vercel com runtime `vercel-php`.
- **Banco:** Neon PostgreSQL, acessado por `DATABASE_URL` com SSL obrigatório.
- **Arquivos privados:** Vercel Blob, acessado pelo proxy privado `api/blob.js` usando `@vercel/blob` e servido por rotas autenticadas do PHP.
- **Migrations remotas:** GitHub Actions por meio de `.github/workflows/database-migrations.yml`.
- **Assets:** `api/index.php` entrega assets permitidos na Vercel; o servidor embutido do PHP (`cli-server`) entrega assets locais a partir de `public/`.

### 3.3. Compatibilidade Legada
- MySQL/MariaDB e variáveis `DB_*` são suportados apenas para instalações antigas quando `DATABASE_URL` não estiver definida.
- Toda nova alteração de persistência deve ser pensada e validada primeiro para PostgreSQL/Neon. Se mantiver compatibilidade MySQL, implemente explicitamente os dois dialetos.

---

## 4. Configuração e Segredos

- A fonte única de configuração da aplicação é `config/config.php`, que lê variáveis de ambiente. **Não criar nem versionar arquivo `.env`**.
- Em produção, segredos ficam nas Environment Variables da Vercel ou nos Secrets do GitHub Actions.
- Localmente, o script `load-env.ps1` (ignorado pelo Git) centraliza as variáveis do desenvolvedor.
- **Nunca gravar, imprimir, retornar em JSON ou inserir em HTML:**
  - `DATABASE_URL` e credenciais `DB_*`;
  - `BLOB_READ_WRITE_TOKEN` e `BLOB_PROXY_SECRET`;
  - `GITHUB_ACTIONS_TOKEN`;
  - `GOOGLE_CLIENT_SECRET`;
  - `SYSTEM_DEV_PASSWORD`.
- Não incluir valores reais dessas variáveis em documentação, testes, logs ou mensagens de erro.

---

## 5. Ambientes e Comandos

### 5.1. Desenvolvimento Local
Use PHP 8.2+ com `pdo_pgsql`, PostgreSQL acessível por `DATABASE_URL` e o servidor embutido:
```powershell
.\load-env.ps1 -Run
# ou:
php -S localhost:8000 index.php
```

### 5.2. Migrations e Seeds
```powershell
php migrate.php --status
php migrate.php
php migrate.php --seed
```
- Produção e Neon devem usar o workflow manual do GitHub Actions.
- `php migrate.php --fresh --confirmar` apaga o schema PostgreSQL conectado e é reservado exclusivamente para ambientes locais descartáveis. **Nunca executar `fresh` em produção.**

### 5.3. Validações Úteis
```powershell
php -l caminho/para/arquivo.php
git diff --check
```

---

## 6. Estrutura Atual do Repositório

```text
├── api/
│   ├── index.php                # Adaptador Vercel PHP e entrega de assets
│   └── blob.js                  # Proxy privado do Vercel Blob (@vercel/blob)
├── app/
│   ├── Controllers/             # Fluxos HTTP, validação e autorização de ações
│   ├── Models/                  # Persistência PDO, escopo por usuário e regras financeiras
│   ├── Helpers/                 # Auth, CSRF, sessão, logs, Blob, GitHub API, calendário
│   └── Views/                   # Telas e layouts MVC
├── config/
│   ├── config.php               # Consolidação e leitura das configurações
│   └── database.php             # Conexão PDO PostgreSQL/MySQL legado (SymoPostgresConnection)
├── database/
│   ├── migrations/              # Migrations versionadas 001 a 020+
│   └── seeds/                   # Seed do dev e configurações globais
├── docs/                        # Documentação viva e especificações do sistema
│   ├── FSD.md                   # Especificação Funcional e Técnica
│   ├── DESIGN.md                # Diretrizes visuais, tokens e componentes
│   ├── MANUTENCAO.md            # Guia técnico completo de manutenção e evolução
│   ├── COMO-PEDIR-MUDANCAS.md   # Guia de interação e modelos de prompts para usuários
│   ├── PLANO.md                 # Histórico de planejamento de fases
│   ├── STATUS.md                # Acompanhamento do progresso das fases
│   ├── ERROS.md                 # Registro histórico de erros e soluções
│   ├── code.html                # Protótipo estático fiel ao Dashboard atual
│   └── screenshots/             # Capturas de tela oficiais do sistema (dados demo)
├── public/                      # CSS, JS, fontes e imagens públicas locais
├── storage/                     # Fallback legado de logs e uploads locais
├── .github/workflows/           # Migrations remotas via GitHub Actions
├── index.php                    # Front Controller
├── migrate.php                  # CLI de migrations e seeds
├── vercel.json                  # Runtime, rewrites e headers da Vercel
├── .vercelignore                # Exclusão de documentação e scripts locais no deploy Vercel
├── package.json                 # Dependência @vercel/blob
├── CHANGELOG.md                 # Histórico oficial de versões, melhorias contínuas e correções (SemVer)
├── LICENSE                      # Licença GNU General Public License v3.0 (GPL-3.0)
├── README.md                    # Apresentação do repositório
└── AGENTS.md                    # Este arquivo de contexto (modo manutenção)
```

---

## 7. Protocolo Obrigatório para Mudanças Futuras (Modo Manutenção)

Toda intervenção de IA neste repositório em modo de manutenção deve seguir rigorosamente o protocolo abaixo:

### Antes de qualquer alteração:
1. **Ler `docs/MANUTENCAO.md`:** Entender a arquitetura, estrutura das pastas, regras de banco e autenticação.
2. **Ler `docs/FSD.md`:** Validar as regras de negócio oficiais vigentes e limites de escopo.
3. **Ler `docs/DESIGN.md`:** Se a alteração envolver interface visual, consultar cores, tipografia, espaçamentos e componentes.
4. **Ler `docs/STATUS.md` e `CHANGELOG.md`:** Verificar o estado atual das fases, melhorias recentes e pendências.
5. **Ler `docs/ERROS.md`:** Conhecer o histórico de erros para evitar reincidências de problemas conhecidos.
6. **Entender o pedido do usuário:** Esclarecer dúvidas antes de alterar código caso o pedido seja ambíguo.
7. **Explicar o plano antes de alterar arquivos:** Apresentar a hipótese de solução, os arquivos que serão alterados e como serão testados.

### Durante o trabalho:
- Começar pelo arquivo, símbolo, rota ou teste mais próximo do comportamento solicitado.
- Fazer a menor alteração coerente com os padrões existentes.
- Não reverter alterações intencionais do usuário nem refatorar código alheio sem necessidade.
- Não introduzir dependências ou CDNs sem autorização expressa.
- Não remover verificações de segurança (CSRF, RBAC, IDOR) sob nenhuma hipótese.

### Depois de qualquer alteração:
1. **Testar o que foi alterado:**
   - Executar `php -l` em todos os arquivos PHP criados ou modificados.
   - Executar `git diff --check` para validar ausência de conflitos e espaços em branco residuais.
   - Executar testes funcionais ou validar transações e concorrência no ambiente de desenvolvimento.
2. **Registrar o progresso pelo Filtro em 3 Níveis:**
   - *Nível 1 (microajustes/invisíveis):* Apenas mensagem clara e padronizada no Git commit.
   - *Nível 2 (melhorias visuais, ajustes de regras, novos campos pontuais ou correções de bugs):* Registrar no `CHANGELOG.md` sob a seção adequada (`Adicionado`, `Modificado`, `Corrigido`, etc.) e atualizar `docs/FSD.md` se envolver regra oficial. Não cria nova fase no plano ou status.
   - *Nível 3 (grandes módulos estruturais e épicos de roadmap):* Registrar a nova versão de marco no `CHANGELOG.md`, atualizar `docs/FSD.md` se envolver ou alterar regras de negócio, e atualizar o checklist e o estado da respectiva fase em `docs/STATUS.md` e `docs/PLANO.md`.
3. **Registrar erro e solução em `docs/ERROS.md`:** Apenas se tiver ocorrido um erro ou incompatibilidade real que precisou de resolução técnica.
4. **Fazer commit ou entregar os comandos:** Fornecer os comandos git formatados (`git add`, `git commit -m "..."`, `git push`) prontos para execução.
5. **Explicar ao usuário como validar:** Indicar claramente como testar e verificar a mudança no navegador ou terminal.

---

## 8. Regras de Segurança e Dados

1. **SQL Injection:** Usar PDO com *Prepared Statements* e parâmetros vinculados. Proibida a concatenação de variáveis de usuário em strings SQL. Identificadores estruturais usados em migrations ou `ORDER BY` devem vir de listas internas controladas.
2. **XSS:** Toda variável renderizada em Views HTML deve passar por `htmlspecialchars($valor, ENT_QUOTES, 'UTF-8')`, salvo conteúdo explicitamente sanitizado e documentado.
3. **CSRF:** Todo formulário mutável (`POST`) exige token CSRF gerado na sessão e validado no Controller. Logout é obrigatoriamente `POST` protegido.
4. **Senhas:** Usar `password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12])` e `password_verify()`. Nunca registrar senha ou token em texto puro. Mínimo de 8 caracteres com letras e números.
5. **Sessão:** Cookies `HttpOnly`, `SameSite=Lax`, `use_only_cookies=1` e `Secure` sob HTTPS; executar `session_regenerate_id(true)` após login; expiração padrão de 60 minutos por inatividade.
6. **Força bruta:** Cinco falhas de login em 15 minutos por e-mail/IP geram bloqueio temporário e evento registrado em `seguranca_logs`.
7. **Isolamento Financeiro (Anti-IDOR):** Toda leitura, alteração, exclusão e associação financeira deve validar e filtrar o `usuario_id` autenticado. Não confiar apenas na ocultação de menus ou botões na View.
8. **RBAC no Backend:**
   - `admin` e `dev` administram somente seus próprios dados financeiros e preferências;
   - `operador` é legado, sem uso no produto atual; o compartilhamento futuro será autorizado por conta, não por papel global;
   - `dev` é obrigatório para gestão de usuários, auditoria global, configurações globais, migrations e GitHub Actions, sem ampliar o escopo financeiro próprio.
9. **IDOR:** Validar a propriedade de contas, categorias, meios, cartões, faturas e transações relacionadas antes de qualquer operação.
10. **Arquivos Privados:** Mídias nunca são públicas; passam pelo proxy Vercel Blob autenticado e são entregues por rotas autenticadas do PHP com validação de MIME real (`finfo_file`).
11. **Proteção contra CSV Injection:** Toda exportação CSV deve neutralizar caracteres iniciais perigosos (`=`, `+`, `-`, `@`, `\t`, `\r`).
12. **Mensagens:** Erros na interface não devem exibir stack trace, queries SQL, credenciais, URLs privadas ou detalhes de infraestrutura.

### Política para Documentos Vivos
Trate `docs/FSD.md`, `docs/PLANO.md`, `docs/STATUS.md` e `docs/ERROS.md` como documentos públicos, mesmo enquanto o repositório for privado:
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

- Escopo atual: avatar de usuário, logotipo da aplicação e imagens de feedback. Comprovantes e anexos de transações continuam fora de escopo.
- Limite padrão: 2 MB.
- Validar MIME real com `finfo_file`, extensão coerente e conteúdo permitido. JPEG, PNG e WebP são aceitos; SVG só pode ser aceito quando sanitizado pelo fluxo específico já implementado.
- Gerar nomes com `bin2hex(random_bytes(16))` ou equivalente seguro. Nunca usar nome original como caminho final.
- Upload, download e exclusão passam pelo `api/blob.js` e SDK oficial `@vercel/blob`.
- O PHP guarda a URL retornada pelo Blob no banco, mas não expõe token. Ao substituir arquivo, remover o objeto anterior quando possível.
- `storage/uploads/` é compatibilidade local; não tratá-lo como armazenamento oficial em Vercel.

---

## 11. Banco de Dados e Migrations

- Tabelas atuais: `usuarios`, `contas`, `categorias`, `meios_pagamento`, `transacoes`, `transferencias`, `cartoes_credito`, `faturas_cartao`, `auditoria_logs`, `seguranca_logs`, `erros_logs`, `configuracoes`, `migracoes`, `sessoes`, `configuracoes_usuario`, `feedback`, `investimentos` e `investimento_movimentos`.
- Migrations 013 a 016 introduzem `dev`, `google_id`, senha anulável para OAuth futuro, `usuario_id` nas entidades financeiras, preferências por usuário e feedback. A migration 018 globaliza o catálogo de meios de pagamento e cria disponibilidade individual; a 019 adiciona a preferência que controla a inclusão das contas no Saldo Atual; a 020 cria as tabelas da aba Investimentos (posições vinculadas a uma conta e movimentos de aporte, resgate, rendimento e atualização de valor, sempre isolados por `usuario_id`).
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
- Usar Inter, numerais tabulares em valores financeiros (`tabular-nums`), Electric Violet, Deep Lavender, Bright Teal e neutros definidos no design system.
- Manter responsividade para desktop, tablet e smartphone.
- Usar Bootstrap e Bootstrap Icons locais; Chart.js local para gráficos.
- Preservar a distinção visual entre dados pessoais, estados de sucesso/erro, pendências, faturas e ações administrativas.
- Não introduzir CDN, biblioteca frontend adicional ou mudança de tokens sem necessidade e sem atualizar a documentação de design.

---

## 14. Protocolo dos Arquivos Vivos e Alinhamento

### Autoridade e função de cada documento

- `docs/FSD.md` é a fonte de verdade para o comportamento, as regras de negócio e os critérios funcionais atuais. Em caso de divergência, seguir o FSD; não alterar o FSD para fazê-lo coincidir com registros antigos do plano ou do status.
- `docs/MANUTENCAO.md` consolida a arquitetura técnica, convenções de código, banco de dados, passos operacionais e critérios de segurança para manutenção.
- `docs/COMO-PEDIR-MUDANCAS.md` guia usuários na elaboração de solicitações e prompts para agentes.
- `CHANGELOG.md` registra o histórico oficial de versões (SemVer) e todas as alterações notáveis do produto (tanto as melhorias contínuas, regras e correções cotidianas quanto a conclusão dos grandes módulos estruturais e épicos de roadmap), categorizados em `Adicionado`, `Modificado`, `Descontinuado`, `Removido`, `Corrigido` e `Segurança`.
- `docs/PLANO.md` contextualiza exclusivamente os grandes marcos estruturais e épicos de roadmap aprovados pelo usuário.
- `docs/STATUS.md` acompanha o progresso exclusivamente das grandes fases e épicos de roadmap.

> [!CRITICAL]
> **Atenção para Agentes — Regra de Governança sobre Novas Fases:**
> NUNCA crie uma nova "Fase" no `docs/PLANO.md` ou `docs/STATUS.md` para correções de bugs, pequenas melhorias de interface, ajustes finos de regras existentes ou novos campos isolados. Para essas alterações de manutenção, utilize sempre o `CHANGELOG.md`. Novas Fases no plano e status são reservadas estritamente para novos módulos estruturais completos ou épicos de roadmap formalmente autorizados pelo usuário.

### Alinhamento de ideias de desenvolvimento

- Quando o usuário desenvolvedor trouxer ideias ou necessidades em uma conversa de alinhamento, primeiro esclarecer objetivo, escopo e critérios de aceite antes de iniciar a implementação.
- Se for uma melhoria de manutenção ou refinamento de regra, planejar a entrega e direcionar o registro para o `CHANGELOG.md` (atualizando `docs/FSD.md` caso altere regras oficiais).
- Se for um novo módulo estrutural ou épico de roadmap, conduzir o alinhamento de escopo antes de codificar: detalhar o plano em `docs/PLANO.md`, preparar a fase no `docs/STATUS.md` e registrar/atualizar as regras funcionais no `docs/FSD.md` caso a funcionalidade envolva ou altere regras de negócio.
- Ao concluir a implementação do épico, garantir a conformidade final do `docs/FSD.md` (se aplicável), registrar a entrega no `CHANGELOG.md` como nova versão de marco e atualizar o estado da fase em `docs/STATUS.md` e `docs/PLANO.md`.

---

## 15. Checklist de Entrega para Agentes em Manutenção

- [ ] Mudança alinhada ao FSD e ao `docs/MANUTENCAO.md`.
- [ ] Dados financeiros continuam isolados por `usuario_id` em todas as queries e mutações.
- [ ] RBAC validado no backend, não apenas na View (`AuthHelper::requireLogin()` ou `requireDev()`).
- [ ] CSRF, escape HTML (`htmlspecialchars`) e Prepared Statements preservados.
- [ ] Nenhum segredo, senha ou token exposto em código ou documentação.
- [ ] Compatibilidade PostgreSQL/Neon considerada e normalizações de dialeto respeitadas.
- [ ] Assets continuam locais (sem CDNs em runtime) e uploads continuam privados.
- [ ] Validação estática executada com sucesso (`php -l` em todos os arquivos alterados).
- [ ] Ausência de conflitos e espaços residuais checada (`git diff --check`).
- [ ] Documentação de progresso atualizada: `CHANGELOG.md` sempre atualizado com a entrega realizada (Níveis 2 e 3); `docs/STATUS.md` e `docs/PLANO.md` atualizados quando a entrega for um grande épico de roadmap (Nível 3).
- [ ] `docs/ERROS.md` atualizado caso tenha ocorrido e sido resolvido incidente relevante.
- [ ] Resumo objetivo entregue ao usuário em português do Brasil com instruções claras de validação e comandos git.
