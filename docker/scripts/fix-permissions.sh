#!/bin/bash

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${GREEN}🔧 Fixing Laravel permissions...${NC}"

# Get project directories
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
DOCKER_DIR="$PROJECT_ROOT/docker"

cd "$DOCKER_DIR"

# Fix permissions in container
echo -e "${GREEN}📁 Setting storage and cache permissions...${NC}"
docker compose exec -T mpv_app bash -c "
    chown -R www-data:www-data /var/www/html/storage
    chown -R www-data:www-data /var/www/html/bootstrap/cache
    chmod -R 775 /var/www/html/storage
    chmod -R 775 /var/www/html/bootstrap/cache
    chmod -R 775 /var/www/html/storage/framework/views
    echo '✅ Permissions fixed'
"
chmod -R 0777 "$PROJECT_ROOT/data/elasticsearch" 
chmod -R 0777 "$PROJECT_ROOT/data/redis"

echo -e "${GREEN}🎉 Permissions fixed successfully!${NC}"
