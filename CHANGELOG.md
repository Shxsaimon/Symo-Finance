# Changelog — Symo Finance

Todas as alterações notáveis, melhorias contínuas, polimentos visuais e correções do sistema **Symo Finance** são documentadas neste arquivo.

O formato é baseado no [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/) e este projeto adere ao [Versionamento Semântico (SemVer)](https://semver.org/lang/pt-BR/).

---

## Categorias de Alterações

- **Adicionado:** para novas funcionalidades, telas, opções ou capacidades.
- **Modificado:** para alterações em funcionalidades ou comportamentos existentes.
- **Descontinuado:** para recursos que serão removidos em versões futuras (aviso prévio).
- **Removido:** para recursos que foram efetivamente removidos do sistema.
- **Corrigido:** para qualquer correção de bug, erro de cálculo, falha visual ou link quebrado.
- **Segurança:** para correções preventivas de vulnerabilidades ou melhorias de isolamento/proteção.

---

## [2.4.1] — 2026-11-20 — Remoção Definitiva do Perfil Legado Operador e Padronização do Badge DEV

### Adicionado
- **Migration 023 (`023_remover_perfil_operador_usuarios.php`):** Atualiza a restrição (`CHECK` no PostgreSQL e `ENUM` no MySQL legado) da coluna `perfil` na tabela `usuarios` para aceitar exclusivamente `'admin'` e `'dev'`, com valor padrão `'admin'`.
- **Classe CSS `.badge-role-dev`:** Nova classe dedicada em `public/css/custom.css` com destaque roxo (`var(--symo-primary-fixed)` / `var(--symo-on-primary-fixed)`) para o selo do perfil técnico `DEV`.

### Modificado
- **Exibição exclusiva do badge `DEV`:** Na barra de navegação superior (`navbar.php`) e no cartão de Meu Perfil (`users/profile.php`), o selo de perfil agora é exibido exclusivamente para usuários com perfil `dev`, ocultando o selo redundante de `ADMIN` para usuários padrão.
- **Seletores de perfil na Gestão de Usuários (`users/index.php`, `UserController.php`, `User.php`):** Substituída a antiga opção `Operador` pelos perfis oficiais `admin` (Administrador padrão) e `dev` (Desenvolvedor).

### Removido
- **Perfil legado `operador`:** Eliminadas todas as referências, métodos (`AuthHelper::isOperador()`), classes CSS (`.badge-role-operador`), avisos de "Modo Consulta (Operador)" nas telas financeiras e menções na documentação ativa (`AGENTS.md`, `docs/FSD.md` e `docs/MANUTENCAO.md`).

---

## [2.4.0] — 2026-11-17 — Autenticação Social Google OAuth 2.0 e E-mails Transacionais (Verificação e Redefinição de Senha)

### Adicionado
- **Verificação de E-mail com Código de 6 Dígitos:** Novos usuários cadastrados por e-mail e senha recebem um código de 6 dígitos com validade de 15 minutos via e-mail transacional (Resend), com tela de validação dedicada (`/verificar-email`), proteção contra força bruta (máximo de 5 tentativas) e reenvio com cooldown de 60 segundos.
- **Povoamento Postergado de Dados Padrão (Otimização do Banco):** A clonagem de dados iniciais (`categorias` e `configuracoes_usuario`) agora ocorre estritamente após a confirmação efetiva do e-mail do usuário, evitando o acúmulo de dados órfãos ou lixo no PostgreSQL em cadastros não ativados ou abandonados.
- **Envio Automático Imediato ao Tentar Login ou Confirmar no Perfil:** Usuários com e-mail pendente que tentem logar ou que cliquem em "Confirmar agora" em `/perfil` recebem automaticamente um novo código de 6 dígitos gerado e despachado na hora para sua caixa de entrada, sem a necessidade de clicar manualmente em reenviar.
- **Indicadores Visuais de Verificação de E-mail:** Inclusão de selos de status de e-mail na tela de Meu Perfil (`/perfil`) e na listagem de usuários do sistema (`/usuarios`), permitindo auditoria visual rápida por parte do usuário e da equipe técnica (`dev`).
- **Recuperação e Redefinição Segura de Senha:** Fluxo completo e funcional de "Esqueci minha senha" (`/esqueci-senha`) disparando e-mail transacional com link criptográfico de uso único e validade de 60 minutos, com tela protegida para cadastro de nova senha (`/redefinir-senha`), validação de força e proteção anti-enumeração de usuários.
- **Helper de E-mails Transacionais (`MailHelper`):** Integração nativa com a API REST do Resend via cURL do PHP, sem dependências externas pesadas, com templates HTML responsivos no padrão visual do Symo e texto plano de fallback.
- **Model `AuthToken` e Migration 022:** Adicionada a coluna `email_verificado_em` na tabela `usuarios` e criada a tabela `tokens_verificacao` com suporte a hashes seguros (SHA-256), contador de tentativas e controle de expiração.
- **Login Social com Google OAuth 2.0 (Fase 14):** Implementação completa da autenticação com contas Google via OAuth 2.0 com suporte a autorização, callback seguro com validação anti-CSRF por `state`, e consulta de perfil no endpoint UserInfo do Google. Contas criadas via Google já nascem com e-mail verificado.
- **Resolução Inteligente de Identidade Google:**
  - *Login Direto:* Acesso instantâneo de contas já vinculadas pelo identificador único e imutável `google_id`, garantindo o acesso contínuo aos dados financeiros mesmo diante de alterações cadastrais externas.
  - *Vínculo de Conta Existente:* Contas locais criadas anteriormente com e-mail/senha são automaticamente vinculadas ao `google_id` mediante a comprovação da titularidade do e-mail verificado pelo Google, preservando todos os dados financeiros.
  - *Novo Autocadastro com Dados Padrão:* Contas criadas a partir do Google nascem com perfil `admin` e recebem a clonagem atômica do template de categorias e preferências pessoais via `DefaultDataHelper`, mantendo o isolamento financeiro total.
- **Helper `GoogleAuthHelper`:** Utilitário nativo em PHP para montagem de URLs de consentimento, troca de authorization code por access token e recuperação de dados de perfil com timeouts e tratamento de falhas.
- **Botão "Continuar com o Google" nas Telas de Autenticação:** Adicionado botão estilizado com o ícone oficial do Google e divisor contextual nas telas de Login (`/login`) e Cadastro (`/registrar`), harmonizado com a identidade visual do Symo.
- **Variáveis de Ambiente Documentadas:** Adicionadas `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, `RESEND_API_KEY` e `RESEND_FROM_EMAIL` em `load-env.example.ps1`.

## [2.3.0] — 2026-11-06 — Pacote de Usabilidade do Dashboard, Refinamento de Lançamentos e Faturas de Cartão

### Adicionado
- **Exclusão de investimentos com Soft Delete e Lixeira (Feedback 16):** Adicionado botão e fluxo de exclusão de posições de investimento na página de detalhes (`/investimentos/{id}`). Exige que o saldo atual seja R$ 0,00 para garantir a consistência patrimonial e a rastreabilidade financeira.
- **Aba de Investimentos na Lixeira:** Integração completa do módulo de investimentos com a Lixeira do sistema (`/lixeira?aba=investimentos`), permitindo visualizar investimentos excluídos, restaurá-los (reativando a posição e seus movimentos associados) ou expurgá-los definitivamente em cascata.
- **Lixeira de movimentações de investimentos:** Movimentações de investimentos (aportes, resgates, rendimentos e ajustes) excluídas individualmente agora contam com listagem, restauração segura com reprocessamento atômico de saldos e expurgo físico na Lixeira.
- **Migration 021:** Adicionadas colunas `excluido_em` e `excluido_por` na tabela `investimentos` (PostgreSQL e MySQL legado), com índice de escopo `idx_investimentos_excluido_em`.
- **Modal unificado no Dashboard:** Botão "+ Novo Lançamento" abre modal interativo (`#modalNovoLancamento`) com abas superiores para alternância rápida entre Despesa e Receita, evitando trocas de página.
- **Campo de Fatura de Destino dinâmico:** Seletor de fatura no modal de despesa em compras no crédito com cálculo dinâmico, exibição do status e total acumulado do ciclo.
- **Endpoint assíncrono de faturas:** Rota `GET /cartoes/faturas-disponiveis/{id}` em `CreditCardController` alimentando as faturas abertas e fechadas não pagas disponíveis para seleção.
- **Cálculo determinístico de datas de fatura:** Novos métodos `CreditCardCalendar::invoiceOpeningDate` e `CreditCardCalendar::nextInvoiceReference` calculando a data de abertura do ciclo e a progressão das referências de fatura.
- **Fallback inteligente em faturas pagas:** Compras datadas em ciclo cuja fatura já foi quitada são automaticamente alocadas na próxima fatura aberta, com aviso contextual explicativo.

### Modificado
- **Prioridade de faturas nos cards de cartão:** O resumo de cartões no Dashboard passa a priorizar faturas com status `fechada` pendentes de quitação, exibindo o ciclo aberto apenas na ausência de pendências.
- **Ancoragem de parcelas no cartão:** Em compras parceladas no crédito, a 1ª parcela registra a data real da transação e as parcelas 2 em diante são registradas contabilmente no dia de abertura da respectiva fatura mensal.
- **Modalidade orientada pelo meio de pagamento:** O meio de pagamento define exclusivamente a saída: meios do tipo crédito exigem seleção de cartão; demais meios exigem conta bancária e status pago/pendente.
- **Padronização visual de receitas:** Criação da classe `.btn-symo-revenue` com tom roxo secundário (`#600de6` / hover `#5300c4`) no `public/css/custom.css`, eliminando o verde genérico nos botões de receita do Dashboard e da tela de lançamentos.
- **Padronização de inputs e seletores:** Altura unificada de 38px e remoção de tipografia desproporcional (`fs-5`) nos formulários dos modais de Despesa, Receita, Edição e Transferências.
- **Preservação de navegação:** Adicionado suporte ao parâmetro `redirect_to` no salvamento de lançamentos para manter o usuário no Dashboard com o período de mês/ano selecionado preservado.
- **Rótulo "Data da Compra":** Substituição do termo "Data de Vencimento" por "Data da Compra" no modal de despesas, refletindo a data de competência da operação.

### Corrigido
- **Restauração de movimentações de investimento na lixeira com PostgreSQL (Feedback 16):** Refatorada a query de bloqueio transacional (`FOR UPDATE`) no método `Trash::restoreInvestmentMovement`, substituindo a junção com `LEFT JOIN` por consultas atômicas dedicadas para cada tabela envolvida (`investimento_movimentos`, `investimentos` e `contas`), eliminando a incompatibilidade do PostgreSQL com locks em tabelas do lado anulável de outer joins (`SQLSTATE[0A000]`).
- **Auto-quitação de faturas de cartão zeradas (Feedback 15):** Faturas de cartão com valor total zerado (`valor_total <= 0`) cujo dia de fechamento já passou passam automaticamente para o status `paga` sem débito em conta bancária, evitando que fiquem travadas como pendências impagáveis no card de cartões.
- **Transição de faturas fechadas ao cancelar ou excluir lançamentos (Feedback 15):** Ao excluir ou cancelar transações vinculadas a uma fatura fechada que reduzam seu saldo devedor a zero, o status da fatura é atualizado automaticamente para `paga`, impedindo que faturas vazias fiquem retidas como fechadas.
- **Restauração de transações em faturas auto-quitadas (Feedback 15):** Ao restaurar da lixeira uma despesa vinculada a uma fatura que foi quitada automaticamente por saldo zerado (sem conta bancária de liquidação), a fatura é reaberta/fechada adequadamente sem bloquear a recuperação do lançamento.
- **Ações na tabela de faturas do cartão (Feedback 15):** O botão "Pagar" só é exibido para faturas fechadas com valor estritamente positivo (`valor_total > 0`), e faturas abertas não exibem mais indevidamente o botão de quitação ("Paga em...").
- **Link de faturas no Dashboard:** Corrigido o botão de visualização de fatura no widget de compromissos pendentes para apontar para a rota oficial `/faturas/{fatura_id}`, eliminando erro 404 em rota inexistente `/cartoes/ver/{id}`.
- **Seleção de fatura atual no cartão:** Corrigida a lógica de seleção de fatura nos cards de cartão para refletir o ciclo corrente com base no dia de fechamento, sem transbordar para faturas de parcelas futuras distantes.
- **Preservação da data de lançamento na edição:** Correção em `TransactionController` e `Transaction` para manter inalterada a `data_transacao` ao editar outros campos de um lançamento.
- **Omissão de sufixos legados de parcelas:** Ocultação de sufixos herdados na descrição (`(Parcela X/Y)`) em listagens e extratos, exibindo o número da parcela em coluna própria.

### Segurança
- **Blindagem de faturas pagas:** Bloqueio de novas compras, alterações ou movimentações em faturas com status `paga`.
- **Validação de fundos no UPDATE atômico:** Garantia de concorrência em transferências e liquidações integrais de fatura com verificação direta de saldo suficiente no banco (`saldo_atual >= :valor`).

---

## [2.2.0] — 2026-10-13 — Perfil de Usuário e Gestão de E-mail

### Adicionado
- **Tela de Perfil acessível na navegação:** Item "Perfil" adicionado dentro da seção Administração, abaixo de Configurações, na barra lateral desktop/mobile e no menu superior do usuário.
- **Auto-edição de e-mail:** Possibilidade de qualquer usuário autenticado alterar o próprio e-mail diretamente no perfil, com validação de formato, normalização para minúsculas e unicidade no banco.

### Modificado
- **Atualização atômica de sessão:** Os dados de identidade do usuário na sessão são atualizados imediatamente após edição de perfil via `AuthHelper::refreshUserData()`.

### Segurança
- **Bloqueio de escalonamento de privilégios no perfil:** Alterações no perfil preservam estritamente o papel (`perfil`) e o status da conta, exigindo senha atual confirmada para troca de senha.

---

## [2.1.0] — 2026-10-10 — Módulo de Investimentos e Reservas

### Adicionado
- **Aba Investimentos no menu Movimentações:** Cadastro de posições (aplicações, reservas de emergência e "caixinhas") vinculadas a contas bancárias ativas.
- **Operações atômicas de investimento:** Registro de aportes (debita conta, credita posição), resgates (inverso), rendimentos (aumenta posição sem alterar conta) e atualizações patrimoniais de valor.
- **Resumo patrimonial:** Cards dedicados de patrimônio investido no Dashboard e na tela de Contas, totalmente desacoplados do Saldo Atual bancário.
- **Tabelas estruturais:** Migração `020_criar_tabelas_investimentos.php` criando `investimentos` e `investimento_movimentos` com isolamento estrito por `usuario_id`.

### Modificado
- **Descontinuação do tipo de conta "investimento":** Novas contas bancárias não aceitam mais o tipo `investimento`, mantendo reservas no módulo próprio.
- **Preferência de inclusão no Saldo Atual:** Cada conta bancária ativa possui seletor para inclusão/exclusão da soma do Saldo Atual do Dashboard.

---

## [2.0.0] — 2026-09-20 — Plataforma Multiusuário, PostgreSQL (Neon) e Isolamento Financeiro

### Adicionado
- **Autocadastro público:** Registro aberto em `/registrar` com clonagem automática de categorias padrão via `DefaultDataHelper`.
- **Perfil técnico `dev`:** Perfil operacional para gestão global de usuários, visualização de auditoria com JSON Diff, catálogo de meios de pagamento e migrations.
- **Banco PostgreSQL oficial (Neon):** Migração estrutural com suporte a SSL via `DATABASE_URL` e classe `SymoPostgresConnection`.
- **Armazenamento privado com Vercel Blob:** Upload e entrega segura de avatares e logos via Serverless Function `api/blob.js` autenticada.
- **Catálogo global de meios de pagamento:** Catálogo oficial com administração pelo perfil `dev` e ativação/inativação de uso individual por usuário.

### Segurança
- **Isolamento financeiro estrito (Anti-IDOR):** Todas as entidades financeiras (`contas`, `categorias`, `cartoes_credito`, `transacoes`, `transferencias`, `investimentos`, `lixeira`) ganham filtro obrigatório por `usuario_id`.
- **Proteção contra força bruta:** Bloqueio temporário após 5 falhas consecutivas de login em 15 minutos com registro em `seguranca_logs`.
