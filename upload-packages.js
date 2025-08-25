const ftp = require('basic-ftp');
const path = require('path');
const fs = require('fs');
const fg = require('fast-glob');

async function loadCredentials() {
    const host = process.env.FTP_HOST || 'ftp.11seconds.de';
    let user = process.env.FTP_USER || null;
    let password = process.env.FTP_PASSWORD || process.env.FTP_PASS || null;
    if ((!user || !password) && fs.existsSync(path.resolve(__dirname, 'config', '.env'))) {
        const envRaw = fs.readFileSync(path.resolve(__dirname, 'config', '.env'), 'utf8');
        const lines = envRaw.split(/\r?\n/);
        for (const line of lines) {
            const trimmed = line.trim();
            if (!trimmed || trimmed.startsWith('#') || !trimmed.includes('=')) continue;
            const idx = trimmed.indexOf('=');
            const key = trimmed.substring(0, idx).trim();
            const val = trimmed.substring(idx + 1).trim();
            if (!user && key === 'FTP_USER') user = val;
            if (!password && (key === 'FTP_PASS' || key === 'FTP_PASSWORD')) password = val;
            if (user && password) break;
        }
    }
    if (!user || !password) {
        console.error('❌ FTP credentials not found in env or config/.env. Aborting.');
        process.exit(1);
    }
    return { host, user, password };
}

async function uploadPackages() {
    const creds = await loadCredentials();
    const client = new ftp.Client();
    client.ftp.verbose = false;

    try {
        await client.access({ host: creds.host, user: creds.user, password: creds.password, secure: false });
        console.log('✅ FTP Connected');

        // Upload package.json and package-lock.json
        const rootFiles = ['package.json', 'package-lock.json'];
        let uploaded = 0;
        for (const f of rootFiles) {
            const local = path.resolve(__dirname, f);
            if (fs.existsSync(local)) {
                // Upload package files into FTP root ("/") so the repository root holds the manifest
                const remote = '/' + path.basename(f);
                await client.uploadFrom(local, remote);
                console.log(`📤 ${f} → ${remote}`);
                uploaded++;
            } else {
                console.log(`⚠️  Skip (not found): ${f}`);
            }
        }

    // NOTE: node_modules upload intentionally disabled. Create node_modules on the server after uploading package files
    console.log('ℹ️  node_modules upload disabled. Please create/install node_modules on the server or locally as needed.');

        console.log(`✅ Upload complete: ${uploaded} files uploaded`);
    } catch (err) {
        console.error('❌ Upload failed:', err.message || err);
    } finally {
        client.close();
    }
}

uploadPackages();
