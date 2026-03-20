<?php
$pageTitle = 'Site Settings';
require_once 'header.php';

$pdo = getDB();
$alert = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['site_phone', 'site_email', 'site_whatsapp', 'site_address', 'facebook_url', 'instagram_url', 'youtube_url'];
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

    foreach ($fields as $key) {
        $stmt->execute([$key, trim($_POST[$key] ?? '')]);
    }

    // Handle password change
    if (!empty($_POST['new_password'])) {
        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            $alert = 'error:Passwords do not match.';
        } elseif (strlen($_POST['new_password']) < 6) {
            $alert = 'error:Password must be at least 6 characters.';
        } else {
            $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE admin_users SET password_hash=? WHERE id=?")->execute([$hash, $_SESSION['admin_id']]);
            $alert = 'success:Password updated successfully!';
        }
    }

    if (!$alert || strpos($alert, 'success') === 0) {
        $alert = 'success:Settings saved successfully!';
    }
}

// Load current settings
$settings = [];
$rows = $pdo->query("SELECT * FROM settings")->fetchAll();
foreach ($rows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<?php if ($alert): ?>
    <?php list($type, $message) = explode(':', $alert, 2); ?>
    <div class="alert alert-<?= $type ?>"><?= sanitize($message) ?></div>
<?php endif; ?>

<form method="POST">
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><h2><i class="fas fa-address-card"></i> Contact Information</h2></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="site_phone" class="form-control" value="<?= sanitize($settings['site_phone'] ?? '') ?>" placeholder="+92 300 1234567">
                </div>
                <div class="form-group">
                    <label>WhatsApp Number</label>
                    <input type="text" name="site_whatsapp" class="form-control" value="<?= sanitize($settings['site_whatsapp'] ?? '') ?>" placeholder="+92 300 1234567">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="site_email" class="form-control" value="<?= sanitize($settings['site_email'] ?? '') ?>" placeholder="info@cardeal.pk">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="site_address" class="form-control" value="<?= sanitize($settings['site_address'] ?? '') ?>" placeholder="City, Country">
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><h2><i class="fab fa-facebook"></i> Social Media</h2></div>
        <div class="card-body">
            <div class="form-row form-row-3">
                <div class="form-group">
                    <label>Facebook URL</label>
                    <input type="url" name="facebook_url" class="form-control" value="<?= sanitize($settings['facebook_url'] ?? '') ?>" placeholder="https://facebook.com/cardeal.pk">
                </div>
                <div class="form-group">
                    <label>Instagram URL</label>
                    <input type="url" name="instagram_url" class="form-control" value="<?= sanitize($settings['instagram_url'] ?? '') ?>" placeholder="https://instagram.com/cardeal.pk">
                </div>
                <div class="form-group">
                    <label>YouTube URL</label>
                    <input type="url" name="youtube_url" class="form-control" value="<?= sanitize($settings['youtube_url'] ?? '') ?>" placeholder="https://youtube.com/@cardeal">
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><h2><i class="fas fa-lock"></i> Change Admin Password</h2></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current" minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password">
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
</form>

<?php require_once 'footer.php'; ?>
