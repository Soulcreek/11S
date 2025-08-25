const fetch = require('node-fetch');
(async () => {
  const params = new URLSearchParams();
  params.set('username','administrator');
  params.set('password','AdminSecure2024!');
  const r = await fetch('https://11seconds.de/admin/api.php?action=login', { method:'POST', body: params, timeout: 10000 });
  const t = await r.text();
  console.log('HTTP', r.status);
  console.log(t);
})();
