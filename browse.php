<?php
/**
 * Browse Cars Page - Dynamic listing with filters
 */
require_once __DIR__ . '/includes/config.php';

$pdo = getDB();

// Get filter values
$filterMake = $_GET['make'] ?? '';
$filterCity = $_GET['city'] ?? '';
$filterFuel = $_GET['fuel'] ?? '';
$filterTrans = $_GET['transmission'] ?? '';
$filterMinPrice = $_GET['min_price'] ?? '';
$filterMaxPrice = $_GET['max_price'] ?? '';
$filterSearch = $_GET['search'] ?? '';
$filterSort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

// Build query
$where = ["c.status = 'approved'"];
$params = [];

if ($filterMake) { $where[] = "c.make = ?"; $params[] = $filterMake; }
if ($filterCity) { $where[] = "c.city = ?"; $params[] = $filterCity; }
if ($filterFuel) { $where[] = "c.fuel_type = ?"; $params[] = $filterFuel; }
if ($filterTrans) { $where[] = "c.transmission = ?"; $params[] = $filterTrans; }
if ($filterMinPrice) { $where[] = "c.price >= ?"; $params[] = (int)$filterMinPrice; }
if ($filterMaxPrice) { $where[] = "c.price <= ?"; $params[] = (int)$filterMaxPrice; }
if ($filterSearch) {
    $where[] = "(c.title LIKE ? OR c.make LIKE ? OR c.model LIKE ?)";
    $s = '%' . $filterSearch . '%';
    $params[] = $s; $params[] = $s; $params[] = $s;
}

$whereStr = implode(' AND ', $where);

$sortOptions = ['newest'=>'c.created_at DESC','price_low'=>'c.price ASC','price_high'=>'c.price DESC','year_new'=>'c.year DESC'];
$sort = $sortOptions[$filterSort] ?? $sortOptions['newest'];

// Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM cars c WHERE $whereStr");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

