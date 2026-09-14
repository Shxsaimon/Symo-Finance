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

## 2026-09-14 - Validação local do Neon bloqueada por extensões e banco local

- **Sintoma:** `migrate.php --status` local falhou porque o MySQL em `localhost:3306` recusou conexão; o PHP do XAMPP também não possui `pdo_pgsql`.
- **Causa:** O ambiente local ainda está configurado para MySQL e não tem o driver PDO PostgreSQL necessário para testar `DATABASE_URL` do Neon.
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

- **Sintoma:** Ao realizar uma transferência de valor superior ao saldo disponível na conta de origem (ex: transferir R$ 3.500,00 de uma conta com R$ 100,00), o sistema debitava a conta deixando-a com saldo negativo de R$ -3.400,00 e creditava integralmente o destino.
- **Causa:** O método `Transfer::createTransfer()` validava apenas se o valor era positivo (`$valor > 0`) e se as contas eram diferentes (`origem != destino`), sem checar se o saldo atual da conta de origem cobria o montante solicitado.
- **Solução aplicada:**
  1. No Model [`Transfer.php`](file:///c:/xampp/htdocs/Symo_Finance/app/Models/Transfer.php), adicionada checagem antes de abrir a transação PDO: `if ($saldoOrigem < $valor) throw new InvalidArgumentException(...)`, informando o saldo disponível e o valor solicitado.
  2. Na View [`transfers/index.php`](file:///c:/xampp/htdocs/Symo_Finance/app/Views/transfers/index.php), adicionado atributo `data-saldo` nas opções do seletor e validação preventiva em JavaScript para impedir o envio do formulário e alertar o usuário visualmente.
- **Como evitar no futuro:** Sempre incluir validação de disponibilidade de fundos tanto no backend quanto no frontend em qualquer operação mutatória de débito/transferência.

## 2026-09-03 - Data de vencimento de compras no cartão de crédito exibida como vencida no Dashboard

- **Sintoma:** Compras efetuadas no cartão de crédito apareciam no widget de "Compromissos Pendentes" do Dashboard como vencidas (ex: "Venceu há 1d"), utilizando a data da compra (`data_transacao`) como se fosse o vencimento financeiro da despesa.
- **Causa:** Na gravação da transação e na query de compromissos pendentes do Dashboard (`getUpcomingPending`), o sistema utilizava `t.data_vencimento` preenchido com a data da compra em vez de herdar a data de vencimento estipulada na fatura do cartão (`fc.data_vencimento`).
- **Solução aplicada:**
  1. Nos métodos `createSingleTransaction` e `createInstallmentTransactions` em [`Transaction.php`](file:///c:/xampp/htdocs/Symo_Finance/app/Models/Transaction.php), a `data_vencimento` da transação passa a herdar a `data_vencimento` da fatura mensal do cartão vinculada.
  2. No método `getUpcomingPending()` em [`Transaction.php`](file:///c:/xampp/htdocs/Symo_Finance/app/Models/Transaction.php), adicionado `LEFT JOIN faturas_cartao fc ON t.fatura_id = fc.id` utilizando `COALESCE(fc.data_vencimento, t.data_vencimento)` como data efetiva de cálculo.
  3. Na view [`dashboard/index.php`](file:///c:/xampp/htdocs/Symo_Finance/app/Views/dashboard/index.php), a ação para compras de cartão direciona diretamente para o detalhe da fatura do cartão em vez do botão de pagamento individual avulso.
  4. Executada sincronização das transações já gravadas no banco de dados.
- **Como evitar no futuro:** Em modelos financeiros com cartões de crédito, sempre desacoplar a data de competência/compra da data de vencimento da obrigação financeira (fatura).


## 2026-09-09 - Log RESTORE da lixeira sem tabela auditada

- **Sintoma:** Ao restaurar itens da lixeira, o registro `RESTORE` era criado com a coluna `tabela` vazia.
- **Causa:** O model `Trash` não inicializava a propriedade `$tabela`; a restauração de transações nunca a definia, deixando o contrato usado por `Model::logAudit()` vazio.
- **Solução aplicada:** O método `restore()` passou a mapear cada entidade aceita para sua tabela (`transacoes`, `contas` ou `categorias`) antes de executar a restauração e o log.
- **Como evitar no futuro:** Todo model que utilizar `logAudit()` deve manter sua tabela definida antes de qualquer operação auditável.

## 2026-09-09 - PHP fora do PATH durante a validação

- **Sintoma:** O comando `php -l` não foi reconhecido no PowerShell.
- **Causa:** O executável do PHP do XAMPP não está disponível no `PATH` da sessão.
- **Solução aplicada:** As validações foram executadas com `C:\xampp\php\php.exe`.
- **Como evitar no futuro:** Configurar o PHP do XAMPP no `PATH` ou usar explicitamente o executável local nos scripts de validação.

---

## 2026-09-09 - MySQL indisponível durante a homologação final

- **Sintoma:** `php migrate.php --status` terminou com `SQLSTATE[HY000] [2002]`, recusando a conexão ao banco.
- **Causa:** O serviço MySQL local não estava aceitando conexões na última validação.
- **Solução aplicada:** O endpoint `/api/health` confirmou estado `degraded` sem expor a mensagem técnica; nenhuma alteração destrutiva foi executada.
- **Como evitar no futuro:** Iniciar o MySQL antes de executar migrations e repetir a validação de status antes de concluir a homologação.

---