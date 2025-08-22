# 11Seconds Data Storage Documentation - MySQL Database

## 📁 Data Storage Architecture

### Primary Storage: MySQL Database on Netcup Server

Your quiz game uses **MySQL database** for all persistent data storage, with configuration managed through the PHP admin center.

```
MySQL Database: quiz_game_db
├── users                     # ✅ User accounts and authentication
├── user_stats               # ✅ Game statistics and performance
├── questions                # ✅ Quiz questions and categories
├── game_sessions            # ✅ Individual game results
├── sessions                 # ✅ Active user sessions
└── audit_log               # ✅ Security and system logs
```

## �️ Database Schema

### Core Tables Structure

#### 1. `users` - User Accounts

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20),
    account_type ENUM('guest', 'registered', 'admin') DEFAULT 'registered',
    verified BOOLEAN DEFAULT FALSE,
    verification_code VARCHAR(10),
    verification_expires DATETIME,
    failed_login_attempts INT DEFAULT 0,
    locked_until DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    google_id VARCHAR(100) UNIQUE NULL
);
```

#### 2. `user_stats` - Game Statistics

```sql
CREATE TABLE user_stats (
    user_id INT PRIMARY KEY,
    games_played INT DEFAULT 0,
    total_score INT DEFAULT 0,
    best_score INT DEFAULT 0,
    average_score DECIMAL(5,2) DEFAULT 0.00,
    total_time_played INT DEFAULT 0,
    streak_current INT DEFAULT 0,
    streak_best INT DEFAULT 0,
    questions_answered INT DEFAULT 0,
    questions_correct INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### 3. `questions` - Quiz Questions Database

```sql
CREATE TABLE questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    text TEXT NOT NULL,
    answer_a VARCHAR(255) NOT NULL,
    answer_b VARCHAR(255) NOT NULL,
    answer_c VARCHAR(255) NOT NULL,
    answer_d VARCHAR(255) NOT NULL,
    correct_answer TINYINT NOT NULL,
    category VARCHAR(50) DEFAULT 'General',
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    points INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    is_active BOOLEAN DEFAULT TRUE,
    times_asked INT DEFAULT 0,
    times_correct INT DEFAULT 0,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

#### 4. `game_sessions` - Individual Game Results

```sql
CREATE TABLE game_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    score INT NOT NULL,
    questions_answered INT NOT NULL,
    questions_correct INT NOT NULL,
    time_taken INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    validated BOOLEAN DEFAULT FALSE,
    validation_flags JSON,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### 5. `sessions` - Active User Sessions

```sql
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### 6. `audit_log` - Security and System Logs

```sql
CREATE TABLE audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

## 🛠️ Data Management Features

### Automatic File Creation

When the admin center is first accessed, the system automatically creates missing data files:

1. **First Admin Login:** Creates `users.json` with admin account
2. **First Game Play:** Creates `questions.json` with sample questions
3. **First Session:** Creates `sessions.json` for session tracking
4. **First Activity:** Creates `audit.json` for logging

### Data Backup System

- **Automatic Backups:** Daily backups created in `/data/backups/`
- **Manual Backups:** Available through admin interface
- **Selective Export:** Export users, questions, or config separately
- **Import Function:** Restore from backup files

### File Permissions Required

```bash
# On your server, ensure these permissions:
chmod 755 /httpdocs/admin/data/
chmod 644 /httpdocs/admin/data/*.json
chmod 755 /httpdocs/admin/data/backups/

# PHP must have write access to:
chown www-data:www-data /httpdocs/admin/data/
chown www-data:www-data /httpdocs/admin/data/backups/
```

## 🔐 Data Security

### Encryption at Rest

- **Passwords:** PBKDF2 hashed with 10,000 iterations + unique salt
- **API Keys:** Encrypted using AES-256-GCM
- **Session Tokens:** Cryptographically secure random tokens
- **Sensitive Data:** Encrypted before JSON storage

### Access Protection

- **File Location:** Inside admin directory (protected by admin authentication)
- **Direct Access:** Blocked by `.htaccess` rules
- **Backup Files:** Automatically encrypted
- **Audit Trail:** All data access logged

## 📊 Data Flow

### User Registration Flow

1. **User submits registration** → Frontend
2. **Data validation** → PHP AuthManager
3. **Password hashing** → PBKDF2 with salt
4. **User creation** → Added to `users.json`
5. **Session creation** → Added to `sessions.json`
6. **Activity logging** → Added to `audit.json`

### Game Data Flow

1. **Question request** → Load from `questions.json`
2. **Score submission** → Validate against user session
3. **Anti-cheat check** → Time/pattern validation
4. **Score update** → Update user stats in `users.json`
5. **Activity logging** → Log to `audit.json`

## 🎯 Advantages of JSON File Storage

### ✅ Benefits

- **No Database Required:** Perfect for static hosting
- **Easy Backup:** Simple file copy operations
- **Version Control:** Can track changes with Git
- **Portable:** Easy to migrate between servers
- **Cost Effective:** No database hosting costs
- **Simple Debugging:** Human-readable JSON format

### ⚠️ Considerations

- **Concurrent Access:** PHP file locking prevents corruption
- **Performance:** Suitable for thousands of users
- **Scalability:** Can migrate to database later if needed
- **Backup Strategy:** Regular automated backups essential

## 🔧 Admin Interface Data Management

### Available Through Admin Center

1. **User Management:** View, edit, delete users
2. **Question Management:** Add, edit, categorize questions
3. **Security Monitoring:** View audit logs and security events
4. **Backup Management:** Create and restore backups
5. **Configuration:** Update system settings
6. **AI Generator:** Bulk import questions from AI

### Data Import/Export

- **JSON Format:** Standard format for all exports
- **Selective Export:** Choose specific data types
- **Backup Restore:** Full system restore capability
- **Migration Tools:** Easy transfer between environments

---

**Summary:** All your quiz game data is stored in JSON files within `/httpdocs/admin/data/`, making it perfect for static hosting while providing enterprise-level functionality through the PHP admin center.
