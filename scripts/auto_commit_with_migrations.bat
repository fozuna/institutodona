@echo off
setlocal
set MSG=%*
if "%MSG%"=="" set MSG=chore: automated commit with migrations
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0auto_commit_with_migrations.ps1" -CommitMessage "%MSG%"
endlocal
