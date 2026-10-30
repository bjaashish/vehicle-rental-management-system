<?php
require_once '../config/db.php';
$pageTitle = 'Manage Bookings';
$base = '../';
require_admin();

// ----- UPDATE status / DELETE -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $map = ['approve' => 'approved', 'reject' => 'rejected', 'complete' => 'completed'];

    $b = $pdo->prepare('SELECT * FROM bookings WHERE id = ?');
    $b->execute([$id]);
    $booking = $b->fetch();

    if (!$booking) {
        flash('error', 'Booking not found.');
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM bookings WHERE id = ?')->execute([$id]);
        flash('success', "Booking #$id deleted.");
    } elseif (isset($map[$action])) {
        if ($action === 'approve') {
            // make sure no other APPROVED booking overlaps
            $chk = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE vehicle_id = ? AND id != ? AND status = 'approved'
                                  AND start_date <= ? AND end_date >= ?");
            $chk->execute([$booking['vehicle_id'], $id, $booking['end_date'], $booking['start_date']]);
            if ($chk->fetchColumn() > 0) {
                flash('error', "Cannot approve #$id: it overlaps another approved booking for this vehicle.");
                redirect('bookings.php?' . http_build_query($_GET));
            }
        }
        $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute([$map[$action], $id]);
        flash('success', "Booking #$id marked as {$map[$action]}.");
    }
    redirect('bookings.php?' . http_build_query($_GET));
}

// ----- READ with search & filters -----
$statuses = ['pending','approved','rejected','cancelled','completed'];
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$where = []; $params = [];
if ($q !== '') {
    $where[] = '(u.name LIKE ? OR u.email LIKE ? OR v.name LIKE ? OR v.brand LIKE ? OR v.reg_number LIKE ? OR b.id = ?)';
    $l = "%$q%"; array_push($params, $l, $l, $l, $l, $l, (int)$q);
}
if (in_array($status, $statuses, true)) { $where[] = 'b.status = ?'; $params[] = $status; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $where[] = 'b.end_date >= ?'; $params[] = $from; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   { $where[] = 'b.start_date <= ?'; $params[] = $to; }

$sql = 'SELECT b.*, u.name AS customer, u.email, u.phone, v.brand, v.name AS vname, v.reg_number
        FROM bookings b JOIN users u ON u.id = b.user_id JOIN vehicles v ON v.id = b.vehicle_id'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . " ORDER BY FIELD(b.status,'pending','approved','completed','rejected','cancelled'), b.start_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
$sum = array_sum(array_map(fn($b) => in_array($b['status'], ['approved','completed']) ? $b['total_amount'] : 0, $bookings));

include '../includes/header.php';
?>
<h1>Manage Bookings</h1>

<div class="toolbar">
    <form method="get">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Customer, vehicle, reg. no. or #ID">
        <select name="status" class="auto-submit">
            <option value="">All statuses</option>
            <?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
        </select>
        <input type="date" name="from" value="<?= e($from) ?>" title="From date">
        <input type="date" name="to" value="<?= e($to) ?>" title="To date">
        <button class="btn btn-primary">Filter</button>
        <a href="bookings.php" class="btn btn-secondary">Reset</a>
    </form>
    <span class="muted"><?= count($bookings) ?> booking(s) · Confirmed value: <strong><?= money($sum) ?></strong></span>
</div>

<div class="table-wrap">
    <table class="data">
        <thead><tr><th>#</th><th>Customer</th><th>Vehicle</th><th>Dates</th><th>Pickup</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($bookings as $b): ?>
            <tr>
                <td><?= (int)$b['id'] ?></td>
                <td><?= e($b['customer']) ?><br><small class="muted"><?= e($b['email']) ?> · <?= e($b['phone']) ?></small></td>
                <td><?= e($b['brand'] . ' ' . $b['vname']) ?><br><small class="muted"><?= e($b['reg_number']) ?></small></td>
                <td><?= date('d M Y', strtotime($b['start_date'])) ?><br><small class="muted">to <?= date('d M Y', strtotime($b['end_date'])) ?> (<?= (int)$b['total_days'] ?>d)</small></td>
                <td><?= e($b['pickup_location']) ?></td>
                <td><?= money($b['total_amount']) ?></td>
                <td><?= status_badge($b['status']) ?></td>
                <td class="actions">
                    <?php
                    $btns = [];
                    if ($b['status'] === 'pending') { $btns = [['approve','success','Approve'], ['reject','warning','Reject']]; }
                    elseif ($b['status'] === 'approved') { $btns = [['complete','success','Complete'], ['reject','warning','Reject']]; }
                    $btns[] = ['delete', 'danger', 'Delete'];
                    foreach ($btns as [$act, $cls, $label]): ?>
                        <form method="post">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="action" value="<?= $act ?>">
                            <button class="btn btn-<?= $cls ?> btn-sm" <?= $act === 'delete' ? 'data-confirm="Permanently delete booking #' . (int)$b['id'] . '?"' : '' ?>><?= $label ?></button>
                        </form>
                    <?php endforeach; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$bookings): ?><tr><td colspan="8" class="text-center muted">No bookings match your filters.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
