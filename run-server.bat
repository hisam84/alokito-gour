@echo off
title Alokito Gour Local Development Server
echo ========================================================
echo   Alokito Gour News Portal - Local Development Server
echo ========================================================
echo.
echo Local Database: database\alokito_gour.sqlite [Active]
echo Web Portal:     http://localhost:8000
echo Admin Panel:    http://localhost:8000/admin/login.php
echo.
echo Admin Login:
echo   Email:    admin@alokitogour.com
echo   Password: admin123
echo.
echo Starting server... Press Ctrl+C to stop.
echo.

php -c php.ini -S localhost:8000 router.php
pause
