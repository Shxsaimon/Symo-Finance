# Como Pedir Mudanças no Symo Finance — Guia para Usuários

Este guia foi criado para orientar qualquer pessoa (mesmo sem conhecimento técnico de programação) a solicitar melhorias, correções ou novas funcionalidades a um assistente de Inteligência Artificial para o sistema **Symo Finance**.

---

## 1. Como Conversar com a IA para Pedir Alterações

Para obter os melhores resultados e evitar que o sistema quebre ou perca regras de segurança, siga estas recomendações simples:

1. **Seja específico sobre o objetivo:** Diga claramente o que você quer alcançar, em qual tela a mudança deve aparecer e o que deve acontecer ao clicar ou salvar.
2. **Exija sempre a leitura dos documentos do projeto:** Toda conversa com a IA deve começar orientando-a a ler os arquivos de documentação viva do sistema.
3. **Peça para a IA explicar o plano antes de editar:** Peça que ela liste quais arquivos pretende alterar e como pretende testar antes de começar a codificar.
4. **Altere uma coisa de cada vez:** Evite pedir várias telas e novos fluxos de negócio em uma única mensagem. Pedidos menores e focados resultam em código muito mais confiável.
5. **Exija validação antes da entrega:** Peça que a IA valide a sintaxe do código (`php -l`), teste a regra e informe exatamente o que foi validado.

---

## 2. A Regra de Ouro: Documentos Obrigatórios

Sempre inclua este bloco em seus primeiros pedidos em um novo chat:

> "Antes de qualquer ação, leia integralmente os seguintes arquivos de contexto do projeto nesta ordem:
> 1. `AGENTS.md`
> 2. `docs/MANUTENCAO.md`
> 3. `docs/FSD.md`
> 4. `docs/DESIGN.md` (se envolver interface visual)
> 5. `docs/STATUS.md`
> 6. `docs/ERROS.md`"

Isso garante que a IA entenda a arquitetura (PHP 8 nativo em MVC, PostgreSQL no Neon, Bootstrap 5 local) e preserve as proteções de isolamento por usuário e segurança.

---

## 3. Checklist Antes de Aceitar Qualquer Alteração

Antes de considerar uma entrega concluída ou autorizar o envio para produção, faça a si mesmo e à IA estas perguntas do checklist:

- [ ] **A regra foi explicada antes de ser codificada?** A IA descreveu onde e o que alterou?
- [ ] **Os dados continuam isolados por usuário?** Um usuário comum consegue ver ou alterar dados de outra pessoa? (Isso nunca pode acontecer).
- [ ] **A sintaxe PHP foi verificada?** A IA executou `php -l` em todos os arquivos modificados?
- [ ] **A segurança foi mantida?** Os formulários possuem proteção contra ataques CSRF e os valores exibidos usam proteção contra XSS?
- [ ] **O visual segue o padrão Symo?** Cores (Electric Violet, Deep Lavender), botões e numerais tabulares em valores financeiros estão no padrão?
- [ ] **Os arquivos vivos foram atualizados?** O `docs/STATUS.md` foi atualizado com o que foi feito? Se houve erro resolvido, foi registrado em `docs/ERROS.md`?

---

## 4. Modelos Práticos de Prompts Prontos

Abaixo estão 8 modelos de prompts prontos para copiar, colar e personalizar conforme a sua necessidade:

---

### Modelo 1: Adicionar um campo em um cadastro existente
*Use quando quiser incluir um novo dado em uma tela já existente (exemplo: campo "Instituição Financeira" em Contas bancárias).*

```text
Olá! Preciso adicionar um novo campo no cadastro de Contas Bancárias do Symo Finance.

O campo a ser adicionado é: "Instituição Bancária" (texto livre com até 60 caracteres, opcional).
Ele deve aparecer no modal de criação e no modal de edição de contas, bem como na listagem de contas logo abaixo do nome da conta.

Antes de alterar qualquer código:
1. Leia `docs/MANUTENCAO.md`, `docs/FSD.md` e `docs/STATUS.md`.
2. Explique o plano detalhando:
   - A nova migration sequencial em `database/migrations/` (PostgreSQL e MySQL legado);
   - Os ajustes no Model `Account.php` e no Controller `AccountController.php`;
   - A inclusão do campo nas Views correspondentes;
   - Como você irá validar a sintaxe e o isolamento por usuário.
3. Aguarde minha confirmação para implementar.
```

---

### Modelo 2: Criar uma nova tela ou funcionalidade
*Use quando quiser uma nova página ou fluxo completo no sistema.*

```text
Olá! Gostaria de criar uma nova tela no Symo Finance para registrar e visualizar "Metas Financeiras".

Cada meta terá: título, valor alvo, valor atual acumulado, data limite e uma conta vinculada opcional. Os dados devem ser estritamente privados por usuário (isolados por usuario_id).

Antes de programar:
1. Leia `docs/MANUTENCAO.md`, `docs/FSD.md`, `docs/DESIGN.md` e `docs/STATUS.md`.
2. Proponha a arquitetura da funcionalidade respeitando o MVC do projeto:
   - Rota em `index.php`;
   - Estrutura da migration do banco de dados;
   - Model, Controller e Views necessárias;
   - Onde o link ficará na barra lateral (`sidebar.php`).
3. Apresente o plano para minha revisão antes de criar os arquivos.
```

---

### Modelo 3: Corrigir um erro ou comportamento inesperado
*Use quando identificar um bug, erro na tela ou divergência de comportamento.*

