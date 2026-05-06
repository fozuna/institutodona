Param(
    [Parameter(Mandatory = $false)]
    [string]$CommitMessage = "chore: automated commit with migrations",
    [Parameter(Mandatory = $false)]
    [switch]$NoCommit
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$logDir = Join-Path $root "storage\logs"
$null = New-Item -ItemType Directory -Path $logDir -Force
$logFile = Join-Path $logDir "auto_commit_$timestamp.log"
$statusFile = Join-Path $logDir "auto_commit_last_status.json"

function Write-Log {
    Param([string]$Message, [string]$Level = "INFO")
    $line = "[{0}] [{1}] {2}" -f (Get-Date -Format "yyyy-MM-dd HH:mm:ss"), $Level, $Message
    Write-Host $line
    Add-Content -Path $logFile -Value $line
}

function Run-Step {
    Param(
        [string]$Name,
        [scriptblock]$Action
    )
    Write-Log "Iniciando etapa: $Name"
    & $Action
    Write-Log "Etapa concluida: $Name"
}

function Notify-Status {
    Param([string]$Result, [string]$Summary)
    $payload = @{
        ts = (Get-Date).ToString("s")
        result = $Result
        summary = $Summary
        log_file = $logFile
    } | ConvertTo-Json -Depth 5
    Set-Content -Path $statusFile -Value $payload -Encoding UTF8
    if ($env:AUTO_COMMIT_WEBHOOK_URL) {
        try {
            Invoke-RestMethod -Uri $env:AUTO_COMMIT_WEBHOOK_URL -Method Post -ContentType "application/json" -Body $payload | Out-Null
            Write-Log "Notificacao webhook enviada."
        } catch {
            Write-Log ("Falha ao enviar webhook: " + $_.Exception.Message) "WARN"
        }
    }
}

function Get-PendingMigrations {
    $json = php app/database/migrate_status.php 2>$null
    if (-not $json) { return @() }
    $obj = $json | ConvertFrom-Json
    return @($obj.pending)
}

function Get-ChangedFiles {
    $lines = git status --porcelain
    $files = @()
    foreach ($l in $lines) {
        if ($l.Length -ge 4) {
            $files += $l.Substring(3)
        }
    }
    return $files
}

function Ensure-MigrationTemplateIfNeeded {
    $changed = Get-ChangedFiles
    $schemaTouch = $changed | Where-Object {
        $_ -match '^app/models/.+Model\.php$' -or
        $_ -match '^app/database/schema\.sql$' -or
        $_ -match '^app/database/Database\.php$'
    }
    $hasNewApply = $changed | Where-Object { $_ -match '^app/database/migrations/\d+.*_apply\.sql$' }
    if ($schemaTouch -and -not $hasNewApply) {
        $prefix = Get-Date -Format "yyyyMMddHHmmss"
        $base = "${prefix}_auto_schema_update"
        $apply = Join-Path $root "app\database\migrations\${base}_apply.sql"
        $rollback = Join-Path $root "app\database\migrations\${base}_rollback.sql"
        $verify = Join-Path $root "app\database\migrations\${base}_verify.sql"
        Set-Content -Path $apply -Encoding UTF8 -Value @"
-- ${base}_apply.sql
-- TODO: preencher DDL idempotente para alteracoes de schema detectadas.
SELECT 1;
"@
        Set-Content -Path $rollback -Encoding UTF8 -Value @"
-- ${base}_rollback.sql
-- TODO: preencher rollback correspondente.
SELECT 1;
"@
        Set-Content -Path $verify -Encoding UTF8 -Value @"
-- ${base}_verify.sql
-- TODO: incluir validacoes pos-migration.
SELECT 1;
"@
        git add $apply $rollback $verify | Out-Null
        throw "Schema alterado sem migration. Templates gerados automaticamente em app/database/migrations. Preencha-os e execute novamente."
    }
}

function Validate-SqlFiles {
    $files = Get-ChangedFiles | Where-Object { $_ -match '^app/database/migrations/.*\.(sql)$' }
    foreach ($f in $files) {
        $full = Join-Path $root $f
        if (-not (Test-Path $full)) { continue }
        $content = Get-Content -Path $full -Raw
        if ([string]::IsNullOrWhiteSpace($content)) {
            throw "SQL vazio: $f"
        }
        if ($content -match 'TODO:') {
            throw "SQL com TODO pendente: $f"
        }
        if ($content -notmatch ';') {
            throw "SQL sem delimitador ';': $f"
        }
    }
}

function Join-OrDefault {
    Param(
        [array]$Items,
        [string]$Default = "nenhum"
    )
    if ($null -eq $Items -or $Items.Count -eq 0) {
        return $Default
    }
    return ($Items -join ', ')
}

$rollbackCandidates = @()
$beforePending = @()

try {
    Set-Location $root

    Run-Step "Deteccao de schema e migrations" {
        Ensure-MigrationTemplateIfNeeded
    }

    Run-Step "Validacao de sintaxe SQL basica" {
        Validate-SqlFiles
    }

    Run-Step "Status inicial de migrations" {
        $script:beforePending = Get-PendingMigrations
        Write-Log ("Migrations pendentes antes: " + (Join-OrDefault -Items $beforePending -Default "nenhuma"))
    }

    Run-Step "Aplicacao de migrations em dev" {
        php app/database/migrate.php | Out-Host
    }

    Run-Step "Identificacao de rollback candidates" {
        $afterPending = Get-PendingMigrations
        $appliedNow = @($beforePending | Where-Object { $afterPending -notcontains $_ })
        foreach ($version in $appliedNow) {
            if ($version -match '^(.*)_apply\.sql$') {
                $rollbackVersion = "$($matches[1])_rollback.sql"
                $rollbackPath = Join-Path $root ("app\database\migrations\" + $rollbackVersion)
                if (Test-Path $rollbackPath) {
                    $rollbackCandidates += $rollbackPath
                }
            }
        }
        Write-Log ("Rollback candidates: " + (Join-OrDefault -Items $rollbackCandidates -Default "nenhum"))
    }

    Run-Step "Teste de integridade de banco" {
        php app/tests/migration_schema_validation_smoke.php | Out-Host
        php app/tests/migration_runner_status_smoke.php | Out-Host
    }

    Run-Step "Testes de regressao alvo" {
        php app/tests/indicadores_validation_unit_test.php | Out-Host
        php app/tests/treinamento_module_integration_test.php | Out-Host
    }

    if (-not $NoCommit) {
        Run-Step "Commit automatizado" {
            git add -A
            git commit -m $CommitMessage
        }
    } else {
        Write-Log "NoCommit ativo: commit nao executado." "WARN"
    }

    Write-Log "Pipeline concluido com sucesso."
    Notify-Status -Result "success" -Summary "Pipeline de migration+commit finalizado."
    exit 0
}
catch {
    Write-Log ("Falha no pipeline: " + $_.Exception.Message) "ERROR"
    if ($rollbackCandidates.Count -gt 0) {
        foreach ($rollback in $rollbackCandidates) {
            try {
                Write-Log "Executando rollback automatico: $rollback" "WARN"
                php app/database/run_sql_file.php $rollback | Out-Host
            } catch {
                Write-Log ("Falha no rollback {0}: {1}" -f $rollback, $_.Exception.Message) "ERROR"
            }
        }
    } else {
        Write-Log "Nenhum rollback automatico aplicavel foi identificado." "WARN"
    }
    Notify-Status -Result "error" -Summary $_.Exception.Message
    exit 1
}
