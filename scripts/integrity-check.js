#!/usr/bin/env node
/**
 * Calls the admin integrity-check endpoint and prints the result.
 * Usage: node scripts/integrity-check.js [--base https://11seconds.de] [--token <ADMIN_RESET_TOKEN>]
 * If --token is omitted, tries process.env.ADMIN_RESET_TOKEN or loads from ./config/.env.
 */
const fs = require('fs');
const path = require('path');
const fetch = require('node-fetch');

function loadDotEnv(filepath) {
  try {
    const text = fs.readFileSync(filepath, 'utf8');
    for (const line of text.split(/\r?\n/)) {
      const m = line.match(/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)\s*$/);
      if (!m) continue;
      const k = m[1];
      let v = m[2];
      if (v.startsWith('"') && v.endsWith('"')) v = v.slice(1, -1);
      if (v.startsWith("'") && v.endsWith("'")) v = v.slice(1, -1);
      if (!(k in process.env)) process.env[k] = v;
    }
  } catch (_) {
    // ignore
  }
}

(async () => {
  const args = process.argv.slice(2);
  let base = 'https://11seconds.de';
  let token = undefined;
  for (let i = 0; i < args.length; i++) {
    if (args[i] === '--base' && args[i+1]) { base = args[i+1]; i++; }
    else if (args[i] === '--token' && args[i+1]) { token = args[i+1]; i++; }
  }

  if (!token) {
    if (process.env.ADMIN_RESET_TOKEN) token = process.env.ADMIN_RESET_TOKEN;
    else {
      loadDotEnv(path.join(process.cwd(), 'config', '.env'));
      token = process.env.ADMIN_RESET_TOKEN;
    }
  }

  const url = new URL('/admin/api.php', base);
  url.searchParams.set('action', 'integrity-check');
  if (token) url.searchParams.set('token', token);

  try {
    const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
    const text = await res.text();
    let data;
    try { data = JSON.parse(text); } catch { data = { parseError: true, raw: text }; }
    console.log('HTTP', res.status);
    console.log(JSON.stringify(data, null, 2));
    if (!res.ok) process.exit(1);
  } catch (err) {
    console.error('Request failed:', err.message);
    process.exit(2);
  }
})();
