<?php
$pageTitle = 'Edit Car';
require_once __DIR__ . '/../includes/config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: cars.php'); exit; }

$pdo = getDB();
$alert = '';
$alertType = '';

// Handle image deletion
if (isset($_GET['del_img'])) {
    requireAdmin();
    $imgId = (int)$_GET['del_img'];
    $img = $pdo->prepare("SELECT * FROM car_images WHERE id=? AND car_id=?");
    $img->execute([$imgId, $id]);
    $img = $img->fetch();
    if ($img) {
        $path = __DIR__ . '/../uploads/cars/' . $img['filename'];
        if (file_exists($path)) unlink($path);
        $pdo->prepare("DELETE FROM car_images WHERE id=?")->execute([$imgId]);
        // If was primary, set another as primary
        if ($img['is_primary']) {
            $pdo->prepare("UPDATE car_images SET is_primary=1 WHERE car_id=? ORDER BY sort_order LIMIT 1")->execute([$id]);
        }
    }
    header("Location: car-edit.php?id=$id");
    exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireAdmin();

    $title = trim($_POST['title'] ?? '');
    $make = trim($_POST['make'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $year = (int)($_POST['year'] ?? 0);
    $price = (int)($_POST['price'] ?? 0);
    $mileage = (int)($_POST['mileage'] ?? 0);
    $fuel = $_POST['fuel_type'] ?? 'Petrol';
    $transmission = $_POST['transmission'] ?? 'Manual';
    $city = trim($_POST['city'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $engine_cc = (int)($_POST['engine_cc'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $seller_name = trim($_POST['seller_name'] ?? '');
    $seller_phone = trim($_POST['seller_phone'] ?? '');
    $seller_whatsapp = trim($_POST['seller_whatsapp'] ?? '');
    $status = $_POST['status'] ?? 'pending';
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    if (empty($title) && !empty($make) && !empty($model)) {
        $title = $make . ' ' . $model . ' ' . $year;
    }

    if (empty($title) || empty($make) || empty($model) || $year < 1990 || $price < 1 || empty($city) || empty($seller_name) || empty($seller_phone)) {
        $alert = 'Please fill in all required fields.';
        $alertType = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE cars SET title=?, make=?, model=?, year=?, price=?, mileage=?, fuel_type=?, transmission=?, city=?, color=?, engine_cc=?, description=?, seller_name=?, seller_phone=?, seller_whatsapp=?, status=?, is_featured=? WHERE id=?");
            $stmt->execute([$title, $make, $model, $year, $price, $mileage, $fuel, $transmission, $city, $color, $engine_cc, $description, $seller_name, $seller_phone, $seller_whatsapp, $status, $is_featured, $id]);

            // Handle new image uploads
            if (!empty($_FILES['images']['name'][0])) {
                $uploadDir = __DIR__ . '/../uploads/cars/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                $existingCount = $pdo->prepare("SELECT COUNT(*) FROM car_images WHERE car_id=?");
                $existingCount->execute([$id]);
                $existing = $existingCount->fetchColumn();
                $hasImages = $existing > 0;

                foreach ($_FILES['images']['tmp_name'] as $i => $tmpName) {
                    if ($existing >= MAX_IMAGES) break;
                    if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    if ($_FILES['images']['size'][$i] > MAX_FILE_SIZE) continue;

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $tmpName);
                    finfo_close($finfo);
                    if (!in_array($mimeType, $allowedTypes)) continue;

                    $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                    $filename = 'car-' . $id . '-' . uniqid() . '.' . strtolower($ext);

                    if (move_uploaded_file($tmpName, $uploadDir . $filename)) {
                        $imgStmt = $pdo->prepare("INSERT INTO car_images (car_id, filename, is_primary, sort_order) VALUES (?, ?, ?, ?)");
                        $imgStmt->execute([$id, $filename, (!$hasImages && $i === 0) ? 1 : 0, $existing + $i]);
                        $existing++;
                        $hasImages = true;
                    }
                }
            }

            header("Location: cars.php?msg=updated");
            exit;
        } catch (PDOException $e) {
            $alert = 'Error updating car: ' . $e->getMessage();
            $alertType = 'error';
        }
    }
}

requireAdmin();
$car = $pdo->prepare("SELECT * FROM cars WHERE id=?");
$car->execute([$id]);
$car = $car->fetch();
if (!$car) { header('Location: cars.php'); exit; }

$images = $pdo->prepare("SELECT * FROM car_images WHERE car_id=? ORDER BY is_primary DESC, sort_order");
$images->execute([$id]);
$images = $images->fetchAll();

require_once 'header.php';
?>

<?php if ($alert): ?>
    <div class="alert alert-<?= $alertType ?>"><?= sanitize($alert) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-edit"></i> Edit: <?= sanitize($car['title']) ?></h2>
        <a href="cars.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <h3 style="font-family:'Montserrat',sans-serif;font-size:0.9rem;color:var(--navy);margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid var(--gray-100);">Car Details</h3>

            <div class="form-row form-row-3">
                <div class="form-group">
                    <label>Make *</label>
                    <select name="make" class="form-control" required>
                        <option value="">Select Make</option>
                        <?php foreach (['Toyota','Honda','Suzuki','Hyundai','KIA','Daihatsu','Nissan','Mitsubishi','BMW','Mercedes','Audi','MG','Changan','BAIC','Proton','Other'] as $m): ?>
                            <option <?= $car['make'] === $m ? 'selected' : '' ?>><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Model *</label>
                    <input type="text" name="model" class="form-control" value="<?= sanitize($car['model']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Year *</label>
                    <input type="number" name="year" class="form-control" value="<?= $car['year'] ?>" min="1990" max="2026" required>
                </div>
            </div>

            <div class="form-group">
                <label>Listing Title</label>
                <input type="text" name="title" class="form-control" value="<?= sanitize($car['title']) ?>">
            </div>

            <div class="form-row form-row-3">
                <div class="form-group">
                    <label>Price (PKR) *</label>
                    <input type="number" name="price" class="form-control" value="<?= $car['price'] ?>" min="1" required>
                </div>
                <div class="form-group">
                    <label>Mileage (km)</label>
                    <input type="number" name="mileage" class="form-control" value="<?= $car['mileage'] ?>" min="0">
                </div>
                <div class="form-group">
                    <label>Engine (cc)</label>
                    <input type="number" name="engine_cc" class="form-control" value="<?= $car['engine_cc'] ?>" min="0">
                </div>
            </div>

            <div class="form-row form-row-3">
                <div class="form-group">
                    <label>Fuel Type</label>
                    <select name="fuel_type" class="form-control">
                        <?php foreach (['Petrol','Diesel','CNG','Hybrid','Electric'] as $f): ?>
                            <option <?= $car['fuel_type'] === $f ? 'selected' : '' ?>><?= $f ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Transmission</label>
                    <select name="transmission" class="form-control">
                        <option <?= $car['transmission'] === 'Manual' ? 'selected' : '' ?>>Manual</option>
                        <option <?= $car['transmission'] === 'Automatic' ? 'selected' : '' ?>>Automatic</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Color</label>
                    <input type="text" name="color" class="form-control" value="<?= sanitize($car['color']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>City *</label>
                <select name="city" class="form-control" required>
                    <?php foreach (['Lahore','Karachi','Islamabad','Rawalpindi','Faisalabad','Multan','Peshawar','Quetta','Sialkot','Gujranwala','Other'] as $c): ?>
                        <option <?= $car['city'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="4"><?= sanitize($car['description']) ?></textarea>
            </div>

            <!-- Current Images -->
            <h3 style="font-family:'Montserrat',sans-serif;font-size:0.9rem;color:var(--navy);margin:20px 0 12px;padding-bottom:6px;border-bottom:1px solid var(--gray-100);">Current Photos</h3>

            <?php if (!empty($images)): ?>
            <div class="img-preview-grid" style="margin-bottom:16px;">
                <?php foreach ($images as $img): ?>
                <div class="img-preview-item" style="width:120px;height:90px;">
                    <img src="../uploads/cars/<?= sanitize($img['filename']) ?>" alt="">
                    <a href="car-edit.php?id=<?= $id ?>&del_img=<?= $img['id'] ?>" class="remove-img confirm-delete" title="Remove"><i class="fas fa-times" style="font-size:0.6rem;"></i></a>
                    <?php if ($img['is_primary']): ?>
                        <span style="position:absolute;bottom:2px;left:2px;background:var(--amber);color:#fff;font-size:0.6rem;padding:1px 5px;border-radius:3px;">Primary</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="color:var(--gray-400);font-size:0.85rem;margin-bottom:12px;">No photos uploaded yet.</p>
            <?php endif; ?>

            <div class="form-group">
                <label>Upload More Photos</label>
                <input type="file" name="images[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
            </div>

            <!-- Seller Info -->
            <h3 style="font-family:'Montserrat',sans-serif;font-size:0.9rem;color:var(--navy);margin:20px 0 12px;padding-bottom:6px;border-bottom:1px solid var(--gray-100);">Seller Information</h3>

            <div class="form-row form-row-3">
                <div class="form-group">
                    <label>Seller Name *</label>
                    <input type="text" name="seller_name" class="form-control" value="<?= sanitize($car['seller_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="text" name="seller_phone" class="form-control" value="<?= sanitize($car['seller_phone']) ?>" required>
                </div>
                <div class="form-group">
                    <label>WhatsApp</label>
                    <input type="text" name="seller_whatsapp" class="form-control" value="<?= sanitize($car['seller_whatsapp']) ?>">
                </div>
            </div>

            <!-- Status -->
            <h3 style="font-family:'Montserrat',sans-serif;font-size:0.9rem;color:var(--navy);margin:20px 0 12px;padding-bottom:6px;border-bottom:1px solid var(--gray-100);">Publishing</h3>

            <div class="form-row">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="approved" <?= $car['status'] === 'approved' ? 'selected' : '' ?>>Approved (Live)</option>
                        <option value="pending" <?= $car['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="rejected" <?= $car['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="sold" <?= $car['status'] === 'sold' ? 'selected' : '' ?>>Sold</option>
                    </select>
                </div>
                <div class="form-group" style="display:flex;align-items:center;gap:8px;padding-top:24px;">
                    <input type="checkbox" name="is_featured" id="isFeatured" <?= $car['is_featured'] ? 'checked' : '' ?> style="width:18px;height:18px;">
                    <label for="isFeatured" style="margin:0;">Featured on Homepage</label>
                </div>
            </div>

            <div style="margin-top:20px;display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Car Listing</button>
                <a href="cars.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>
