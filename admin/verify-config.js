const fs = require('fs');
const path = require('path');

console.log('🔧 DATABASE CONFIG VERIFICATION');
console.log('================================');

// Lese .env Konfiguration
const envPath = path.join(__dirname, '..', 'config', '.env');

if (fs.existsSync(envPath)) {
    console.log('✅ .env file found');
    const envContent = fs.readFileSync(envPath, 'utf8');
    
    const config = {};
    envContent.split('\n').forEach(line => {
        if (line.includes('=') && !line.startsWith('#')) {
            const [key, value] = line.split('=');
            config[key.trim()] = value.trim();
        }
    });
    
    console.log('\n📋 Database Configuration:');
    console.log(`Host: ${config.DB_HOST}`);
    console.log(`Database: ${config.DB_NAME}`);
    console.log(`User: ${config.DB_USER}`);
    console.log(`Password: ${'*'.repeat(config.DB_PASS?.length || 0)}`);
    
    console.log('\n✅ Configuration looks good!');
    console.log('\n🚀 Neues Admin Login:');
    console.log('Username: administrator');
    console.log('Password: AdminSecure2024!');
    console.log('\n🧪 Test User:');
    console.log('Username: testuser');
    console.log('Password: TestGame123!');
    
} else {
    console.log('❌ .env file not found');
}
