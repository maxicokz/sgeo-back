#!/bin/bash

# SGEO Analytics Deployment Script

echo "🚀 Starting SGEO Analytics deployment..."

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${YELLOW}⚠️  .env file not found. Copying from .env.example...${NC}"
    cp .env.example .env
    echo -e "${RED}❗ Please edit .env file with your configuration before proceeding!${NC}"
    exit 1
fi

# Install dependencies
echo -e "${GREEN}📦 Installing Composer dependencies...${NC}"
composer install --no-dev --optimize-autoloader

# Create necessary directories
echo -e "${GREEN}📁 Creating directories...${NC}"
mkdir -p logs reports temp cache
chmod 755 logs reports temp cache

# Set permissions
echo -e "${GREEN}🔐 Setting permissions...${NC}"
if [ -d "/var/www" ]; then
    # For Apache
    chown -R www-data:www-data logs reports temp cache
else
    # For Nginx
    chown -R nginx:nginx logs reports temp cache
fi

# Check database connection
echo -e "${GREEN}🔍 Checking database connection...${NC}"
php -r "
require 'vendor/autoload.php';
\$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
\$dotenv->load();
try {
    \$pdo = new PDO(
        'pgsql:host=' . \$_ENV['DB_HOST'] . ';port=' . \$_ENV['DB_PORT'] . ';dbname=' . \$_ENV['DB_NAME'],
        \$_ENV['DB_USER'],
        \$_ENV['DB_PASSWORD']
    );
    echo '✅ Database connection successful\n';
} catch (PDOException \$e) {
    echo '❌ Database connection failed: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Database connection failed. Please check your configuration.${NC}"
    exit 1
fi

# Import database schema (optional, uncomment if needed)
# echo -e "${GREEN}📊 Importing database schema...${NC}"
# psql -U $DB_USER -d $DB_NAME -f database/schema.sql

echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo ""
echo -e "${YELLOW}📝 Next steps:${NC}"
echo "1. Configure your web server (Apache/Nginx)"
echo "2. Point DocumentRoot to: $(pwd)/public"
echo "3. Access the application in your browser"
echo "4. Login with default credentials: admin / admin123"
echo "5. Change the admin password immediately!"
echo ""
echo -e "${GREEN}🎉 SGEO Analytics is ready to use!${NC}"
