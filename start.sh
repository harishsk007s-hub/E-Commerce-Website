#!/bin/bash

# ==============================================================================
# Environment Propagation for PHP
# ==============================================================================
echo "Propagating environment variables to /var/www/html/.env..."
env | grep -E "^(DB_|DATABASE_URL|SMTP_|EMAIL_|ALLOWED_|VITE_|APP_ENV|BASE_URL|FRONTEND_URL|X_API_KEY|RAZORPAY_)" > /var/www/html/.env

# ==============================================================================
# Start Node.js Express Backend
# ==============================================================================
echo "Starting Node.js Express server on port 8000..."
cd /var/www/html/server

# Run Express in the background and redirect output to log file
node dist/index.js > /var/log/express.log 2>&1 &
EXPRESS_PID=$!

# Delay to ensure Express starts correctly
sleep 2

# Verify if Express process is still running
if ! kill -0 $EXPRESS_PID 2>/dev/null; then
  echo "❌ Node.js Express backend failed to start! Printing logs:"
  cat /var/log/express.log
  exit 1
else
  echo "✅ Node.js Express backend started successfully (PID: $EXPRESS_PID)."
fi

# ==============================================================================
# Port Configuration for Apache (Render dynamically assigns $PORT)
# ==============================================================================
if [ ! -z "$PORT" ]; then
  echo "Changing Apache listen port to $PORT..."
  sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
  sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/g" /etc/apache2/sites-available/000-default.conf
fi

# ==============================================================================
# Run Database Updates and Seeding
# ==============================================================================
echo "Running database updates and seeding..."
php /var/www/html/backend/update_db.php

# ==============================================================================
# Configure and Verify Apache MPM
# ==============================================================================
echo "Ensuring only mpm_prefork is enabled..."
a2dismod mpm_event || true
a2dismod mpm_worker || true
a2enmod mpm_prefork || true

echo "=== Verifying Apache Configuration ==="
apachectl -t
apachectl -M

# ==============================================================================
# Start Apache Web Server (Foreground)
# ==============================================================================
echo "Starting Apache Web Server..."
cd /var/www/html
exec apache2-foreground