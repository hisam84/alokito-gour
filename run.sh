#!/usr/bin/env bash
# ======================================================================
#   ALOKITO GOUR NEWS PORTAL - Local Server (Linux / macOS)
# ======================================================================

cd "$(dirname "$0")"

# Find PHP binary
PHP_BIN=""
if command -v php >/dev/null 2>&1; then
    PHP_BIN="$(command -v php)"
fi

if [ -z "$PHP_BIN" ]; then
    echo "======================================================================"
    echo " [!] PHP is not installed on this system."
    echo "======================================================================"
    echo " Please install PHP and SQLite extensions:"
    echo "   - On macOS: brew install php"
    echo "   - On Ubuntu/Debian: sudo apt update && sudo apt install -y php-cli php-sqlite3 php-gd php-mbstring php-curl"
    echo "======================================================================"
    exit 1
fi

# Auto-initialize SQLite database if not present
if [ ! -f "database/alokito_gour.sqlite" ]; then
    echo "[*] Database not found. Initializing local database..."
    "$PHP_BIN" -c php.ini database/init_sqlite.php
fi

PORT=8000
# Check if port is in use
if lsof -Pi :$PORT -sTCP:LISTEN -t >/dev/null 2>&1; then
    echo "[!] Port $PORT is busy. Trying port 8080..."
    PORT=8080
fi

echo "======================================================================"
echo "   Alokito Gour News Portal - Local Server"
echo "======================================================================"
echo "   PHP Engine:   $PHP_BIN"
echo "   Web Portal:   http://localhost:$PORT"
echo "   Admin Panel:  http://localhost:$PORT/admin/login.php"
echo "   ------------------------------------------------------------------"
echo "   Admin Login:"
echo "     Email:    admin@alokitogour.com"
echo "     Password: admin123"
echo "   ------------------------------------------------------------------"
echo "   Press Ctrl + C to stop the server."
echo "======================================================================"

# Open browser
if command -v xdg-open >/dev/null 2>&1; then
    (sleep 1 && xdg-open "http://localhost:$PORT") &
elif command -v open >/dev/null 2>&1; then
    (sleep 1 && open "http://localhost:$PORT") &
fi

"$PHP_BIN" -c php.ini -S "0.0.0.0:$PORT" router.php

