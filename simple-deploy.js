#!/usr/bin/env node

// simple-deploy.js - modular deployment entrypoint
// IMPORTANT DEPLOYMENT POLICY: Admin files MUST be uploaded under /httpdocs/admin
// and package.json MUST NOT be placed in /httpdocs. This script enforces that.
const path = require('path');
const fs = require('fs');
const logger = require('./deployment/logger');
const FTPClient = require('./deployment/ftpClient');

const deployMap = {
    'admin/database.php': '/httpdocs/admin/database.php',
    'admin/api.php': '/httpdocs/admin/api.php',
    'admin/index.php': '/httpdocs/admin/index.php',
    'admin/login.php': '/httpdocs/admin/login.php',
    'admin/session.php': '/httpdocs/admin/session.php',
    'admin/users.html': '/httpdocs/admin/users.html',
    'admin/.htaccess': '/httpdocs/admin/.htaccess',
    'admin/db-test.php': '/httpdocs/admin/db-test.php',
    'config/.env': '/httpdocs/config/.env',
    'index.html': '/httpdocs/index.html'
};

// Safety: never allow package.json to be uploaded into /httpdocs
for (const remote of Object.values(deployMap)) {
    if (remote.replace(/\\/g, '/').startsWith('/httpdocs/package.json') || remote.endsWith('/package.json')) {
        throw new Error('Deployment map contains package.json target inside /httpdocs - aborting');
    }
}

async function runDeploy({ dryRun = false } = {}) {
    logger.info('Starting deployment (modular)');

    const ftpConfig = {
        host: process.env.FTP_HOST || 'ftp.11seconds.de',
        user: process.env.FTP_USER || 'k302164_11s',
        password: process.env.FTP_PASSWORD,
        secure: false
    };

    // Optional secure override via env
    const secureEnv = (process.env.FTP_SECURE || '').toLowerCase();
    if (secureEnv === '1' || secureEnv === 'true') {
        ftpConfig.secure = true;
    }

    if (!dryRun && (!ftpConfig.user || !ftpConfig.password)) {
        logger.error('Missing FTP credentials. Set FTP_USER and FTP_PASSWORD or run with --dry-run.');
        process.exitCode = 2;
        return;
    }

    const client = new FTPClient(ftpConfig);
    try {
        if (!dryRun) await client.connect();

        let uploaded = 0;
        for (const [local, remote] of Object.entries(deployMap)) {
            if (!fs.existsSync(local)) {
                logger.warn(`Skip missing file: ${local}`);
                continue;
            }

            if (dryRun) {
                logger.info(`DRY RUN: Would upload ${local} -> ${remote}`);
                continue;
            }

            const remoteDir = path.dirname(remote).replace(/\\/g, '/');
            await client.ensureDir(remoteDir);
            await client.upload(local, remote);
            uploaded++;
        }

        logger.info(`Upload finished: ${uploaded} files uploaded`);

        try {
            const verifyUrl = 'https://11seconds.de/admin/login.php';
            logger.info(`Attempting verification fetch: ${verifyUrl}`);
            const fetch = require('node-fetch');
            const res = await fetch(verifyUrl, { timeout: 5000 });
            logger.info(`Verification fetch: HTTP ${res.status}`);
        } catch (e) {
            logger.warn(`Verification fetch failed: ${e.message}`);
        }

    } catch (err) {
        logger.error(`Deployment failed: ${err.message}`);
        process.exitCode = 3;
    } finally {
        await client.close();
    }
}

if (require.main === module) {
    const action = process.argv[2] || 'status';
    const dryRun = process.argv.includes('--dry-run') || process.argv.includes('-d');

    if (action === 'deploy') {
        runDeploy({ dryRun }).then(() => process.exit(process.exitCode || 0));
    } else if (action === 'status') {
        (async () => {
            const fetch = require('node-fetch');
            try {
                const r = await fetch('https://11seconds.de/', { timeout: 5000 });
                console.log('Site root: HTTP', r.status);
                const a = await fetch('https://11seconds.de/admin/login.php', { timeout: 5000 });
                console.log('Admin login: HTTP', a.status);
            } catch (e) {
                console.error('Status check failed:', e.message);
                process.exitCode = 4;
            }
        })();
    } else {
        console.log('Usage: node simple-deploy.js [deploy|status] [--dry-run]');
    }
}
