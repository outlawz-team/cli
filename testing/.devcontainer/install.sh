#!/bin/bash

set -e

echo "🌱 Radicle Development Environment"
echo "Installing dependencies and setting up project..."

# Set git safe directory
git config --global --add safe.directory /radicle/app

# Create Laravel storage directories with proper permissions
echo "📁 Creating Laravel storage directories..."
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
chmod -R 775 storage
chown -R www-data:www-data storage

# Copy .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📋 Copying .env file..."
    cp .devcontainer/.env.example .env
fi

# Install Composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-interaction --no-progress --prefer-dist

# Install NPM dependencies
echo "📦 Installing NPM dependencies..."
npm install

echo "✅ Installation complete!"