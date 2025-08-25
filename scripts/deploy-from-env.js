// Loads FTP creds from config/.env and runs the real deploy
const fs = require('fs');
const path = require('path');

function loadDotEnv(file) {
  if (!fs.existsSync(file)) return {};
  const txt = fs.readFileSync(file, 'utf8');
  const out = {};
  for (const line of txt.split(/\r?\n/)) {
    const m = line.match(/^([A-Z0-9_]+)=(.*)$/);
    if (m) out[m[1]] = m[2];
  }
  return out;
}

(async () => {
  const envPath = path.resolve(__dirname, '..', 'config', '.env');
  const env = loadDotEnv(envPath);
  // Map FTP_PASS -> FTP_PASSWORD expected by simple-deploy.js
  if (env.FTP_HOST && !process.env.FTP_HOST) process.env.FTP_HOST = env.FTP_HOST;
  if (env.FTP_USER && !process.env.FTP_USER) process.env.FTP_USER = env.FTP_USER;
  if (env.FTP_PASS && !process.env.FTP_PASSWORD) process.env.FTP_PASSWORD = env.FTP_PASS;
  if (env.FTP_PORT && !process.env.FTP_PORT) process.env.FTP_PORT = env.FTP_PORT;

  // Optional verbosity/secure toggles
  if (!process.env.FTP_VERBOSE) process.env.FTP_VERBOSE = '1';
  if (!process.env.FTP_SECURE) process.env.FTP_SECURE = 'false';

  // Run deploy
  require('../simple-deploy');
  process.argv[2] = 'deploy';
  // Note: simple-deploy.js executes based on require.main guard; so spawn as child instead
  const { spawn } = require('child_process');
  const child = spawn(process.execPath, [path.resolve(__dirname, '..', 'simple-deploy.js'), 'deploy'], { stdio: 'inherit' });
  child.on('exit', (code) => process.exit(code));
})();
