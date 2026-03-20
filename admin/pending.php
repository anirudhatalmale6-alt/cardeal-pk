<?php
$pageTitle = 'Pending Approval';
require_once 'header.php';

$pdo = getDB();

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'approve') {
        $pdo->prepare("UPDATE cars SET status='approved' WHERE id=?")->execute([$id]);
    } elseif ($_GET['action'] === 'reject') {
        $pdo->prepare("UPDATE cars SET status='rejected' WHERE id=?")->execute([$id]);
    }
    header('Location: pending.php');
    exit;
}

$cars = $pdo->query("SELECT c.*, (SELECT filename FROM car_images WHERE car_id=c.id AND is_primary=1 LIMIT 1) as image FROM cars c WHERE c.status='pending' ORDER BY c.created_at DESC")->fetchAll();
?>

<?php if (empty($cars)): ?>
<div class="card">
    <div class="empty-state" style="padding:60px;">
        <i class="fas fa-check-circle" style="color:var(--green);"></i>
        <p style="margin-top:10px;">No pending listings! All caught up.</p>
    </div>
</div>
<?php else: ?>
<p style="color:var(--gray-600);margin-bottom:16px;"><?= count($cars) ?> car(s) waiting for your approval.</p>

<?php foreach ($cars as $car): ?>
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="display:flex;gap:20px;align-items:flex-start;">
        <?php if ($car['image']): ?>
            <img src="../uploads/cars/<?= sanitize($car['image']) ?>" style="width:150px;height:112px;border-radius:8px;object-fit:cover;">
        <?php else: ?>
            <div style="width:150px;height:112px;border-radius:8px;background:var(--gray-50);display:flex;align-items:center;justify-content:center;"><i class="fas fa-car" style="font-size:2rem;color:var(--gray-200);"></i></div>
        <?php endif; ?>
        <div style="flex:1;">
            <h3 style="font-family:'Montserrat',sans-serif;font-size:1rem;color:var(--navy);margin-bottom:4px;"><?= sanitize($car['title']) ?></h3>
            <p style="color:var(--gray-400);font-size:0.82rem;margin-bottom:8px;">
                <?= $car['year'] ?> &middot; <?= sanitize($car['fuel_type']) ?> &middot; <?= sanitize($car['transmission']) ?> &middot; <?= sanitize($car['city']) ?>
            </p>
            <p style="font-size:1.1rem;font-weight:700;color:var(--navy);margin-bottom:8px;"><?= formatPrice($car['price']) ?></p>
            <?php if ($car['description']): ?>
                <p style="color:var(--gray-600);font-size:0.85rem;margin-bottom:10px;"><?= sanitize(substr($car['description'], 0, 200)) ?>...</p>
            <?php endif; ?>
            <p style="font-size:0.82rem;color:var(--gray-400);">
                <i class="fas fa-user"></i> <?= sanitize($car['seller_name']) ?> &middot;
                <i class="fas fa-phone"></i> <?= sanitize($car['seller_phone']) ?> &middot;
                <i class="fas fa-clock"></i> <?= timeAgo($car['created_at']) ?>
            </p>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <a href="pending.php?action=approve&id=<?= $car['id'] ?>" class="btn btn-success"><i class="fas fa-check"></i> Approve</a>
            <a href="pending.php?action=reject&id=<?= $car['id'] ?>" class="btn btn-danger"><i class="fas fa-times"></i> Reject</a>
            <a href="car-edit.php?id=<?= $car['id'] ?>" class="btn btn-outline"><i class="fas fa-edit"></i> Edit</a>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
