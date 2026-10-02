# Registro de Erros e Soluções — Symo

Este documento é o arquivo vivo destinado a registrar quaisquer erros técnicos, incompatibilidades, falhas de execução ou problemas de ambiente encontrados durante o desenvolvimento e manutenção do projeto **Symo**, junto com a análise de causa raiz e a solução aplicada.

---

## Modelo de Registro

Sempre que um erro for identificado e resolvido, adicione uma nova entrada seguindo o formato abaixo:

```markdown
## YYYY-MM-DD - <Título Curto do Erro>

- **Sintoma:** Descrição clara do que aconteceu ou da mensagem de erro observada.
- **Causa:** Análise técnica da causa raiz do problema.
- **Solução aplicada:** Código ou configuração alterada para corrigir o problema.
- **Como evitar no futuro:** Boa prática, teste ou validação recomendada para prevenir reincidência.
```

---

## Registros de Ocorrências

## 2026-09-21 - Validação da migration de feedback bloqueada por MySQL indisponível

- **Sintoma:** O comando `php migrate.php --status` não conseguiu verificar a migration `016_criar_tabela_feedback.php` porque a conexão local foi recusada.
- **Causa:** O serviço MySQL local não estava aceitando conexões na porta configurada para o Symo.
- **Solução aplicada:** A migration foi validada por lint PHP e permaneceu pendente de execução; nenhuma alteração de banco ou configuração foi realizada.
- **Como evitar no futuro:** Iniciar o MySQL antes de executar `php migrate.php` e confirmar o status da migration após o serviço ficar disponível.

## 2026-09-16 - Modal de logs inicializado antes do Bootstrap

- **Sintoma:** O console informava `window.carregarLogGitHub is not a function` ao clicar em `Ver log`.
- **Causa:** O JavaScript da view era executado antes dos scripts do layout; como `bootstrap` ainda não existia, a inicialização retornava sem definir o handler global.
- **Solução aplicada:** O polling e a inicialização do modal foram adiados para o evento `DOMContentLoaded`, após o carregamento dos assets do Bootstrap.
- **Como evitar no futuro:** Views que dependem de scripts carregados pelo layout devem inicializar componentes somente após `DOMContentLoaded` ou após confirmação da disponibilidade da biblioteca.

## 2026-09-16 - Download de logs do GitHub retornava 302

- **Sintoma:** O botão de log retornava 404 mesmo com job concluído e ID correto.
- **Causa:** A API do GitHub retorna `302 Found` com uma URL temporária no cabeçalho `Location`; o cURL esperava somente respostas 2xx e não seguia o download.
- **Solução aplicada:** O helper passou a extrair `Location`, baixar o log em uma segunda requisição sem reenviar o token GitHub e retornar o conteúdo em JSON. A interface passou a exibir o log em modal Bootstrap.
- **Como evitar no futuro:** Tratar explicitamente respostas de redirecionamento da API de logs e nunca encaminhar o token GitHub para a URL temporária de download.

## 2026-09-16 - Execução de migrations integrada ao painel

- **Sintoma:** A tela administrativa executava migrations diretamente na Function PHP, enquanto a execução oficial usava GitHub Actions com PHP e `pdo_pgsql`.
- **Causa:** Havia dois caminhos de execução, com risco de divergência de ambiente e de incompatibilidade SQL no runtime web.
- **Solução aplicada:** O painel passou a disparar o workflow `database-migrations.yml` pela API do GitHub, usando as ações `status`, `migrate` e `seed`, com acompanhamento da execução, jobs e logs para administradores.
- **Como evitar no futuro:** Manter migrations de produção no workflow controlado e nunca expor o token GitHub no HTML ou JavaScript.

## 2026-09-16 - Upload Blob usando endpoint fabricado

- **Sintoma:** O upload falhava ao validar a resposta porque o fluxo usava um endpoint de armazenamento montado manualmente.
- **Causa:** O fluxo não utilizava o SDK oficial, não suportava corretamente Blob privado e não possuía rota server-side para leitura autenticada.
- **Solução aplicada:** Criada a Function `api/blob.js` com `@vercel/blob` para `put`, `get` e `del`; o PHP passou a chamá-la com segredo interno, guardar a URL retornada e servir imagens por rotas autenticadas. Adicionados suporte a SVG sanitizado e migration 012 para campos `TEXT`.
- **Como evitar no futuro:** Não fabricar URLs do Blob nem expor tokens no navegador; testar upload, leitura e exclusão com o mesmo ambiente Vercel configurado.

## 2026-09-14 - Assets continuavam 404 após ajuste do rewrite

- **Sintoma:** Os links em `/public/...` ou na raiz dos assets retornavam 404/texto plano; o navegador recusava CSS e JavaScript por MIME incorreto e o Chart.js não era definido.
- **Causa:** O runtime comunitário `vercel-php` estava encaminhando os assets para a Function e não os entregava como arquivos estáticos confiáveis apenas com o rewrite.
- **Solução aplicada:** A Function `api/index.php` passou a servir somente assets permitidos de `public/`, com validação de caminho, MIME explícito e cache; `public/**` foi incluído no bundle da Function.
- **Como evitar no futuro:** Validar no deploy o status e o `Content-Type` de `/css/*.css`, `/js/*.js` e `/img/*.svg` antes de testar componentes da interface.

