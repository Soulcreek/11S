/**
 * 🚀 SIMPLE FTP UPL        // Upload-Map: Lokaler Pfad -> Remote Pfad (KORRIGIERT: httpdocs/)
        const uploads = {
            // Admin Center (Green Glass) - RICHTIG: in httpdocs/admin/
            'admin/database.php': '/httpdocs/admin/database.php',
            'admin/api.php': '/httpdocs/admin/api.php',
            'admin/index.php': '/httpdocs/admin/index.php',
            'admin/login.php': '/httpdocs/admin/login.php',
            'admin/session.php': '/httpdocs/admin/session.php',
            'admin/users.html': '/httpdocs/admin/users.html',
            'admin/.htaccess': '/httpdocs/admin/.htaccess',
            
            // Config 
            'config/.env': '/httpdocs/config/.env',
            
            // Root Files
            // package.json intentionally omitted from uploads to avoid Passenger/Node detection.
            'index.html': '/httpdocs/index.html',
 * Lädt einfach alle benötigten Files hoch - OHNE Validierung!
 */

const ftp = require('basic-ftp');
const fs = require('fs');
const path = require('path');

async function uploadEverything() {
    const client = new ftp.Client();
    
    // INFO: This script enforces deploying admin files to /httpdocs/admin on the remote FTP server.
    // Policy: remote admin folder must be /httpdocs/admin. Do NOT upload package.json into /httpdocs.
    console.log('NOTICE: Enforcing remote admin target: /httpdocs/admin');
    console.log('🚀 UPLOADING GREEN GLASS ADMIN CENTER...');
    
    try {
        // FTP Verbindung
        await client.access({
            host: 'ftp.11seconds.de',
            user: process.env.FTP_USER || 'k302164_11s',
            password: process.env.FTP_PASSWORD,
            secure: false
        });
        
        console.log('✅ FTP Connected');
        
        // Upload-Map: Lokaler Pfad -> Remote Pfad
        const uploads = {
            // Admin Center (Green Glass) - enforced remote target under /httpdocs/admin
            'admin/database.php': '/httpdocs/admin/database.php',
            'admin/api.php': '/httpdocs/admin/api.php',
            'admin/index.php': '/httpdocs/admin/index.php',
            'admin/login.php': '/httpdocs/admin/login.php',
            'admin/session.php': '/httpdocs/admin/session.php',
            'admin/users.html': '/httpdocs/admin/users.html',
            
            // Config (deployed under /httpdocs/config)
            'config/.env': '/httpdocs/config/.env',
            
            // Main App (falls vorhanden)
            'index.html': '/httpdocs/index.html',
            'app.js': '/httpdocs/app.js',
            'style.css': '/httpdocs/style.css',
            
            // Static Files
            'favicon.ico': '/httpdocs/favicon.ico',
            'robots.txt': '/httpdocs/robots.txt'
        };
        
        let uploaded = 0;
        
        for (const [localFile, remotePath] of Object.entries(uploads)) {
            try {
                if (fs.existsSync(localFile)) {
                    // Erstelle Remote Directory
                    const remoteDir = path.dirname(remotePath).replace(/\\/g, '/');
                    if (remoteDir !== '.' && remoteDir !== '/') {
                        await client.ensureDir(remoteDir);
                    }
                    
                    // Upload File
                    await client.uploadFrom(localFile, remotePath);
                    console.log(`📤 ${localFile} → ${remotePath}`);
                    uploaded++;
                } else {
                    console.log(`⚠️  Skip: ${localFile} (nicht gefunden)`);
                }
            } catch (err) {
                console.log(`❌ Error uploading ${localFile}: ${err.message}`);
            }
        }
        
        console.log(`✅ Upload complete: ${uploaded} files uploaded`);
        
    } catch (error) {
        console.error('❌ Upload failed:', error.message);
    } finally {
        client.close();
    }
}

// RUN IT!
uploadEverything();