```text
Olá! Identifiquei um erro no Symo Finance.

Sintoma: Ao tentar cadastrar uma despesa com cartão de crédito no dia 15, ela está sendo vinculada à fatura do mês atual, quando o fechamento do meu cartão é justamente no dia 15 (deveria entrar no ciclo do próximo mês devido à fronteira exclusiva).

Por favor:
1. Leia `docs/MANUTENCAO.md`, `docs/FSD.md`, `docs/STATUS.md` e `docs/ERROS.md`.
2. Investigue o código em `app/Controllers/TransactionController.php`, `app/Models/CreditCardInvoice.php` e `app/Helpers/CreditCardCalendar.php`.
3. Explique a causa raiz do problema e a correção proposta.
4. Execute os testes necessários (`php -l`), certifique-se de que nada mais foi quebrado e registre a solução em `docs/ERROS.md`.
```

---

### Modelo 4: Alterar uma regra de negócio
*Use quando quiser modificar uma fórmula de cálculo, um limite ou a forma como uma operação financeira funciona.*

```text
Olá! Preciso alterar uma regra de negócio no módulo de Cartões de Crédito do Symo Finance.

Regra atual: Ao ultrapassar o limite do cartão em uma compra, o sistema exibe apenas um alerta informativo em amarelo, permitindo a compra.
Nova regra desejada: Manter o alerta em amarelo para compras avulsas, mas exibir um aviso em vermelho destacando o valor exato que excederá o limite caso a compra parcelada seja confirmada.

Antes de alterar o código:
1. Leia `docs/MANUTENCAO.md`, `docs/FSD.md` e `docs/STATUS.md`.
2. Localize onde essa validação e alerta são processados no backend e na View.
3. Apresente a alteração planejada garantindo que a gravação continue funcionando e sem quebrar operações concorrentes.
```

---

### Modelo 5: Ajustar visual e interface conforme o Design System
*Use quando um botão, espaçamento, tabela ou componente estiver fora do padrão visual.*

```text
Olá! Preciso padronizar a tela de "Investimentos" (`/investimentos`) para deixá-la 100% alinhada ao Guia de Estilo do Symo Finance.

Ajustes necessários:
- Os valores monetários do card e da tabela devem usar numerais tabulares (`tabular-nums`) e a fonte Inter;
- O botão principal de "Novo Investimento" deve usar a cor Electric Violet (`#6F02FF`) com cantos arredondados de 8px (`rounded-2`);
- Os filtros superiores devem ficar em um card com borda sutil e fundo limpo conforme o `docs/DESIGN.md`.

Por favor:
1. Leia `docs/DESIGN.md`, `docs/MANUTENCAO.md` e a View correspondente em `app/Views/investments/`.
2. Mostre o código que será ajustado em `custom.css` ou na View.
3. Garanta que nenhuma CDN externa seja adicionada.
```

---

### Modelo 6: Criar um relatório ou filtro analítico
*Use quando precisar de uma nova consulta consolidada ou opção de filtro.*

```text
Olá! Gostaria de adicionar um novo filtro na tela de Relatórios (`/relatorios`) do Symo Finance.

Novo filtro: Filtrar despesas e receitas por "Faixa de Valor" (opções: Até R$ 100, R$ 100 a R$ 500, R$ 500 a R$ 2.000, e Acima de R$ 2.000).
Esse filtro deve funcionar tanto na visualização da tela quanto na exportação do arquivo CSV.

Por favor:
1. Leia `docs/MANUTENCAO.md`, `docs/FSD.md` e os arquivos `app/Models/Report.php` e `app/Controllers/ReportController.php`.
2. Implemente a lógica garantindo Prepared Statements PDO contra SQL Injection e preservando o UTF-8 BOM no CSV.
3. Execute `php -l` para validar a sintaxe e teste a exportação.
```

---

### Modelo 7: Revisar a segurança após uma alteração
*Use para solicitar uma auditoria focada após implementar mudanças no sistema.*

```text
Olá! Acabamos de implementar alterações recentes no módulo de Transferências e Contas do Symo Finance.

Por favor, faça uma revisão de segurança completa em `app/Controllers/TransferController.php`, `app/Models/Transfer.php` e `app/Views/transfers/`:
1. Leia a seção de Cuidados de Segurança do `docs/MANUTENCAO.md`.
2. Verifique se:
   - Todas as queries utilizam Prepared Statements;
   - Todas as saídas de tela usam `htmlspecialchars`;
   - Todas as ações mutatórias (POST) validam o token CSRF;
   - Existe proteção contra IDOR (o usuário não consegue transferir a partir de conta de terceiros);
   - A operação de saldo é atômica com `beginTransaction()` e `commit()`.
3. Apresente um relatório com eventuais pontos frágeis e as correções preventivas aplicadas.
```

---

### Modelo 8: Preparar alteração para commit e entrega
*Use quando todas as alterações tiverem sido testadas e você quiser salvar o trabalho no Git.*

```text
Olá! Concluímos as tarefas combinadas. Agora precisamos preparar as alterações para commit e entrega.

Por favor:
1. Execute `git diff --check` para garantir que não há conflitos ou espaços em branco inadequados.
2. Execute `php -l` em todos os arquivos modificados.
3. Atualize o arquivo `docs/STATUS.md` marcando as tarefas finalizadas e o estado da fase.
4. Caso tenhamos corrigido algum erro relevante, confirme se foi registrado em `docs/ERROS.md`.
5. Apresente o resumo do que foi feito e os comandos git (`git status`, `git add`, `git commit` e `git push`) formatados prontos para eu executar.
```
