<?php
require_once 'config/db.php';
$base = '';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM vehicles WHERE id = ?');
$stmt->execute([$id]);
$v = $stmt->fetch();
if (!$v) {
    flash('error', 'Vehicle not found.');
    redirect('vehicles.php');
}

$errors = [];
$old = ['start_date' => '', 'end_date' => '', 'pickup_location' => ''];

// ----- Handle booking (CREATE) -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    verify_csrf();

    $old['start_date'] = $_POST['start_date'] ?? '';
    $old['end_date'] = $_POST['end_date'] ?? '';
    $old['pickup_location'] = trim($_POST['pickup_location'] ?? '');

    $start = DateTime::createFromFormat('Y-m-d', $old['start_date']);
    $end   = DateTime::createFromFormat('Y-m-d', $old['end_date']);
    $today = new DateTime('today');

    if (is_admin()) $errors[] = 'Admins cannot make bookings. Log in as a customer.';
    if ($v['status'] !== 'available') $errors[] = 'This vehicle is currently under maintenance.';
    if (!$start || !$end) {
        $errors[] = 'Please select valid start and end dates.';
    } else {
        $start->setTime(0, 0); $end->setTime(0, 0);
        if ($start < $today) $errors[] = 'Start date cannot be in the past.';
        if ($end < $start) $errors[] = 'End date must be on or after the start date.';
        if ($start->diff($end)->days > 60) $errors[] = 'Maximum rental period is 60 days.';
    }
    if (strlen($old['pickup_location']) < 3 || strlen($old['pickup_location']) > 150) {
        $errors[] = 'Pickup location must be 3–150 characters.';
    }

    if (!$errors && !is_vehicle_available($pdo, $v['id'], $old['start_date'], $old['end_date'])) {
        $errors[] = 'Sorry, this vehicle is already booked for some of those dates. Please choose different dates.';
    }

    if (!$errors) {
        $days = $start->diff($end)->days + 1;
        $amount = $days * $v['price_per_day'];   // price is always calculated on the server
        $ins = $pdo->prepare('INSERT INTO bookings (user_id, vehicle_id, start_date, end_date, total_days, total_amount, pickup_location)
                              VALUES (?, ?, ?, ?, ?, ?, ?)');
        $ins->execute([current_user()['id'], $v['id'], $old['start_date'], $old['end_date'], $days, $amount, $old['pickup_location']]);
        flash('success', "Booking request submitted for {$days} day(s). Total: " . money($amount) . '. Awaiting admin approval.');
        redirect('my_bookings.php');
    }
}

// Upcoming booked periods (to show the user)
$booked = $pdo->prepare("SELECT start_date, end_date FROM bookings
                         WHERE vehicle_id = ? AND status IN ('pending','approved') AND end_date >= CURDATE()
                         ORDER BY start_date");
$booked->execute([$v['id']]);
$bookedRanges = $booked->fetchAll();

$pageTitle = $v['brand'] . ' ' . $v['name'];
include 'includes/header.php';
?>
<p><a href="vehicles.php">← Back to vehicles</a></p>

<div class="details">
    <div>
        <div class="details-img"><?= vehicle_image($v) ?></div>
        <h1 class="mt"><?= e($v['brand'] . ' ' . $v['name']) ?> <?= status_badge($v['status']) ?></h1>
        <p class="muted"><?= nl2br(e($v['description'])) ?></p>
        <table class="spec-table">
            <tr><td>Type</td><td><?= vehicle_icon($v['type']) ?> <?= e($v['type']) ?></td></tr>
            <tr><td>Fuel</td><td><?= e($v['fuel']) ?></td></tr>
            <tr><td>Transmission</td><td><?= e($v['transmission']) ?></td></tr>
            <tr><td>Seating</td><td><?= (int)$v['seats'] ?> persons</td></tr>
            <tr><td>Registration No.</td><td><?= e($v['reg_number']) ?></td></tr>
            <tr><td>Rate</td><td><strong><?= money($v['price_per_day']) ?></strong> per day</td></tr>
        </table>
    </div>

    <aside class="booking-box">
        <h2>Book this vehicle</h2>
        <div class="price"><?= money($v['price_per_day']) ?> <small>/day</small></div>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-error mt"><?= e($err) ?></div>
        <?php endforeach; ?>

        <?php if ($v['status'] !== 'available'): ?>
            <div class="alert alert-info mt">This vehicle is under maintenance and cannot be booked right now.</div>
        <?php elseif (!is_logged_in()): ?>
            <p class="mt">Please <a href="login.php">log in</a> or <a href="register.php">create an account</a> to book.</p>
        <?php else: ?>
            <form method="post" id="bookingForm" class="validate mt" data-rate="<?= e($v['price_per_day']) ?>" novalidate>
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="start_date">Pickup date <span class="req">*</span></label>
                    <input type="date" id="start_date" name="start_date" min="<?= date('Y-m-d') ?>" value="<?= e($old['start_date']) ?>" data-rules="required|future">
                </div>
                <div class="form-group">
                    <label for="end_date">Return date <span class="req">*</span></label>
                    <input type="date" id="end_date" name="end_date" min="<?= date('Y-m-d') ?>" value="<?= e($old['end_date']) ?>" data-rules="required|future|after:start_date">
                </div>
                <div class="form-group">
                    <label for="pickup_location">Pickup location <span class="req">*</span></label>
                    <input type="text" id="pickup_location" name="pickup_location" maxlength="150" value="<?= e($old['pickup_location']) ?>" placeholder="e.g. Connaught Place, Delhi" data-rules="required|min:3|max:150">
                </div>
                <div class="price-summary">
                    <div><span>Rate</span><span><?= money($v['price_per_day']) ?></span></div>
                    <div><span>Days</span><span id="sumDays">0</span></div>
                    <div class="total"><span>Total</span><span id="sumTotal">₹0.00</span></div>
                </div>
                <button class="btn btn-primary btn-block">Confirm Booking</button>
            </form>
        <?php endif; ?>

        <?php if ($bookedRanges): ?>
            <h4 class="mt">Already booked:</h4>
            <ul class="muted" style="padding-left:18px;font-size:.9rem">
                <?php foreach ($bookedRanges as $r): ?>
                    <li><?= date('d M Y', strtotime($r['start_date'])) ?> → <?= date('d M Y', strtotime($r['end_date'])) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </aside>
</div>

<?php include 'includes/footer.php'; ?>
