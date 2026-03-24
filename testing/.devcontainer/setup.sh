#!/bin/bash

set -e

echo "🚀 Setting up WordPress with Radicle..."

# Wait for database to be ready
echo "⏳ Waiting for database connection..."
sleep 10

# Build assets
echo "🔨 Building assets..."
npm run build

# Install WordPress core
echo "🌐 Installing WordPress..."
wp core install \
    --url=http://web \
    --title=Radicle \
    --admin_user=admin \
    --admin_password=admin \
    --admin_email=admin@example.com \
    --skip-email \
    --allow-root

# Activate theme if it exists
if wp theme is-installed radicle --allow-root 2>/dev/null; then
    echo "🎨 Activating Radicle theme..."
    wp theme activate radicle --allow-root
fi

# Install Playwright for testing
echo "🎭 Installing Playwright..."
npx playwright install chromium

echo "✅ Setup complete! WordPress is ready at http://localhost:8080"
echo "📧 Admin credentials: admin / admin"