const ftp = require('basic-ftp');
const fs = require('fs');
const path = require('path');
(async () => {
    const cfgPath = path.resolve(__dirname, '..', 'config', '.env');
    let host = 'ftp.11seconds.de', user = null, pass = null;
    let lines = [];
    if (fs.existsSync(cfgPath)) {
        lines = fs.readFileSync(cfgPath,'utf8').split(/\r?\n/);
        for (const l of lines) {
            const t = l.trim(); if (!t || t.startsWith('#') || !t.includes('=')) continue;
            const [k,v] = t.split('=',2).map(s=>s.trim());
            if (k==='FTP_HOST') host = v;
            if (k==='FTP_USER') user = v;
            if (k==='FTP_PASS' || k==='FTP_PASSWORD') pass = v;
        }
    }
    if (!user || !pass) { console.error('FTP creds missing'); process.exit(1); }
    // read port if provided
    let port = 21;
    const portLine = lines.find(l => l && l.trim().startsWith('FTP_PORT='));
    if (portLine) {
        try { port = parseInt(portLine.split('=')[1].trim(), 10) || port; } catch (e) { }
    }

    const modes = [
        { secure: false, name: 'plain' },
        { secure: true, name: 'ftps-explicit' },
        { secure: 'implicit', name: 'ftps-implicit' }
    ];

    const remote = '/httpdocs/admin/db-test-debug.log';
    const local = path.resolve(__dirname, 'db-test-debug.log');

    let lastError = null;
    for (const mode of modes) {
        const client = new ftp.Client();
        client.ftp.verbose = true; // print protocol level info to stderr
        client.ftp.socketTimeout = 30000;
        console.log(`Trying FTP mode: ${mode.name} (port ${port}) ...`);
        try {
            await client.access({ host, port, user, password: pass, secure: mode.secure, secureOptions: { rejectUnauthorized: false } });
            console.log('Connected, attempting download:', remote);
            await client.downloadTo(local, remote);
            console.log('Downloaded to', local);
            console.log('--- file start ---');
            console.log(require('fs').readFileSync(local,'utf8'));
            console.log('--- file end ---');
            client.close();
            process.exit(0);
        } catch (e) {
            lastError = e;
            console.error(`Mode ${mode.name} failed:`, e && e.message ? e.message : e);
            // small delay before next attempt
            await new Promise(r => setTimeout(r, 800));
        } finally {
            try { client.close(); } catch (e) { }
        }
    }
    console.error('All connection modes failed. Last error:', lastError && (lastError.stack || lastError.message || String(lastError)));
    process.exit(1);
})();
