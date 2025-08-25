const fs = require('fs');
const ftp = require('basic-ftp');
const fetch = require('node-fetch');

function loadEnv(file) {
  const lines = fs.readFileSync(file, 'utf8').split(/\r?\n/);
  const env = {};
  for (const l of lines) {
    if (!l.trim() || l.trim().startsWith('#')) continue;
    const idx = l.indexOf('='); if (idx === -1) continue;
    env[l.slice(0, idx).trim()] = l.slice(idx+1).trim();
  }
  return env;
}

(async ()=>{
  const env = loadEnv('config/.env');
  const client = new ftp.Client();
  client.ftp.verbose = !!process.env.FTP_VERBOSE;
  try {
    await client.access({ host: env.FTP_HOST, user: env.FTP_USER, password: env.FTP_PASS, secure:false });
    await client.ensureDir('/httpdocs/admin');
    await client.uploadFrom('admin/diag-quick.php', '/httpdocs/admin/diag-quick.php');
    console.log('Uploaded diag-quick.php');
    client.close();

    const r = await fetch('https://11seconds.de/admin/diag-quick.php',{timeout:5000});
    console.log('HTTP diag status', r.status);
    console.log(await r.text());

    const logR = await fetch('https://11seconds.de/admin/db-test-debug.log',{timeout:5000});
    console.log('LOG status', logR.status);
    console.log((await logR.text()).slice(-500));
  } catch (e) {
    console.error('Error', e.message);
    client.close();
  }
})();
