// File: api/db-mysql-fixed.js
// Description: Fixed MySQL configuration for Netcup Webhosting 4000

const path = require('path');
require('dotenv').config({ path: path.resolve(__dirname, '../.env') });
const mysql = require('mysql2');

console.log('🔧 Initializing MySQL connection for Netcup Webhosting...');

// Netcup-optimized MySQL configuration
const connectionConfig = {
  host: process.env.DB_HOST,
  port: parseInt(process.env.DB_PORT) || 3306,
  user: process.env.DB_USER,
  password: process.env.DB_PASS,
  database: process.env.DB_NAME,
  charset: process.env.DB_CHARSET || 'utf8mb4',

  // Netcup-specific optimizations
  ssl: false, // Netcup meist ohne SSL
  connectTimeout: 60000, // Länger Timeout für langsame Verbindungen
  acquireTimeout: 60000,
  timeout: 60000,

  // Connection pool settings
  waitForConnections: true,
  connectionLimit: 5, // Reduziert für Shared Hosting
  queueLimit: 0,

  // Retry logic
  reconnect: true,

  // Additional options for shared hosting
  timezone: '+00:00',
  typeCast: true,
  supportBigNumbers: true,
  bigNumberStrings: true
};

console.log(`📡 Connecting to MySQL: ${connectionConfig.user}@${connectionConfig.host}:${connectionConfig.port}/${connectionConfig.database}`);

// Create connection pool
const pool = mysql.createPool(connectionConfig);

// Add connection event handlers
pool.on('connection', function (connection) {
  console.log('✅ MySQL connected as id ' + connection.threadId);
});

pool.on('error', function (err) {
  console.error('❌ MySQL Pool Error:', err);
  if (err.code === 'PROTOCOL_CONNECTION_LOST') {
    console.log('🔄 MySQL connection lost, will retry...');
  } else {
    throw err;
  }
});

// Test connection on startup
pool.getConnection((err, connection) => {
  if (err) {
    console.error('❌ MySQL Initial Connection Failed:', err.message);
    console.log('💡 This might be normal if MySQL is only accessible from the web server');
  } else {
    console.log('✅ MySQL Initial Connection Successful');
    connection.query('SELECT 1 + 1 as test', (error, results) => {
      if (error) {
        console.error('❌ MySQL Test Query Failed:', error.message);
      } else {
        console.log('✅ MySQL Test Query Successful:', results[0]);
      }
      connection.release();
    });
  }
});

// Export promise-based pool
const promisePool = pool.promise();

// Add custom query wrapper with better error handling
const query = async (sql, params = []) => {
  try {
    const [rows, fields] = await promisePool.execute(sql, params);
    return [rows, fields];
  } catch (error) {
    console.error('❌ MySQL Query Error:', {
      sql: sql.substring(0, 100) + (sql.length > 100 ? '...' : ''),
      error: error.message,
      code: error.code
    });

    // Re-throw with more context
    const enhancedError = new Error(`MySQL Error: ${error.message}`);
    enhancedError.originalError = error;
    enhancedError.sql = sql;
    throw enhancedError;
  }
};

// Create enhanced pool object
const enhancedPool = {
  query,
  execute: promisePool.execute.bind(promisePool),
  getConnection: promisePool.getConnection.bind(promisePool),
  end: promisePool.end.bind(promisePool)
};

module.exports = enhancedPool;
