# Contexto do Projeto para Agentes de IA — Symo

Este arquivo fornece todas as diretrizes, contexto técnico, padrões arquiteturais e regras de operação para agentes de inteligência artificial que atuarem neste repositório em qualquer momento ou sessão.

---

## 1. Idioma e Comunicação

- **Idioma Obrigatório:** Responda e documente sempre em **português do Brasil**.
- Código, variáveis, nomes de funções, comentários e mensagens de erro no sistema devem ser claros, consistentes e em português do Brasil quando aplicável às regras de negócio.

---

## 2. Visão do Projeto e Stack Tecnológica

O **Symo** é uma plataforma web para gestão financeira pessoal e compartilhada (orçamento familiar ou empresarial em modelo *single-tenant* colaborativo).

- **Linguagem Backend:** PHP 8.1+ nativo (com tipagem estrita `declare(strict_types=1);`, sem frameworks pesados).
- **Banco de Dados:** MySQL 8.0+ / MariaDB 10.4+ (motor obrigatório `InnoDB`, charset `utf8mb4`, collation `utf8mb4_unicode_ci`).
- **Acesso a Dados:** PDO (*PHP Data Objects*) com *Prepared Statements* obrigatórios em 100% das consultas.
- **Padrão Arquitetural:** MVC (*Model-View-Controller*) desacoplado com *Front Controller* centralizado (`index.php`).
- **Tecnologias Frontend:**
  - HTML5 semântico.
  - CSS3 com variáveis / Design Tokens do tema *Symo* (`docs/DESIGN.md`).
  - JavaScript Vanilla (ES6+) para manipulação de DOM e modais.
  - Bootstrap 5.3.x (armazenado e servido localmente em `public/css/` e `public/js/`).
  - Bootstrap Icons (armazenado e servido localmente).
  - Chart.js 4.x (armazenado e servido localmente em `public/js/`).
- **Autonomia de Assets:** Nenhuma dependência externa via CDN em tempo de execução. Todos os assets residem na pasta `public/`.

---

## 3. Ambientes de Execução

- **Desenvolvimento Local:** Apache + PHP 8.x + MySQL (ex: XAMPP, WampServer, Laragon). O projeto roda tipicamente em subpasta dentro de `htdocs/`.
- **Produção:** Servidor Apache 2.4+ / PHP 8.x em hospedagem compartilhada (cPanel / Hostnet) ou VPS Linux.
- **Portabilidade:** É proibido o uso de URLs absolutas fixas em código. O sistema calcula a URL base dinamicamente ou a obtém de `config/config.php` (`BASE_URL`).

---

## 4. Estrutura de Diretórios do Projeto

```text
├── config/                  # Configurações de banco, app e segurança (HTTP Denied)
│   ├── .htaccess
│   ├── config.php
│   └── database.php
├── app/                     # Núcleo da aplicação MVC (HTTP Denied)
│   ├── .htaccess
│   ├── Controllers/
│   ├── Models/
│   ├── Views/
│   │   ├── layouts/
│   │   ├── auth/
│   │   ├── dashboard/
│   │   ├── transactions/
│   │   ├── accounts/
│   │   ├── categories/
│   │   ├── payment-methods/
│   │   ├── credit-cards/
│   │   ├── transfers/
│   │   ├── reports/
│   │   ├── users/
│   │   ├── trash/
│   │   ├── audit/
│   │   ├── settings/
│   │   └── errors/
│   └── Helpers/
├── database/                # Migrations e dados iniciais (HTTP Denied)
│   ├── .htaccess
│   ├── migrations/
│   └── seeds/
├── storage/                 # Logs e arquivos enviados (Protegido)
│   ├── .htaccess
│   ├── logs/
│   └── uploads/
│       ├── avatars/
│       └── logos/
├── public/                  # Assets estáticos servidos diretamente
│   ├── css/
│   ├── js/
│   ├── fonts/
│   └── img/
├── .htaccess                # Roteamento Front Controller (Rewrite para index.php)
├── index.php                # Ponto de entrada único da aplicação
├── migrate.php              # Script CLI de migrations
├── README.md                # Guia de instalação e requisitos
├── docs/                    # Documentação oficial e arquivos vivos
└── AGENTS.md                # Este arquivo de contexto
```

---

## 5. Comandos Principais

- **Executar Migrations no Terminal:**
  ```bash
  php migrate.php
  ```
- **Iniciar Servidor Embutido do PHP (para testes rápidos):**
  ```bash
  php -S localhost:8000 index.php
  ```

