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


