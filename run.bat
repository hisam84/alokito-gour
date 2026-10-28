@echo off
title Alokito Gour Local Server
cd /d "%~dp0"

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0tools\run_server.ps1"

if %errorlevel% neq 0 (
    echo.
    echo [!] Server exited or encountered an error.
    pause
)
