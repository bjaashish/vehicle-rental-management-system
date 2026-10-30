<?php
require_once 'config/db.php';
$pageTitle = 'My Bookings';
$base = '';
require_login();
$uid = current_user()['id'];

// ----- Cancel a booking (UPDATE) -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    verify_csrf();
    $bid = (int)($_POST['booking_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled'
                           WHERE id = ? AND user_id = ? AND status IN ('pending','approved') AND start_date > CURDATE()");
    $stmt->execute([$bid, $uid]);
    if ($stmt->rowCount()) flash('success', 'Booking #' . $bid . ' cancelled.');
    else flash('error', 'This booking cannot be cancelled.');
    redirect('my_bookings.php');
}

// ----- List with status filter (READ) -----
$status = $_GET['status'] ?? '';
$statuses = ['pending','approved','rejected','cancelled','completed'];
$sql = 'SELECT b.*, v.name, v.brand, v.type FROM bookings b JOIN vehicles v ON v.id = b.vehicle_id WHERE b.user_id = ?';
$params = [$uid];
if (in_array($status, $statuses, true)) { $sql .= ' AND b.status = ?'; $params[] = $status; }
$sql .= ' ORDER BY b.created_at DESC, b.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$spent = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE user_id = ? AND status IN ('approved','completed')");
$spent->execute([$uid]);

include 'includes/header.php';
?>
<div class="toolbar">
    <div>
        <h1>My Bookings</h1>
        <p class="muted">Total spent on confirmed rentals: <strong><?= money($spent->fetchColumn()) ?></strong></p>
    </div>
    <form method="get">
        <select name="status" class="auto-submit" aria-label="Filter by status">
            <option value="">All statuses</option>
            <?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
        </select>
    </form>
</div>

<?php if ($bookings): ?>
<div class="table-wrap">
    <table class="data">
        <thead>
            <tr><th>#</th><th>Vehicle</th><th>Dates</th><th>Days</th><th>Pickup</th><th>Amount</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php foreach ($bookings as $b): ?>
            <tr>
                <td><?= (int)$b['id'] ?></td>
                <td><?= vehicle_icon($b['type']) ?> <a href="vehicle.php?id=<?= (int)$b['vehicle_id'] ?>"><?= e($b['brand'] . ' ' . $b['name']) ?></a></td>
                <td><?= date('d M Y', strtotime($b['start_date'])) ?> → <?= date('d M Y', strtotime($b['end_date'])) ?></td>
                <td><?= (int)$b['total_days'] ?></td>
                <td><?= e($b['pickup_location']) ?></td>
                <td><?= money($b['total_amount']) ?></td>
                <td><?= status_badge($b['status']) ?></td>
                <td>
                    <?php if (in_array($b['status'], ['pending','approved']) && strtotime($b['start_date']) > strtotime('today')): ?>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                            <button class="btn btn-danger btn-sm" data-confirm="Cancel this booking?">Cancel</button>
                        </form>
                    <?php else: ?>—<?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <div class="empty"><div class="icon">📅</div><h3>No bookings found</h3><p><a href="vehicles.php" class="btn btn-primary mt">Browse vehicles</a></p></div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
