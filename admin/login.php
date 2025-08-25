<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>11Seconds Admin - Login</title>
    <style>
        /* 🎨 GREEN GLASS LOGIN DESIGN */
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
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
            overflow: hidden;
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
                radial-gradient(circle at 30% 40%, rgba(34, 197, 94, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 70% 60%, rgba(16, 185, 129, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 20% 80%, rgba(5, 150, 105, 0.1) 0%, transparent 50%);
            animation: float 15s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.8; }
            50% { transform: translateY(-20px) rotate(2deg); opacity: 1; }
        }

        /* Login Container */
        .login-container {
            background: var(--glass-green);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow-glass);
            width: 100%;
            max-width: 420px;
            position: relative;
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from { 
                opacity: 0; 
                transform: translateY(30px) scale(0.9); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0) scale(1); 
            }
        }

        /* Logo and Title */
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-logo {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
            filter: drop-shadow(0 0 20px rgba(34, 197, 94, 0.5));
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .login-title {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .login-subtitle {
            color: var(--text-secondary);
            font-size: 16px;
        }

        /* Form Styles */
        .login-form {
            space-y: 25px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input {
            width: 100%;
            padding: 15px 20px;
            background: var(--glass-green-hover);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 16px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .form-input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
            background: var(--glass-green-active);
        }

        .form-input::placeholder {
            color: var(--text-secondary);
        }

        /* Login Button */
        .login-button {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, #10b981, #059669);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
            background: linear-gradient(135deg, #059669, #047857);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .login-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Error Message */
        .error-message {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Loading Animation */
        .loading-spinner {
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--glass-border);
        }

        .login-footer p {
            color: var(--text-secondary);
            font-size: 12px;
        }

        /* Demo Info */
        .demo-info {
            background: var(--glass-green-hover);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 20px;
            margin-top: 25px;
            text-align: center;
        }

        .demo-info h4 {
            color: #10b981;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .demo-credentials {
            font-family: 'Courier New', monospace;
            background: rgba(0, 0, 0, 0.2);
            padding: 10px;
            border-radius: 8px;
            margin: 5px 0;
            font-size: 13px;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 25px;
            }
            
            .login-logo {
                font-size: 40px;
            }
            
            .login-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <span class="login-logo">🎮</span>
            <h1 class="login-title">Admin Center</h1>
            <p class="login-subtitle">11Seconds Game Management</p>
        </div>

        <div id="error-message" class="error-message"></div>

        <form class="login-form" id="login-form">
            <div class="form-group">
                <label class="form-label" for="username">👤 Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-input" 
                    placeholder="Enter username"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">🔒 Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-input" 
                    placeholder="Enter password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="login-button" id="login-btn">
                <span class="loading-spinner" id="loading"></span>
                <span id="login-text">🚀 Login to Admin</span>
            </button>
        </form>

        <div class="demo-info">
            <h4>🔐 Admin Access Required</h4>
            <p>Please enter your administrator credentials to access the 11Seconds Admin Center.</p>
            <p><small>Contact system administrator if you need access.</small></p>
        </div>

        <div class="login-footer">
            <p>&copy; 2024 11Seconds • Green Glass Design System v2.0</p>
        </div>
    </div>

    <script>
        // 🎮 LOGIN JAVASCRIPT
        document.getElementById('login-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const button = document.getElementById('login-btn');
            const loading = document.getElementById('loading');
            const loginText = document.getElementById('login-text');
            const errorDiv = document.getElementById('error-message');
            
            // Show loading state
            button.disabled = true;
            loading.style.display = 'inline-block';
            loginText.textContent = '🔄 Logging in...';
            errorDiv.style.display = 'none';
            
            // Get form data
            const formData = new FormData(this);
            
            try {
                const response = await fetch('api.php?action=login', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    loginText.textContent = '✅ Login successful!';
                    setTimeout(() => {
                        window.location.href = 'index.html';
                    }, 1000);
                } else {
                    throw new Error(result.error || 'Login failed');
                }
                
            } catch (error) {
                errorDiv.textContent = '❌ ' + error.message;
                errorDiv.style.display = 'block';
                
                button.disabled = false;
                loading.style.display = 'none';
                loginText.textContent = '🚀 Login to Admin';
            }
        });

        // Auto-focus username field
        document.getElementById('username').focus();

        // Enter key handling
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('login-form').dispatchEvent(new Event('submit'));
            }
        });

        // Demo credential click handlers - REMOVED FOR SECURITY
        // Credentials are no longer displayed publicly
    </script>
</body>
</html>
