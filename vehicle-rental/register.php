<?php
require_once 'config/db.php';
$pageTitle = 'Create Account';
$base = '';
if (is_logged_in()) redirect('index.php');

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old['name']  = trim($_POST['name'] ?? '');
    $old['email'] = strtolower(trim($_POST['email'] ?? ''));
    $old['phone'] = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // ----- Server-side validation -----
    if (!preg_match("/^[A-Za-z][A-Za-z .'-]{1,99}$/", $old['name'])) $errors['name'] = 'Enter a valid name (letters only, 2–100 chars).';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    if (!preg_match('/^[6-9]\d{9}$/', $old['phone'])) $errors['phone'] = 'Enter a valid 10-digit mobile number.';
    if (strlen($password) < 6) $errors['password'] = 'Password must be at least 6 characters.';
    elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) $errors['password'] = 'Password must contain letters and numbers.';
    if ($password !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';

    if (!isset($errors['email'])) {
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $chk->execute([$old['email']]);
        if ($chk->fetch()) $errors['email'] = 'An account with this email already exists.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)');
        $stmt->execute([$old['name'], $old['email'], $old['phone'], password_hash($password, PASSWORD_DEFAULT)]);
        flash('success', 'Account created successfully! Please log in.');
        redirect('login.php');
    }
}

include 'includes/header.php';
?>
<div class="form-card">
    <h1 class="text-center">Create Account</h1>
    <p class="text-center muted">Join us and book your first ride in minutes.</p>

    <form method="post" class="validate mt" novalidate>
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="name">Full name <span class="req">*</span></label>
            <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" data-rules="required|name" class="<?= isset($errors['name']) ? 'invalid' : '' ?>">
            <small class="error-msg"><?= e($errors['name'] ?? '') ?></small>
        </div>
        <div class="form-group">
            <label for="email">Email <span class="req">*</span></label>
            <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" data-rules="required|email" class="<?= isset($errors['email']) ? 'invalid' : '' ?>">
            <small class="error-msg"><?= e($errors['email'] ?? '') ?></small>
        </div>
        <div class="form-group">
            <label for="phone">Mobile number <span class="req">*</span></label>
            <input type="tel" id="phone" name="phone" maxlength="10" value="<?= e($old['phone']) ?>" placeholder="10-digit number" data-rules="required|phone" class="<?= isset($errors['phone']) ? 'invalid' : '' ?>">
            <small class="error-msg"><?= e($errors['phone'] ?? '') ?></small>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="password">Password <span class="req">*</span></label>
                <input type="password" id="password" name="password" data-rules="required|min:6|strong" class="<?= isset($errors['password']) ? 'invalid' : '' ?>">
                <small class="error-msg"><?= e($errors['password'] ?? '') ?></small>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm password <span class="req">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" data-rules="required|match:password" class="<?= isset($errors['confirm_password']) ? 'invalid' : '' ?>">
                <small class="error-msg"><?= e($errors['confirm_password'] ?? '') ?></small>
            </div>
        </div>
        <button class="btn btn-primary btn-block">Sign Up</button>
    </form>
    <p class="form-footer">Already have an account? <a href="login.php">Log in</a></p>
</div>
<?php include 'includes/footer.php'; ?>
