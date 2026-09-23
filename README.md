# Symo Finance

O Symo Finance é uma aplicacao web de gestão financeira para pessoas, casais, familias, freelancers e MEIs. Cada pessoa possui seus dados financeiros por padrao e, na visao de evolucao do produto, podera compartilhar seletivamente contas financeiras com outras pessoas.

O projeto usa PHP nativo com MVC, PostgreSQL no Neon, Vercel para execucao serverless e Vercel Blob para arquivos privados. O MySQL permanece como caminho de compatibilidade legado, nao como ambiente recomendado para novos deploys.

## Funcionalidades atuais

- Autocadastro, login por e-mail e senha, sessoes persistidas no banco e protecao contra tentativas repetidas de login.
- Isolamento dos dados financeiros por usuario: contas, categorias, meios de pagamento, cartoes, transacoes, transferencias, relatorios e lixeira.
- Dashboard mensal com saldo consolidado, receitas, despesas, evolucao e despesas por categoria.
- Contas financeiras, categorias, meios de pagamento, receitas, despesas, parcelamentos, transferencias e cartoes de credito com faturas.
- Relatorios filtraveis e exportacao CSV compativel com planilhas.
- Lixeira, auditoria, logs de seguranca e feedback beta.
- Upload e entrega autenticada de avatares e logotipo por Vercel Blob.
- Operacao de migrations por GitHub Actions para ambientes remotos.

## O que ainda esta planejado

- Login com Google OAuth 2.0.
- Verificacao de e-mail e recuperacao de senha por e-mail. A tela de esqueci a senha existe, mas o fluxo de redefinicao ainda nao esta implementado.
- Contas compartilhadas por convite: o criador podera convidar outro usuario por e-mail, revogar o acesso e definir se o convidado pode visualizar, lancar movimentacoes e editar somente os proprios lancamentos. O criador tambem definira se o historico ja existente sera visivel.

Esses itens sao direcao de produto, nao recursos disponiveis no estado atual do codigo.

## Arquitetura

```text
Navegador
  -> Vercel / api/index.php
  -> Front Controller PHP / MVC
  -> Neon PostgreSQL
  -> Vercel Blob privado (avatares e logotipo)

GitHub Actions
  -> migrate.php
  -> Neon PostgreSQL
```

- `index.php`: front controller, rotas, seguranca HTTP e sessao.
- `app/`: controllers, models, helpers e views.
- `database/migrations/`: evolucao versionada do banco.
- `api/index.php`: adaptador para o runtime PHP da Vercel e entrega de assets publicos.
- `api/blob.js`: proxy privado para operacoes no Vercel Blob.
- `.github/workflows/database-migrations.yml`: workflow manual de status, migrations, seed e reset controlado.

## Ambientes oficiais

| Finalidade | Servicos |
| --- | --- |
| Producao e homologacao | Vercel + Neon PostgreSQL + Vercel Blob + GitHub Actions |
| Compatibilidade legado | PHP com PDO MySQL/MariaDB |

Para o fluxo oficial, use PHP 8.2 ou superior com `pdo_pgsql`. O codigo mantem suporte condicional a MySQL para instalacoes antigas, mas novas configuracoes devem usar `DATABASE_URL` PostgreSQL.

## Configuracao

O arquivo de referencia e [config/config.example.php](config/config.example.php). A aplicacao le variaveis de ambiente; nao armazena segredos no repositorio.

| Variavel | Finalidade |
| --- | --- |
| `DATABASE_URL` | URL PostgreSQL do Neon; ativa o driver oficial. |
| `APP_BASE_URL` | URL publica da aplicacao. |
| `APP_ENV` e `APP_DEBUG` | Ambiente e diagnostico. Em producao, use `production` e `false`. |
| `SYSTEM_DEV_NAME`, `SYSTEM_DEV_EMAIL`, `SYSTEM_DEV_PASSWORD` | Usuario tecnico global criado pelo seed. |
| `BLOB_PROXY_SECRET` | Segredo entre o PHP e a Function de Blob. |
| `BLOB_PROXY_URL` | URL da Function `api/blob.js`. |
| `BLOB_READ_WRITE_TOKEN` | Credencial do Vercel Blob usada pela Function Node. |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | Reservadas para o OAuth Google, ainda nao integrado. |

O perfil `dev` e operacional e deve ser restrito a quem administra a plataforma. Usuarios que se cadastram recebem o perfil `admin` de sua propria conta financeira.

## Banco de dados e migrations

Em ambiente com PHP e `pdo_pgsql` habilitado:

```bash
php migrate.php --status
php migrate.php
php migrate.php --seed
```

Em producao, execute essas operacoes pelo workflow manual **Symo Database Migrations** no GitHub Actions, configurando os secrets `NEON_DATABASE_URL`, `SYSTEM_DEV_NAME`, `SYSTEM_DEV_EMAIL` e `SYSTEM_DEV_PASSWORD`.

`php migrate.php --fresh --confirmar` apaga todo o schema PostgreSQL conectado antes de recria-lo. Use somente em ambiente descartavel. No GitHub Actions, a acao `fresh` exige a confirmacao textual `RESETAR`.

## Desenvolvimento local

1. Configure um PostgreSQL acessivel e exporte `DATABASE_URL`.
2. Garanta que o PHP tenha `pdo_pgsql`, `pdo`, `mbstring`, `fileinfo`, `json`, `session` e `curl` habilitados.
3. Execute as migrations.
4. Inicie o servidor PHP:

```bash
php -S localhost:8000 index.php
```

5. Acesse `http://localhost:8000`.

Para testar uploads privados fora da Vercel, configure tambem as variaveis do Blob e uma URL alcancavel para o proxy. Sem elas, o nucleo financeiro continua utilizavel, mas uploads remotos nao funcionam.

## Seguranca

- Consultas usam PDO e statements preparados.
- Formularios mutaveis usam CSRF.
- Senhas usam BCRYPT com custo 12.
- Sessoes usam cookies `HttpOnly`, `SameSite=Lax` e `Secure` sob HTTPS.
- Dados financeiros sao filtrados pelo usuario autenticado.
- O Blob permanece privado; imagens sao servidas por rotas autenticadas.

## Documentacao

- [FSD](docs/FSD.md): especificacao funcional e tecnica, incluindo o roadmap de contas compartilhadas.
- [STATUS](docs/STATUS.md): estado de implementacao e proximo passo tecnico.
- [ERROS](docs/ERROS.md): problemas conhecidos e solucoes aplicadas.
- [DESIGN](docs/DESIGN.md): design system.
- [AGENTS](AGENTS.md): convencoes para contribuidores automatizados.