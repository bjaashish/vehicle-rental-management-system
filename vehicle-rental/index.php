<?php
require_once 'config/db.php';
$pageTitle = 'Home';
$base = '';

$featured = $pdo->query("SELECT * FROM vehicles WHERE status = 'available' ORDER BY created_at DESC, id DESC LIMIT 6")->fetchAll();
$counts = $pdo->query("SELECT
        (SELECT COUNT(*) FROM vehicles) AS vehicles,
        (SELECT COUNT(*) FROM users WHERE role='user') AS customers,
        (SELECT COUNT(*) FROM bookings) AS bookings")->fetch();

include 'includes/header.php';
?>
<section class="hero">
    <div>
        <h1>Rent the right ride, right now.</h1>
        <p>Cars, SUVs, bikes, scooters, vans and trucks — transparent daily pricing and instant online booking.</p>
        <form class="quick-search" action="vehicles.php" method="get">
            <input type="text" name="q" placeholder="Search brand or model…" aria-label="Search">
            <select name="type" aria-label="Vehicle type">
                <option value="">All Types</option>
                <?php foreach (['Car','SUV','Bike','Scooter','Van','Truck'] as $t): ?>
                    <option><?= $t ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary">Search</button>
        </form>
    </div>
    <div class="hero-art">🚙</div>
</section>

<div class="stats">
    <div class="stat"><div class="num"><?= (int)$counts['vehicles'] ?>+</div><div class="label">Vehicles in fleet</div></div>
    <div class="stat green"><div class="num"><?= (int)$counts['customers'] ?>+</div><div class="label">Happy customers</div></div>
    <div class="stat amber"><div class="num"><?= (int)$counts['bookings'] ?>+</div><div class="label">Bookings made</div></div>
    <div class="stat cyan"><div class="num">24/7</div><div class="label">Roadside support</div></div>
</div>

<div class="section-title">
    <h2>Featured Vehicles</h2>
    <a href="vehicles.php" class="btn btn-outline-dark btn-sm">View all →</a>
</div>

<div class="grid">
<?php foreach ($featured as $v): include 'includes/vehicle_card.php'; endforeach; ?>
</div>

<section class="features">
    <div class="feature"><div class="icon">⚡</div><h3>Instant Booking</h3><p class="muted">Pick dates, see the total, book in under a minute.</p></div>
    <div class="feature"><div class="icon">💰</div><h3>Transparent Pricing</h3><p class="muted">Simple per-day rates. No hidden charges.</p></div>
    <div class="feature"><div class="icon">🛡️</div><h3>Verified Fleet</h3><p class="muted">Every vehicle is serviced and inspected regularly.</p></div>
    <div class="feature"><div class="icon">📱</div><h3>Manage Anywhere</h3><p class="muted">Track and cancel bookings from any device.</p></div>
</section>

<?php include 'includes/footer.php'; ?>
