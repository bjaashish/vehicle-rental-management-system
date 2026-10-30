<?php
require_once 'config/db.php';
$pageTitle = 'Browse Vehicles';
$base = '';

// ----- Read filters -----
$q     = trim($_GET['q'] ?? '');
$type  = $_GET['type'] ?? '';
$fuel  = $_GET['fuel'] ?? '';
$trans = $_GET['transmission'] ?? '';
$max   = $_GET['max_price'] ?? '';
$sort  = $_GET['sort'] ?? 'newest';
$page  = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;

$types = ['Car','SUV','Bike','Scooter','Van','Truck'];
$fuels = ['Petrol','Diesel','Electric','CNG','Hybrid'];
$sorts = [
    'newest'     => 'id DESC',
    'price_asc'  => 'price_per_day ASC',
    'price_desc' => 'price_per_day DESC',
    'name'       => 'brand ASC, name ASC',
];

// ----- Build query safely with prepared params -----
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(name LIKE ? OR brand LIKE ? OR description LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if (in_array($type, $types, true))  { $where[] = 'type = ?';  $params[] = $type; }
if (in_array($fuel, $fuels, true))  { $where[] = 'fuel = ?';  $params[] = $fuel; }
if (in_array($trans, ['Manual','Automatic'], true)) { $where[] = 'transmission = ?'; $params[] = $trans; }
if (is_numeric($max) && $max > 0)   { $where[] = 'price_per_day <= ?'; $params[] = (float)$max; }

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$orderSql = $sorts[$sort] ?? $sorts['newest'];

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM vehicles $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT * FROM vehicles $whereSql ORDER BY status = 'available' DESC, $orderSql LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

function page_link($p) {
    $qs = $_GET; $qs['page'] = $p;
    return 'vehicles.php?' . http_build_query($qs);
}

include 'includes/header.php';
?>
<h1>Browse Vehicles</h1>
<p class="muted">Search and filter our fleet to find your perfect ride.</p>

<form class="filter-bar" method="get" action="vehicles.php">
    <div class="search-field">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Brand, model or keyword">
    </div>
    <div>
        <label for="type">Type</label>
        <select id="type" name="type" class="auto-submit">
            <option value="">All</option>
            <?php foreach ($types as $t): ?><option <?= $type === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="fuel">Fuel</label>
        <select id="fuel" name="fuel" class="auto-submit">
            <option value="">All</option>
            <?php foreach ($fuels as $f): ?><option <?= $fuel === $f ? 'selected' : '' ?>><?= $f ?></option><?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="transmission">Gearbox</label>
        <select id="transmission" name="transmission" class="auto-submit">
            <option value="">All</option>
            <?php foreach (['Manual','Automatic'] as $t): ?><option <?= $trans === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="max_price">Max ₹/day</label>
        <input type="number" id="max_price" name="max_price" min="0" step="100" value="<?= e($max) ?>" placeholder="Any">
    </div>
    <div>
        <label for="sort">Sort by</label>
        <select id="sort" name="sort" class="auto-submit">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low → High</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High → Low</option>
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A–Z</option>
        </select>
    </div>
    <div class="actions">
        <button class="btn btn-primary">Apply</button>
        <a href="vehicles.php" class="btn btn-secondary">Reset</a>
    </div>
</form>

<p class="result-count"><?= $total ?> vehicle<?= $total === 1 ? '' : 's' ?> found<?= $q !== '' ? ' for “' . e($q) . '”' : '' ?></p>

<?php if ($vehicles): ?>
    <div class="grid">
        <?php foreach ($vehicles as $v): include 'includes/vehicle_card.php'; endforeach; ?>
    </div>
    <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Pagination">
            <?php if ($page > 1): ?><a href="<?= e(page_link($page - 1)) ?>">« Prev</a><?php endif; ?>
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="<?= e(page_link($i)) ?>" class="<?= $i === $page ? 'current' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $pages): ?><a href="<?= e(page_link($page + 1)) ?>">Next »</a><?php endif; ?>
        </nav>
    <?php endif; ?>
<?php else: ?>
    <div class="empty"><div class="icon">🔍</div><h3>No vehicles match your filters</h3><p>Try changing or resetting the filters.</p></div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
