<?php
$pageTitle = 'All Cars';
require_once 'header.php';

$pdo = getDB();

// Handle status changes
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'approve') {
        $pdo->prepare("UPDATE cars SET status='approved' WHERE id=?")->execute([$id]);
        $msg = 'Car approved!';
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE cars SET status='rejected' WHERE id=?")->execute([$id]);
        $msg = 'Car rejected.';
    } elseif ($action === 'sold') {
        $pdo->prepare("UPDATE cars SET status='sold' WHERE id=?")->execute([$id]);
        $msg = 'Car marked as sold.';
    } elseif ($action === 'feature') {
        $pdo->prepare("UPDATE cars SET is_featured = NOT is_featured WHERE id=?")->execute([$id]);
        $msg = 'Featured status toggled.';
    } elseif ($action === 'delete') {
        // Delete images from filesystem
        $images = $pdo->prepare("SELECT filename FROM car_images WHERE car_id=?");
        $images->execute([$id]);
        foreach ($images->fetchAll() as $img) {
            $path = __DIR__ . '/../uploads/cars/' . $img['filename'];
            if (file_exists($path)) unlink($path);
        }
        $pdo->prepare("DELETE FROM cars WHERE id=?")->execute([$id]);
        $msg = 'Car deleted.';
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') $msg = 'Car listing added successfully!';
    if ($_GET['msg'] === 'updated') $msg = 'Car listing updated successfully!';
}

// Filter
$statusFilter = $_GET['status'] ?? '';
$where = '';
$params = [];
if ($statusFilter && in_array($statusFilter, ['pending', 'approved', 'rejected', 'sold'])) {
    $where = "WHERE status = ?";
    $params[] = $statusFilter;
}

$cars = $pdo->prepare("SELECT c.*, (SELECT filename FROM car_images WHERE car_id=c.id AND is_primary=1 LIMIT 1) as image FROM cars c $where ORDER BY c.created_at DESC");
$cars->execute($params);
$cars = $cars->fetchAll();
?>

<?php if (!empty($msg)): ?>
    <div class="alert alert-success"><?= sanitize($msg) ?></div>
<?php endif; ?>

<!-- Filter tabs -->
<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
    <a href="cars.php" class="btn <?= !$statusFilter ? 'btn-primary' : 'btn-outline' ?> btn-sm">All</a>
    <a href="cars.php?status=approved" class="btn <?= $statusFilter==='approved' ? 'btn-success' : 'btn-outline' ?> btn-sm">Approved</a>
    <a href="cars.php?status=pending" class="btn <?= $statusFilter==='pending' ? 'btn-primary' : 'btn-outline' ?> btn-sm">Pending</a>
    <a href="cars.php?status=sold" class="btn <?= $statusFilter==='sold' ? 'btn-primary' : 'btn-outline' ?> btn-sm">Sold</a>
    <a href="cars.php?status=rejected" class="btn <?= $statusFilter==='rejected' ? 'btn-danger' : 'btn-outline' ?> btn-sm">Rejected</a>
    <a href="car-add.php" class="btn btn-primary btn-sm" style="margin-left:auto;"><i class="fas fa-plus"></i> Add Car</a>
</div>

<div class="card">
    <div class="table-container">
        <?php if (empty($cars)): ?>
            <div class="empty-state">
                <i class="fas fa-car"></i>
                <p>No cars found. <a href="car-add.php" style="color: var(--amber);">Add a new listing!</a></p>
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Car</th>
                    <th>Price</th>
                    <th>City</th>
                    <th>Seller</th>
                    <th>Status</th>
                    <th>Featured</th>
                    <th>Views</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cars as $car): ?>
                <tr>
                    <td>
                        <div class="car-info-cell">
                            <?php if ($car['image']): ?>
                                <img src="../uploads/cars/<?= sanitize($car['image']) ?>" class="car-thumb" alt="">
                            <?php else: ?>
                                <div class="car-thumb" style="display:flex;align-items:center;justify-content:center;"><i class="fas fa-car" style="color:var(--gray-400);"></i></div>
                            <?php endif; ?>
                            <div class="car-info-text">
                                <h4><?= sanitize($car['title']) ?></h4>
                                <span><?= $car['year'] ?> &middot; <?= sanitize($car['fuel_type']) ?> &middot; <?= sanitize($car['transmission']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td><strong><?= formatPrice($car['price']) ?></strong></td>
                    <td><?= sanitize($car['city']) ?></td>
                    <td>
                        <div style="font-size:0.85rem;"><?= sanitize($car['seller_name']) ?></div>
                        <div style="font-size:0.78rem;color:var(--gray-400);"><?= sanitize($car['seller_phone']) ?></div>
                    </td>
                    <td><span class="status-badge status-<?= $car['status'] ?>"><?= ucfirst($car['status']) ?></span></td>
                    <td>
                        <a href="cars.php?action=feature&id=<?= $car['id'] ?>" title="Toggle featured">
                            <i class="fas fa-star" style="color:<?= $car['is_featured'] ? 'var(--amber)' : 'var(--gray-200)' ?>;font-size:1.1rem;"></i>
                        </a>
                    </td>
                    <td style="color:var(--gray-400);"><?= $car['views'] ?></td>
                    <td>
                        <div class="action-btns">
                            <a href="car-edit.php?id=<?= $car['id'] ?>" class="btn btn-outline btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                            <?php if ($car['status'] === 'pending'): ?>
                                <a href="cars.php?action=approve&id=<?= $car['id'] ?>" class="btn btn-success btn-sm" title="Approve"><i class="fas fa-check"></i></a>
                                <a href="cars.php?action=reject&id=<?= $car['id'] ?>" class="btn btn-danger btn-sm" title="Reject"><i class="fas fa-times"></i></a>
                            <?php elseif ($car['status'] === 'approved'): ?>
                                <a href="cars.php?action=sold&id=<?= $car['id'] ?>" class="btn btn-outline btn-sm" title="Mark Sold"><i class="fas fa-tag"></i></a>
                            <?php endif; ?>
                            <a href="cars.php?action=delete&id=<?= $car['id'] ?>" class="btn btn-danger btn-sm confirm-delete" title="Delete"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
