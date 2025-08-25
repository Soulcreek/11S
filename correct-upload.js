/**
 * 🚀 KORRIGIERTES DEPLOYMENT - httpdocs/admin/ Pfad
 */

const ftp = require('basic-ftp');
const fs = require('fs');

// IMPORTANT DEPLOYMENT POLICY:
// - Admin files MUST be uploaded under /httpdocs/admin
// - package.json MUST NEVER be uploaded into /httpdocs (keep it in repo root)
console.log('NOTICE: Deployment policy enforced: admin -> /httpdocs/admin; package.json forbidden in /httpdocs');

async function uploadEverything() {
    const client = new ftp.Client();
    
    console.log('🎯 UPLOADING TO CORRECT PATH: httpdocs/admin/');
    
    try {
        // FTP Verbindung
        await client.access({
            host: 'ftp.11seconds.de',
            user: process.env.FTP_USER || 'k302164_11s',
            password: process.env.FTP_PASSWORD,
            secure: false
        });
        
        console.log('✅ FTP Connected');
        
    // KORRIGIERTE Upload-Map: -> httpdocs/
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
            
            // Root Files (für Node.js/Passenger)
            // NOTE: package.json must NOT be deployed to /httpdocs — keep it in repo root.
            'index.html': '/httpdocs/index.html'
        };

        // Safety check: refuse to upload package.json into /httpdocs
        for (const v of Object.values(uploads)) {
            if (v.replace(/\\/g, '/').toLowerCase().includes('/httpdocs/package.json') || v.endsWith('/package.json')) {
                throw new Error('Refusing to upload package.json into /httpdocs');
            }
        }
        
        let uploaded = 0;
        for (const [localPath, remotePath] of Object.entries(uploads)) {
            if (fs.existsSync(localPath)) {
                try {
                    // Erstelle Remote-Directory falls nötig
                    const remoteDir = remotePath.substring(0, remotePath.lastIndexOf('/'));
                    await client.ensureDir(remoteDir);
                    
                    await client.uploadFrom(localPath, remotePath);
                    console.log(`📤 ${localPath} → ${remotePath}`);
                    uploaded++;
                } catch (error) {
                    console.error(`❌ Failed: ${localPath} - ${error.message}`);
                }
            } else {
                console.log(`⚠️  Skip: ${localPath} (nicht gefunden)`);
            }
        }
        
        console.log(`✅ Upload complete: ${uploaded} files uploaded to httpdocs/`);
        
        // Test connection
        client.close();
        
    } catch (error) {
        console.error('❌ Upload failed:', error.message);
    }
}

// Export for programmatic use. Protect from running on require by checking main.
if (require.main === module) {
    uploadEverything();
}

module.exports = { uploadEverything };
