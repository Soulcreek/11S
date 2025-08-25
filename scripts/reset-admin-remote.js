/*
 * scripts/reset-admin-remote.js
 *
 * Safely resets/creates the admin user on the live server by:
 * 1) Appending a one-time ADMIN_RESET_TOKEN to /httpdocs/config/.env via FTP
 * 2) Calling /admin/api.php?action=reset-admin with the token and provided credentials
 * 3) Removing the token from the remote .env to close the window
 *
 * Usage:
 *   node scripts/reset-admin-remote.js --username administrator --email admin@11seconds.de --password "StrongPass123!"
 *
 * Reads FTP creds from local config/.env (FTP_HOST, FTP_USER, FTP_PASS). Nothing is printed about secrets.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const ftp = require('basic-ftp');
const fetch = require('node-fetch');

function loadDotEnv(file) {
  if (!fs.existsSync(file)) return {};
  const out = {};
  for (const line of fs.readFileSync(file, 'utf8').split(/\r?\n/)) {
    const m = line.match(/^([A-Z0-9_]+)=(.*)$/);
    if (m) out[m[1]] = m[2];
  }
  return out;
}

function arg(name, def) {
  const i = process.argv.indexOf(`--${name}`);
  if (i !== -1 && i + 1 < process.argv.length) return process.argv[i + 1];
  return def;
}

(async () => {
  try {
    const envPath = path.resolve(__dirname, '..', 'config', '.env');
    const env = loadDotEnv(envPath);
    const ftpHost = env.FTP_HOST || 'ftp.11seconds.de';
    const ftpUser = env.FTP_USER;
    const ftpPass = env.FTP_PASS;
    if (!ftpUser || !ftpPass) {
      console.error('FTP credentials missing. Ensure config/.env has FTP_USER and FTP_PASS.');
      process.exit(2);
    }

    const username = arg('username', 'administrator');
    const email = arg('email', 'admin@11seconds.de');
    const password = arg('password', 'Admin!11S-Temp-Reset');

    const client = new ftp.Client();
    client.ftp.verbose = !!process.env.FTP_VERBOSE;
    await client.access({ host: ftpHost, user: ftpUser, password: ftpPass, secure: false });

    const remoteEnv = '/httpdocs/config/.env';
    const tmpLocal = path.resolve(__dirname, 'tmp.remote.env');

    // 1) Download current remote .env
    try {
      await client.downloadTo(tmpLocal, remoteEnv);
    } catch (e) {
      console.error('Failed to download remote .env:', e.message);
      client.close();
      process.exit(3);
    }

    const original = fs.readFileSync(tmpLocal, 'utf8');
    const token = crypto.randomBytes(20).toString('hex');

    // Prepare updated content: remove any existing ADMIN_RESET_TOKEN then append fresh one
    const withoutOld = original
      .split(/\r?\n/)
      .filter((l) => !/^\s*ADMIN_RESET_TOKEN\s*=/.test(l))
      .join('\n');
    const updated = withoutOld + (withoutOld.endsWith('\n') ? '' : '\n') + `ADMIN_RESET_TOKEN=${token}\n`;

    fs.writeFileSync(tmpLocal, updated, 'utf8');

    // Upload modified .env with token
    await client.uploadFrom(tmpLocal, remoteEnv);

    // 2) Call reset-admin with token
    const url = 'https://11seconds.de/admin/api.php?action=reset-admin';
    const params = new URLSearchParams();
    params.set('token', token);
    params.set('username', username);
    params.set('email', email);
    params.set('password', password);

    let ok = false;
    let apiRespText = null;
    try {
      const resp = await fetch(url, { method: 'POST', body: params, timeout: 10000 });
      apiRespText = await resp.text();
      ok = resp.ok;
    } catch (e) {
      console.error('Reset-admin call failed:', e.message);
    }

    // 3) Remove token from remote .env (restore original content)
    fs.writeFileSync(tmpLocal, withoutOld, 'utf8');
    await client.uploadFrom(tmpLocal, remoteEnv);
    client.close();
    try { fs.unlinkSync(tmpLocal); } catch {}

    if (!ok) {
      console.error('Reset-admin API did not return success. Response:', apiRespText);
      process.exit(4);
    }

    console.log('Admin reset executed successfully. Credentials have been updated.');
    console.log(`Username: ${username}`);
    console.log(`Email: ${email}`);
    console.log('Password: [set as provided]');
  } catch (err) {
    console.error('Unexpected error:', err.message);
    process.exit(5);
  }
})();
