# Symo — Gestão Financeira Pessoal e Compartilhada

Plataforma web para controle financeiro centralizado e colaborativo em tempo real.

---

## 1. Requisitos do Sistema

- **Servidor Web:** Apache 2.4+ com módulo `mod_rewrite` habilitado.
- **PHP:** Versão 8.1 ou superior com extensões `pdo`, `pdo_mysql`, `mbstring`, `fileinfo`, `json` e `session`.
- **Banco de Dados:** MySQL 8.0+ ou MariaDB 10.4+ (motor `InnoDB`, charset `utf8mb4`).

---

## 2. Instalação e Execução Local

1. **Clonar / Posicionar o Projeto:**
   - Coloque os arquivos dentro do diretório do servidor web (ex: `htdocs/Symo_Finance/` no XAMPP).

2. **Configuração do Banco:**
   - Ajuste os parâmetros de conexão em `config/config.php` se necessário (padrão: host `localhost`, usuário `root`, senha vazia, banco `symo_db`).

3. **Executar as Migrations:**
   - Execute o script via CLI:
   ```bash
   php migrate.php
   ```

4. **Acesso no Navegador:**
   - Abra a URL local: `http://localhost/Symo_Finance/`

---

## 3. Estrutura de Documentos Oficiais

- `docs/FSD.md` — Especificação Funcional e Técnica completa.
- `docs/DESIGN.md` — Guia de Estilo e Design Tokens (*Symo*).
- `docs/PLANO.md` — Plano de Construção Incremental.
- `docs/STATUS.md` — Acompanhamento do progresso das fases.
- `docs/ERROS.md` — Registro de problemas e soluções.
- `AGENTS.md` — Contexto oficial para agentes de IA.

