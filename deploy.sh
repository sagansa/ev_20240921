#!/bin/bash

# Build script that automatically sends files to GitHub after building

# Exit on any error
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Starting build process...${NC}"

# Run the Vite build
echo -e "${YELLOW}Building assets with Vite...${NC}"
npm run build

# Check if build was successful
if [ $? -eq 0 ]; then
    echo -e "${GREEN}Build completed successfully!${NC}"
else
    echo -e "${RED}Build failed!${NC}"
    exit 1
fi

# Add built files to git (force add because public/build is in .gitignore)
echo -e "${YELLOW}Adding built files to git...${NC}"
git add -f public/build/

# Commit the built files with a timestamp message
TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")
COMMIT_MESSAGE="Build assets - $TIMESTAMP"

echo -e "${YELLOW}Committing built files...${NC}"
git commit -m "$COMMIT_MESSAGE" || echo -e "${YELLOW}No changes to commit${NC}"

# Push to GitHub
echo -e "${YELLOW}Pushing to GitHub...${NC}"
git push origin main || echo -e "${RED}Failed to push to GitHub${NC}"

echo -e "${GREEN}Build and deployment process completed!${NC}"