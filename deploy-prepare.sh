#!/bin/bash
# File: deploy-prepare.sh
# Description: Prepare files for Netcup Webhosting 4000 deployment

echo "🚀 Preparing 11Seconds Quiz Game for Netcup deployment..."

# Create deployment directory
mkdir -p deploy-package

# Copy backend files
echo "📁 Copying backend files..."
cp -r api/ deploy-package/
cp app.js deploy-package/
cp package.json deploy-package/
cp .env deploy-package/

# Copy frontend files to httpdocs
echo "📁 Copying frontend files..."
cp -r httpdocs/ deploy-package/

# Create deployment README
cat > deploy-package/DEPLOYMENT.md << 'EOF'
# 11Seconds Quiz Game - Netcup Deployment Guide

## Files Structure:
- `app.js` - Main server file
- `api/` - Backend API routes and database logic
- `httpdocs/` - Frontend files (React build)
- `package.json` - Dependencies
- `.env` - Environment configuration

## Installation Steps:

1. Upload all files to your Netcup Webhosting root directory
2. Install dependencies: `npm install`
3. Configure MySQL database in .env file
4. Start server: `node app.js`
5. Access game at your domain

## Environment Variables (.env):
```
DB_HOST=10.35.233.76
DB_PORT=3306
DB_USER=your_mysql_username
DB_PASS=your_mysql_password
DB_NAME=your_database_name
JWT_SECRET=your_secure_jwt_secret
PORT=3011
```

## Database Setup:
The system will automatically create tables when first started.
Run setup-extended-questions.js to populate with 60+ questions.
EOF

echo "✅ Deployment package ready in 'deploy-package' directory"
echo "📦 Files prepared for Netcup Webhosting 4000"
