<?php
require_once '../config/db.php';
$pageTitle = 'Manage Vehicles';
$base = '../';
require_admin();

// ----- DELETE / toggle status -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $active = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE vehicle_id = ? AND status IN ('pending','approved') AND end_date >= CURDATE()");
        $active->execute([$id]);
        if ($active->fetchColumn() > 0) {
            flash('error', 'Cannot delete: this vehicle has active/upcoming bookings. Set it to maintenance instead.');
        } else {
            $img = $pdo->prepare('SELECT image FROM vehicles WHERE id = ?');
            $img->execute([$id]);
            $file = $img->fetchColumn();
            $pdo->prepare('DELETE FROM vehicles WHERE id = ?')->execute([$id]);
            if ($file && is_file(__DIR__ . '/../uploads/' . $file)) unlink(__DIR__ . '/../uploads/' . $file);
            flash('success', 'Vehicle deleted.');
        }
    } elseif ($action === 'toggle') {
        $pdo->prepare("UPDATE vehicles SET status = IF(status='available','maintenance','available') WHERE id = ?")->execute([$id]);
        flash('success', 'Vehicle status updated.');
    }
    redirect('vehicles.php?' . http_build_query(array_intersect_key($_GET, array_flip(['q','type','status']))));
}

// ----- READ with search & filter -----
$q = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';
$where = []; $params = [];
if ($q !== '') { $where[] = '(name LIKE ? OR brand LIKE ? OR reg_number LIKE ?)'; $l = "%$q%"; array_push($params, $l, $l, $l); }
if (in_array($type, ['Car','SUV','Bike','Scooter','Van','Truck'], true)) { $where[] = 'type = ?'; $params[] = $type; }
if (in_array($status, ['available','maintenance'], true)) { $where[] = 'status = ?'; $params[] = $status; }
$sql = 'SELECT v.*, (SELECT COUNT(*) FROM bookings b WHERE b.vehicle_id = v.id) AS total_bookings FROM vehicles v'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY v.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

include '../includes/header.php';
?>
<div class="toolbar">
    <h1>Manage Vehicles</h1>
    <a href="vehicle_form.php" class="btn btn-primary">+ Add Vehicle</a>
</div>

<div class="toolbar">
    <form method="get">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Name, brand or reg. no.">
        <select name="type" class="auto-submit">
            <option value="">All types</option>
            <?php foreach (['Car','SUV','Bike','Scooter','Van','Truck'] as $t): ?><option <?= $type === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
        </select>
        <select name="status" class="auto-submit">
            <option value="">All statuses</option>
            <option value="available" <?= $status === 'available' ? 'selected' : '' ?>>Available</option>
            <option value="maintenance" <?= $status === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
        </select>
        <button class="btn btn-primary">Search</button>
        <a href="vehicles.php" class="btn btn-secondary">Reset</a>
    </form>
    <span class="muted"><?= count($vehicles) ?> result(s)</span>
</div>

<div class="table-wrap">
    <table class="data">
        <thead><tr><th>ID</th><th>Vehicle</th><th>Type</th><th>Reg. No.</th><th>Fuel / Gear</th><th>Rate/day</th><th>Bookings</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($vehicles as $v): ?>
            <tr>
                <td><?= (int)$v['id'] ?></td>
                <td><strong><?= e($v['brand'] . ' ' . $v['name']) ?></strong><br><small class="muted"><?= (int)$v['seats'] ?> seats</small></td>
                <td><?= vehicle_icon($v['type']) ?> <?= e($v['type']) ?></td>
                <td><?= e($v['reg_number']) ?></td>
                <td><?= e($v['fuel']) ?> / <?= e($v['transmission']) ?></td>
                <td><?= money($v['price_per_day']) ?></td>
                <td><?= (int)$v['total_bookings'] ?></td>
                <td><?= status_badge($v['status']) ?></td>
                <td class="actions">
                    <a href="vehicle_form.php?id=<?= (int)$v['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                    <form method="post">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><input type="hidden" name="action" value="toggle">
                        <button class="btn btn-warning btn-sm"><?= $v['status'] === 'available' ? 'Maintenance' : 'Make available' ?></button>
                    </form>
                    <form method="post">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><input type="hidden" name="action" value="delete">
                        <button class="btn btn-danger btn-sm" data-confirm="Delete <?= e($v['brand'] . ' ' . $v['name']) ?>? Its booking history will also be removed.">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$vehicles): ?><tr><td colspan="9" class="text-center muted">No vehicles found.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
