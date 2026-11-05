# Capturas de Tela do Sistema — Symo Finance

Esta pasta armazena as capturas de tela oficiais do **Symo Finance** para exibição no `README.md`, documentação técnica e portfólio.

> [!IMPORTANT]
> **Regra de Privacidade e Segurança:** Todas as capturas de tela devem ser feitas exclusivamente utilizando a massa de dados fictícia gerada pelo seed de demonstração (`php migrate.php --seed-demo` / `database/seeds/dev_demo_seeder.php`), nunca expondo dados financeiros ou e-mails reais.

---

## Checklist de Telas e Nomenclatura Padrão

Salve os arquivos `.png` nesta pasta (`docs/screenshots/`) utilizando exatamente os nomes abaixo:

### Desktop (1920x1080)
- [x] `01-login.png` — Tela de Login (`/login`) e link de autocadastro
- [x] `02-cadastro.png` — Tela de Criação de Conta (`/registrar`)
- [x] `03-dashboard.png` — Dashboard Principal (`/dashboard`) com os 5 cards de KPIs, gráficos de evolução/categorias, compromissos pendentes e resumo de cartões
- [x] `04-lancamentos.png` — Listagem de Lançamentos Financeiros (`/transacoes`) com filtros, indicadores do período e tabela zebrada
- [x] `05-modal-despesa-cartao.png` — Modal de Nova Despesa demonstrando a seleção de Cartão de Crédito e parcelamento
- [x] `06-transferencias.png` — Tela de Transferências entre Contas (`/transferencias`)
- [x] `07-cartoes-credito.png` — Visão Geral de Cartões de Crédito e Limites (`/cartoes`)
- [x] `08-fatura-cartao.png` — Detalhamento de Fatura Mensal do Cartão (`/faturas/{id}`) com ciclo de fechamento/vencimento
- [x] `09-investimentos.png` — Aba de Investimentos e Reservas (`/investimentos`) com resumo patrimonial e rendimentos
- [x] `10-investimento-detalhe.png` — Histórico de Movimentações de uma Posição de Investimento (`/investimentos/{id}`)
- [x] `11-contas-bancarias.png` — Gestão de Contas Bancárias (`/contas`) com cards de Saldo Atual vs. Investimentos
- [x] `12-categorias.png` — Gestão de Categorias de Receitas e Despesas (`/categorias`) com ícones e cores
- [x] `13-meios-pagamento.png` — Catálogo de Meios de Pagamento (`/meios-pagamento`) com ativação individual
- [x] `14-relatorios.png` — Relatórios Analíticos e Sintéticos (`/relatorios`) com botão de exportação CSV
- [x] `15-lixeira.png` — Lixeira do Sistema (`/lixeira`) com restauração atômica de saldos
- [x] `16-auditoria-diff.png` — Trilha de Auditoria (`/auditoria`) exibindo o modal de comparativo JSON Diff (Antes vs. Depois)
- [x] `17-configuracoes.png` — Painel de Configurações e Status/Disparo de Migrations via GitHub Actions (`/configuracoes`)
- [x] `18-perfil.png` — Tela de Perfil do Usuário (`/perfil`)

### Mobile (Responsividade em Smartphones — ~390px a 430px)
- [x] `mobile-01-login.png` — Tela de Login (`/login`) adaptada para smartphone
- [x] `mobile-02-cadastro.png` — Tela de Criação de Conta (`/registrar`)
- [x] `mobile-03-menu-offcanvas.png` — Menu lateral aberto via botão hambúrguer mobile (offcanvas em gradiente)
- [x] `mobile-04-dashboard1.png` — Dashboard mobile (topo, boas-vindas e KPIs de Saldo Atual e Investimentos)
- [x] `mobile-04-dashboard2.png` — Dashboard mobile (KPIs de Receitas, Despesas e Balanço Líquido)
- [x] `mobile-04-dashboard3.png` — Dashboard mobile (gráficos Chart.js empilhados, compromissos e limites de cartões)
- [x] `mobile-05-lancamentos1.png` — Listagem de Lançamentos (filtros e indicadores do período)
- [x] `mobile-05-lancamentos2.png` — Listagem de Lançamentos (tabela financeira com rolagem adaptada)
- [x] `mobile-06-modal-despesa-cartao.png` — Modal de Nova Despesa em tela cheia mobile com opções de cartão
- [x] `mobile-07-transferencias1.png` — Transferências entre Contas (cards de resumo)
- [x] `mobile-07-transferencias2.png` — Transferências entre Contas (histórico e ações)
- [x] `mobile-08-cartoes-credito.png` — Visão Geral de Cartões de Crédito e Limites (`/cartoes`)
- [x] `mobile-09-fatura-cartao.png` — Detalhamento de Fatura Mensal (`/faturas/{id}`) adaptada para mobile
- [x] `mobile-10-investimentos.png` — Aba de Investimentos e Reservas (`/investimentos`)
- [x] `mobile-11-investimento-detalhe.png` — Histórico de Movimentações de uma Posição (`/investimentos/{id}`)
- [x] `mobile-12-contas-bancarias.png` — Gestão de Contas Bancárias (`/contas`)
- [x] `mobile-13-categorias.png` — Gestão de Categorias (`/categorias`)
- [x] `mobile-14-meios-pagamento.png` — Catálogo de Meios de Pagamento (`/meios-pagamento`)
- [x] `mobile-15-relatorios1.png` — Relatórios Analíticos e Sintéticos (filtros e gráficos de participação)
- [x] `mobile-15-relatorios2.png` — Relatórios Analíticos (extrato detalhado)
- [x] `mobile-16-lixeira.png` — Lixeira do Sistema (`/lixeira`)
- [x] `mobile-17-auditoria-diff.png` — Trilha de Auditoria e modal JSON Diff (`/auditoria`)
- [x] `mobile-18-configuracoes1.png` — Configurações (preferências de exibição)
- [x] `mobile-18-configuracoes2.png` — Configurações (painel de status de migrations)
- [x] `mobile-18-configuracoes3.png` — Configurações (histórico de execuções GitHub Actions)
- [x] `mobile-19-perfil1.png` — Tela de Perfil do Usuário (dados pessoais e avatar)
- [x] `mobile-19-perfil2.png` — Tela de Perfil do Usuário (alteração segura de senha)
