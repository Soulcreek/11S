# 11Seconds Complete Deployment Guide

## 📋 Overview

This guide covers the complete deployment of the 11Seconds Quiz Game including:

- React frontend application
- Node.js backend API
- Professional Admin Center
- Enhanced Authentication System

## 🏗️ System Architecture

```
11seconds.de/
├── httpdocs/                    # Web root for static files
│   ├── index.html              # React app entry point
│   ├── static/                 # React build assets
│   └── admin/                  # Admin Center (NEW)
│       ├── dashboard.php       # Admin dashboard
│       ├── user-management-enhanced.php
│       ├── security-dashboard.php
│       ├── includes/           # Authentication system
│       └── data/               # JSON data storage
├── api/                        # Backend API routes
├── app.js                      # Node.js server entry point
└── package.json               # Server dependencies
```

## 🚀 Quick Deployment

### Prerequisites

1. Node.js 22.x installed on target server
2. FTP access to your hosting provider
3. Document root configured to `/httpdocs`
4. PHP 7.4+ with write permissions to `/admin/data/`

### One-Command Deploy

```powershell
# Build and deploy everything (recommended)
powershell -ExecutionPolicy Bypass -File .\deploy.ps1 -Method ftp -Build

# Deploy without building (if build exists)
powershell -ExecutionPolicy Bypass -File .\deploy.ps1 -Method ftp

# Test deployment connections
powershell -ExecutionPolicy Bypass -File .\deploy.ps1 -Test
```

## ⚙️ Configuration

### 1. Copy Environment Template

```powershell
# Copy and edit deployment configuration
copy .env.deploy.template .env.deploy
# Edit .env.deploy with your credentials
```

### 2. Configure .env.deploy

```properties
# FTP Settings (Primary deployment method)
FTP_HOST=your-ftp-host.com
FTP_USER=your-ftp-username
FTP_PASSWORD=your-ftp-password
FTP_REMOTE_PATH=/httpdocs

# SSH Settings (Fallback deployment method)
SSH_HOST=your-ssh-host.com
SSH_USER=your-ssh-username
SSH_REMOTE_PATH=/var/www/html

# Domain Settings
DOMAIN_URL=https://your-domain.com

# Build Settings
BUILD_PATH=web/build
DEPLOYMENT_NAME=11seconds-static-deploy

# Admin Center Settings
UPLOAD_ADMIN=true
ADMIN_DEFAULT_USER=admin
ADMIN_DEFAULT_PASS=admin123

# Backend upload (API files)
FTP_UPLOAD_BACKEND=true
```

## 🔐 Admin Center Setup

### First-Time Setup

1. Deploy the application using the deployment script
2. Navigate to `https://your-domain.com/admin/`
3. Login with default credentials:
   - **Username:** `admin`
   - **Password:** `admin123`
4. **IMMEDIATELY** change the default password in Settings

### Admin Features

- **Enhanced User Management:** Professional user administration with filtering, security monitoring, and bulk operations
- **Security Dashboard:** Real-time threat monitoring, suspicious activity detection, and security alerts
- **Question Management:** Full CRUD operations for quiz questions with category management
- **AI Question Generator:** Automated question creation using Google Gemini AI
- **System Monitoring:** Performance metrics, session tracking, and audit logs
- **Backup & Export:** Complete data backup and selective exports

### Security Configuration

After deployment, configure these security settings in the admin panel:

1. **Password Policy:**

   - Minimum 8 characters
   - Require uppercase, lowercase, numbers
   - Optional special characters

2. **Rate Limiting:**

   - Login attempts: 5 per 15 minutes
   - Registration: 3 per hour per IP
   - Score submission: 10 per minute

3. **Session Management:**
   - Session timeout: 1 hour default
   - Secure session tokens
   - Cross-site protection enabled

## 🔧 Post-Deployment Steps

### 1. Server Configuration

On your hosting panel:

```bash
# Navigate to your domain root
cd /your-domain/

# Install Node.js dependencies
npm install

# Start the Node.js application
npm start
# or for production:
NODE_ENV=production npm start
```

### 2. Environment Variables (Server)

Configure these in your hosting panel:

```properties
NODE_ENV=production
PORT=3000
JWT_SECRET=your-super-secret-jwt-key
DB_HOST=localhost
DB_USER=your-db-user
DB_PASS=your-db-password
DB_NAME=your-database-name
```

### 3. Database Setup (if using MySQL)

```sql
-- Create tables as needed
-- User authentication is handled by JSON files in admin/data/
-- Questions can be imported via admin interface
```

## 📊 Deployment Components

### What Gets Deployed

#### Frontend (React App) → `/httpdocs/`

- Built React application
- Static assets (CSS, JS, images)
- Service worker for PWA functionality
- Responsive design for mobile/desktop

