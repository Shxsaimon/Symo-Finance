param([switch]$Run, [switch]$Migrate, [switch]$Seed, [switch]$Status, [int]$Port = 8000)
# Ambiente Local do Symo Finance (Protegido e ignorado pelo Git)
# Aponte DATABASE_URL para uma BRANCH DE TESTES do Neon.
$env:DATABASE_URL = 'postgresql://USUARIO:SENHA@HOST-DA-BRANCH.neon.tech/NOME_DB?sslmode=require'

# --- Blob (mesmo store da produção) -----------------------------------------
# O PHP não usa o token do Blob: ele chama o proxy api/blob.js já implantado na Vercel,
# que detém o BLOB_READ_WRITE_TOKEN. Aqui só são necessários a URL do proxy e o segredo.
# Valores em Vercel → Settings → Environment Variables (Production).
# ATENÇÃO: uploads feitos localmente gravam no Blob oficial.
$env:BLOB_PROXY_URL    = 'https://SEU-DOMINIO.vercel.app/api/blob'
$env:BLOB_PROXY_SECRET = 'COLE_AQUI_O_BLOB_PROXY_SECRET'

# --- Ambiente local ----------------------------------------------------------
if (-not $env:APP_ENV)      { $env:APP_ENV = 'local' }
if (-not $env:APP_DEBUG)    { $env:APP_DEBUG = 'true' }
if (-not $env:APP_BASE_URL) { $env:APP_BASE_URL = 'http://localhost:8000' }

# Opcional: trecho do host de PRODUÇÃO do Neon para bloquear execução acidental.
# $env:SYMO_PROD_HOST = 'ep-xxxxxxxx'

# Necessários apenas com -Seed (usuário dev local).
# $env:SYSTEM_DEV_NAME     = 'Dev Local'
# $env:SYSTEM_DEV_EMAIL    = 'dev@local.test'
# $env:SYSTEM_DEV_PASSWORD = 'senha-local'

# --- Execução ----------------------------------------------------------------
# Uso: .\load-env.ps1            só carrega as variáveis (use com ponto: . .\load-env.ps1)
#      .\load-env.ps1 -Run       carrega e sobe o servidor em http://localhost:8000
#      .\load-env.ps1 -Run -Migrate | -Seed    aplica migrations (e seed) antes de subir
#      .\load-env.ps1 -Status    mostra o status das migrations

$destino = ($env:DATABASE_URL -replace '^[a-z]+://[^@]*@', '') -replace '\?.*$', ''
Write-Host "Ambiente do Symo Finance carregado. Banco: $destino" -ForegroundColor Green

if ($env:SYMO_PROD_HOST -and $env:DATABASE_URL -like "*$($env:SYMO_PROD_HOST)*") {
    throw 'DATABASE_URL aponta para o host de produção (SYMO_PROD_HOST). Execução bloqueada.'
}

if ($Status -or $Migrate -or $Seed -or $Run) {
    Push-Location $PSScriptRoot
    try {
        $env:APP_BASE_URL = "http://localhost:$Port"
        if ($Status) { php migrate.php --status; return }
        if ($Seed) { php migrate.php --seed; if ($LASTEXITCODE -ne 0) { throw 'Falha no migrate/seed.' } }
        elseif ($Migrate) { php migrate.php; if ($LASTEXITCODE -ne 0) { throw 'Falha nas migrations.' } }
        if ($Run) {
            Write-Host "Servidor em http://localhost:$Port (Ctrl+C para parar)" -ForegroundColor Green
            php -S "localhost:$Port" index.php
        }
    } finally { Pop-Location }
}