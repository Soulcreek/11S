// File: db.js
// Description: Creates and manages the connection to the MySQL database.

// Load environment variables from .env file
require('dotenv').config();

const mysql = require('mysql2');

// Create a connection pool. This is more efficient than creating a new
// connection for every single query.
const pool = mysql.createPool({
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASS,
  database: process.env.DB_NAME,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

// Export the promise-based version of the pool for modern async/await syntax
module.exports = pool.promise();
