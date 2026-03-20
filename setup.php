<?php
/**
 * Cardeal.pk - Database Setup Script
 * Run this once to create database tables and admin account.
 * DELETE THIS FILE after setup!
 */

require_once __DIR__ . '/includes/config.php';

$messages = [];

// Create database tables
try {
    $pdo = getDB();

    // Cars table
    $pdo->exec("CREATE TABLE IF NOT EXISTS cars (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE,
        make VARCHAR(100) NOT NULL,
        model VARCHAR(100) NOT NULL,
        year INT NOT NULL,
        price BIGINT NOT NULL,
        mileage INT DEFAULT 0,
        fuel_type ENUM('Petrol','Diesel','CNG','Hybrid','Electric') DEFAULT 'Petrol',
        transmission ENUM('Manual','Automatic') DEFAULT 'Manual',
        city VARCHAR(100) NOT NULL,
        color VARCHAR(50) DEFAULT '',
        engine_cc INT DEFAULT 0,
        description TEXT,
        seller_name VARCHAR(100) NOT NULL,
        seller_phone VARCHAR(20) NOT NULL,
        seller_whatsapp VARCHAR(20) DEFAULT '',
        status ENUM('pending','approved','rejected','sold') DEFAULT 'pending',
        is_featured TINYINT(1) DEFAULT 0,
        views INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $messages[] = ['success', 'Cars table created.'];

    // Car images table
    $pdo->exec("CREATE TABLE IF NOT EXISTS car_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        car_id INT NOT NULL,
        filename VARCHAR(255) NOT NULL,
        is_primary TINYINT(1) DEFAULT 0,
        sort_order INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $messages[] = ['success', 'Car images table created.'];

    // Contact messages table
    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(255) DEFAULT '',
        phone VARCHAR(20) NOT NULL,
        car_interest VARCHAR(255) DEFAULT '',
        budget VARCHAR(50) DEFAULT '',
        message TEXT,
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $messages[] = ['success', 'Contact messages table created.'];

    // Admin users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $messages[] = ['success', 'Admin users table created.'];

    // Site settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $messages[] = ['success', 'Settings table created.'];

    // Insert default settings
    $pdo->exec("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
        ('site_phone', '+92 300 1234567'),
        ('site_email', 'info@cardeal.pk'),
        ('site_whatsapp', '+92 300 1234567'),
        ('site_address', 'Lahore, Pakistan'),
        ('facebook_url', ''),
        ('instagram_url', ''),
        ('youtube_url', '')
    ");
    $messages[] = ['success', 'Default settings inserted.'];

} catch (PDOException $e) {
    $messages[] = ['error', 'Database error: ' . $e->getMessage()];
}

// Handle admin account creation
$adminCreated = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_username'])) {
    $username = trim($_POST['admin_username']);
    $password = $_POST['admin_password'];
    $confirm = $_POST['admin_confirm'];

    if (empty($username) || empty($password)) {
        $messages[] = ['error', 'Username and password are required.'];
    } elseif ($password !== $confirm) {
        $messages[] = ['error', 'Passwords do not match.'];
    } elseif (strlen($password) < 6) {
        $messages[] = ['error', 'Password must be at least 6 characters.'];
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE password_hash = ?");
            $stmt->execute([$username, $hash, $hash]);
            $messages[] = ['success', "Admin account '$username' created successfully!"];
            $adminCreated = true;
        } catch (PDOException $e) {
            $messages[] = ['error', 'Error creating admin: ' . $e->getMessage()];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cardeal.pk - Setup</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 40px 20px; }
        .setup-container { max-width: 600px; margin: 0 auto; }
        h1 { color: #1a3a5c; margin-bottom: 8px; }
        .subtitle { color: #666; margin-bottom: 30px; }
        .card { background: #fff; border-radius: 10px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .card h2 { color: #1a3a5c; font-size: 1.1rem; margin-bottom: 16px; border-bottom: 2px solid #e8850c; padding-bottom: 8px; }
        .msg { padding: 10px 14px; border-radius: 6px; margin-bottom: 8px; font-size: 0.9rem; }
        .msg-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .msg-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        label { display: block; font-weight: 600; margin-bottom: 4px; color: #333; font-size: 0.9rem; }
        input[type="text"], input[type="password"] {
            width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.95rem; margin-bottom: 14px;
        }
        input:focus { outline: none; border-color: #e8850c; box-shadow: 0 0 0 3px rgba(232,133,12,0.1); }
        .btn { background: #e8850c; color: #fff; border: none; padding: 12px 28px; border-radius: 6px; font-size: 0.95rem; font-weight: 600; cursor: pointer; }
        .btn:hover { background: #d4780a; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffc107; padding: 12px; border-radius: 6px; margin-top: 20px; font-size: 0.9rem; }
    </style>
</head>
<body>
<div class="setup-container">
    <h1>Cardeal.pk Setup</h1>
    <p class="subtitle">Database tables and admin account setup</p>

    <div class="card">
        <h2>Database Tables</h2>
        <?php foreach ($messages as $msg): ?>
            <div class="msg msg-<?= $msg[0] ?>"><?= $msg[1] ?></div>
        <?php endforeach; ?>
    </div>

    <?php if (!$adminCreated): ?>
    <div class="card">
        <h2>Create Admin Account</h2>
        <form method="POST">
            <label>Username</label>
            <input type="text" name="admin_username" value="admin" required>
            <label>Password</label>
            <input type="password" name="admin_password" required minlength="6">
            <label>Confirm Password</label>
            <input type="password" name="admin_confirm" required minlength="6">
            <button type="submit" class="btn">Create Admin Account</button>
        </form>
    </div>
    <?php else: ?>
    <div class="card">
        <h2>Setup Complete!</h2>
        <p style="margin-bottom:12px;">You can now <a href="admin/" style="color:#e8850c;font-weight:600;">log in to the admin panel</a>.</p>
    </div>
    <?php endif; ?>

    <div class="warning">
        <strong>Important:</strong> Delete this file (setup.php) after completing the setup for security!
    </div>
</div>
</body>
</html>
