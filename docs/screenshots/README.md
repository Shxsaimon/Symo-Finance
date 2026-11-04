# Capturas de Tela do Sistema — Symo Finance

Esta pasta armazena as capturas de tela oficiais do **Symo Finance** para exibição no `README.md`, documentação técnica e portfólio.

> [!IMPORTANT]
> **Regra de Privacidade e Segurança:** Todas as capturas de tela devem ser feitas exclusivamente utilizando a massa de dados fictícia gerada pelo seed de demonstração (`php migrate.php --seed-demo` / `database/seeds/dev_demo_seeder.php`), nunca expondo dados financeiros ou e-mails reais.

---

## Checklist de Telas e Nomenclatura Padrão

Salve os arquivos `.png` nesta pasta (`docs/screenshots/`) utilizando exatamente os nomes abaixo:

- [ ] `01-login.png` — Tela de Login (`/login`) e link de autocadastro
- [ ] `02-cadastro.png` — Tela de Criação de Conta (`/registrar`)
- [ ] `03-dashboard.png` — Dashboard Principal (`/dashboard`) com os 5 cards de KPIs, gráficos de evolução/categorias, compromissos pendentes e resumo de cartões
- [ ] `04-lancamentos.png` — Listagem de Lançamentos Financeiros (`/transacoes`) com filtros, indicadores do período e tabela zebrada
- [ ] `05-modal-despesa-cartao.png` — Modal de Nova Despesa demonstrando a seleção de Cartão de Crédito e parcelamento
- [ ] `06-transferencias.png` — Tela de Transferências entre Contas (`/transferencias`)
- [ ] `07-cartoes-credito.png` — Visão Geral de Cartões de Crédito e Limites (`/cartoes`)
- [ ] `08-fatura-cartao.png` — Detalhamento de Fatura Mensal do Cartão (`/faturas/{id}`) com ciclo de fechamento/vencimento
- [ ] `09-investimentos.png` — Aba de Investimentos e Reservas (`/investimentos`) com resumo patrimonial e rendimentos
- [ ] `10-investimento-detalhe.png` — Histórico de Movimentações de uma Posição de Investimento (`/investimentos/{id}`)
- [ ] `11-contas-bancarias.png` — Gestão de Contas Bancárias (`/contas`) com cards de Saldo Atual vs. Investimentos
- [ ] `12-categorias.png` — Gestão de Categorias de Receitas e Despesas (`/categorias`) com ícones e cores
- [ ] `13-meios-pagamento.png` — Catálogo de Meios de Pagamento (`/meios-pagamento`) com ativação individual
- [ ] `14-relatorios.png` — Relatórios Analíticos e Sintéticos (`/relatorios`) com botão de exportação CSV
- [ ] `15-lixeira.png` — Lixeira do Sistema (`/lixeira`) com restauração atômica de saldos
- [ ] `16-auditoria-diff.png` — Trilha de Auditoria (`/auditoria`) exibindo o modal de comparativo JSON Diff (Antes vs. Depois)
- [ ] `17-configuracoes.png` — Painel de Configurações e Status/Disparo de Migrations via GitHub Actions (`/configuracoes`)
- [ ] `18-perfil.png` — Tela de Perfil do Usuário (`/perfil`)
