const ftp = require('basic-ftp');

async function removeRemotePackage() {
    const client = new ftp.Client();
    client.ftp.verbose = false;

    const path = require('path');
    const fs = require('fs');

    // Prefer environment variables, fall back to config/.env in repo
    const host = process.env.FTP_HOST || 'ftp.11seconds.de';
    let user = process.env.FTP_USER || null;
    let password = process.env.FTP_PASSWORD || process.env.FTP_PASS || null;

    // Try to read config/.env if credentials are not provided via environment
    if ((!user || !password) && fs.existsSync(path.resolve(__dirname, 'config', '.env'))) {
        try {
            const envRaw = fs.readFileSync(path.resolve(__dirname, 'config', '.env'), 'utf8');
            const lines = envRaw.split(/\r?\n/);
            for (const line of lines) {
                const trimmed = line.trim();
                if (!trimmed || trimmed.startsWith('#') || !trimmed.includes('=')) continue;
                const idx = trimmed.indexOf('=');
                const key = trimmed.substring(0, idx).trim();
                const val = trimmed.substring(idx + 1).trim();
                if (!user && (key === 'FTP_USER')) user = val;
                if (!password && (key === 'FTP_PASS' || key === 'FTP_PASSWORD')) password = val;
                if (user && password) break;
            }
        } catch (err) {
            console.error('❌ Failed to read config/.env:', err.message);
        }
    }

    if (!user || !password) {
        console.error('❌ FTP credentials not found in environment or config/.env. Aborting.');
        process.exit(1);
    }

    try {
        await client.access({ host, user, password, secure: false });
        console.log('✅ FTP Connected');

        const remotePath = '/httpdocs/package.json';

        try {
            // Try to remove file
            await client.remove(remotePath);
            console.log(`🗑️  Removed remote file: ${remotePath}`);
        } catch (err) {
            // If file not found, log and continue
            const msg = (err && err.message) ? err.message : '';
            if (err && (err.code === 550 || /No such file/i.test(msg) || /does not exist/i.test(msg))) {
                console.log('ℹ️  Remote package.json not found — nothing to remove.');
            } else {
                console.error('❌ Error removing remote package.json:', msg || err);
            }
        }
    } catch (err) {
        console.error('❌ FTP error:', err.message);
    } finally {
        client.close();
    }
}

removeRemotePackage();
