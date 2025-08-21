// File: api/db.js
// Description: Creates and manages the connection to the MySQL database.

const path = require('path');
// Lade die .env-Datei aus dem Hauptverzeichnis
// KORREKTUR: Der Pfad geht jetzt nur eine Ebene nach oben (von /api/ zu /)
require('dotenv').config({ path: path.resolve(__dirname, '../.env') });

const mysql = require('mysql2');

// Create a connection pool. This is more efficient than creating a new
// connection for every single query.
const pool = mysql.createPool({
  host: process.env.DB_HOST,
  port: process.env.DB_PORT, // Use the port from the .env file
  user: process.env.DB_USER,
  password: process.env.DB_PASS,
  database: process.env.DB_NAME,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

// Export the promise-based version of the pool for modern async/await syntax
module.exports = pool.promise();
