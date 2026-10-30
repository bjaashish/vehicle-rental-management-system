<?php
// ---------- Helpers ----------
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function money($amount) {
    return '₹' . number_format((float)$amount, 2);
}

// ---------- Flash messages ----------
function flash($type, $message) {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function show_flash() {
    if (empty($_SESSION['flash'])) return;
    foreach ($_SESSION['flash'] as $f) {
        echo '<div class="alert alert-' . e($f['type']) . '">' . e($f['msg']) . '<button class="alert-close" aria-label="Close">&times;</button></div>';
    }
    unset($_SESSION['flash']);
}

// ---------- CSRF protection ----------
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function verify_csrf() {
    if (!isset($_POST['csrf']) || !hash_equals(csrf_token(), $_POST['csrf'])) {
        http_response_code(403);
        die('Invalid request (CSRF token mismatch). Please go back and try again.');
    }
}

// ---------- Authentication ----------
function is_logged_in() {
    return isset($_SESSION['user']);
}

function is_admin() {
    return is_logged_in() && $_SESSION['user']['role'] === 'admin';
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login($base = '') {
    if (!is_logged_in()) {
        flash('error', 'Please log in to continue.');
        redirect($base . 'login.php');
    }
}

function require_admin($base = '../') {
    if (!is_admin()) {
        flash('error', 'Admin access only.');
        redirect($base . 'login.php');
    }
}

// ---------- Domain helpers ----------
function vehicle_icon($type) {
    $icons = ['Car' => '🚗', 'SUV' => '🚙', 'Bike' => '🏍️', 'Scooter' => '🛵', 'Van' => '🚐', 'Truck' => '🚚'];
    return $icons[$type] ?? '🚘';
}

function vehicle_image($v, $base = '') {
    if (!empty($v['image']) && file_exists(__DIR__ . '/../uploads/' . $v['image'])) {
        return '<img src="' . $base . 'uploads/' . e($v['image']) . '" alt="' . e($v['brand'] . ' ' . $v['name']) . '">';
    }
    return '<div class="img-placeholder type-' . strtolower(e($v['type'])) . '">' . vehicle_icon($v['type']) . '</div>';
}

function status_badge($status) {
    return '<span class="badge badge-' . e($status) . '">' . e(ucfirst($status)) . '</span>';
}

// Is the vehicle free between two dates? (ignores rejected/cancelled bookings)
function is_vehicle_available(PDO $pdo, $vehicleId, $start, $end, $excludeBookingId = 0) {
    $sql = "SELECT COUNT(*) FROM bookings
            WHERE vehicle_id = ? AND id != ?
              AND status IN ('pending','approved')
              AND start_date <= ? AND end_date >= ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$vehicleId, $excludeBookingId, $end, $start]);
    return (int)$stmt->fetchColumn() === 0;
}

// Handles an optional image upload; returns new filename, null if none, or false on error
function handle_upload($field) {
    if (empty($_FILES[$field]['name'])) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) return false;
    if ($f['size'] > 2 * 1024 * 1024) return false;
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime = mime_content_type($f['tmp_name']);
    if (!isset($allowed[$mime])) return false;
    $name = 'v_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($f['tmp_name'], __DIR__ . '/../uploads/' . $name)) return false;
    return $name;
}
