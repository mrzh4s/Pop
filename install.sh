#!/bin/bash

# Pop Framework Installation Script
# This script sets up the Pop Framework template

set -e

echo "🚀 Pop Framework - Installation"
echo "================================"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${YELLOW}Creating .env file...${NC}"
    cp .env.example .env
    echo -e "${GREEN}✓ .env file created${NC}"
else
    echo -e "${GREEN}✓ .env file already exists${NC}"
fi

# Install PHP dependencies
if command -v composer &> /dev/null; then
    echo -e "${YELLOW}Installing PHP dependencies...${NC}"
    composer install --no-dev --optimize-autoloader
    echo -e "${GREEN}✓ PHP dependencies installed${NC}"
else
    echo -e "${YELLOW}⚠ Composer not found. Skipping PHP dependencies.${NC}"
    echo "  Install composer from: https://getcomposer.org"
fi

# Set permissions for storage
echo -e "${YELLOW}Setting storage permissions...${NC}"
chmod -R 775 storage
echo -e "${GREEN}✓ Storage permissions set${NC}"

# Create assets directory
mkdir -p infrastructure/http/public/assets
chmod -R 775 infrastructure/http/public/assets
echo -e "${GREEN}✓ Assets directory created${NC}"

echo ""
echo -e "${GREEN}✅ Installation complete!${NC}"
echo ""
echo "Next steps:"
echo "  1. Configure your .env file"
echo "  2. Start development server:"
echo "     ${YELLOW}php -S localhost:8000 -t infrastructure/http/public${NC}"
echo "  3. Visit http://localhost:8000"
echo ""
echo "To add a JavaScript framework:"
echo "  - Visit the welcome page for step-by-step guides"
echo "  - Choose React, Vue, or Svelte"
echo "  - Copy the examples and start building!"
echo ""
