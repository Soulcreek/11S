# 📦 Production Package Builder
# Erstellt optimiertes Package für Production Deployment

Write-Host "📦 Building production package..." -ForegroundColor Green

$ProjectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $ProjectRoot

$BuildDir = "build-production"
if (Test-Path $BuildDir) {
    Remove-Item $BuildDir -Recurse -Force
}
New-Item -ItemType Directory -Path $BuildDir | Out-Null

# Production package.json erstellen
$ProductionPackage = @{
    name = "11seconds-quiz-game"
    version = "1.0.0"
    description = "11Seconds Quiz Game - Production Build"
    main = "app.js"
    engines = @{
        node = ">=16.0.0"
    }
    scripts = @{
        start = "node app.js"
        setup = "node setup-extended-questions.js"
    }
    dependencies = @{
        express = "^4.18.2"
        cors = "^2.8.5"
        bcryptjs = "^2.4.3"
        jsonwebtoken = "^9.0.2"
        mysql2 = "^3.6.0"
        sqlite3 = "^5.1.6"
        dotenv = "^16.3.1"
    }
    keywords = @("quiz", "game", "netcup", "production")
    author = "Marcel"
    license = "MIT"
}

$ProductionPackage | ConvertTo-Json -Depth 10 | Out-File "$BuildDir\package.json" -Encoding UTF8

# Dateien kopieren
Copy-Item "app.js" "$BuildDir\" -Force
Copy-Item "api" "$BuildDir\" -Recurse -Force
Copy-Item "httpdocs" "$BuildDir\" -Recurse -Force
Copy-Item "setup-extended-questions.js" "$BuildDir\" -Force

# Production .env template
$EnvTemplate = @"
# 11Seconds Quiz Game - Production Configuration
DB_HOST=10.35.233.76
DB_PORT=3306
DB_USER=your_mysql_username
DB_PASS=your_mysql_password
DB_NAME=your_database_name
JWT_SECRET=generate_secure_jwt_secret_here
PORT=3011
NODE_ENV=production
"@

$EnvTemplate | Out-File "$BuildDir\.env.template" -Encoding UTF8

# README für Production
$ReadmeContent = @"
# 11Seconds Quiz Game - Production Deployment

## Quick Start:
1. Configure .env with your database credentials
2. npm install
3. node setup-extended-questions.js
4. node app.js

## Access:
- Game: http://your-domain:3011
- API: http://your-domain:3011/api

Built: $(Get-Date)
"@

$ReadmeContent | Out-File "$BuildDir\README.md" -Encoding UTF8

Write-Host "✅ Production package built in: $BuildDir" -ForegroundColor Green

# Optional: ZIP erstellen
if (Get-Command Compress-Archive -ErrorAction SilentlyContinue) {
    $ZipName = "11seconds-production-$(Get-Date -Format 'yyyyMMdd-HHmmss').zip"
    Compress-Archive -Path "$BuildDir\*" -DestinationPath $ZipName -Force
    Write-Host "📦 ZIP created: $ZipName" -ForegroundColor Cyan
}

Start-Process explorer.exe -ArgumentList $BuildDir
