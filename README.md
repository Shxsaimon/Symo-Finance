# Symo Finance

O Symo Finance é uma aplicação web de gestão financeira para pessoas, casais, famílias, freelancers e MEIs. Cada pessoa possui seus dados financeiros por padrão e, na visão de evolução do produto, poderá compartilhar seletivamente contas financeiras com outras pessoas.

O projeto usa PHP nativo com MVC, PostgreSQL no Neon, Vercel para execução serverless e Vercel Blob para arquivos privados. O MySQL permanece como caminho de compatibilidade legado, não como ambiente recomendado para novos deploys.

## Funcionalidades atuais

- Autocadastro, login por e-mail e senha, sessões persistidas no banco e proteção contra tentativas repetidas de login.
- Isolamento dos dados financeiros por usuário: contas, categorias, meios de pagamento, cartões, transações, transferências, relatórios e lixeira.
- Dashboard mensal com Saldo Atual, receitas, despesas, evolução e despesas por categoria.
- Contas financeiras, categorias, meios de pagamento, receitas, despesas, parcelamentos, transferências e cartões de crédito com faturas.
- Relatórios filtráveis e exportação CSV compatível com planilhas.
- Lixeira, auditoria, logs de segurança e feedback beta.
- Upload e entrega autenticada de avatares e logotipo por Vercel Blob.
- Operação de migrations por GitHub Actions para ambientes remotos.

## O que ainda está planejado

- Login com Google OAuth 2.0.
- Verificação de e-mail e recuperação de senha por e-mail. A tela de esqueci a senha existe, mas o fluxo de redefinição ainda não está implementado.
- Contas compartilhadas por convite: o criador poderá convidar outro usuário por e-mail, revogar o acesso e definir se o convidado pode visualizar, lançar movimentações e editar somente os próprios lançamentos. O criador também definirá se o histórico já existente será visível.

Esses itens são direção de produto, não recursos disponíveis no estado atual do código.

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

- `index.php`: front controller, rotas, segurança HTTP e sessão.
- `app/`: controllers, models, helpers e views.
- `database/migrations/`: evolução versionada do banco.
- `api/index.php`: adaptador para o runtime PHP da Vercel e entrega de assets públicos.
- `api/blob.js`: proxy privado para operações no Vercel Blob.
- `.github/workflows/database-migrations.yml`: workflow manual de status, migrations, seed e reset controlado.

## Ambientes oficiais

| Finalidade | Serviços |
| --- | --- |
| Produção e homologação | Vercel + Neon PostgreSQL + Vercel Blob + GitHub Actions |
| Compatibilidade legado | PHP com PDO MySQL/MariaDB |

Para o fluxo oficial, use PHP 8.2 ou superior com `pdo_pgsql`. O código mantém suporte condicional ao MySQL para instalações antigas, mas novas configurações devem usar `DATABASE_URL` PostgreSQL.

## Configuração

O arquivo de referência é [config/config.example.php](config/config.example.php). A aplicação lê variáveis de ambiente; não armazena segredos no repositório.

| Variável | Finalidade |
| --- | --- |
| `DATABASE_URL` | URL PostgreSQL do Neon; ativa o driver oficial. |
| `APP_BASE_URL` | URL pública da aplicação. |
| `APP_ENV` e `APP_DEBUG` | Ambiente e diagnóstico. Em produção, use `production` e `false`. |
| `SYSTEM_DEV_NAME`, `SYSTEM_DEV_EMAIL`, `SYSTEM_DEV_PASSWORD` | Usuário técnico da plataforma criado pelo seed; seus dados financeiros permanecem no próprio escopo. |
| `BLOB_PROXY_SECRET` | Segredo entre o PHP e a Function de Blob. |
| `BLOB_PROXY_URL` | URL da Function `api/blob.js`. |
| `BLOB_READ_WRITE_TOKEN` | Credencial do Vercel Blob usada pela Function Node. |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | Reservadas para o OAuth Google, ainda não integrado. |

O perfil `dev` é operacional e deve ser restrito a quem administra a plataforma. Usuários que se cadastram recebem o perfil `admin` de sua própria conta financeira.

## Banco de dados e migrations

Em ambiente com PHP e `pdo_pgsql` habilitado:

```bash
php migrate.php --status
php migrate.php
php migrate.php --seed
```

Em produção, execute essas operações pelo workflow manual **Symo Database Migrations** no GitHub Actions, configurando os secrets `NEON_DATABASE_URL`, `SYSTEM_DEV_NAME`, `SYSTEM_DEV_EMAIL` e `SYSTEM_DEV_PASSWORD`.

`php migrate.php --fresh --confirmar` apaga todo o schema PostgreSQL conectado antes de recriá-lo. Use somente em ambiente descartável. No GitHub Actions, a ação `fresh` exige a confirmação textual `RESETAR`.

## Desenvolvimento local

Fluxo recomendado, sem XAMPP e com banco isolado da produção:

1. Crie uma branch do Neon (*Branches → Create branch*) e use a connection string dela, nunca a de produção.
2. Copie `load-env.example.ps1` para `load-env.ps1` (ignorado pelo Git) e preencha `DATABASE_URL`.
3. Preencha também `BLOB_PROXY_URL` (URL do proxy `api/blob` já implantado na Vercel) e `BLOB_PROXY_SECRET`; uploads locais gravam no Blob oficial.
4. Execute `.\load-env.ps1 -Run -Migrate` (use `-Seed` na primeira vez, com `SYSTEM_DEV_*` definidos) para aplicar as migrations e subir o servidor em `http://localhost:8000`. `.\load-env.ps1 -Status` só consulta as migrations.

Passos manuais equivalentes:

1. Configure um PostgreSQL acessível e exporte `DATABASE_URL`.
2. Garanta que o PHP tenha `pdo_pgsql`, `pdo`, `mbstring`, `fileinfo`, `json`, `session` e `curl` habilitados.
3. Execute as migrations.
4. Inicie o servidor PHP:

```bash
php -S localhost:8000 index.php
```

5. Acesse `http://localhost:8000`.

Para testar uploads privados fora da Vercel, configure também as variáveis do Blob e uma URL alcançável para o proxy. Sem elas, o núcleo financeiro continua utilizável, mas uploads remotos não funcionam.

## Segurança

- Consultas usam PDO e statements preparados.
- Formulários mutáveis usam CSRF.
- Senhas usam BCRYPT com custo 12.
- Sessões usam cookies `HttpOnly`, `SameSite=Lax` e `Secure` sob HTTPS.
- Dados financeiros são filtrados pelo usuário autenticado.
- O Blob permanece privado; imagens são servidas por rotas autenticadas.

## Documentação

- [FSD](docs/FSD.md): especificação funcional e técnica, incluindo o roadmap de contas compartilhadas.
- [STATUS](docs/STATUS.md): estado de implementação e próximo passo técnico.
- [ERROS](docs/ERROS.md): problemas conhecidos e soluções aplicadas.
- [DESIGN](docs/DESIGN.md): design system.
- [AGENTS](AGENTS.md): convenções para contribuidores automatizados.