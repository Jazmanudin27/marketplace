#!/usr/bin/env bash
set -e

echo "=== Setup Nginx Reverse Proxy Port 5009 ==="

# 1. Cari file konfigurasi Nginx yang mengarah ke project marketplace
TARGET_PATH="/var/www/html/project/marketplace"
CONF_FILE=$(grep -rnwl '/etc/nginx' -e "$TARGET_PATH" 2>/dev/null | head -n 1 || true)

if [ -z "$CONF_FILE" ]; then
    # Fallback cari file di sites-enabled
    CONF_FILE=$(ls /etc/nginx/sites-enabled/* 2>/dev/null | head -n 1 || true)
fi

if [ -z "$CONF_FILE" ] || [ ! -f "$CONF_FILE" ]; then
    echo "❌ Error: Tidak dapat menemukan file konfigurasi Nginx di /etc/nginx."
    exit 1
fi

echo "✔ Menemukan file Nginx: $CONF_FILE"

# 2. Cek apakah blok proxy sudah ada
if grep -q "127.0.0.1:5009" "$CONF_FILE"; then
    echo "ℹ Blok proxy port 5009 sudah ada di $CONF_FILE."
else
    # Backup file konfigurasi
    BACKUP_FILE="${CONF_FILE}.bak_$(date +%s)"
    cp "$CONF_FILE" "$BACKUP_FILE"
    echo "✔ Backup dibuat: $BACKUP_FILE"

    # Blok proxy yang akan ditambahkan
    PROXY_BLOCK="    location /api/v2/owner/ {\n        proxy_pass http://127.0.0.1:5009;\n        proxy_http_version 1.1;\n        proxy_set_header Upgrade \$http_upgrade;\n        proxy_set_header Connection 'upgrade';\n        proxy_set_header Host \$host;\n        proxy_cache_bypass \$http_upgrade;\n    }\n"

    # Sisipkan sebelum baris 'location /' atau 'location ~ \\.php'
    if grep -q "location / " "$CONF_FILE" || grep -q "location / {" "$CONF_FILE"; then
        sed -i "/location \/ {/i\\$PROXY_BLOCK" "$CONF_FILE"
    elif grep -q "location ~ \\\.php" "$CONF_FILE"; then
        sed -i "/location ~ \\\.php/i\\$PROXY_BLOCK" "$CONF_FILE"
    else
        # Sisipkan sebelum penutup server block terakhir '}'
        sed -i '$e echo -e "\n'"$PROXY_BLOCK"'"' "$CONF_FILE"
    fi

    echo "✔ Blok proxy berhasil disisipkan ke $CONF_FILE"
fi

# 3. Test sintaks Nginx dan reload
echo "🔍 Menguji sintaks Nginx..."
if nginx -t; then
    echo "✔ Sintaks Nginx valid. Mereload Nginx..."
    systemctl reload nginx
    echo "🎉 BERHASIL! Proxy Nginx ke port 5009 telah aktif."
else
    echo "❌ Sintaks Nginx gagal! Mengembalikan file backup..."
    if [ -n "$BACKUP_FILE" ] && [ -f "$BACKUP_FILE" ]; then
        cp "$BACKUP_FILE" "$CONF_FILE"
        systemctl reload nginx || true
        echo "✔ Konfigurasi telah dikembalikan ke sebelumnya."
    fi
    exit 1
fi
