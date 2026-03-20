<?php
$pageTitle = 'Add New Car';
$alert = '';
$alertType = '';

require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireAdmin();
    $pdo = getDB();

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
    $status = $_POST['status'] ?? 'approved';
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // Auto-generate title if empty
    if (empty($title) && !empty($make) && !empty($model)) {
        $title = $make . ' ' . $model . ' ' . $year;
    }

    if (empty($title) || empty($make) || empty($model) || $year < 1990 || $price < 1 || empty($city) || empty($seller_name) || empty($seller_phone)) {
        $alert = 'Please fill in all required fields.';
        $alertType = 'error';
    } else {
        try {
            $slug = generateSlug($title . '-' . $year);
            // Ensure unique slug
            $check = $pdo->prepare("SELECT id FROM cars WHERE slug = ?");
            $check->execute([$slug]);
            if ($check->fetch()) {
                $slug .= '-' . time();
            }

            $stmt = $pdo->prepare("INSERT INTO cars (title, slug, make, model, year, price, mileage, fuel_type, transmission, city, color, engine_cc, description, seller_name, seller_phone, seller_whatsapp, status, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $slug, $make, $model, $year, $price, $mileage, $fuel, $transmission, $city, $color, $engine_cc, $description, $seller_name, $seller_phone, $seller_whatsapp, $status, $is_featured]);
            $carId = $pdo->lastInsertId();

            // Handle image uploads
            if (!empty($_FILES['images']['name'][0])) {
                $uploadDir = __DIR__ . '/../uploads/cars/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                $isPrimary = true;

                foreach ($_FILES['images']['tmp_name'] as $i => $tmpName) {
                    if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    if ($_FILES['images']['size'][$i] > MAX_FILE_SIZE) continue;

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $tmpName);
                    finfo_close($finfo);

                    if (!in_array($mimeType, $allowedTypes)) continue;

                    $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                    $filename = 'car-' . $carId . '-' . uniqid() . '.' . strtolower($ext);

                    if (move_uploaded_file($tmpName, $uploadDir . $filename)) {
                        $imgStmt = $pdo->prepare("INSERT INTO car_images (car_id, filename, is_primary, sort_order) VALUES (?, ?, ?, ?)");
                        $imgStmt->execute([$carId, $filename, $isPrimary ? 1 : 0, $i]);
                        $isPrimary = false;
                    }
                }
            }

            header('Location: cars.php?msg=added');
            exit;
        } catch (PDOException $e) {
            $alert = 'Error saving car: ' . $e->getMessage();
            $alertType = 'error';
        }
    }
}

require_once 'header.php';
?>

