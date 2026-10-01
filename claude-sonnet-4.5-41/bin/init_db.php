<?php

require dirname(__DIR__) . '/config/bootstrap.php';

use Cake\Datasource\ConnectionManager;

$connection = ConnectionManager::get('default');

$dbFile = ROOT . DS . 'data' . DS . 'feature_flags.sqlite';
$dataDir = dirname($dbFile);

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

if (file_exists($dbFile)) {
    unlink($dbFile);
}

$connection->execute('
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        created DATETIME,
        modified DATETIME
    )
');

$connection->execute('
    CREATE TABLE IF NOT EXISTS flags (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        flag_key VARCHAR(255) NOT NULL UNIQUE,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        enabled INTEGER DEFAULT 0,
        config_value TEXT,
        created DATETIME,
        modified DATETIME
    )
');

$adminToken = bin2hex(random_bytes(32));
$adminPassword = hash('sha256', 'admin123');

$connection->execute(
    'INSERT INTO users (username, password, token, created, modified) VALUES (?, ?, ?, datetime("now"), datetime("now"))',
    ['admin', $adminPassword, $adminToken]
);

$connection->execute(
    'INSERT INTO flags (flag_key, name, description, enabled, config_value, created, modified) 
     VALUES (?, ?, ?, ?, ?, datetime("now"), datetime("now"))',
    ['new_ui', 'New UI Feature', 'Enable the new user interface', 1, null]
);

$connection->execute(
    'INSERT INTO flags (flag_key, name, description, enabled, config_value, created, modified) 
     VALUES (?, ?, ?, ?, ?, datetime("now"), datetime("now"))',
    ['beta_features', 'Beta Features', 'Enable beta features for testing', 0, '{"max_users": 100}']
);

$connection->execute(
    'INSERT INTO flags (flag_key, name, description, enabled, config_value, created, modified) 
     VALUES (?, ?, ?, ?, ?, datetime("now"), datetime("now"))',
    ['maintenance_mode', 'Maintenance Mode', 'Put application in maintenance mode', 0, null]
);

echo "Database initialized successfully!\n";
echo "Admin credentials:\n";
echo "  Username: admin\n";
echo "  Password: admin123\n";
echo "  Token: $adminToken\n";