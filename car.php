<?php
/**
 * Car Detail Page - Shows a single car listing with all photos and details
 */
require_once __DIR__ . '/includes/config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: browse.php'); exit; }

$pdo = getDB();

// Increment views
$pdo->prepare("UPDATE cars SET views = views + 1 WHERE id = ?")->execute([$id]);

$stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ? AND status IN ('approved', 'sold')");
$stmt->execute([$id]);
$car = $stmt->fetch();

if (!$car) { header('Location: browse.php'); exit; }

$imgStmt = $pdo->prepare("SELECT * FROM car_images WHERE car_id = ? ORDER BY is_primary DESC, sort_order");
$imgStmt->execute([$id]);
$images = $imgStmt->fetchAll();

// Get 4 related cars (same make)
$related = $pdo->prepare("SELECT c.*, (SELECT filename FROM car_images WHERE car_id=c.id AND is_primary=1 LIMIT 1) as image FROM cars c WHERE c.make = ? AND c.id != ? AND c.status='approved' ORDER BY RAND() LIMIT 4");
$related->execute([$car['make'], $id]);
$relatedCars = $related->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($car['title']) ?> - Cardeal.pk</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --navy: #1a3a5c; --navy-deep: #0f2540; --navy-light: #2a5580;
            --amber: #e8850c; --amber-light: #f5a623;
            --white: #ffffff; --gray-50: #f5f6f8; --gray-100: #eaecf0;
            --gray-200: #d0d5dd; --gray-400: #8e95a2; --gray-600: #555d6e; --gray-800: #2d3340;
            --font-heading: 'Montserrat', sans-serif; --font-body: 'Open Sans', sans-serif;
        }
        html { scroll-behavior: smooth; }
        body { font-family: var(--font-body); color: var(--gray-800); background: var(--gray-50); }
        a { text-decoration: none; color: inherit; }
        .container { max-width: 1100px; margin: 0 auto; padding: 0 20px; }

        /* Navbar */
        .navbar { position:fixed;top:0;left:0;right:0;z-index:1000;background:var(--navy-deep);border-bottom:3px solid var(--amber); }
        .navbar-inner { max-width:1100px;margin:0 auto;padding:0 20px;display:flex;align-items:center;justify-content:space-between;height:60px; }
        .logo { font-family:var(--font-heading);font-size:1.5rem;font-weight:900;color:var(--white); }
        .logo span { color:var(--amber); }
        .nav-links { display:flex;align-items:center;gap:28px;list-style:none; }
        .nav-links a { font-family:var(--font-heading);font-size:0.82rem;font-weight:600;color:rgba(255,255,255,0.85);text-transform:uppercase;letter-spacing:0.05em;transition:0.3s; }
        .nav-links a:hover { color:var(--amber); }
        .nav-cta { background:var(--amber);color:var(--white)!important;padding:8px 20px;border-radius:5px;font-weight:700!important; }

        .page-wrap { padding-top: 80px; padding-bottom: 40px; }

        /* Breadcrumb */
        .breadcrumb { font-size:0.82rem;color:var(--gray-400);margin-bottom:20px; }
        .breadcrumb a { color:var(--amber); }
        .breadcrumb a:hover { text-decoration:underline; }

        /* Gallery */
        .car-gallery { display:grid;gap:8px;margin-bottom:24px; }
        .car-gallery.has-images { grid-template-columns: 1fr 1fr; }
        .car-gallery.single-image { grid-template-columns: 1fr; max-width: 600px; }
        .main-image { grid-row: span 2; border-radius:12px;overflow:hidden;aspect-ratio:4/3; }
        .main-image img { width:100%;height:100%;object-fit:cover; }
        .side-images { display:grid;gap:8px; }
        .side-images .thumb { border-radius:8px;overflow:hidden;aspect-ratio:4/3;cursor:pointer;opacity:0.85;transition:0.2s; }
        .side-images .thumb:hover { opacity:1; }
        .side-images .thumb img { width:100%;height:100%;object-fit:cover; }
        .no-image { background:var(--gray-100);border-radius:12px;display:flex;align-items:center;justify-content:center;aspect-ratio:16/9;color:var(--gray-400);font-size:1.2rem; }

        /* Detail layout */
        .detail-grid { display:grid;grid-template-columns:1fr 340px;gap:24px; }

        /* Info */
        .car-title { font-family:var(--font-heading);font-size:1.6rem;font-weight:800;color:var(--navy);margin-bottom:4px; }
        .car-subtitle { font-size:0.9rem;color:var(--gray-400);margin-bottom:16px; }
        .car-price { font-family:var(--font-heading);font-size:1.8rem;font-weight:800;color:var(--amber);margin-bottom:20px; }
        <?php if ($car['status'] === 'sold'): ?>
        .sold-badge { display:inline-block;background:#dc3545;color:#fff;padding:4px 12px;border-radius:6px;font-size:0.82rem;font-weight:700;margin-left:10px; }
        <?php endif; ?>

        .specs-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:24px; }
        .spec-item { background:var(--white);border:1px solid var(--gray-100);border-radius:8px;padding:12px;text-align:center; }
        .spec-item i { color:var(--amber);font-size:1rem;margin-bottom:4px; }
        .spec-item .spec-label { font-size:0.72rem;color:var(--gray-400);text-transform:uppercase;letter-spacing:0.05em; }
        .spec-item .spec-value { font-family:var(--font-heading);font-size:0.88rem;font-weight:700;color:var(--navy); }

        .description-section { background:var(--white);border-radius:10px;padding:20px;margin-bottom:24px; }
        .description-section h3 { font-family:var(--font-heading);font-size:1rem;font-weight:700;color:var(--navy);margin-bottom:10px; }
        .description-section p { font-size:0.9rem;color:var(--gray-600);line-height:1.7; }

        /* Seller card */
        .seller-card { background:var(--white);border-radius:10px;padding:20px;position:sticky;top:80px; }
        .seller-card h3 { font-family:var(--font-heading);font-size:0.95rem;font-weight:700;color:var(--navy);margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--gray-100); }
        .seller-name { font-size:1rem;font-weight:600;color:var(--gray-800);margin-bottom:12px; }
        .seller-btn { display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:12px;border:none;border-radius:8px;font-family:var(--font-heading);font-size:0.9rem;font-weight:700;cursor:pointer;transition:0.2s;margin-bottom:8px; }
        .btn-call { background:var(--navy);color:var(--white); }
        .btn-call:hover { background:var(--navy-light); }
        .btn-whatsapp { background:#25D366;color:var(--white); }
        .btn-whatsapp:hover { background:#1da851; }

        /* Related cars */
        .related-section { margin-top:40px; }
        .related-section h2 { font-family:var(--font-heading);font-size:1.2rem;font-weight:700;color:var(--navy);margin-bottom:16px; }
        .related-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:16px; }
        .related-card { background:var(--white);border-radius:10px;overflow:hidden;transition:0.3s; }
        .related-card:hover { box-shadow:0 4px 16px rgba(0,0,0,0.1);transform:translateY(-2px); }
        .related-card img { width:100%;aspect-ratio:4/3;object-fit:cover; }
        .related-card .card-info { padding:12px; }
        .related-card h4 { font-family:var(--font-heading);font-size:0.82rem;font-weight:700;color:var(--navy);margin-bottom:4px; }
        .related-card .card-price { font-size:0.9rem;font-weight:700;color:var(--amber); }

        /* Footer */
        .footer { background:var(--navy-deep);color:rgba(255,255,255,0.6);text-align:center;padding:20px;margin-top:40px;font-size:0.82rem; }

        @media (max-width: 768px) {
            .detail-grid { grid-template-columns:1fr; }
            .specs-grid { grid-template-columns:repeat(2,1fr); }
            .related-grid { grid-template-columns:1fr 1fr; }
            .car-gallery.has-images { grid-template-columns:1fr; }
            .main-image { grid-row: auto; }
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="navbar-inner">
        <a href="index.html" class="logo">Cardeal<span>.pk</span></a>
        <ul class="nav-links">
            <li><a href="index.html">Home</a></li>
            <li><a href="browse.php">Buy a Car</a></li>
            <li><a href="sell.html">Sell Your Car</a></li>
            <li><a href="contact.html">Contact</a></li>
        </ul>
    </div>
</nav>

<div class="page-wrap">
    <div class="container">
        <div class="breadcrumb">
            <a href="index.html">Home</a> &raquo; <a href="browse.php">Cars</a> &raquo; <?= sanitize($car['title']) ?>
        </div>

        <!-- Photo Gallery -->
        <?php if (!empty($images)): ?>
        <div class="car-gallery <?= count($images) > 1 ? 'has-images' : 'single-image' ?>">
            <div class="main-image" id="mainImage">
                <img src="uploads/cars/<?= sanitize($images[0]['filename']) ?>" alt="<?= sanitize($car['title']) ?>" id="mainImg">
            </div>
            <?php if (count($images) > 1): ?>
            <div class="side-images">
                <?php foreach ($images as $i => $img): ?>
                <div class="thumb" onclick="document.getElementById('mainImg').src='uploads/cars/<?= sanitize($img['filename']) ?>'">
                    <img src="uploads/cars/<?= sanitize($img['filename']) ?>" alt="">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="no-image"><i class="fas fa-car"></i>&nbsp; No photos available</div>
        <?php endif; ?>

        <!-- Detail Grid -->
        <div class="detail-grid">
            <div>
                <h1 class="car-title">
                    <?= sanitize($car['title']) ?>
                    <?php if ($car['status'] === 'sold'): ?><span class="sold-badge">SOLD</span><?php endif; ?>
                </h1>
                <p class="car-subtitle"><?= $car['year'] ?> &middot; <?= sanitize($car['make'] . ' ' . $car['model']) ?> &middot; <?= sanitize($car['city']) ?></p>
                <div class="car-price"><?= formatPrice($car['price']) ?></div>

                <div class="specs-grid">
                    <div class="spec-item">
                        <i class="fas fa-calendar"></i>
                        <div class="spec-label">Year</div>
                        <div class="spec-value"><?= $car['year'] ?></div>
                    </div>
                    <div class="spec-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <div class="spec-label">Mileage</div>
                        <div class="spec-value"><?= $car['mileage'] > 0 ? number_format($car['mileage']) . ' km' : 'N/A' ?></div>
                    </div>
                    <div class="spec-item">
                        <i class="fas fa-gas-pump"></i>
                        <div class="spec-label">Fuel</div>
                        <div class="spec-value"><?= sanitize($car['fuel_type']) ?></div>
                    </div>
                    <div class="spec-item">
                        <i class="fas fa-cogs"></i>
                        <div class="spec-label">Transmission</div>
                        <div class="spec-value"><?= sanitize($car['transmission']) ?></div>
                    </div>
                    <?php if ($car['engine_cc'] > 0): ?>
                    <div class="spec-item">
                        <i class="fas fa-bolt"></i>
                        <div class="spec-label">Engine</div>
                        <div class="spec-value"><?= number_format($car['engine_cc']) ?> cc</div>
                    </div>
                    <?php endif; ?>
                    <?php if ($car['color']): ?>
                    <div class="spec-item">
                        <i class="fas fa-palette"></i>
                        <div class="spec-label">Color</div>
                        <div class="spec-value"><?= sanitize($car['color']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($car['description']): ?>
                <div class="description-section">
                    <h3>Description</h3>
                    <p><?= nl2br(sanitize($car['description'])) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Seller Card -->
            <div>
                <div class="seller-card">
                    <h3><i class="fas fa-user"></i> Seller Information</h3>
                    <p class="seller-name"><?= sanitize($car['seller_name']) ?></p>
                    <a href="tel:<?= sanitize($car['seller_phone']) ?>" class="seller-btn btn-call">
                        <i class="fas fa-phone"></i> <?= sanitize($car['seller_phone']) ?>
                    </a>
                    <?php if ($car['seller_whatsapp']): ?>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $car['seller_whatsapp']) ?>?text=Hi, I'm interested in your <?= rawurlencode($car['title']) ?> listed on Cardeal.pk" target="_blank" class="seller-btn btn-whatsapp">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                    <?php endif; ?>
                    <p style="font-size:0.78rem;color:var(--gray-400);margin-top:10px;text-align:center;">
                        Listed <?= timeAgo($car['created_at']) ?> &middot; <?= $car['views'] ?> views
                    </p>
                </div>
            </div>
        </div>

        <!-- Related Cars -->
        <?php if (!empty($relatedCars)): ?>
        <div class="related-section">
            <h2>More <?= sanitize($car['make']) ?> Cars</h2>
            <div class="related-grid">
                <?php foreach ($relatedCars as $rc): ?>
                <a href="car.php?id=<?= $rc['id'] ?>" class="related-card">
                    <?php if ($rc['image']): ?>
                        <img src="uploads/cars/<?= sanitize($rc['image']) ?>" alt="">
                    <?php else: ?>
                        <div style="aspect-ratio:4/3;background:var(--gray-100);display:flex;align-items:center;justify-content:center;"><i class="fas fa-car" style="color:var(--gray-300);font-size:1.5rem;"></i></div>
                    <?php endif; ?>
                    <div class="card-info">
                        <h4><?= sanitize($rc['title']) ?></h4>
                        <div class="card-price"><?= formatPrice($rc['price']) ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="footer">&copy; <?= date('Y') ?> Cardeal.pk &mdash; We Bring Buyers To Your Car</div>
</body>
</html>
