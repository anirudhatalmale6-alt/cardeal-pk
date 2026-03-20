<?php
$pageTitle = 'Dashboard';
require_once 'header.php';

$pdo = getDB();

// Stats
$approvedCars = $pdo->query("SELECT COUNT(*) FROM cars WHERE status='approved'")->fetchColumn();
$soldCars = $pdo->query("SELECT COUNT(*) FROM cars WHERE status='sold'")->fetchColumn();
$totalViews = $pdo->query("SELECT COALESCE(SUM(views), 0) FROM cars")->fetchColumn();
$totalMessages = $pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();

// Recent cars
$recentCars = $pdo->query("SELECT c.*, (SELECT filename FROM car_images WHERE car_id=c.id AND is_primary=1 LIMIT 1) as image FROM cars c ORDER BY c.created_at DESC LIMIT 5")->fetchAll();

// Recent messages
$recentMessages = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>

<!-- Stats -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <h3><?= $pendingCount ?></h3>
            <p>Pending Approval</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <h3><?= $approvedCars ?></h3>
            <p>Active Listings</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon navy"><i class="fas fa-car"></i></div>
        <div class="stat-info">
            <h3><?= $soldCars ?></h3>
            <p>Cars Sold</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-envelope"></i></div>
        <div class="stat-info">
            <h3><?= $unreadCount ?></h3>
            <p>Unread Messages</p>
        </div>
    </div>
</div>

<!-- Recent Cars -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h2>Recent Car Listings</h2>
        <a href="cars.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-container">
        <?php if (empty($recentCars)): ?>
            <div class="empty-state">
                <i class="fas fa-car"></i>
                <p>No car listings yet. <a href="car-add.php" style="color: var(--amber);">Add the first one!</a></p>
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Car</th>
                    <th>Price</th>
                    <th>City</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentCars as $car): ?>
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
                                <span><?= sanitize($car['make'] . ' ' . $car['model'] . ' ' . $car['year']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td><strong><?= formatPrice($car['price']) ?></strong></td>
                    <td><?= sanitize($car['city']) ?></td>
                    <td><span class="status-badge status-<?= $car['status'] ?>"><?= ucfirst($car['status']) ?></span></td>
                    <td style="color:var(--gray-400);font-size:0.82rem;"><?= timeAgo($car['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Messages -->
<div class="card">
    <div class="card-header">
        <h2>Recent Messages</h2>
        <a href="messages.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-container">
        <?php if (empty($recentMessages)): ?>
            <div class="empty-state">
                <i class="fas fa-envelope"></i>
                <p>No messages yet.</p>
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Car Interest</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentMessages as $msg): ?>
                <tr>
                    <td><strong><?= sanitize($msg['name']) ?></strong></td>
                    <td><?= sanitize($msg['phone']) ?></td>
                    <td><?= sanitize($msg['car_interest'] ?: '-') ?></td>
                    <td style="color:var(--gray-400);font-size:0.82rem;"><?= timeAgo($msg['created_at']) ?></td>
                    <td>
                        <?php if (!$msg['is_read']): ?>
                            <span class="status-badge status-pending">New</span>
                        <?php else: ?>
                            <span class="status-badge status-approved">Read</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