#### Admin Center → `/httpdocs/admin/`

- **Authentication System:**
  - `includes/AuthManager.php` - Core authentication logic
  - `includes/GoogleAuth.php` - Google OAuth integration
- **Admin Interfaces:**
  - `dashboard.php` - Main admin dashboard
  - `user-management-enhanced.php` - Professional user management
  - `security-dashboard.php` - Real-time security monitoring
  - `settings.php` - System configuration
- **Data Storage:**
  - `data/auth-config.json` - Security and authentication settings
  - `data/users.json` - User accounts (created automatically)
  - `data/questions.json` - Quiz questions database

#### Backend API → `/api/`

- RESTful API endpoints
- Authentication routes
- Game logic endpoints
- Score validation system
- Session management

## 🛡️ Security Features

### Authentication System

- **Multi-Modal Login:** Username/email + password, Google OAuth, guest accounts
- **Email/SMS Verification:** Configurable verification via email or SMS
- **Password Security:** PBKDF2 hashing with 10,000 iterations + salt
- **Account Lockout:** Automatic lockout after 5 failed attempts
- **Session Management:** Secure token-based sessions with timeout

### Anti-Cheat System

- **Time Validation:** Prevents impossible completion times
- **Score Verification:** Validates score calculations server-side
- **Pattern Detection:** Identifies suspicious answer patterns
- **Session Integrity:** Prevents session manipulation
- **Rate Limiting:** Prevents spam and automated attacks

### Admin Security

- **Role-Based Access:** Admin-only areas with authentication
- **Audit Logging:** All admin actions are logged
- **Security Monitoring:** Real-time threat detection
- **Data Encryption:** Sensitive data encrypted at rest

## 🔍 Verification Steps

### 1. Frontend Check

Visit your domain and verify:

- [ ] Game loads properly
- [ ] Responsive design works on mobile/desktop
- [ ] User registration/login functions
- [ ] Google Sign-In works (after OAuth setup)
- [ ] Guest mode functions
- [ ] Score submission works

### 2. Admin Center Check

Visit `https://your-domain.com/admin/` and verify:

- [ ] Login page loads
- [ ] Default credentials work
- [ ] Dashboard shows statistics
- [ ] User management loads user list
- [ ] Security dashboard shows monitoring data
- [ ] Settings page accessible

### 3. API Health Check

Visit `https://your-domain.com/api/health` and verify:

- [ ] JSON response with status "OK"
- [ ] Timestamp shows current server time
- [ ] No error messages

### 4. Backend Functionality

Test these endpoints:

- [ ] `POST /api/auth/register` - User registration
- [ ] `POST /api/auth/login` - User login
- [ ] `POST /api/auth/guest` - Guest account creation
- [ ] `POST /api/game/score` - Score submission
- [ ] `GET /api/questions/random` - Random questions

## 🐛 Troubleshooting

### Common Issues

**Frontend not loading:**

- Check if build files exist in `/httpdocs/`
- Verify file permissions
- Check browser console for errors

**Admin center access denied:**

- Verify PHP write permissions to `/admin/data/`
- Check if JSON data files are created
- Ensure sessions are working in PHP

**Backend API errors:**

- Check if Node.js is running
- Verify environment variables are set
- Check server logs for errors
- Ensure database connection (if using DB)

**Deployment fails:**

- Verify FTP credentials in `.env.deploy`
- Check network connectivity
- Ensure target directories exist
- Verify file permissions on server

### Performance Issues

- Enable gzip compression on server
- Set proper cache headers for static files
- Optimize images and assets
- Use CDN for better global performance

## 📈 Monitoring & Maintenance

### Regular Tasks

- [ ] **Weekly:** Check security dashboard for threats
- [ ] **Weekly:** Review user registration trends
- [ ] **Monthly:** Update admin passwords
- [ ] **Monthly:** Backup user data and questions
- [ ] **Quarterly:** Review and update security settings

### Monitoring URLs

- **Frontend:** `https://your-domain.com/`
- **Admin Center:** `https://your-domain.com/admin/`
- **API Health:** `https://your-domain.com/api/health`
- **Security Dashboard:** `https://your-domain.com/admin/security-dashboard.php`

## 📞 Support & Updates

### Getting Help

1. Check this documentation first
2. Review error logs in admin dashboard
3. Check browser developer console
4. Contact development team with specific error messages

### Updates

To update the system:

1. Pull latest changes from repository
2. Run deployment script: `.\deploy.ps1 -Build -Method ftp`
3. Clear browser cache
4. Test functionality
5. Monitor for issues in admin dashboard

---

_This deployment guide covers the complete 11Seconds Quiz Game system with professional admin center and enhanced security features._