<?php if ($alert): ?>
    <div class="alert alert-<?= $alertType ?>"><?= sanitize($alert) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-plus-circle"></i> Add New Car Listing</h2>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <!-- Car Details -->
            <h3 style="font-family:'Montserrat',sans-serif;font-size:0.9rem;color:var(--navy);margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid var(--gray-100);">Car Details</h3>

            <div class="form-row form-row-3">
                <div class="form-group">
                    <label>Make *</label>
                    <select name="make" class="form-control" required>
                        <option value="">Select Make</option>
                        <option>Toyota</option>
                        <option>Honda</option>
                        <option>Suzuki</option>
                        <option>Hyundai</option>
                        <option>KIA</option>
                        <option>Daihatsu</option>
                        <option>Nissan</option>
                        <option>Mitsubishi</option>
                        <option>BMW</option>
                        <option>Mercedes</option>
                        <option>Audi</option>
                        <option>MG</option>
                        <option>Changan</option>
                        <option>BAIC</option>
                        <option>Proton</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Model *</label>
                    <input type="text" name="model" class="form-control" placeholder="e.g. Corolla, Civic" required>
                </div>
                <div class="form-group">
                    <label>Year *</label>
                    <input type="number" name="year" class="form-control" min="1990" max="2026" placeholder="2024" required>
                </div>
            </div>

            <div class="form-group">
                <label>Listing Title (auto-generated if empty)</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Toyota Corolla 2020 - Excellent Condition">
            </div>

            <div class="form-row form-row-3">
                <div class="form-group">
                    <label>Price (PKR) *</label>
                    <input type="number" name="price" class="form-control" min="1" placeholder="3500000" required>
                </div>
                <div class="form-group">
                    <label>Mileage (km)</label>
                    <input type="number" name="mileage" class="form-control" min="0" placeholder="45000">
                </div>
                <div class="form-group">
                    <label>Engine (cc)</label>
                    <input type="number" name="engine_cc" class="form-control" min="0" placeholder="1300">
                </div>
            </div>

            <div class="form-row form-row-3">
                <div class="form-group">
                    <label>Fuel Type</label>
                    <select name="fuel_type" class="form-control">
                        <option>Petrol</option>
                        <option>Diesel</option>
                        <option>CNG</option>
                        <option>Hybrid</option>
                        <option>Electric</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Transmission</label>
                    <select name="transmission" class="form-control">
                        <option>Manual</option>
                        <option>Automatic</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Color</label>
                    <input type="text" name="color" class="form-control" placeholder="e.g. White, Black">
                </div>
            </div>

            <div class="form-group">
                <label>City *</label>
                <select name="city" class="form-control" required>
                    <option value="">Select City</option>
                    <option>Lahore</option>
                    <option>Karachi</option>
                    <option>Islamabad</option>
                    <option>Rawalpindi</option>
                    <option>Faisalabad</option>
                    <option>Multan</option>
                    <option>Peshawar</option>
                    <option>Quetta</option>
                    <option>Sialkot</option>
                    <option>Gujranwala</option>
                    <option>Other</option>
                </select>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="4" placeholder="Describe the car condition, features, any modifications, etc."></textarea>
            </div>

            <!-- Images -->
            <h3 style="font-family:'Montserrat',sans-serif;font-size:0.9rem;color:var(--navy);margin:20px 0 12px;padding-bottom:6px;border-bottom:1px solid var(--gray-100);">Photos (up to <?= MAX_IMAGES ?>)</h3>

            <div class="form-group">
                <label>Upload Car Photos</label>
                <input type="file" name="images[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple id="imageInput">
                <div id="imagePreview" class="img-preview-grid"></div>
            </div>

            <!-- Seller Info -->
            <h3 style="font-family:'Montserrat',sans-serif;font-size:0.9rem;color:var(--navy);margin:20px 0 12px;padding-bottom:6px;border-bottom:1px solid var(--gray-100);">Seller Information</h3>

            <div class="form-row form-row-3">
                <div class="form-group">
                    <label>Seller Name *</label>
                    <input type="text" name="seller_name" class="form-control" required placeholder="Full name">
                </div>
                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="text" name="seller_phone" class="form-control" required placeholder="03001234567">
                </div>
                <div class="form-group">
                    <label>WhatsApp (optional)</label>
                    <input type="text" name="seller_whatsapp" class="form-control" placeholder="03001234567">
                </div>
            </div>

            <!-- Status -->
            <h3 style="font-family:'Montserrat',sans-serif;font-size:0.9rem;color:var(--navy);margin:20px 0 12px;padding-bottom:6px;border-bottom:1px solid var(--gray-100);">Publishing</h3>

            <div class="form-row">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="approved">Approved (Live)</option>
                        <option value="pending">Pending Review</option>
                    </select>
                </div>
                <div class="form-group" style="display:flex;align-items:center;gap:8px;padding-top:24px;">
                    <input type="checkbox" name="is_featured" id="isFeatured" style="width:18px;height:18px;">
                    <label for="isFeatured" style="margin:0;">Featured on Homepage</label>
                </div>
            </div>

            <div style="margin-top:20px;display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Car Listing</button>
                <a href="cars.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
// Image preview
document.getElementById('imageInput').addEventListener('change', function(e) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    const files = Array.from(e.target.files).slice(0, <?= MAX_IMAGES ?>);
    files.forEach(file => {
        const reader = new FileReader();
        reader.onload = function(ev) {
            const div = document.createElement('div');
            div.className = 'img-preview-item';
            div.innerHTML = '<img src="' + ev.target.result + '">';
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
});
</script>

<?php require_once 'footer.php'; ?>
