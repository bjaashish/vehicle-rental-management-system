<?php
require_once '../config/db.php';
$base = '../';
require_admin();

$types = ['Car','SUV','Bike','Scooter','Van','Truck'];
$fuels = ['Petrol','Diesel','Electric','CNG','Hybrid'];
$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

$v = ['name' => '', 'brand' => '', 'type' => 'Car', 'fuel' => 'Petrol', 'transmission' => 'Manual', 'seats' => 5,
      'reg_number' => '', 'price_per_day' => '', 'description' => '', 'status' => 'available', 'image' => null];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM vehicles WHERE id = ?');
    $stmt->execute([$id]);
    $v = $stmt->fetch();
    if (!$v) { flash('error', 'Vehicle not found.'); redirect('vehicles.php'); }
}
$errors = [];

// ----- CREATE / UPDATE -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach (['name','brand','type','fuel','transmission','seats','reg_number','price_per_day','description','status'] as $f) {
        $v[$f] = trim($_POST[$f] ?? '');
    }
    $v['reg_number'] = strtoupper(preg_replace('/\s+/', '', $v['reg_number']));

    if (strlen($v['name']) < 2 || strlen($v['name']) > 100) $errors['name'] = 'Model name must be 2–100 characters.';
    if (strlen($v['brand']) < 2 || strlen($v['brand']) > 60) $errors['brand'] = 'Brand must be 2–60 characters.';
    if (!in_array($v['type'], $types, true)) $errors['type'] = 'Choose a valid type.';
    if (!in_array($v['fuel'], $fuels, true)) $errors['fuel'] = 'Choose a valid fuel.';
    if (!in_array($v['transmission'], ['Manual','Automatic'], true)) $errors['transmission'] = 'Choose a transmission.';
    if (!ctype_digit((string)$v['seats']) || $v['seats'] < 1 || $v['seats'] > 50) $errors['seats'] = 'Seats must be 1–50.';
    if (!preg_match('/^[A-Z]{2}\d{1,2}[A-Z]{0,3}\d{4}$/', $v['reg_number'])) $errors['reg_number'] = 'Format like DL01AB1234.';
    if (!is_numeric($v['price_per_day']) || $v['price_per_day'] <= 0 || $v['price_per_day'] > 100000) $errors['price_per_day'] = 'Enter a price between 1 and 100000.';
    if (!in_array($v['status'], ['available','maintenance'], true)) $errors['status'] = 'Choose a status.';

    if (!isset($errors['reg_number'])) {
        $dup = $pdo->prepare('SELECT id FROM vehicles WHERE reg_number = ? AND id != ?');
        $dup->execute([$v['reg_number'], $id]);
        if ($dup->fetch()) $errors['reg_number'] = 'This registration number already exists.';
    }

    $newImage = null;
    if (!$errors) {
        $newImage = handle_upload('image');
        if ($newImage === false) $errors['image'] = 'Upload a JPG, PNG, WEBP or GIF image under 2 MB.';
    }

    if (!$errors) {
        $data = [$v['name'], $v['brand'], $v['type'], $v['fuel'], $v['transmission'], (int)$v['seats'],
                 $v['reg_number'], (float)$v['price_per_day'], $v['description'], $v['status']];
        if ($isEdit) {
            $sql = 'UPDATE vehicles SET name=?, brand=?, type=?, fuel=?, transmission=?, seats=?, reg_number=?, price_per_day=?, description=?, status=?';
            if ($newImage) { $sql .= ', image=?'; $data[] = $newImage; }
            $sql .= ' WHERE id=?';
            $data[] = $id;
            $pdo->prepare($sql)->execute($data);
            if ($newImage && $v['image'] && is_file(__DIR__ . '/../uploads/' . $v['image'])) unlink(__DIR__ . '/../uploads/' . $v['image']);
            flash('success', 'Vehicle updated successfully.');
        } else {
            $data[] = $newImage;
            $pdo->prepare('INSERT INTO vehicles (name, brand, type, fuel, transmission, seats, reg_number, price_per_day, description, status, image)
                           VALUES (?,?,?,?,?,?,?,?,?,?,?)')->execute($data);
            flash('success', 'Vehicle added successfully.');
        }
        redirect('vehicles.php');
    }
}

