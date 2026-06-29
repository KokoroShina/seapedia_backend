#!/bin/bash
# Build script for Railway deployment
# Usage: ./railway-build.sh

set -e

echo "🚀 Starting Railway build..."

# Install dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Generate application key if not set
echo "🔑 Generating application key..."
php artisan key:generate --force

# Clear and cache config
echo "⚙️  Caching configuration..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Run migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

echo "✅ Railway build completed!"
