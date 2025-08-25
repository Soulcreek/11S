const ftp = require('basic-ftp');
const path = require('path');
const fs = require('fs');

async function deployUnifiedAdmin() {
    const client = new ftp.Client();
    
    try {
        console.log('🚀 Deploying Unified Admin Center...');
        
        // Connect to FTP
        await client.access({
            host: 'ftp.11seconds.de',
            user: process.env.FTP_USER,
            password: process.env.FTP_PASSWORD,
            secure: false
        });
        
        console.log('✓ FTP connection established');
        
        // Navigate to admin directory
        await client.ensureDir('/httpdocs/admin');
        await client.cd('/httpdocs/admin');
        
        console.log('✓ Navigated to remote admin directory');
        
        // Upload the new unified admin center
        const localAdminPath = './temp-admin-deploy';
        
        // Upload all files from temp admin deploy
        if (fs.existsSync(localAdminPath)) {
            console.log('📤 Uploading unified admin center files...');
            await client.uploadFromDir(localAdminPath);
            console.log('✅ Unified admin center deployed successfully!');
            
            // Verify deployment
            const files = await client.list();
            console.log('\n📋 Deployed files:');
            files.forEach(file => {
                console.log(`  - ${file.name} (${file.size} bytes)`);
            });
            
        } else {
            throw new Error('Local admin deployment directory not found');
        }
        
        console.log('\n🎉 Deployment completed!');
        console.log('🌐 Access your unified admin center at: https://11seconds.de/admin/admin-center.php');
        
    } catch (error) {
        console.error('❌ Deployment failed:', error.message);
        throw error;
    } finally {
        client.close();
    }
}

// Run deployment
deployUnifiedAdmin().catch(error => {
    console.error('Deployment error:', error);
    process.exit(1);
});
