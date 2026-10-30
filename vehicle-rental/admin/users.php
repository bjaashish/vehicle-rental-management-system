<?php
require_once '../config/db.php';
$pageTitle = 'Manage Users';
$base = '../';
require_admin();
$me = current_user()['id'];

// ----- UPDATE role / DELETE -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($id === $me) {
        flash('error', 'You cannot modify your own admin account here.');
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        flash('success', 'User and their bookings deleted.');
    } elseif ($action === 'toggle_role') {
        $pdo->prepare("UPDATE users SET role = IF(role='admin','user','admin') WHERE id = ?")->execute([$id]);
        flash('success', 'User role updated.');
    }
    redirect('users.php');
}

// ----- READ with search -----
$q = trim($_GET['q'] ?? '');
$role = $_GET['role'] ?? '';
$where = []; $params = [];
if ($q !== '') { $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)'; $l = "%$q%"; array_push($params, $l, $l, $l); }
if (in_array($role, ['user','admin'], true)) { $where[] = 'u.role = ?'; $params[] = $role; }
$sql = "SELECT u.*, COUNT(b.id) AS bookings, COALESCE(SUM(CASE WHEN b.status IN ('approved','completed') THEN b.total_amount END),0) AS spent
        FROM users u LEFT JOIN bookings b ON b.user_id = u.id"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' GROUP BY u.id ORDER BY u.created_at DESC, u.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

include '../includes/header.php';
?>
<h1>Manage Users</h1>
<div class="toolbar">
    <form method="get">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Name, email or phone">
        <select name="role" class="auto-submit">
            <option value="">All roles</option>
            <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>Customers</option>
            <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admins</option>
        </select>
        <button class="btn btn-primary">Search</button>
        <a href="users.php" class="btn btn-secondary">Reset</a>
    </form>
    <input type="search" data-table-search="#usersTable" placeholder="Quick filter this list…">
</div>

<div class="table-wrap">
    <table class="data" id="usersTable">
        <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Bookings</th><th>Spent</th><th>Joined</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= (int)$u['id'] ?></td>
                <td><?= e($u['name']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= e($u['phone']) ?></td>
                <td><?= status_badge($u['role']) ?></td>
                <td><?= (int)$u['bookings'] ?></td>
                <td><?= money($u['spent']) ?></td>
                <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td class="actions">
                    <?php if ((int)$u['id'] !== $me): ?>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="action" value="toggle_role">
                            <button class="btn btn-warning btn-sm" data-confirm="Change role of <?= e($u['name']) ?>?"><?= $u['role'] === 'admin' ? 'Make user' : 'Make admin' ?></button></form>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="action" value="delete">
                            <button class="btn btn-danger btn-sm" data-confirm="Delete <?= e($u['name']) ?> and all their bookings?">Delete</button></form>
                    <?php else: ?><span class="muted">(you)</span><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$users): ?><tr><td colspan="9" class="text-center muted">No users found.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
