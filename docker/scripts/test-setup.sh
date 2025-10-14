#!/bin/bash

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 Image Search API - Test Environment Setup${NC}"
echo "==========================================="

# Get directories
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SRC_DIR="$PROJECT_ROOT/src"
DOCKER_DIR="$PROJECT_ROOT/docker"

echo -e "${GREEN}📁 Project root: $PROJECT_ROOT${NC}"
echo -e "${GREEN}📁 Source directory: $SRC_DIR${NC}"
echo -e "${GREEN}📁 Docker directory: $DOCKER_DIR${NC}"

# Function to check command success
check_success() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ $1${NC}"
    else
        echo -e "${RED}❌ $1 failed${NC}"
        exit 1
    fi
}

# Work with .env files in source directory
cd "$SRC_DIR"

# Check if .env.testing exists in src directory
if [ ! -f ".env.testing" ]; then
    echo -e "${YELLOW}📝 .env.testing not found in src directory${NC}"
    
    if [ -f ".env.dev" ]; then
        cp .env.dev .env.testing
        echo -e "${GREEN}✅ Created .env.testing from dev${NC}"
        
        # Update key configurations for testing
        sed -i.bak 's/APP_ENV=local/APP_ENV=testing/g' .env.testing
        sed -i.bak 's/APP_DEBUG=true/APP_DEBUG=false/g' .env.testing
        sed -i.bak 's/DB_DATABASE=.*/DB_DATABASE=mpv_image_search_api_pg_test/g' .env.testing
        
        rm -f .env.testing.bak
        echo -e "${GREEN}✅ Updated .env.testing for test environment${NC}"
        echo -e "${YELLOW}⚠️  Please review .env.testing configuration${NC}"
    else
        echo -e "${RED}❌ .env.dev not found in src directory${NC}"
        echo -e "${YELLOW}📋 Please create .env.testing manually in src/ with:${NC}"
        cat << 'EOF'
APP_ENV=testing
APP_DEBUG=false
APP_KEY=

DB_CONNECTION=pgsql
DB_HOST=mpv_postgres
DB_PORT=5432
DB_DATABASE=mpv_image_search_api_pg_test
DB_USERNAME=postgres
DB_PASSWORD=root

EOF
        exit 1
    fi
fi

# Execute Docker commands from docker directory
cd "$DOCKER_DIR"

# Create test database
echo -e "${GREEN}📦 Creating test database...${NC}"
docker compose exec -T mpv_postgres createdb -U laravel mpv_image_search_api_pg_test 2>/dev/null

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Test database created${NC}"
else
    echo -e "${YELLOW}⚠️  Database might already exist (continuing)${NC}"
fi

# Generate application key (using src as working directory in container)
echo -e "${GREEN}🔑 Generating application key...${NC}"
docker compose exec -T mpv_app bash -c "cd /var/www/html && php artisan key:generate --env=testing --force"
check_success "Application key generated"

# Run migrations
echo -e "${GREEN}📊 Running database migrations...${NC}"
docker compose exec -T mpv_app bash -c "cd /var/www/html && php artisan migrate --env=testing --force"
check_success "Database migrations completed"

# Run seeders if they exist
cd "$SRC_DIR"
if [ -f "database/seeders/DatabaseSeeder.php" ]; then
    echo -e "${GREEN}🌱 Seeding test data...${NC}"
    cd "$DOCKER_DIR"
    docker compose exec -T mpv_app bash -c "cd /var/www/html && php artisan db:seed --env=testing --force"
    check_success "Test data seeded"
fi

echo ""
echo -e "${GREEN}🎉 Test environment setup complete!${NC}"
echo -e "${GREEN}📝 Next steps:${NC}"
echo -e "   Run tests: ${YELLOW}cd docker && docker compose exec mpv_app php artisan test${NC}"
echo ""
