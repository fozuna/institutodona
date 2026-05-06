Param(
    [switch]$LocalOnly
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

git config core.hooksPath scripts/git-hooks | Out-Null

if (-not $LocalOnly) {
    Write-Host "Git hooks configurados para este repositorio: scripts/git-hooks"
    Write-Host "Hook ativo: pre-commit -> scripts/auto_commit_with_migrations.ps1 -NoCommit"
}
