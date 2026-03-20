<?php
$pageTitle = 'Messages';
require_once 'header.php';

$pdo = getDB();

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'read') {
        $pdo->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?")->execute([$id]);
    } elseif ($_GET['action'] === 'delete') {
        $pdo->prepare("DELETE FROM contact_messages WHERE id=?")->execute([$id]);
    }
    header('Location: messages.php');
    exit;
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $pdo->exec("UPDATE contact_messages SET is_read=1");
    header('Location: messages.php');
    exit;
}

$messages = $pdo->query("SELECT * FROM contact_messages ORDER BY is_read ASC, created_at DESC")->fetchAll();
?>

<div style="display:flex;justify-content:space-between;margin-bottom:16px;">
    <p style="color:var(--gray-600);"><?= count($messages) ?> total messages, <?= $unreadCount ?> unread</p>
    <?php if ($unreadCount > 0): ?>
        <a href="messages.php?mark_all_read=1" class="btn btn-outline btn-sm"><i class="fas fa-check-double"></i> Mark All Read</a>
    <?php endif; ?>
</div>

<?php if (empty($messages)): ?>
<div class="card">
    <div class="empty-state" style="padding:60px;">
        <i class="fas fa-envelope-open"></i>
        <p>No messages yet.</p>
    </div>
</div>
<?php else: ?>

<?php foreach ($messages as $msg): ?>
<div class="card" style="margin-bottom:12px;<?= !$msg['is_read'] ? 'border-left:3px solid var(--amber);' : '' ?>">
    <div class="card-body" style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;">
        <div style="flex:1;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                <strong style="font-size:0.95rem;color:var(--navy);"><?= sanitize($msg['name']) ?></strong>
                <?php if (!$msg['is_read']): ?>
                    <span class="status-badge status-pending" style="font-size:0.7rem;">New</span>
                <?php endif; ?>
                <span style="font-size:0.78rem;color:var(--gray-400);margin-left:auto;"><?= timeAgo($msg['created_at']) ?></span>
            </div>
            <div style="display:flex;gap:16px;font-size:0.82rem;color:var(--gray-600);margin-bottom:8px;">
                <?php if ($msg['phone']): ?><span><i class="fas fa-phone"></i> <?= sanitize($msg['phone']) ?></span><?php endif; ?>
                <?php if ($msg['email']): ?><span><i class="fas fa-envelope"></i> <?= sanitize($msg['email']) ?></span><?php endif; ?>
                <?php if ($msg['car_interest']): ?><span><i class="fas fa-car"></i> <?= sanitize($msg['car_interest']) ?></span><?php endif; ?>
                <?php if ($msg['budget']): ?><span><i class="fas fa-money-bill"></i> <?= sanitize($msg['budget']) ?></span><?php endif; ?>
            </div>
            <?php if ($msg['message']): ?>
                <p style="font-size:0.88rem;color:var(--gray-800);background:var(--gray-50);padding:10px 14px;border-radius:6px;"><?= nl2br(sanitize($msg['message'])) ?></p>
            <?php endif; ?>
        </div>
        <div class="action-btns" style="flex-shrink:0;">
            <?php if (!$msg['is_read']): ?>
                <a href="messages.php?action=read&id=<?= $msg['id'] ?>" class="btn btn-outline btn-sm" title="Mark Read"><i class="fas fa-check"></i></a>
            <?php endif; ?>
            <?php if ($msg['phone']): ?>
                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $msg['phone']) ?>" target="_blank" class="btn btn-success btn-sm" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
            <?php endif; ?>
            <a href="messages.php?action=delete&id=<?= $msg['id'] ?>" class="btn btn-danger btn-sm confirm-delete" title="Delete"><i class="fas fa-trash"></i></a>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<?php require_once 'footer.php'; ?>
