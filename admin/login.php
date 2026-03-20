<?php
require_once __DIR__ . '/../includes/config.php';

$error = '';

if (isAdmin()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Cardeal.pk</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Open Sans', sans-serif;
            background: linear-gradient(135deg, #0f2540 0%, #1a3a5c 50%, #2a5580 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: #fff;
            border-radius: 12px;
            padding: 40px 36px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .logo {
            text-align: center;
            font-family: 'Montserrat', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            color: #1a3a5c;
            margin-bottom: 6px;
        }
        .logo span { color: #e8850c; }
        .subtitle {
            text-align: center;
            color: #888;
            font-size: 0.85rem;
            margin-bottom: 28px;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 0.85rem;
            margin-bottom: 16px;
            border: 1px solid #f5c6cb;
        }
        label {
            display: block;
            font-weight: 600;
            color: #333;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
            margin-bottom: 16px;
            transition: 0.2s;
        }
        input:focus {
            outline: none;
            border-color: #e8850c;
            box-shadow: 0 0 0 3px rgba(232,133,12,0.1);
        }
        .btn-login {
            width: 100%;
            padding: 13px;
            background: #e8850c;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .btn-login:hover { background: #d4780a; transform: translateY(-1px); }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #888;
            font-size: 0.85rem;
        }
        .back-link a { color: #1a3a5c; font-weight: 600; }
        .back-link a:hover { color: #e8850c; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="logo">Cardeal<span>.pk</span></div>
    <p class="subtitle">Admin Panel</p>

    <?php if ($error): ?>
        <div class="error"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Username</label>
        <input type="text" name="username" required autofocus value="<?= sanitize($username ?? '') ?>">
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit" class="btn-login">Sign In</button>
    </form>
    <p class="back-link"><a href="../index.html">Back to website</a></p>
</div>
</body>
</html>
