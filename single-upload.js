/**
 * Single File Upload Script
 */
const ftp = require('basic-ftp');

async function uploadSingle() {
    const client = new ftp.Client();
    
    try {
        await client.access({
            host: 'ftp.11seconds.de',
            user: process.env.FTP_USER,
            password: process.env.FTP_PASSWORD,
            secure: false
        });
        
        console.log('✅ FTP Connected');
        
        // Upload PHP test to root
        await client.uploadFrom('phptest.php', '/phptest.php');
        console.log('📤 Uploaded phptest.php to ROOT');
        
        client.close();
        console.log('✅ Done');
        
    } catch (error) {
        console.error('❌ Upload failed:', error.message);
    }
}

uploadSingle();