// Fetch
$stmt = $pdo->prepare("SELECT c.*, (SELECT filename FROM car_images WHERE car_id=c.id AND is_primary=1 LIMIT 1) as image FROM cars c WHERE $whereStr ORDER BY $sort LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$cars = $stmt->fetchAll();

// Get makes and cities for filter dropdowns
$makes = $pdo->query("SELECT DISTINCT make FROM cars WHERE status='approved' ORDER BY make")->fetchAll(PDO::FETCH_COLUMN);
$cities = $pdo->query("SELECT DISTINCT city FROM cars WHERE status='approved' ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy a Car - Cardeal.pk</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0;padding:0;box-sizing:border-box; }
        :root {
            --navy:#1a3a5c;--navy-deep:#0f2540;--navy-light:#2a5580;
            --amber:#e8850c;--amber-light:#f5a623;
            --white:#ffffff;--gray-50:#f5f6f8;--gray-100:#eaecf0;
            --gray-200:#d0d5dd;--gray-400:#8e95a2;--gray-600:#555d6e;--gray-800:#2d3340;
            --font-heading:'Montserrat',sans-serif;--font-body:'Open Sans',sans-serif;
        }
        html{scroll-behavior:smooth;}
        body{font-family:var(--font-body);color:var(--gray-800);background:var(--gray-50);}
        a{text-decoration:none;color:inherit;}
        .container{max-width:1100px;margin:0 auto;padding:0 20px;}

        .navbar{position:fixed;top:0;left:0;right:0;z-index:1000;background:var(--navy-deep);border-bottom:3px solid var(--amber);}
        .navbar-inner{max-width:1100px;margin:0 auto;padding:0 20px;display:flex;align-items:center;justify-content:space-between;height:60px;}
        .logo{font-family:var(--font-heading);font-size:1.5rem;font-weight:900;color:var(--white);}
        .logo span{color:var(--amber);}
        .nav-links{display:flex;align-items:center;gap:28px;list-style:none;}
        .nav-links a{font-family:var(--font-heading);font-size:0.82rem;font-weight:600;color:rgba(255,255,255,0.85);text-transform:uppercase;letter-spacing:0.05em;transition:0.3s;}
        .nav-links a:hover{color:var(--amber);}
        .nav-cta{background:var(--amber);color:var(--white)!important;padding:8px 20px;border-radius:5px;font-weight:700!important;}

        .page-header{background:var(--navy);padding:90px 0 30px;text-align:center;margin-bottom:0;}
        .page-header h1{font-family:var(--font-heading);font-size:1.6rem;font-weight:800;color:var(--white);text-transform:uppercase;}
        .page-header p{color:rgba(255,255,255,0.7);font-size:0.9rem;margin-top:4px;}

        /* Filters */
        .filters{background:var(--white);padding:16px 20px;border-bottom:1px solid var(--gray-100);}
        .filters-inner{max-width:1100px;margin:0 auto;display:flex;gap:10px;flex-wrap:wrap;align-items:center;}
        .filter-input,.filter-select{padding:8px 12px;border:1px solid var(--gray-200);border-radius:6px;font-family:var(--font-body);font-size:0.85rem;background:var(--white);}
        .filter-select{cursor:pointer;}
        .filter-input:focus,.filter-select:focus{outline:none;border-color:var(--amber);}
        .filter-btn{padding:8px 18px;background:var(--amber);color:var(--white);border:none;border-radius:6px;font-family:var(--font-heading);font-size:0.82rem;font-weight:700;cursor:pointer;transition:0.2s;}
        .filter-btn:hover{background:var(--amber-light);}
        .filter-reset{padding:8px 14px;background:transparent;border:1px solid var(--gray-200);border-radius:6px;color:var(--gray-600);font-size:0.82rem;cursor:pointer;}

        .browse-content{padding:24px 0 40px;}
        .results-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}
        .results-bar p{font-size:0.88rem;color:var(--gray-600);}
        .sort-select{padding:6px 10px;border:1px solid var(--gray-200);border-radius:5px;font-size:0.82rem;cursor:pointer;}

        /* Car Grid */
        .car-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;}
        .car-card{background:var(--white);border-radius:10px;overflow:hidden;transition:0.3s;border:1px solid var(--gray-100);}
        .car-card:hover{box-shadow:0 6px 24px rgba(0,0,0,0.1);transform:translateY(-3px);}
        .car-card-img{position:relative;aspect-ratio:4/3;overflow:hidden;background:var(--gray-100);}
        .car-card-img img{width:100%;height:100%;object-fit:cover;transition:0.3s;}
        .car-card:hover .car-card-img img{transform:scale(1.05);}
        .car-card-img .featured-tag{position:absolute;top:10px;left:10px;background:var(--amber);color:var(--white);font-size:0.7rem;font-weight:700;padding:3px 8px;border-radius:4px;text-transform:uppercase;}
        .car-card-body{padding:14px 16px;}
        .car-card h3{font-family:var(--font-heading);font-size:0.92rem;font-weight:700;color:var(--navy);margin-bottom:4px;}
        .car-card .card-meta{font-size:0.78rem;color:var(--gray-400);margin-bottom:8px;}
        .car-card .card-price{font-family:var(--font-heading);font-size:1.1rem;font-weight:800;color:var(--amber);margin-bottom:8px;}
        .car-card .card-specs{display:flex;gap:10px;font-size:0.75rem;color:var(--gray-600);}
        .car-card .card-specs span{display:flex;align-items:center;gap:4px;}
        .car-card .card-specs i{color:var(--gray-400);font-size:0.7rem;}

        .no-results{text-align:center;padding:60px 20px;color:var(--gray-400);}
        .no-results i{font-size:2rem;margin-bottom:10px;}

        /* Pagination */
        .pagination{display:flex;justify-content:center;gap:6px;margin-top:24px;}
        .pagination a,.pagination span{padding:8px 14px;border-radius:6px;font-size:0.85rem;font-weight:600;}
        .pagination a{background:var(--white);border:1px solid var(--gray-200);color:var(--gray-600);transition:0.2s;}
        .pagination a:hover{border-color:var(--amber);color:var(--amber);}
        .pagination .active{background:var(--amber);color:var(--white);border:1px solid var(--amber);}

        .footer{background:var(--navy-deep);color:rgba(255,255,255,0.6);text-align:center;padding:20px;font-size:0.82rem;}

        @media(max-width:768px){
            .car-grid{grid-template-columns:1fr 1fr;}
            .filters-inner{flex-direction:column;}
        }
        @media(max-width:480px){
            .car-grid{grid-template-columns:1fr;}
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="navbar-inner">
        <a href="index.html" class="logo">Cardeal<span>.pk</span></a>
        <ul class="nav-links">
            <li><a href="index.html">Home</a></li>
            <li><a href="browse.php" style="color:var(--amber);">Buy a Car</a></li>
            <li><a href="sell.html">Sell Your Car</a></li>
            <li><a href="contact.html">Contact</a></li>
        </ul>
    </div>
</nav>

<div class="page-header">
    <div class="container">
        <h1>Browse Cars For Sale</h1>
        <p><?= $total ?> car<?= $total !== 1 ? 's' : '' ?> available</p>
    </div>
</div>

<!-- Filters -->
<div class="filters">
    <form class="filters-inner" method="GET">
        <input type="text" name="search" class="filter-input" placeholder="Search..." value="<?= sanitize($filterSearch) ?>" style="min-width:160px;">
        <select name="make" class="filter-select">
            <option value="">All Makes</option>
            <?php foreach ($makes as $m): ?>
                <option <?= $filterMake === $m ? 'selected' : '' ?>><?= sanitize($m) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="city" class="filter-select">
            <option value="">All Cities</option>
            <?php foreach ($cities as $c): ?>
                <option <?= $filterCity === $c ? 'selected' : '' ?>><?= sanitize($c) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="fuel" class="filter-select">
            <option value="">Fuel Type</option>
            <?php foreach (['Petrol','Diesel','CNG','Hybrid','Electric'] as $f): ?>
                <option <?= $filterFuel === $f ? 'selected' : '' ?>><?= $f ?></option>
            <?php endforeach; ?>
        </select>
        <select name="transmission" class="filter-select">
            <option value="">Transmission</option>
            <option <?= $filterTrans === 'Manual' ? 'selected' : '' ?>>Manual</option>
            <option <?= $filterTrans === 'Automatic' ? 'selected' : '' ?>>Automatic</option>
        </select>
        <input type="number" name="min_price" class="filter-input" placeholder="Min Price" value="<?= sanitize($filterMinPrice) ?>" style="width:110px;">
        <input type="number" name="max_price" class="filter-input" placeholder="Max Price" value="<?= sanitize($filterMaxPrice) ?>" style="width:110px;">
        <input type="hidden" name="sort" value="<?= sanitize($filterSort) ?>">
        <button type="submit" class="filter-btn"><i class="fas fa-search"></i> Search</button>
        <a href="browse.php" class="filter-reset">Reset</a>
    </form>
</div>

<div class="browse-content">
    <div class="container">
        <div class="results-bar">
            <p>Showing <?= count($cars) ?> of <?= $total ?> cars</p>
            <select class="sort-select" onchange="location.href='browse.php?<?= http_build_query(array_merge($_GET, ['sort'=>''])) ?>'.replace('sort=','sort='+this.value)">
                <option value="newest" <?= $filterSort==='newest'?'selected':'' ?>>Newest First</option>
                <option value="price_low" <?= $filterSort==='price_low'?'selected':'' ?>>Price: Low to High</option>
                <option value="price_high" <?= $filterSort==='price_high'?'selected':'' ?>>Price: High to Low</option>
                <option value="year_new" <?= $filterSort==='year_new'?'selected':'' ?>>Year: Newest</option>
            </select>
        </div>

        <?php if (empty($cars)): ?>
        <div class="no-results">
            <i class="fas fa-search"></i>
            <p>No cars found matching your criteria.</p>
            <a href="browse.php" style="color:var(--amber);font-weight:600;">Clear all filters</a>
        </div>
        <?php else: ?>
        <div class="car-grid">
            <?php foreach ($cars as $car): ?>
            <a href="car.php?id=<?= $car['id'] ?>" class="car-card">
                <div class="car-card-img">
                    <?php if ($car['image']): ?>
                        <img src="uploads/cars/<?= sanitize($car['image']) ?>" alt="<?= sanitize($car['title']) ?>">
                    <?php else: ?>
                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;"><i class="fas fa-car" style="font-size:2rem;color:var(--gray-300);"></i></div>
                    <?php endif; ?>
                    <?php if ($car['is_featured']): ?>
                        <span class="featured-tag">Featured</span>
                    <?php endif; ?>
                </div>
                <div class="car-card-body">
                    <h3><?= sanitize($car['title']) ?></h3>
                    <div class="card-meta"><?= $car['year'] ?> &middot; <?= sanitize($car['city']) ?></div>
                    <div class="card-price"><?= formatPrice($car['price']) ?></div>
                    <div class="card-specs">
                        <span><i class="fas fa-gas-pump"></i> <?= sanitize($car['fuel_type']) ?></span>
                        <span><i class="fas fa-cogs"></i> <?= sanitize($car['transmission']) ?></span>
                        <?php if ($car['mileage'] > 0): ?>
                        <span><i class="fas fa-tachometer-alt"></i> <?= number_format($car['mileage']) ?> km</span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="browse.php?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo; Prev</a>
            <?php endif; ?>
            <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="browse.php?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="browse.php?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="footer">&copy; <?= date('Y') ?> Cardeal.pk &mdash; We Bring Buyers To Your Car</div>
</body>
</html>