$pageTitle = $isEdit ? 'Edit Vehicle' : 'Add Vehicle';
include '../includes/header.php';

function err($errors, $k) { return isset($errors[$k]) ? '<small class="error-msg">' . e($errors[$k]) . '</small>' : ''; }
?>
<p><a href="vehicles.php">← Back to vehicles</a></p>
<div class="form-card wide">
    <h1><?= $isEdit ? 'Edit Vehicle' : 'Add New Vehicle' ?></h1>
    <form method="post" enctype="multipart/form-data" class="validate mt" novalidate>
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label for="brand">Brand <span class="req">*</span></label>
                <input type="text" id="brand" name="brand" value="<?= e($v['brand']) ?>" placeholder="e.g. Honda" data-rules="required|min:2|max:60">
                <?= err($errors, 'brand') ?>
            </div>
            <div class="form-group">
                <label for="name">Model name <span class="req">*</span></label>
                <input type="text" id="name" name="name" value="<?= e($v['name']) ?>" placeholder="e.g. City" data-rules="required|min:2|max:100">
                <?= err($errors, 'name') ?>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="type">Type <span class="req">*</span></label>
                <select id="type" name="type" data-rules="required">
                    <?php foreach ($types as $t): ?><option <?= $v['type'] === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
                </select>
                <?= err($errors, 'type') ?>
            </div>
            <div class="form-group">
                <label for="fuel">Fuel <span class="req">*</span></label>
                <select id="fuel" name="fuel" data-rules="required">
                    <?php foreach ($fuels as $f): ?><option <?= $v['fuel'] === $f ? 'selected' : '' ?>><?= $f ?></option><?php endforeach; ?>
                </select>
                <?= err($errors, 'fuel') ?>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="transmission">Transmission <span class="req">*</span></label>
                <select id="transmission" name="transmission">
                    <?php foreach (['Manual','Automatic'] as $t): ?><option <?= $v['transmission'] === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
                </select>
                <?= err($errors, 'transmission') ?>
            </div>
            <div class="form-group">
                <label for="seats">Seats <span class="req">*</span></label>
                <input type="number" id="seats" name="seats" min="1" max="50" value="<?= e($v['seats']) ?>" data-rules="required|number|positive">
                <?= err($errors, 'seats') ?>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="reg_number">Registration number <span class="req">*</span></label>
                <input type="text" id="reg_number" name="reg_number" maxlength="20" value="<?= e($v['reg_number']) ?>" placeholder="DL01AB1234" data-rules="required|regno" style="text-transform:uppercase">
                <?= err($errors, 'reg_number') ?>
            </div>
            <div class="form-group">
                <label for="price_per_day">Price per day (₹) <span class="req">*</span></label>
                <input type="number" id="price_per_day" name="price_per_day" min="1" step="0.01" value="<?= e($v['price_per_day']) ?>" data-rules="required|number|positive">
                <?= err($errors, 'price_per_day') ?>
            </div>
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="available" <?= $v['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                <option value="maintenance" <?= $v['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
            </select>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" maxlength="1000" data-rules="max:1000"><?= e($v['description']) ?></textarea>
        </div>
        <div class="form-group">
            <label for="imageInput">Photo (optional, max 2 MB)</label>
            <input type="file" id="imageInput" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
            <?= err($errors, 'image') ?>
            <?php $hasImg = !empty($v['image']) && is_file(__DIR__ . '/../uploads/' . $v['image']); ?>
            <img id="imagePreview" src="<?= $hasImg ? '../uploads/' . e($v['image']) : '' ?>" alt="Preview" style="max-height:160px;margin-top:10px;border-radius:8px;<?= $hasImg ? '' : 'display:none' ?>">
        </div>
        <div class="actions">
            <button class="btn btn-primary"><?= $isEdit ? 'Update Vehicle' : 'Add Vehicle' ?></button>
            <a href="vehicles.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php include '../includes/footer.php'; ?>
