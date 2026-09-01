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