---

## 6. Regras de Segurança e Boas Práticas

1. **Proteção contra SQL Injection:** Proibida qualquer interpolação ou concatenação de variáveis em queries. Utilize exclusivamente PDO com *Prepared Statements* e `bindValue` / `bindParam`.
2. **Proteção contra XSS:** Toda variável ecoada em Views HTML deve passar obrigatoriamente por `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.
3. **Proteção contra CSRF:** Formulários com métodos `POST`, `PUT` ou `DELETE` devem conter token anti-CSRF gerado na sessão e validado no Controller correspondente.
4. **Armazenamento de Senhas:** Utilizar `password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12])` e `password_verify()`. Senhas nunca são salvas ou logadas em texto puro.
5. **Gestão Segura de Sessão:** 
   - Cookies configurados com `httponly = 1`, `samesite = Lax` e `use_only_cookies = 1`.
   - Executar `session_regenerate_id(true)` no login bem-sucedido.
   - Expiração estrita de sessão após 60 minutos de inatividade.
6. **Proteção contra Força Bruta:** Bloqueio temporário de 15 minutos após 5 tentativas consecutivas de login inválido por IP/e-mail, com registro na tabela `security_logs`.
7. **Controle de Acesso RBAC:**
   - Perfis: `admin` e `operador`.
   - Operadores não acessam áreas de usuários, lixeira, auditoria ou configurações globais.
   - Operadores só editam/excluem transações financeiras criadas por eles mesmos. Tentativas de alteração de registros de terceiros geram erro 403 e log de segurança.
8. **Proteção de Pastas e Arquivos:**
   - Todos os arquivos PHP internos contêm a guarda:
     ```php
     defined('APP_RUNNING') or die('Acesso direto proibido.');
     ```
   - Pastas `config/`, `app/`, `database/` e `storage/logs/` possuem `.htaccess` com `Require all denied`.
   - Pasta `storage/uploads/` possui `.htaccess` com execução de scripts desabilitada (`php_flag engine off`).
   - Não utilizar arquivos `.env`. As configurações residem em `config/config.php`.
9. **Upload Seguro:** Validação de tamanho máximo (2 MB), validação de MIME-Type real com `finfo_file` (aceitando apenas `image/jpeg`, `image/png`, `image/webp`), renomeação com hash randômico (`bin2hex(random_bytes(16))`) e remoção do arquivo anterior.
10. **Logs e Contingência:** Erros de sistema gravam em banco e, em caso de indisponibilidade de banco, caem automaticamente para `storage/logs/error.log`. Mensagens de erro visíveis ao usuário são sempre genéricas e amigáveis.

---

## 7. Interface e Design System

- Siga estritamente as diretrizes do arquivo `docs/DESIGN.md`.
- **Cores Principais:**
  - Electric Violet: `#6F02FF` (Ações principais, botões primários, branding).
  - Deep Lavender: `#793CFE` (Ações secundárias e elementos de apoio).
  - Bright Teal: `#3BD7D7` (Destaques, dados terciários, badges especiais).
  - Neutros: Fundo `Surface` (`#FAF9F9`), Cards em `Surface-Container-Lowest` (`#FFFFFF`), bordas em `Neutral 200/300`.
- **Tipografia:** Fonte *Inter*. Aplicar `tabular-nums` em tabelas e valores monetários para alinhamento perfeito.
- **Arredondamento:** Padrão 8px (`0.5rem`) para inputs, botões e cards; 16px (`1rem`) para modais.

---

## 8. Protocolo dos Arquivos Vivos

Todo agente de IA deve seguir rigorosamente este ciclo de trabalho:

### Antes de iniciar qualquer trabalho:
1. Ler `docs/FSD.md`.
2. Ler `docs/DESIGN.md`.
3. Ler `docs/INSUMOS.md`.
4. Ler `docs/PLANO.md`.
5. Ler `docs/STATUS.md`.
6. Ler `docs/ERROS.md`.

*Observação:* Use sempre caminhos relativos à raiz do projeto. Não transformar estes caminhos em links absolutos. Não usar links `file:///`. Não registrar caminhos locais específicos de uma máquina no repositório.

### Ao terminar qualquer trabalho:
1. Atualizar `docs/STATUS.md` com o estado real do projeto e tarefas concluídas.
2. Registrar erros e soluções em `docs/ERROS.md`, caso tenham ocorrido problemas.
3. Informar ao usuário o que foi feito de forma clara e objetiva.
4. Informar como testar ou validar a entrega realizada.