## 2026-09-14 - Assets públicos retornando 404 na Vercel

- **Sintoma:** CSS, JavaScript e imagens em `/public/` retornavam 404 e o Dashboard era exibido sem estilos.
- **Causa:** O rewrite global `/(.*)` encaminhava também os arquivos estáticos para `api/index.php`, onde o Router PHP respondia 404.
- **Solução aplicada:** O rewrite passou a excluir `/public/` e `/api/`, permitindo que a Vercel sirva assets e Functions diretamente.
- **Como evitar no futuro:** Rewrites do front controller devem excluir diretórios de assets e endpoints já reconhecidos pela plataforma.

## 2026-09-14 - Funções MONTH e YEAR incompatíveis no Dashboard PostgreSQL

- **Sintoma:** Após o login, o Dashboard retornou `Undefined function: 7 ERROR: function month(date) does not exist` em `Transaction.php`.
- **Causa:** PostgreSQL não implementa as funções MySQL `MONTH()` e `YEAR()` usadas nas consultas de resumo, evolução, relatórios e transferências.
- **Solução aplicada:** O adaptador `SymoPostgresConnection` passou a converter essas funções para `EXTRACT(MONTH FROM ...)` e `EXTRACT(YEAR FROM ...)` antes de executar `prepare` ou `exec`.
- **Como evitar no futuro:** Ao adicionar consultas, validar funções específicas do MySQL contra PostgreSQL e preferir filtros por intervalo de datas.

## 2026-09-14 - Execução remota de migrations via GitHub Actions

- **Sintoma:** A Vercel não oferece terminal CLI persistente para executar `migrate.php`.
- **Causa:** `migrate.php` exige execução em modo CLI e o runtime serverless da Vercel é orientado a requisições.
- **Solução aplicada:** Criado workflow manual `.github/workflows/database-migrations.yml`, com PHP 8.2, `pdo_pgsql` e ações controladas de status, migration ou seed.
- **Como evitar no futuro:** Armazenar a URL do Neon em Secret do GitHub e nunca permitir `--fresh` em produção.

## 2026-09-14 - Validação local do Neon bloqueada por extensões e banco local

- **Sintoma:** A verificação de migrations local falhou porque a conexão ao banco foi recusada e o PHP disponível não possuía o driver PostgreSQL necessário.
- **Causa:** O ambiente local não estava preparado para conectar ao banco PostgreSQL de desenvolvimento.
- **Solução aplicada:** O código foi preparado para PostgreSQL, mas a execução contra o Neon deve ser feita em um PHP com `pdo_pgsql` habilitado ou por uma ferramenta PostgreSQL compatível.
- **Como evitar no futuro:** Validar `php -m` antes de executar migrations remotas e nunca usar `--fresh` contra o banco de produção.

## 2026-09-14 - Classe Database não encontrada no runtime Linux

- **Sintoma:** A Vercel retornou `Class "Database" not found` ao construir o `Model` durante o login.
- **Causa:** O autoloader montava `Database.php`, enquanto o arquivo versionado era `config/database.php`; Windows trata essa diferença de forma diferente do Linux.
- **Solução aplicada:** Adicionado mapeamento explícito de `Database` para `database.php` no autoloader, sem renomear o arquivo e sem alterar o modo de debug.
- **Como evitar no futuro:** Validar nomes de arquivos e imports em ambiente Linux ou CI, pois o filesystem de produção diferencia maiúsculas e minúsculas.

## 2026-09-11 - Function PHP fora do diretório api na Vercel

- **Sintoma:** O deploy falhou com `The pattern "index.php" defined in functions doesn't match any Serverless Functions inside the api directory`.
- **Causa:** O runtime comunitário `vercel-php` identifica Functions PHP dentro de `api/`; o `index.php` raiz do projeto não era reconhecido como uma Function.
- **Solução aplicada:** Criado `api/index.php` como adaptador que inclui o front controller existente, atualizado o padrão de `functions` para `api/*.php` e direcionado o rewrite global para `/api/index.php`.
- **Como evitar no futuro:** Manter os entrypoints PHP destinados à Vercel dentro de `api/` e validar o deploy com a versão do runtime compatível com a versão de PHP requerida.

## 2026-09-01 - SQLSTATE[HY093]: Invalid parameter number com placeholders duplicados em PDO nativo

- **Sintoma:** Erro `PDOException: SQLSTATE[HY093]: Invalid parameter number` ao executar query preparada com o mesmo placeholder `:id` utilizado mais de uma vez na cláusula `WHERE (conta_origem_id = :id OR conta_destino_id = :id)`.
- **Causa:** Com `PDO::ATTR_EMULATE_PREPARES => false`, o driver nativo do MySQL não permite a reutilização do mesmo identificador nomeado de parâmetro mais de uma vez na mesma query preparada.
- **Solução aplicada:** Utilizar identificadores nomeados distintos para cada parâmetro (ex: `:id_origem` e `:id_destino`) com seus respectivos `bindValue()`.
- **Como evitar no futuro:** Sempre atribuir nomes únicos a todos os placeholders nomeados em queries com múltiplas condições comparativas.

