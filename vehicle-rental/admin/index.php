<?php
require_once '../config/db.php';
$pageTitle = 'Admin Dashboard';
$base = '../';
require_admin();

// Mark message read / delete message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $mid = (int)($_POST['message_id'] ?? 0);
    if (($_POST['action'] ?? '') === 'read_msg') {
        $pdo->prepare('UPDATE messages SET is_read = 1 WHERE id = ?')->execute([$mid]);
    } elseif (($_POST['action'] ?? '') === 'delete_msg') {
        $pdo->prepare('DELETE FROM messages WHERE id = ?')->execute([$mid]);
        flash('success', 'Message deleted.');
    }
    redirect('index.php');
}

$s = $pdo->query("SELECT
    (SELECT COUNT(*) FROM vehicles) AS vehicles,
    (SELECT COUNT(*) FROM vehicles WHERE status='available') AS available,
    (SELECT COUNT(*) FROM users WHERE role='user') AS users,
    (SELECT COUNT(*) FROM bookings) AS bookings,
    (SELECT COUNT(*) FROM bookings WHERE status='pending') AS pending,
    (SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE status IN ('approved','completed')) AS revenue
")->fetch();

$recent = $pdo->query("SELECT b.*, u.name AS customer, v.brand, v.name AS vname
                       FROM bookings b JOIN users u ON u.id=b.user_id JOIN vehicles v ON v.id=b.vehicle_id
                       ORDER BY b.created_at DESC, b.id DESC LIMIT 6")->fetchAll();

$byType = $pdo->query("SELECT v.type, COUNT(b.id) AS cnt FROM vehicles v
                       LEFT JOIN bookings b ON b.vehicle_id = v.id GROUP BY v.type ORDER BY cnt DESC")->fetchAll();
$maxType = max(1, max(array_column($byType, 'cnt') ?: [1]));

$messages = $pdo->query('SELECT * FROM messages ORDER BY created_at DESC LIMIT 5')->fetchAll();

include '../includes/header.php';
?>
<div class="toolbar">
    <h1>Admin Dashboard</h1>
    <div class="actions">
        <a href="vehicle_form.php" class="btn btn-primary">+ Add Vehicle</a>
        <a href="bookings.php?status=pending" class="btn btn-accent">Review Pending (<?= (int)$s['pending'] ?>)</a>
    </div>
</div>

<div class="stats">
    <div class="stat"><div class="num"><?= (int)$s['vehicles'] ?></div><div class="label">Total vehicles (<?= (int)$s['available'] ?> available)</div></div>
    <div class="stat cyan"><div class="num"><?= (int)$s['users'] ?></div><div class="label">Registered customers</div></div>
    <div class="stat amber"><div class="num"><?= (int)$s['bookings'] ?></div><div class="label">Total bookings (<?= (int)$s['pending'] ?> pending)</div></div>
    <div class="stat green"><div class="num"><?= money($s['revenue']) ?></div><div class="label">Revenue (approved + completed)</div></div>
</div>

<div class="details">
    <div>
        <div class="section-title"><h2>Recent Bookings</h2><a href="bookings.php">View all →</a></div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>#</th><th>Customer</th><th>Vehicle</th><th>Dates</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $b): ?>
                    <tr>
                        <td><?= (int)$b['id'] ?></td>
                        <td><?= e($b['customer']) ?></td>
                        <td><?= e($b['brand'] . ' ' . $b['vname']) ?></td>
                        <td><?= date('d M', strtotime($b['start_date'])) ?> – <?= date('d M Y', strtotime($b['end_date'])) ?></td>
                        <td><?= money($b['total_amount']) ?></td>
                        <td><?= status_badge($b['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recent): ?><tr><td colspan="6" class="text-center muted">No bookings yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div>
        <div class="info-box">
            <h3>Bookings by vehicle type</h3>
            <?php foreach ($byType as $t): ?>
                <div style="margin:10px 0">
                    <div style="display:flex;justify-content:space-between;font-size:.9rem"><span><?= vehicle_icon($t['type']) ?> <?= e($t['type']) ?></span><strong><?= (int)$t['cnt'] ?></strong></div>
                    <div style="background:var(--bg);border-radius:6px;height:10px"><div style="width:<?= round($t['cnt'] / $maxType * 100) ?>%;background:var(--primary);height:10px;border-radius:6px"></div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="section-title mt"><h2>Customer Messages</h2></div>
<?php if ($messages): ?>
<div class="table-wrap">
    <table class="data">
        <thead><tr><th>From</th><th>Subject</th><th>Message</th><th>Received</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($messages as $m): ?>
            <tr style="<?= $m['is_read'] ? '' : 'font-weight:600' ?>">
                <td><?= e($m['name']) ?><br><small class="muted"><?= e($m['email']) ?></small></td>
                <td><?= e($m['subject']) ?></td>
                <td style="max-width:320px"><?= e($m['message']) ?></td>
                <td><?= date('d M Y', strtotime($m['created_at'])) ?></td>
                <td class="actions">
                    <?php if (!$m['is_read']): ?>
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read_msg"><input type="hidden" name="message_id" value="<?= (int)$m['id'] ?>"><button class="btn btn-success btn-sm">Mark read</button></form>
                    <?php endif; ?>
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete_msg"><input type="hidden" name="message_id" value="<?= (int)$m['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Delete this message?">Delete</button></form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?><div class="empty">No messages.</div><?php endif; ?>

<?php include '../includes/footer.php'; ?>
