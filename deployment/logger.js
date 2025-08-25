// Simple logger used by deployment modules
const levels = { DEBUG: 10, INFO: 20, WARN: 30, ERROR: 40 };

function timestamp() { return new Date().toISOString(); }

function log(level, msg) {
  const name = level.padEnd(5);
  console.log(`[${timestamp()}] [${name}] ${msg}`);
}

module.exports = {
  debug: (m) => log('DEBUG', m),
  info:  (m) => log('INFO', m),
  warn:  (m) => log('WARN', m),
  error: (m) => log('ERROR', m)
};
