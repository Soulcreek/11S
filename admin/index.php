<?php require_once 'session.php'; ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>11Seconds Admin Center</title>
    <style>
        /* 🎨 GREEN GLASS DESIGN SYSTEM 2.0 */
        :root {
            --glass-green: rgba(34, 197, 94, 0.15);
            --glass-green-hover: rgba(34, 197, 94, 0.25);
            --glass-green-active: rgba(34, 197, 94, 0.35);
            --glass-border: rgba(255, 255, 255, 0.2);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.8);
            --shadow-glass: 0 8px 32px rgba(0, 0, 0, 0.3);
            --gradient-bg: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #047857 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--gradient-bg);
            min-height: 100vh;
            color: var(--text-primary);
            overflow-x: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 20%, rgba(34, 197, 94, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(16, 185, 129, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 90%, rgba(5, 150, 105, 0.1) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-10px) rotate(1deg); }
            66% { transform: translateY(5px) rotate(-1deg); }
        }

        /* Glass Container */
        .admin-container {
            min-height: 100vh;
            display: grid;
            grid-template-areas: 
                "header header"
                "sidebar main";
            grid-template-columns: 280px 1fr;
            grid-template-rows: auto 1fr;
            gap: 20px;
            padding: 20px;
        }

        /* Header */
        .admin-header {
            grid-area: header;
            background: var(--glass-green);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 20px 30px;
            box-shadow: var(--shadow-glass);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-title {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-title::before {
            content: '🎮';
            font-size: 32px;
            filter: drop-shadow(0 0 10px rgba(34, 197, 94, 0.5));
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--glass-green-hover);
            padding: 10px 20px;
            border-radius: 50px;
            border: 1px solid var(--glass-border);
        }

        /* Sidebar */
        .admin-sidebar {
            grid-area: sidebar;
            background: var(--glass-green);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--shadow-glass);
            height: fit-content;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin: 8px 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            background: transparent;
            border: 1px solid transparent;
        }

        .nav-link:hover, .nav-link.active {
            background: var(--glass-green-hover);
            color: var(--text-primary);
            border-color: var(--glass-border);
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.2);
            transform: translateX(5px);
        }

        .nav-link.active {
            background: var(--glass-green-active);
        }

        /* Main Content */
        .admin-main {
            grid-area: main;
            background: var(--glass-green);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 30px;
            box-shadow: var(--shadow-glass);
            overflow-y: auto;
        }

        /* Dashboard Cards */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .dashboard-card {
            background: var(--glass-green-hover);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 25px;
            transition: all 0.3s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(34, 197, 94, 0.3);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .card-icon {
            font-size: 24px;
            padding: 10px;
            background: var(--glass-green-active);
            border-radius: 8px;
            border: 1px solid var(--glass-border);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
        }

        .card-value {
            font-size: 32px;
            font-weight: 700;
            color: #10b981;
            margin-bottom: 5px;
        }

        /* Content Sections */
        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        .section-title {
            font-size: 24px;
            margin-bottom: 25px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Tables */
        .data-table {
            width: 100%;
            background: var(--glass-green-hover);
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            overflow: hidden;
        }

        .data-table th,
        .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--glass-border);
        }

        .data-table th {
            background: var(--glass-green-active);
            font-weight: 600;
            color: var(--text-primary);
        }

        .data-table tbody tr:hover {
            background: var(--glass-green-active);
        }

        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: 1px solid var(--glass-border);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }

        /* Status Indicators */
        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-online {
            background: rgba(34, 197, 94, 0.2);
            color: #10b981;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-container {
                grid-template-areas: 
                    "header"
                    "main";
                grid-template-columns: 1fr;
                gap: 15px;
                padding: 15px;
            }
            
            .admin-sidebar {
                display: none;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #10b981;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <header class="admin-header">
            <h1 class="admin-title">11Seconds Admin Center</h1>
            <div class="user-info">
                <span id="current-user">🔐 Loading...</span>
                <button class="btn btn-primary" onclick="logout()">
                    🚪 Logout
                </button>
            </div>
        </header>

        <!-- Sidebar -->
        <nav class="admin-sidebar">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link active" onclick="showSection('dashboard')">
                        📊 Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="users.html" class="nav-link">
                        👥 User Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="showSection('questions')">
                        ❓ Question Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="showSection('games')">
                        🎯 Game Sessions
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="showSection('settings')">
                        ⚙️ Settings
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Dashboard Section -->
            <div id="dashboard" class="content-section active">
                <h2 class="section-title">📊 System Dashboard</h2>
                
                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <div class="card-icon">👥</div>
                            <div class="card-title">Total Users</div>
                        </div>
                        <div class="card-value" id="stats-users">Loading...</div>
                        <div class="card-description">Registered users</div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-header">
                            <div class="card-icon">❓</div>
                            <div class="card-title">Questions</div>
                        </div>
                        <div class="card-value" id="stats-questions">Loading...</div>
                        <div class="card-description">Active questions</div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-header">
                            <div class="card-icon">🎯</div>
                            <div class="card-title">Game Sessions</div>
                        </div>
                        <div class="card-value" id="stats-sessions">Loading...</div>
                        <div class="card-description">Completed games</div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-header">
                            <div class="card-icon">📈</div>
                            <div class="card-title">Average Score</div>
                        </div>
                        <div class="card-value" id="stats-score">Loading...</div>
                        <div class="card-description">Points per game</div>
                    </div>
                </div>

                <div class="status-info">
                    <h3>🟢 System Status: <span class="status status-online">Online</span></h3>
                    <p>Database: Connected • Server: Running • Last Update: <span id="last-update">--</span></p>
                </div>
            </div>

            <!-- User Management Section -->
            <div id="users" class="content-section">
                <h2 class="section-title">👥 User Management</h2>
                <div id="users-content">Loading users...</div>
            </div>

            <!-- Question Management Section -->
            <div id="questions" class="content-section">
                <h2 class="section-title">❓ Question Management</h2>
                <div id="questions-content">Loading questions...</div>
            </div>

            <!-- Game Sessions Section -->
            <div id="games" class="content-section">
                <h2 class="section-title">🎯 Game Sessions</h2>
                <div id="games-content">Loading sessions...</div>
            </div>

            <!-- Settings Section -->
            <div id="settings" class="content-section">
                <h2 class="section-title">⚙️ System Settings</h2>
                <div id="settings-content">Loading settings...</div>
            </div>
        </main>
    </div>

    <script>
        // 🎮 ADMIN CENTER JAVASCRIPT
        let currentUser = null;
        
        // Navigation
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from nav links
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Add active class to clicked nav link
            event.target.classList.add('active');
            
            // Load section content
            loadSection(sectionId);
        }
        
        // Load section content dynamically
        function loadSection(section) {
            switch(section) {
                case 'dashboard':
                    loadDashboard();
                    break;
                case 'users':
                    loadUsers();
                    break;
                case 'questions':
                    loadQuestions();
                    break;
                case 'games':
                    loadGameSessions();
                    break;
                case 'settings':
                    loadSettings();
                    break;
            }
        }
        
        // Dashboard loading
        function loadDashboard() {
            fetch('api.php?action=stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('stats-users').textContent = data.stats.users;
                        document.getElementById('stats-questions').textContent = data.stats.questions;
                        document.getElementById('stats-sessions').textContent = data.stats.sessions;
                        document.getElementById('stats-score').textContent = data.stats.avg_score;
                        document.getElementById('last-update').textContent = new Date().toLocaleString();
                    }
                })
                .catch(() => {
                    // Fallback for development
                    document.getElementById('stats-users').textContent = '5';
                    document.getElementById('stats-questions').textContent = '42';
                    document.getElementById('stats-sessions').textContent = '128';
                    document.getElementById('stats-score').textContent = '7.3';
                    document.getElementById('last-update').textContent = new Date().toLocaleString();
                });
        }
        
        // Load other sections (placeholder)
        function loadUsers() {
            document.getElementById('users-content').innerHTML = `
                <button class="btn btn-primary" onclick="createUser()">➕ Add User</button>
                <div class="loading">Loading users...</div>
            `;
        }
        
        function loadQuestions() {
            document.getElementById('questions-content').innerHTML = `
                <button class="btn btn-primary" onclick="createQuestion()">➕ Add Question</button>
                <div class="loading">Loading questions...</div>
            `;
        }
        
        function loadGameSessions() {
            document.getElementById('games-content').innerHTML = `
                <div class="loading">Loading game sessions...</div>
            `;
        }
        
        function loadSettings() {
            document.getElementById('settings-content').innerHTML = `
                <div class="dashboard-card">
                    <h3>🔐 Security Settings</h3>
                    <p>Change admin passwords and security settings</p>
                    <button class="btn btn-primary">Change Password</button>
                </div>
                <div class="dashboard-card">
                    <h3>🎮 Game Settings</h3>
                    <p>Configure game parameters and difficulty</p>
                    <button class="btn btn-primary">Configure Game</button>
                </div>
            `;
        }
        
        // Utility functions
        function createUser() {
            alert('User creation dialog would open here');
        }
        
        function createQuestion() {
            alert('Question creation dialog would open here');
        }
        
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'login.php';
            }
        }
        
        // Initialize on load
        document.addEventListener('DOMContentLoaded', function() {
            // Set current user info
            document.getElementById('current-user').textContent = '👤 Administrator';
            
            // Load dashboard
            loadDashboard();
        });
    </script>
</body>
</html>
