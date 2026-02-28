<?php
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/config/config.php';
require BASE_PATH . '/src/Core/helpers.php';

spl_autoload_register(function (string $c): void {
    $f = BASE_PATH . '/src/' . str_replace(['TiendaMoroni\\', '\\'], ['', '/'], $c) . '.php';
    if (file_exists($f)) require $f;
});

session_start();
$db = TiendaMoroni\Core\Database::getInstance();

// Add email_verified
$col = $db->query('SHOW COLUMNS FROM users LIKE "email_verified"');
if ($col->rowCount() === 0) {
    $db->exec('ALTER TABLE users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER email');
    echo "Added email_verified\n";
} else {
    echo "email_verified already exists\n";
}

// Add email_verified_at
$col2 = $db->query('SHOW COLUMNS FROM users LIKE "email_verified_at"');
if ($col2->rowCount() === 0) {
    $db->exec('ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER email_verified');
    echo "Added email_verified_at\n";
} else {
    echo "email_verified_at already exists\n";
}

// Create email_verifications table
$db->exec('CREATE TABLE IF NOT EXISTS email_verifications (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    BIGINT UNSIGNED NOT NULL,
  email      VARCHAR(150) NOT NULL,
  token_hash VARCHAR(128) NOT NULL,
  expires_at DATETIME NOT NULL,
  used       TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ev_token (token_hash),
  INDEX idx_ev_user (user_id),
  CONSTRAINT fk_ev_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
echo "email_verifications table OK\n";

// Mark existing users as verified so they can still log in
$db->exec('UPDATE users SET email_verified = 1 WHERE email_verified = 0');
echo "Existing users marked as verified\n";

echo "DESCRIBE users:\n";
$rows = $db->query('DESCRIBE users')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "  {$r['Field']} ({$r['Type']})\n";
}
