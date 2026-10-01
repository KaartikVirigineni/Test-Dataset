const Database = require('better-sqlite3');
const path = require('path');
const fs = require('fs');

const dataDir = path.join(__dirname, '../data');
if (!fs.existsSync(dataDir)) {
  fs.mkdirSync(dataDir, { recursive: true });
}

const dbPath = path.join(dataDir, 'cmp.db');
const db = new Database(dbPath);

db.pragma('journal_mode = WAL');

// Create tables
db.exec(`
  CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
  );

  CREATE TABLE IF NOT EXISTS cookies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    category TEXT NOT NULL,
    description TEXT,
    duration INTEGER,
    vendor TEXT,
    is_essential INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
  );

  CREATE TABLE IF NOT EXISTS consents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    cookie_id INTEGER NOT NULL,
    granted INTEGER NOT NULL DEFAULT 0,
    consent_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address TEXT,
    user_agent TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (cookie_id) REFERENCES cookies(id) ON DELETE CASCADE,
    UNIQUE(user_id, cookie_id)
  );

  CREATE TABLE IF NOT EXISTS consent_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    action TEXT NOT NULL,
    details TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  );

  CREATE INDEX IF NOT EXISTS idx_consents_user ON consents(user_id);
  CREATE INDEX IF NOT EXISTS idx_consents_cookie ON consents(cookie_id);
  CREATE INDEX IF NOT EXISTS idx_logs_user ON consent_logs(user_id);
`);

// Insert default cookies if empty
const cookieCount = db.prepare('SELECT COUNT(*) as count FROM cookies').get();
if (cookieCount.count === 0) {
  const insertCookie = db.prepare(`
    INSERT INTO cookies (name, category, description, duration, vendor, is_essential)
    VALUES (?, ?, ?, ?, ?, ?)
  `);

  const defaultCookies = [
    ['session_id', 'Essential', 'Session identifier for user authentication', 86400, 'Internal', 1],
    ['analytics_token', 'Analytics', 'Google Analytics tracking cookie', 31536000, 'Google', 0],
    ['marketing_id', 'Marketing', 'Marketing and advertising cookie', 7776000, 'Facebook', 0],
    ['preferences', 'Functional', 'User preferences and settings', 2592000, 'Internal', 0]
  ];

  for (const cookie of defaultCookies) {
    insertCookie.run(...cookie);
  }
}

module.exports = db;