## 2026-09-01 - TypeError em handlers de rota com parâmetros dinâmicos sob declare(strict_types=1)

- **Sintoma:** Erro `TypeError: AccountController::update(): Argument #1 ($id) must be of type int, string given` ao disparar ações com parâmetros de rota dinâmicos (ex: `/contas/atualizar/3`, `/contas/status/3`, `/categorias/excluir/20`).
- **Causa:** O método `Router::dispatch()` extrai os parâmetros da URI via `preg_match()`, o que sempre produz valores do tipo `string`. Devido ao modo estrito do PHP (`declare(strict_types=1)`), a chamada via `call_user_func_array()` rejeitava passar strings para métodos declarados com `int $id`.
- **Solução aplicada:** 
  1. Em `Router::dispatch()`, adicionada verificação com `ctype_digit()` para fazer o casting automático de parâmetros estritamente inteiros para `(int)`.
  2. Nos métodos de controllers que recebem IDs de rota (`update`, `toggle`, `delete`), a assinatura foi flexibilizada para `int|string $id` com sanitização interna via `(int) $id`.
- **Como evitar no futuro:** Sempre configurar roteadores para converter parâmetros numéricos e projetar handlers de rotas com tipo composto `int|string` em projetos com tipagem estrita ativa.

## 2026-09-03 - Ausência de validação de saldo suficiente na conta de origem em transferências

- **Sintoma:** Uma transferência acima do saldo disponível podia debitar a conta de origem além do valor disponível e creditar integralmente o destino.
- **Causa:** O método `Transfer::createTransfer()` validava apenas se o valor era positivo (`$valor > 0`) e se as contas eram diferentes (`origem != destino`), sem checar se o saldo atual da conta de origem cobria o montante solicitado.
- **Solução aplicada:**
  1. A operação passou a validar o saldo disponível no servidor e a atualizar os saldos de forma transacional.
  2. A interface recebeu uma validação preventiva complementar; a validação do servidor permanece autoritativa.
- **Como evitar no futuro:** Sempre incluir validação de disponibilidade de fundos tanto no backend quanto no frontend em qualquer operação mutatória de débito/transferência.

## 2026-09-03 - Data de vencimento de compras no cartão de crédito exibida como vencida no Dashboard

- **Sintoma:** Compras efetuadas no cartão de crédito apareciam no widget de "Compromissos Pendentes" do Dashboard como vencidas (ex: "Venceu há 1d"), utilizando a data da compra (`data_transacao`) como se fosse o vencimento financeiro da despesa.
- **Causa:** O vencimento da compra era calculado a partir da data da transação, sem considerar corretamente o ciclo da fatura associada.
- **Solução aplicada:**
  1. O cálculo de vencimento passou a considerar a fatura associada à compra.
  2. O Dashboard e as ações correspondentes passaram a usar o vencimento da fatura.
- **Como evitar no futuro:** Em modelos financeiros com cartões de crédito, sempre desacoplar a data de competência/compra da data de vencimento da obrigação financeira (fatura).


## 2026-09-09 - Log RESTORE da lixeira sem tabela auditada

- **Sintoma:** Ao restaurar itens da lixeira, o registro `RESTORE` era criado com a coluna `tabela` vazia.
- **Causa:** O model `Trash` não inicializava a propriedade `$tabela`; a restauração de transações nunca a definia, deixando o contrato usado por `Model::logAudit()` vazio.
- **Solução aplicada:** O método `restore()` passou a mapear cada entidade aceita para sua tabela (`transacoes`, `contas` ou `categorias`) antes de executar a restauração e o log.
- **Como evitar no futuro:** Todo model que utilizar `logAudit()` deve manter sua tabela definida antes de qualquer operação auditável.

## 2026-09-09 - PHP fora do PATH durante a validação

- **Sintoma:** O comando `php -l` não foi reconhecido no PowerShell.
- **Causa:** O executável do PHP não estava disponível no `PATH` da sessão.
- **Solução aplicada:** As validações foram executadas com o interpretador PHP configurado no ambiente local.
- **Como evitar no futuro:** Configurar o PHP no `PATH` ou usar o executável local configurado para as validações.

---

## 2026-09-09 - MySQL indisponível durante a homologação final

- **Sintoma:** `php migrate.php --status` terminou com `SQLSTATE[HY000] [2002]`, recusando a conexão ao banco.
- **Causa:** O serviço MySQL local não estava aceitando conexões na última validação.
- **Solução aplicada:** O endpoint `/api/health` confirmou estado `degraded` sem expor a mensagem técnica; nenhuma alteração destrutiva foi executada.
- **Como evitar no futuro:** Iniciar o MySQL antes de executar migrations e repetir a validação de status antes de concluir a homologação.

---